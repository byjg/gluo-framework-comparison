<?php

namespace App\Controllers;

use App\Models\ProductModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ProductController extends ResourceController
{
    protected $modelName = ProductModel::class;
    protected $format    = 'json';

    /**
     * The fields ?orderBy= accepts, and their columns
     */
    private const SORTABLE = ['id' => 'id', 'name' => 'name', 'price' => 'price', 'createdAt' => 'created_at', 'updatedAt' => 'updated_at'];

    /**
     * ?page= (from 0), ?size=, ?orderBy=name desc,id
     */
    public function index(): ResponseInterface
    {
        $page = max(0, (int) $this->request->getGet('page'));
        $size = min(100, max(1, (int) ($this->request->getGet('size') ?? 20)));

        foreach (explode(',', (string) ($this->request->getGet('orderBy') ?? 'id')) as $item) {
            [$field, $direction] = array_pad(preg_split('/\s+/', trim($item)), 2, 'asc');
            if (isset(self::SORTABLE[$field])) {
                $this->model->orderBy(self::SORTABLE[$field], strtolower($direction) === 'desc' ? 'DESC' : 'ASC');
            }
        }

        return $this->respond(array_map($this->toArray(...), $this->model->findAll($size, $page * $size)));
    }

    public function show($id = null): ResponseInterface
    {
        $product = $this->model->find($id);

        return $product ? $this->respond($this->toArray($product)) : $this->failNotFound('Product not found');
    }

    public function create(): ResponseInterface
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }
        $id = $this->model->insert($this->request->getJSON(true) ?? []);
        if ($id === false) {
            return $this->invalid();
        }

        return $this->respondCreated($this->toArray($this->model->find($id)));
    }

    public function update($id = null): ResponseInterface
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }
        if (! $this->model->find($id)) {
            return $this->failNotFound('Product not found');
        }
        if (! $this->model->update($id, $this->request->getJSON(true) ?? [])) {
            return $this->invalid();
        }

        return $this->respond($this->toArray($this->model->find($id)));
    }

    /**
     * Soft delete: the model sets deleted_at, and find() skips the row from then on
     */
    public function delete($id = null): ResponseInterface
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }
        if (! $this->model->find($id)) {
            return $this->failNotFound('Product not found');
        }
        $this->model->delete($id);

        return $this->respondNoContent();
    }

    /**
     * Shield's group filter redirects, made for pages: an API answers 403
     */
    private function denyUnlessAdmin(): ?ResponseInterface
    {
        return auth('jwt')->user()->inGroup('admin') ? null : $this->failForbidden('Only an admin can change products');
    }

    /**
     * The model's validation errors, as the SPA reads them: {message}
     */
    private function invalid(): ResponseInterface
    {
        return $this->respond(['message' => implode(' ', $this->model->errors())], 400);
    }

    /**
     * The product as Gluo's API writes it, the contract the SPA reads
     */
    private function toArray(array $row): array
    {
        return [
            'id'        => $row['id'],
            'name'      => $row['name'],
            'price'     => $row['price'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
            'deletedAt' => $row['deleted_at'],
        ];
    }
}
