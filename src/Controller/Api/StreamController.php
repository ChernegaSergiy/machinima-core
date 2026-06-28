<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class StreamController extends AbstractController
{
    #[Route('/stream', name: 'api_stream', methods: ['GET'])]
    public function stream(): StreamedResponse
    {
        $response = new StreamedResponse(function () {
            // Keep connection alive.
            // In a full Symfony FPM environment, standard SSE ties up FPM workers.
            // To achieve true non-blocking push, this should eventually use Symfony Mercure.
            // For now, this mimics the basic stream loop.
            while (true) {
                if (connection_aborted()) {
                    break;
                }
                
                echo "data: " . json_encode(['type' => 'PING']) . "\n\n";
                ob_flush();
                flush();
                
                sleep(5);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
