import React, {useEffect, useState} from 'react';
import {ArrowDown, ArrowUp, Edit3, Plus, Trash2} from 'lucide-react';
import {api} from '@/lib/api';
import {Alert, Button, Card, EmptyState, Field, Input, Modal, PageHeader, Pagination} from '@/components/ui';

const blankForm = { name: '', price: '' };

export default function Products() {
  const [products, setProducts] = useState([]);
  const [form, setForm] = useState(blankForm);
  const [editing, setEditing] = useState(null);
  const [error, setError] = useState('');
  const [formError, setFormError] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  // Paged and sorted by the API (?page=&size=&orderBy=), so every product can be reached.
  const [page, setPage] = useState(0);
  const [size, setSize] = useState(20);
  const [descending, setDescending] = useState(false);

  // Every load goes through the effect below: it uses the current page, size and order, and
  // aborts the previous request, so a response for a page the user has left cannot
  // overwrite the newer one. A save or a delete asks for a reload with reload().
  const [reloadKey, setReloadKey] = useState(0);
  const reload = () => setReloadKey((key) => key + 1);

  const load = async (signal) => {
    setError('');
    try {
      const params = new URLSearchParams({ page, size, orderBy: descending ? 'name desc,id' : 'name,id' });
      const data = await api.request(`/product?${params}`, { method: 'GET', signal });
      setProducts(Array.isArray(data) ? data : []);
    } catch (err) {
      if (signal.aborted) return;
      setError(err.message);
    }
  };

  useEffect(() => {
    const controller = new AbortController();
    load(controller.signal);
    return () => controller.abort();
  }, [page, size, descending, reloadKey]);

  const changeSize = (newSize) => {
    setSize(newSize);
    setPage(0);
  };

  const open = (product = null) => {
    setEditing(product);
    setForm(product ? { name: product.name, price: product.price } : blankForm);
    setFormError('');
    setModalOpen(true);
  };

  const save = async (e) => {
    e.preventDefault();
    setFormError('');
    try {
      const payload = { name: form.name.trim(), price: Number(form.price) };
      if (editing) {
        await api.putJson(`/product/${editing.id}`, payload);
      } else {
        await api.postJson('/product', payload);
      }
      setModalOpen(false);
      reload();
    } catch (err) {
      setFormError(err.message);
    }
  };

  const remove = async (product) => {
    setError('');
    try {
      await api.request(`/product/${product.id}`, { method: 'DELETE' });
      reload();
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Products"
        actions={
          <Button type="button" onClick={() => open()}>
            <Plus size={16} />
            New product
          </Button>
        }
      />

      <Card className="p-4">
        {error && <Alert className="mb-4">{error}</Alert>}

        {products.length === 0 ? (
          <EmptyState title={page === 0 ? 'No products yet' : 'No more products'} />
        ) : (
          <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th scope="col" className="px-4 py-3 font-semibold" aria-sort={descending ? 'descending' : 'ascending'}>
                  <button type="button" className="inline-flex items-center gap-1 uppercase" onClick={() => setDescending(!descending)}>
                    Name
                    {descending ? <ArrowDown size={12} /> : <ArrowUp size={12} />}
                  </button>
                </th>
                <th scope="col" className="px-4 py-3 font-semibold">Price</th>
                <th scope="col" className="px-4 py-3 text-right font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white">
              {products.map((product) => (
                <tr key={product.id}>
                  <td className="px-4 py-3 font-medium text-slate-900">{product.name}</td>
                  <td className="px-4 py-3 text-slate-600">{Number(product.price).toFixed(2)}</td>
                  <td className="space-x-2 whitespace-nowrap px-4 py-3 text-right">
                    <Button type="button" variant="outline" className="px-3 py-1.5" onClick={() => open(product)}>
                      <Edit3 size={14} />
                      Edit
                    </Button>
                    <Button type="button" variant="outline" className="px-3 py-1.5" onClick={() => remove(product)}>
                      <Trash2 size={14} />
                      Delete
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}

        {(products.length > 0 || page > 0) && (
          <Pagination
            className="mt-4"
            page={page}
            size={size}
            count={products.length}
            onPageChange={setPage}
            onSizeChange={changeSize}
          />
        )}
      </Card>

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editing ? 'Edit product' : 'New product'}
        footer={
          <>
            <Button type="button" variant="outline" onClick={() => setModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" form="product-form">
              Save
            </Button>
          </>
        }
      >
        <form id="product-form" onSubmit={save} className="space-y-4">
          {formError && <Alert>{formError}</Alert>}
          <Field label="Name">
            <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required maxLength={100} autoFocus />
          </Field>
          <Field label="Price">
            <Input type="number" step="0.01" min="0" value={form.price} onChange={(e) => setForm({ ...form, price: e.target.value })} required />
          </Field>
        </form>
      </Modal>
    </div>
  );
}
