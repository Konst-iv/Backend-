<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function createUser(string $email, string $phone, string $name, string $password): User
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        if (!preg_match('/^\+7\d{10}$/', $phone)) {
            throw new InvalidArgumentException('Phone must be in format +7XXXXXXXXXX');
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            throw new InvalidArgumentException('User with this email already exists');
        }

        if ($this->userRepository->findOneBy(['phone' => $phone])) {
            throw new InvalidArgumentException('User with this phone already exists');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPhone($phone);
        $user->setName($name);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function getUserById(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findOneBy(['email' => $email]);
    }

    public function getUserByPhone(string $phone): ?User
    {
        return $this->userRepository->findOneBy(['phone' => $phone]);
    }

    public function generateJwtToken(User $user): string
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
