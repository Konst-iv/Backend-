<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuthController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/api/auth/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
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

            $token = $this->generateJwtToken($user);

            return $this->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'phone' => $user->getPhone(),
                        'name' => $user->getName(),
                    ],
                    'token' => $token
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

    #[Route('/api/auth/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['phone']) || !isset($data['password'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Missing required fields: phone, password'
                ], 400);
            }

            $user = $this->userService->getUserByPhone($data['phone']);

            if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid credentials'
                ], 401);
            }

            $token = $this->generateJwtToken($user);

            return $this->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'phone' => $user->getPhone(),
                        'name' => $user->getName(),
                    ],
                    'token' => $token
                ]
            ]);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    #[Route('/api/auth/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'Not authenticated'
            ], 401);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
            ]
        ]);
    }

    #[Route('/api/auth/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    private function generateJwtToken(User $user): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'phone' => $user->getPhone(),
            'exp' => time() + 3600,
            'iat' => time(),
            'sub' => $user->getId()
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header ?: ''));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload ?: ''));

        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, 'your-secret-key', true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }
}
