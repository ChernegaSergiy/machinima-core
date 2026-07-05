<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\IdentityProviderRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    public function __construct(
        private IdentityProviderRegistry $registry,
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        $providers = $this->registry->getAvailableProviders();

        if (0 === count($providers)) {
            $this->addFlash('error', 'No authentication providers available.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('login.html.twig', [
            'providers' => $providers,
        ]);
    }
}
