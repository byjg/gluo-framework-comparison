import React, {useEffect, useState} from 'react';
import {Edit3, Plus, Trash2} from 'lucide-react';
import {api} from '@/lib/api';
import {Alert, Button, Card, EmptyState, Field, Input, Modal, PageHeader} from '@/components/ui';

const blankForm = { name: '', price: '' };

export default function Products() {
  const [products, setProducts] = useState([]);
  const [form, setForm] = useState(blankForm);
  const [editing, setEditing] = useState(null);
  const [error, setError] = useState('');
  const [formError, setFormError] = useState('');
  const [modalOpen, setModalOpen] = useState(false);

  const load = async () => {
    setError('');
    try {
      const data = await api.request('/product', { method: 'GET' });
      setProducts(Array.isArray(data) ? data : []);
    } catch (err) {
      setError(err.message);
    }
  };

  useEffect(() => {
    load();
  }, []);

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
        await api.putJson('/product', { id: editing.id, ...payload });
      } else {
        await api.postJson('/product', payload);
      }
      setModalOpen(false);
      await load();
    } catch (err) {
      setFormError(err.message);
    }
  };

  const remove = async (product) => {
    setError('');
    try {
      await api.request(`/product/${product.id}`, { method: 'DELETE' });
      await load();
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
          <EmptyState title="No products yet" />
        ) : (
          <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th scope="col" className="px-4 py-3 font-semibold">Name</th>
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
