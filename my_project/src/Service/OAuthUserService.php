<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;

class OAuthUserService
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findOrCreateUser(UserResponseInterface $response): User
    {
        $email = $response->getEmail();
        $oauthId = $response->getUserIdentifier();
        $data = $response->getData();

        $user = $this->userRepository->findOneBy([
            'oauthProvider' => 'yandex',
            'oauthId' => $oauthId
        ]);

        if ($user) {
            return $this->updateUser($user, $response, $data);
        }

        if ($email) {
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user) {
                return $this->linkUser($user, $response, $data);
            }
        }

        return $this->createUser($response, $data);
    }

    private function createUser(UserResponseInterface $response, array $data): User
    {
        $user = new User();

        $email = $response->getEmail();

        if ($email) {
            $user->setEmail($email);
        } else {
            $login = $data['login'] ?? 'user_' . time();
            $user->setEmail($login . '@yandex.temp');
        }

        $name = $this->extractNameFromData($data);
        $user->setName($name);

        $user->setPhone($this->generateTempPhone());

        $user->setOauthProvider('yandex');
        $user->setOauthId($response->getUsername());

        if (isset($data['default_avatar_id']) && !empty($data['default_avatar_id'])) {
            $user->setAvatar('https://avatars.yandex.net/get-yapic/' . $data['default_avatar_id'] . '/islands-200');
        }

        $user->setPassword(bin2hex(random_bytes(32)));
        $user->setRoles(['ROLE_USER']);

        $this->saveTokens($user, $response);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function updateUser(User $user, UserResponseInterface $response, array $data): User
    {
        $newName = $this->extractNameFromData($data);

        if (!empty($newName) && $newName !== $user->getName()) {
            $user->setName($newName);
        }

        $email = $response->getEmail();

        if ($email && !$user->getEmail()) {
            $user->setEmail($email);
        }

        if (isset($data['default_avatar_id']) && !empty($data['default_avatar_id'])) {
            $avatarUrl = 'https://avatars.yandex.net/get-yapic/' . $data['default_avatar_id'] . '/islands-200';

            if ($avatarUrl !== $user->getAvatar()) {
                $user->setAvatar($avatarUrl);
            }
        }

        $this->saveTokens($user, $response);

        $this->entityManager->flush();

        return $user;
    }

    private function linkUser(User $user, UserResponseInterface $response, array $data): User
    {
        $user->setOauthProvider('yandex');
        $user->setOauthId($response->getUsername());

        if (!$user->getName()) {
            $name = $this->extractNameFromData($data);

            if (!empty($name)) {
                $user->setName($name);
            }
        }

        $this->saveTokens($user, $response);

        $this->entityManager->flush();

        return $user;
    }

    private function extractNameFromData(array $data): string
    {
        $name = '';

        if (!empty($data['real_name'])) {
            $name = $data['real_name'];
        } elseif (!empty($data['first_name']) || !empty($data['last_name'])) {
            $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        } elseif (!empty($data['login'])) {
            $name = $data['login'];
        }

        return $name ?: 'Пользователь Яндекс';
    }

    private function saveTokens(User $user, UserResponseInterface $response): void
    {
        $token = $response->getOAuthToken();

        if ($token) {
            $user->setOauthAccessToken($token->getAccessToken());

            if ($token->getRefreshToken()) {
                $user->setOauthRefreshToken($token->getRefreshToken());
            }

            if ($token->getExpiresIn()) {
                $user->setOauthTokenExpires(
                    (new DateTimeImmutable())->add(new DateInterval('PT' . $token->getExpiresIn() . 'S'))
                );
            }
        }
    }

    private function generateTempPhone(): string
    {
        return '+7' . str_pad((string)rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
    }
}
