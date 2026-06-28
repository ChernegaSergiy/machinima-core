<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MiniAppController extends AbstractController
{
    #[Route('/', name: 'app_miniapp_index')]
    public function index(): Response
    {
        return $this->render('miniapp/index.html.twig');
    }
}
