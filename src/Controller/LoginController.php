<?php
namespace App\Controller;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginController extends AbstractController
{
    public function login(Request $request, DocumentManager $dm): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Invalid email or password'], 400);
        }

        // Find the user in the database
        $user = $dm->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || $user->getPassword() !== $password) { // NOTE: Password hashing is recommended for production
            return new JsonResponse(['error' => 'Invalid email or password'], 401);
        }

        // Return user data
        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
        ]);
    }
}
