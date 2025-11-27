<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\UserService;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function __construct(private UserService $userService)
    {
    }

    #[Route('/api/users', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $requiredFields = ['email', 'phone', 'name', 'password'];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return $this->json([
                        'success' => false,
                        'error' => "Missing required field: $field"
                    ], 400);
                }
            }

            $user = $this->userService->createUser(
                $data['email'],
                $data['phone'],
                $data['name'],
                $data['password']
            );

            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'name' => $user->getName(),
                    'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
                ]
            ], 201);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    #[Route('/api/users/{id}', name: 'get_user', methods: ['GET'])]
    public function getUserById(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return $this->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'name' => $user->getName(),
                    'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
                ]
            ]);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }
}
