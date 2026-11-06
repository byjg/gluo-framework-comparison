<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Product;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\Exception\PersistenceFailedException;

/**
 * @property \App\Model\Table\ProductsTable $Products
 */
class ProductsController extends AppController
{
    /**
     * The fields ?orderBy= accepts, and their columns
     */
    private const array SORTABLE = ['id' => 'id', 'name' => 'name', 'price' => 'price', 'createdAt' => 'created_at', 'updatedAt' => 'updated_at'];

    /**
     * ?page= (from 0), ?size=, ?orderBy=name desc,id
     */
    public function index(): void
    {
        $page = max(0, (int)$this->request->getQuery('page'));
        $size = min(100, max(1, (int)($this->request->getQuery('size') ?? 20)));

        $order = [];
        foreach (explode(',', (string)($this->request->getQuery('orderBy') ?? 'id')) as $item) {
            [$field, $direction] = array_pad(preg_split('/\s+/', trim($item)), 2, 'asc');
            if (isset(self::SORTABLE[$field])) {
                $order[self::SORTABLE[$field]] = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
            }
        }

        $products = $this->Products->find()->orderBy($order)->limit($size)->offset($page * $size)->all();
        $this->respond(array_map($this->toArray(...), $products->toList()));
    }

    /**
     * get() answers 404 when missing; the Trash behavior hides a deleted one
     */
    public function view(string $id): void
    {
        $this->respond($this->toArray($this->Products->get($id)));
    }

    public function add(): void
    {
        $this->denyUnlessAdmin();
        $product = $this->save($this->Products->newEntity($this->request->getData()));
        $this->response = $this->response->withStatus(201);
        $this->respond($this->toArray($product));
    }

    public function edit(string $id): void
    {
        $this->denyUnlessAdmin();
        $product = $this->Products->patchEntity($this->Products->get($id), $this->request->getData());
        $this->respond($this->toArray($this->save($product)));
    }

    /**
     * Soft delete: the Trash behavior sets deleted_at
     */
    public function delete(string $id): void
    {
        $this->denyUnlessAdmin();
        $this->Products->deleteOrFail($this->Products->get($id));
        $this->response = $this->response->withStatus(204);
        $this->autoRender = false;
    }

    private function denyUnlessAdmin(): void
    {
        if ($this->Authentication->getIdentity()?->get('role') !== 'admin') {
            throw new ForbiddenException('Only an admin can change products');
        }
    }

    /**
     * The table validates; its errors answer 400 as {message}
     */
    private function save(Product $product): Product
    {
        try {
            return $this->Products->saveOrFail($product);
        } catch (PersistenceFailedException) {
            $messages = [];
            $errors = $product->getErrors();
            array_walk_recursive($errors, function (string $message) use (&$messages): void {
                $messages[] = $message;
            });
            throw new BadRequestException(implode(' ', $messages));
        }
    }

    private function respond(array $data): void
    {
        $this->set('data', $data);
        $this->viewBuilder()->setOption('serialize', 'data');
    }

    /**
     * The product as Gluo's API writes it, the contract the SPA reads
     */
    private function toArray(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float)$product->price,
            'createdAt' => $product->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $product->updated_at?->format('Y-m-d H:i:s'),
            'deletedAt' => $product->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
