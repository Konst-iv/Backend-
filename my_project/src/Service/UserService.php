<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createUser(string $email, string $phone, string $name): User
    {
        // Проверяем существует ли пользователь с таким email
        if ($this->userRepository->findOneBy(['email' => $email])) {
            throw new InvalidArgumentException('User with this email already exists');
        }

        // Проверяем существует ли пользователь с таким телефоном
        if ($this->userRepository->findOneBy(['phone' => $phone])) {
            throw new InvalidArgumentException('User with this phone already exists');
        }

        // Создаем нового пользователя
        $user = new User();
        $user->setEmail($email);
        $user->setPhone($phone);
        $user->setName($name);

        // Сохраняем в базу
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
}
