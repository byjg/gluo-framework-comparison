<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Utility\Security;
use Firebase\JWT\JWT;

class UsersController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['login']);
    }

    /**
     * POST /login {username, password}: the Form authenticator checked the password
     */
    public function login(): void
    {
        $this->request->allowMethod(['post']);
        if (!$this->Authentication->getResult()?->isValid()) {
            throw new UnauthorizedException('Invalid username or password');
        }

        $this->respondWithToken();
    }

    /**
     * POST /refreshtoken: a new token for the user of the current one
     */
    public function refresh(): void
    {
        $this->request->allowMethod(['post']);
        $this->respondWithToken();
    }

    /**
     * {token, data}: an HS256 JWT signed with the salt, as the Jwt authenticator reads it
     */
    private function respondWithToken(): void
    {
        /** @var \App\Model\Entity\User $user */
        $user = $this->Authentication->getIdentity()->getOriginalData();
        $token = JWT::encode(['sub' => $user->id, 'exp' => time() + 3600], Security::getSalt(), 'HS256');

        $this->set(['token' => $token, 'data' => $user->tokenData()]);
        $this->viewBuilder()->setOption('serialize', ['token', 'data']);
    }
}
