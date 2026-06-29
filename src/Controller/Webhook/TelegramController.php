<?php

namespace App\Controller\Webhook;

use morfeditorial\MyBot;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TelegramController extends AbstractController
{
    #[Route('/webhook/telegram', name: 'webhook_telegram', methods: ['POST'])]
    public function handle(Request $request, ContainerInterface $container): Response
    {
        $botToken = $this->getParameter('telegram.bot_token');

        // Initialize the bot with the app's container
        $bot = new MyBot($botToken, $container);

        $update = json_decode($request->getContent(), true);
        if ($update) {
            $bot->handleUpdate($update);
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
