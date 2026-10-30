<?php

namespace App\Controller;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class TokenController extends AbstractController
{
    /**
     * A new token for the user of the current one (the SPA calls it in the last 5 minutes)
     */
    #[Route('/refreshtoken', name: 'app_token_refresh', methods: ['POST'])]
    public function refresh(#[CurrentUser] User $user, JWTTokenManagerInterface $tokens): JsonResponse
    {
        return $this->json(['token' => $tokens->create($user), 'data' => $user->tokenData()]);
    }
}
