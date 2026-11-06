<?php

namespace App\Controllers;

use App\Models\ProductModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\RESTful\ResourcePresenter;

class ProductController extends ResourcePresenter
{
    protected $modelName = ProductModel::class;

    protected $helpers = ['form'];

    public function index()
    {
        return view('products/index', [
            'products' => $this->model->orderBy('name')->paginate(20),
            'pager'    => $this->model->pager,
        ]);
    }

    public function new()
    {
        return view('products/form', ['product' => null]);
    }

    public function create(): RedirectResponse
    {
        if (! $this->model->insert($this->request->getPost(['name', 'price']))) {
            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }

        return redirect()->to('/products');
    }

    public function edit($id = null)
    {
        $product = $this->model->find($id) ?? throw new \CodeIgniter\Exceptions\PageNotFoundException();

        return view('products/form', ['product' => $product]);
    }

    public function update($id = null): RedirectResponse
    {
        if (! $this->model->update($id, $this->request->getPost(['name', 'price']))) {
            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }

        return redirect()->to('/products');
    }

    public function delete($id = null): RedirectResponse
    {
        $this->model->delete($id);

        return redirect()->to('/products');
    }
}
