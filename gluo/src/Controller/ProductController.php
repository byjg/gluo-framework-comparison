<?php

namespace App\Controller;

use ByJG\Config\Exception\ConfigException;
use ByJG\Config\Exception\ConfigNotFoundException;
use ByJG\Config\Exception\DependencyInjectionException;
use ByJG\Config\Exception\InvalidDateException;
use ByJG\Config\Exception\KeyNotFoundException;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\RestServer\Exception\Error400Exception;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Exception\Error403Exception;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use OpenApi\Attributes as OA;
use ReflectionException;
use ByJG\Gluo\Attribute\RequireAuthenticated;
use ByJG\Gluo\Attribute\RequireRole;
use ByJG\Gluo\Attribute\ValidateRequest;
use ByJG\Gluo\Util\OrderBy;
use ByJG\MicroOrm\Query;
use App\Model\Product;
use App\Model\User;

class ProductController
{
    /**
     * Get the Product by id
     *
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @throws Error404Exception
     */
    #[OA\Get(
        path: "/product/{id}",
        security: [
            ["jwt-token" => []]
        ],
        tags: ["Product"],
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        schema: new OA\Schema(
            type: "integer",
            format: "int32"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "The object Product",
        content: new OA\JsonContent(ref: "#/components/schemas/Product")
    )]
    #[RequireAuthenticated]
    public function getProduct(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(Product::getOrFail($request->attribute('id')));
    }

    /**
     * List Product
     *
     * @param mixed $response
     * @param mixed $request
     * @return void
     */
    #[OA\Get(
        path: "/product",
        security: [
            ["jwt-token" => []]
        ],
        tags: ["Product"]
    )]
    #[OA\Parameter(
        name: "page",
        description: "Page number",
        in: "query",
        required: false,
        schema: new OA\Schema(
            type: "integer",
        )
    )]
    #[OA\Parameter(
        name: "size",
        description: "Page size",
        in: "query",
        required: false,
        schema: new OA\Schema(
            type: "integer",
        )
    )]
    #[OA\Parameter(
        name: "orderBy",
        description: "Fields to sort by, separated by commas; add ' desc' after a field for descending order. Example: name desc,id",
        in: "query",
        required: false,
        schema: new OA\Schema(
            type: "string",
        )
    )]
    #[OA\Response(
        response: 200,
        description: "The object Product",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Product"))
    )]
    #[OA\Response(
        response: 401,
        description: "Not Authorized",
        content: new OA\JsonContent(ref: "#/components/schemas/error")
    )]
    #[RequireAuthenticated]
    public function listProduct(HttpResponse $response, HttpRequest $request): void
    {
        // Paginated (default is page 0, 50 per page) and sorted by the orderBy parameter
        $page = (int)($request->queryString('page') ?? 0);
        $size = (int)($request->queryString('size') ?? 50);

        $query = Query::getInstance()
            ->table(Product::tableName())
            ->orderBy(OrderBy::fromQueryString($request->queryString('orderBy'), Product::class))
            ->limit($page * $size, $size);

        $response->write(Product::query($query));
    }


    /**
     * Create a new Product
     *
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @return void
     */
    #[OA\Post(
        path: "/product",
        security: [
            ["jwt-token" => []]
        ],
        tags: ["Product"]
    )]
    #[OA\RequestBody(
        description: "The object Product to be created",
        required: true,
        content: new OA\JsonContent(
            required: [ "name", "price" ],
            properties: [

                new OA\Property(property: "name", type: "string", format: "string", maxLength: 100, minLength: 1),
                new OA\Property(property: "price", type: "number", format: "double", maximum: 99999999.99, minimum: 0),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "The object to be created",
        content: new OA\JsonContent(
            required: [ "id" ],
            properties: [

                new OA\Property(property: "id", type: "integer", format: "int32")
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Not Authorized",
        content: new OA\JsonContent(ref: "#/components/schemas/error")
    )]
    #[RequireRole(User::ROLE_ADMIN)]
    #[ValidateRequest]
    public function postProduct(HttpResponse $response, HttpRequest $request): void
    {
        $payload = ValidateRequest::getPayload();

        // Create a new ActiveRecord instance with payload
        $model = Product::new($payload);
        $model->save();

        $response->write(["id" => $model->getId()]);
    }


    /**
     * Update an existing Product
     *
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @return void
     * @throws Error401Exception
     * @throws Error404Exception
     * @throws ConfigException
     * @throws ConfigNotFoundException
     * @throws DependencyInjectionException
     * @throws InvalidDateException
     * @throws KeyNotFoundException
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws Error400Exception
     * @throws Error403Exception
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws ReflectionException
     */
    #[OA\Put(
        path: "/product/{id}",
        security: [
            ["jwt-token" => []]
        ],
        tags: ["Product"]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        schema: new OA\Schema(
            type: "integer",
            format: "int32"
        )
    )]
    #[OA\RequestBody(
        description: "The object Product to be updated",
        required: true,
        content: new OA\JsonContent(
            required: [ "name", "price" ],
            properties: [

                new OA\Property(property: "name", type: "string", format: "string", maxLength: 100, minLength: 1),
                new OA\Property(property: "price", type: "number", format: "double", maximum: 99999999.99, minimum: 0),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Nothing to return"
    )]
    #[OA\Response(
        response: 401,
        description: "Not Authorized",
        content: new OA\JsonContent(ref: "#/components/schemas/error")
    )]
    #[RequireRole(User::ROLE_ADMIN)]
    #[ValidateRequest]
    public function putProduct(HttpResponse $response, HttpRequest $request): void
    {
        $model = Product::getOrFail($request->attribute('id'));

        // The key comes from the path; the body has only the writable fields
        $model->fill(ValidateRequest::getPayload());
        $model->save();
    }

    /**
     * Soft-delete a Product. Because the model uses the OaDeletedAt trait, delete()
     * sets deleted_at instead of removing the row.
     *
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @return void
     * @throws Error404Exception
     */
    #[OA\Delete(
        path: "/product/{id}",
        security: [
            ["jwt-token" => []]
        ],
        tags: ["Product"]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer", format: "int32")
    )]
    #[OA\Response(
        response: 200,
        description: "Nothing to return"
    )]
    #[OA\Response(
        response: 401,
        description: "Not Authorized",
        content: new OA\JsonContent(ref: "#/components/schemas/error")
    )]
    #[RequireRole(User::ROLE_ADMIN)]
    public function deleteProduct(HttpResponse $response, HttpRequest $request): void
    {
        Product::getOrFail($request->attribute('id'))->delete();
    }

}
