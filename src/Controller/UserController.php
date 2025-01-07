<?php

namespace App\Controller;

use App\Document\User;
use App\Document\Zone;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

/**
 * @Route("/users", name="get_users", methods={"GET"})
 */
public function getUsers(DocumentManager $dm): JsonResponse
{
    $users = $dm->getRepository(User::class)->findAll();

    $userData = [];
    foreach ($users as $user) {
        $zones = [];
        foreach ($user->getAssignedZones() as $zone) {
            $zones[] = ['id' => $zone->getId(), 'name' => $zone->getName()];
        }

        $userData[] = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'role' => $user->getRole(),
            'assignedZones' => $zones,
        ];
    }

    return $this->json($userData);
}


/**
 * @Route("/users", name="create_user", methods={"POST"})
 */
public function createUser(Request $request, DocumentManager $dm): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        return $this->json(['message' => 'Name, Email, and Password are required'], 400);
    }

    $user = new User();
    $user->setUsername($data['name']);
    $user->setEmail($data['email']);
    $user->setPassword($data['password']);
    $user->setRole($data['role'] ?? 'User');

    if (isset($data['assignedZones'])) {
        $zones = $this->getReferencedZones($data['assignedZones'], $dm);
        $user->setAssignedZones($zones);
    }

    $dm->persist($user);
    $dm->flush();

    return $this->json(['message' => 'User created successfully'], 201);
}


/**
 * @Route("/users/{id}", name="update_user", methods={"PUT"})
 */
public function updateUser(string $id, Request $request, DocumentManager $dm): JsonResponse
{
    $user = $dm->getRepository(User::class)->find($id);
    if (!$user) {
        return $this->json(['message' => 'User not found'], 404);
    }

    $data = json_decode($request->getContent(), true);

    $user->setUsername($data['name'] ?? $user->getUsername());
    $user->setEmail($data['email'] ?? $user->getEmail());
    $user->setRole($data['role'] ?? $user->getRole());

    if (!empty($data['password'])) {
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
    }

    if (isset($data['assignedZones'])) {
        $zones = $this->getReferencedZones($data['assignedZones'], $dm);
        $user->setAssignedZones($zones);
    }

    $dm->flush();

    return $this->json(['message' => 'User updated successfully']);
}


private function getReferencedZones(array $zoneIds, DocumentManager $dm): array
{
    $zones = [];
    foreach ($zoneIds as $zoneId) {
        $zone = $dm->getRepository(Zone::class)->find($zoneId);
        if ($zone) {
            $zones[] = $zone;
        }
    }
    return $zones;
}

/**
 * @Route("/zones", name="get_zones", methods={"GET"})
 */
public function getZones(DocumentManager $dm): JsonResponse
{
    $zones = $dm->getRepository(Zone::class)->findAll();
    return $this->json($zones);
}



/**
 * @Route("/users/{id}", name="delete_user", methods={"DELETE"})
 */
public function deleteUser(string $id, DocumentManager $dm): JsonResponse
{
    $user = $dm->getRepository(User::class)->find($id);
    if (!$user) {
        return $this->json(['message' => 'User not found'], 404);
    }

    $dm->remove($user);
    $dm->flush();

    return $this->json(['message' => 'User deleted successfully']);
    }
}

