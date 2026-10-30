<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * The fields ?orderBy= accepts, as the SPA names them
     */
    private const array SORTABLE = ['id', 'name', 'price', 'createdAt', 'updatedAt'];

    /**
     * Display a listing of the resource: ?page= (from 0), ?size=, ?orderBy=name desc,id
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $page = max(0, $request->integer('page'));
        $size = min(100, max(1, $request->integer('size', 20)));

        $query = Product::query();
        foreach (explode(',', (string) $request->query('orderBy', 'id')) as $order) {
            [$field, $direction] = array_pad(preg_split('/\s+/', trim($order)), 2, 'asc');
            if (in_array($field, self::SORTABLE, true)) {
                $query->orderBy(Str::snake($field), strtolower($direction) === 'desc' ? 'desc' : 'asc');
            }
        }

        return ProductResource::collection($query->offset($page * $size)->limit($size)->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        return new ProductResource(Product::create($request->validated()));
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
