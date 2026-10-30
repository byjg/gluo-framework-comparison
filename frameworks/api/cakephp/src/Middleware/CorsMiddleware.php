<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CORS for the SPA (frameworks/api/frontend), with CakePHP's own builder: the response's cors().
 * A preflight (OPTIONS) is answered here, before routing and authentication.
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $request->getMethod() === 'OPTIONS' ? new Response() : $handler->handle($request);
        if (!$response instanceof Response) {
            return $response;
        }

        return $response->cors($request)
            ->allowOrigin(['http://localhost:7083'])
            ->allowMethods(['GET', 'POST', 'PUT', 'DELETE'])
            ->allowHeaders(['Authorization', 'Content-Type'])
            ->maxAge(3600)
            ->build();
    }
}
