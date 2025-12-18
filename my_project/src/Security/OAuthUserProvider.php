<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\OAuthUserService;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 * @psalm-suppress UnusedClass
 */
class OAuthUserProvider implements UserProviderInterface, OAuthAwareUserProviderInterface
{
    private $oauthUserService;
    private $userRepository;

    public function __construct(OAuthUserService $oauthUserService, UserRepository $userRepository)
    {
        $this->oauthUserService = $oauthUserService;
        $this->userRepository = $userRepository;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
    {
        $logData = sprintf(
            "[%s] Данные от Яндекса: Email: %s, ID: %s, RawData: %s\n",
            date('Y-m-d H:i:s'),
            $response->getEmail() ?? 'no-email',
            $response->getUserIdentifier(),
            json_encode($response->getData(), JSON_UNESCAPED_UNICODE)
        );

        file_put_contents(__DIR__ . '/../../public/log.txt', $logData, FILE_APPEND);

        return $this->oauthUserService->findOrCreateUser($response);
    }

    #[\Override]
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneBy(['email' => $identifier]);

        if (!$user) {
            $e = new UserNotFoundException(sprintf('User with email "%s" not found.', $identifier));
            $e->setUserIdentifier($identifier);

            throw $e;
        }

        return $user;
    }

    #[\Override]
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    #[\Override]
    public function supportsClass($class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
