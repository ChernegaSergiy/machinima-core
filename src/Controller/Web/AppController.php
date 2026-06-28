<?php

namespace App\Controller\Web;

use App\Entity\Content;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(EntityManagerInterface $em): Response
    {
        // If not authenticated, the security firewall should handle it,
        // but if we use lazy firewall, we can check manually or let security.yaml handle it.
        // Assuming the user is authenticated via initData
        
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $feed = $em->getRepository(Content::class)->findBy([], ['trendingScore' => 'DESC'], 20);

        return $this->render('app/index.html.twig', [
            'feed' => $feed,
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        return $this->render('app/login.html.twig');
    }
}
