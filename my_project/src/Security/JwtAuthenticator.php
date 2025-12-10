<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Exception;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class JwtAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    #[Override]
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $message = $authException ? $authException->getMessage() : 'Authentication required';

        return new JsonResponse([
            'success' => false,
            'error' => $message
        ], Response::HTTP_UNAUTHORIZED);
    }

    #[Override]
    public function supports(Request $request): ?bool
    {
        $authHeader = $request->headers->get('Authorization');

        return $authHeader !== null && str_starts_with($authHeader, 'Bearer ');
    }

    #[Override]
    public function authenticate(Request $request): Passport
    {
        $authorizationHeader = $request->headers->get('Authorization');

        if (!$authorizationHeader) {
            throw new CustomUserMessageAuthenticationException('No Authorization header provided');
        }

        $token = substr($authorizationHeader, 7);

        if (empty($token)) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $user = $this->validateToken($token);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier()));
    }

    private function validateToken(string $token): ?User
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode($parts[1]), true);

            if (!isset($payload['phone']) || !isset($payload['exp'])) {
                return null;
            }

            if ($payload['exp'] < time()) {
                return null;
            }

            $user = $this->userRepository->findOneBy(['phone' => $payload['phone']]);

            return $user instanceof User ? $user : null;
        } catch (Exception $e) {
            return null;
        }
    }

    #[Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Authentication successful - allow the request to continue
        return null;
    }

    #[Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {

        return new JsonResponse([
            'success' => false,
            'error' => 'Authentication failed: ' . $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }
}
