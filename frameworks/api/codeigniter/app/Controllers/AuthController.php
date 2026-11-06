<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;

class AuthController extends BaseController
{
    use ResponseTrait;

    /**
     * POST /login {username, password}: Shield checks the password, its JWT manager issues the token
     */
    public function login(): ResponseInterface
    {
        $body   = $this->request->getJSON(true) ?? [];
        $result = auth('session')->check([
            'email'    => (string) ($body['username'] ?? ''),
            'password' => (string) ($body['password'] ?? ''),
        ]);
        if (! $result->isOK()) {
            return $this->respond(['message' => $result->reason()], 401);
        }

        return $this->respond($this->tokenResponse($result->extraInfo()));
    }

    /**
     * POST /refreshtoken: a new token for the user of the current one
     */
    public function refresh(): ResponseInterface
    {
        return $this->respond($this->tokenResponse(auth('jwt')->user()));
    }

    private function tokenResponse(User $user): array
    {
        return [
            'token' => service('jwtmanager')->generateToken($user),
            'data'  => ['userid' => $user->id, 'name' => $user->username, 'role' => $user->inGroup('admin') ? 'admin' : 'user'],
        ];
    }
}
