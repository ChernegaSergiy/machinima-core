<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Controller;

use Morfeditorial\MachinimaCoreBundle\Entity\UserIdentity;
use Morfeditorial\MachinimaCoreBundle\Service\IdentityProviderRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

final class LinkedAccountsController extends AbstractController
{
    public function __construct(
        private IdentityProviderRegistry $registry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
    ) {
    }

    #[Route('/profile/linked-accounts', name: 'app_profile_linked_accounts')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (null === $user) {
            return $this->redirectToRoute('app_login');
        }

        $identities = $this->entityManager->getRepository(UserIdentity::class)->findBy([
            'user' => $user,
        ]);

        $linkedProviders = array_map(fn (UserIdentity $identity) => $identity->getProviderName(), $identities);

        $availableProviders = array_filter(
            $this->registry->getAvailableProviders(),
            fn (array $provider) => !in_array($provider['name'], $linkedProviders, true),
        );

        $linkRouteExists = null !== $this->router->getRouteCollection()->get('popular_oidcs_link');

        return $this->render('profile/linked_accounts.html.twig', [
            'identities' => $identities,
            'available_providers' => array_values($availableProviders),
            'link_route_exists' => $linkRouteExists,
        ]);
    }
}
