<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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

class TelegramWebAppAuthenticator extends AbstractAuthenticator
{
    private EntityManagerInterface $entityManager;
    private string $botToken;

    public function __construct(EntityManagerInterface $entityManager, string $botToken)
    {
        $this->entityManager = $entityManager;
        $this->botToken = $botToken;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-Telegram-Init-Data') || $request->query->has('initData') || $request->request->has('initData') || $request->cookies->has('tma_init_data');
    }

    public function authenticate(Request $request): Passport
    {
        $initData = $request->headers->get('X-Telegram-Init-Data') ?? $request->query->get('initData') ?? $request->request->get('initData') ?? $request->cookies->get('tma_init_data');

        if (null === $initData) {
            throw new CustomUserMessageAuthenticationException('No Telegram init data provided');
        }

        $userData = $this->validateInitData($initData);

        if (!$userData || !isset($userData['id'])) {
            throw new CustomUserMessageAuthenticationException('Invalid Telegram init data signature');
        }

        $telegramId = $userData['id'];

        return new SelfValidatingPassport(new UserBadge((string) $telegramId, function ($userIdentifier) {
            $user = $this->entityManager->getRepository(User::class)->find((int) $userIdentifier);

            if (!$user) {
                // Creates a new user in the DB dynamically if they don't exist yet
                $user = new User();
                $user->setId((int) $userIdentifier);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Allow the request to continue to the Controller
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    private function validateInitData(string $initData): ?array
    {
        parse_str($initData, $parsedData);

        if (!isset($parsedData['hash'])) {
            return null;
        }

        $hash = $parsedData['hash'];
        unset($parsedData['hash']);

        ksort($parsedData);

        $dataCheckString = [];
        foreach ($parsedData as $key => $value) {
            $dataCheckString[] = $key.'='.$value;
        }
        $dataCheckString = implode("\n", $dataCheckString);

        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $calculatedHash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        if (hash_equals($calculatedHash, $hash)) {
            return json_decode($parsedData['user'] ?? '{}', true);
        }

        return null;
    }
}
