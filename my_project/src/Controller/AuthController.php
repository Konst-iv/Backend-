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
        $data = json_decode($request->getContent(), true) ?? [];
        $responseData = ['success' => false];
        $statusCode = 400;

        try {
            $requiredFields = ['email', 'phone', 'name', 'password'];
            $missingField = null;

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $missingField = $field;
                    break;
                }
            }

            if ($missingField) {
                $responseData['error'] = "Missing required field: $missingField";
            } else {
                $user = $this->userService->createUser(
                    $data['email'],
                    $data['phone'],
                    $data['name'],
                    $data['password']
                );

                $responseData = [
                    'success' => true,
                    'data' => [
                        'user' => [
                            'id' => $user->getId(),
                            'email' => $user->getEmail(),
                            'phone' => $user->getPhone(),
                            'name' => $user->getName(),
                        ],
                        'token' => $this->userService->generateJwtToken($user)
                    ]
                ];
                $statusCode = 201;
            }
        } catch (InvalidArgumentException $e) {
            $responseData['error'] = $e->getMessage();
            $statusCode = 400;
        } catch (Exception $e) {
            $responseData['error'] = 'Internal server error';
            $statusCode = 500;
        }

        return $this->json($responseData, $statusCode);
    }

    #[Route('/api/auth/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $responseData = ['success' => false];
        $statusCode = 400;

        try {
            if (!isset($data['phone']) || !isset($data['password'])) {
                $responseData['error'] = 'Missing required fields: phone, password';
            } else {
                $user = $this->userService->getUserByPhone($data['phone']);

                if ($user && $this->passwordHasher->isPasswordValid($user, $data['password'])) {
                    $responseData = [
                        'success' => true,
                        'data' => [
                            'user' => [
                                'id' => $user->getId(),
                                'email' => $user->getEmail(),
                                'phone' => $user->getPhone(),
                                'name' => $user->getName(),
                            ],
                            'token' => $this->userService->generateJwtToken($user)
                        ]
                    ];
                    $statusCode = 200;
                } else {
                    $responseData['error'] = 'Invalid credentials';
                    $statusCode = 401;
                }
            }
        } catch (Exception $e) {
            $responseData['error'] = 'Internal server error';
            $statusCode = 500;
        }

        return $this->json($responseData, $statusCode);
    }

    // Данные извлекаются автоматически из JWT-токена, переданного в заголовке Authorization.
    // Слой безопасности Symfony (Firewall) расшифровывает токен до попадания в контроллер,
    // находит пользователя в БД и передает объект через атрибут #[CurrentUser].

    #[Route('/api/auth/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        $responseData = ['success' => false, 'error' => 'Not authenticated'];
        $statusCode = 401;

        if ($user) {
            $responseData = [
                'success' => true,
                'data' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'name' => $user->getName(),
                    'roles' => $user->getRoles(),
                ]
            ];
            $statusCode = 200;
        }

        return $this->json($responseData, $statusCode);
    }

    #[Route('/api/auth/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->json(['success' => true, 'message' => 'Successfully logged out']);
    }
}
