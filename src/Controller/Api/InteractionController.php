<?php

namespace App\Controller\Api;

use App\Entity\Content;
use App\Entity\ContentInteraction;
use App\Entity\ContentView;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('/api')]
class InteractionController extends AbstractController
{
    #[Route('/interact', name: 'api_interact', methods: ['POST'])]
    public function interact(Request $request, EntityManagerInterface $em, HubInterface $hub): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        
        if (!isset($body['content_id'], $body['type'])) {
            return $this->json(['success' => false, 'error' => 'Missing content_id or type'], 400);
        }

        $contentId = (int) $body['content_id'];
        $type = (string) $body['type'];
        $userId = isset($body['user_id']) ? (int) $body['user_id'] : null;

        $content = $em->getRepository(Content::class)->find($contentId);
        if (!$content) {
            return $this->json(['success' => false, 'error' => 'Content not found'], 404);
        }

        if ($type === 'view') {
            // Register view
            $user = $userId ? $em->getRepository(User::class)->find($userId) : null;
            
            // Avoid duplicate views from same user
            if ($user) {
                $existingView = $em->getRepository(ContentView::class)->findOneBy([
                    'user' => $user,
                    'content' => $content
                ]);
                
                if (!$existingView) {
                    $view = new ContentView();
                    $view->setUser($user);
                    $view->setContent($content);
                    $view->setCreatedAt(date('Y-m-d H:i:s'));
                    $em->persist($view);
                    
                    $content->setViewsCount($content->getViewsCount() + 1);
                    $em->flush();
                }
            } else {
                $content->setViewsCount($content->getViewsCount() + 1);
                $em->flush();
            }
        } else {
            // Like or Dislike
            if (!$userId) {
                return $this->json(['success' => false, 'error' => 'User ID is required for liking/disliking'], 400);
            }

            $user = $em->getRepository(User::class)->find($userId);
            if (!$user) {
                return $this->json(['success' => false, 'error' => 'User not found'], 404);
            }

            if (!in_array($type, ['like', 'dislike'])) {
                return $this->json(['success' => false, 'error' => 'Invalid interaction type'], 400);
            }

            $interaction = $em->getRepository(ContentInteraction::class)->findOneBy([
                'user' => $user,
                'content' => $content
            ]);

            if ($interaction) {
                if ($interaction->getInteractionType() === $type) {
                    // Remove interaction
                    $em->remove($interaction);
                    if ($type === 'like') {
                        $content->setLikesCount(max(0, $content->getLikesCount() - 1));
                    } else {
                        $content->setDislikesCount(max(0, $content->getDislikesCount() - 1));
                    }
                } else {
                    // Switch interaction
                    $interaction->setInteractionType($type);
                    if ($type === 'like') {
                        $content->setLikesCount($content->getLikesCount() + 1);
                        $content->setDislikesCount(max(0, $content->getDislikesCount() - 1));
                    } else {
                        $content->setDislikesCount($content->getDislikesCount() + 1);
                        $content->setLikesCount(max(0, $content->getLikesCount() - 1));
                    }
                }
            } else {
                // New interaction
                $interaction = new ContentInteraction();
                $interaction->setUser($user);
                $interaction->setContent($content);
                $interaction->setInteractionType($type);
                $interaction->setCreatedAt(date('Y-m-d H:i:s'));
                $em->persist($interaction);

                if ($type === 'like') {
                    $content->setLikesCount($content->getLikesCount() + 1);
                } else {
                    $content->setDislikesCount($content->getDislikesCount() + 1);
                }
            }

            $em->flush();
        }

        // Broadcast new stats via Mercure
        $update = new Update(
            'machinima/updates',
            json_encode([
                'type' => 'STATS_UPDATE',
                'content_id' => $contentId,
                'likes' => $content->getLikesCount(),
                'dislikes' => $content->getDislikesCount(),
                'views' => $content->getViewsCount()
            ])
        );
        try {
            $hub->publish($update);
        } catch (\Throwable $e) {
            // Ignore Mercure errors if hub is not running
        }

        return $this->json([
            'success' => true,
            'likes' => $content->getLikesCount(),
            'dislikes' => $content->getDislikesCount(),
            'views' => $content->getViewsCount()
        ]);
    }

    #[Route('/user/{userId}/interactions', name: 'api_user_interactions', methods: ['GET'])]
    public function getUserInteractions(int $userId, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'User not found'], 404);
        }

        $interactions = $em->getRepository(ContentInteraction::class)->findBy(['user' => $user]);
        $likedIds = [];
        $dislikedIds = [];
        foreach ($interactions as $interaction) {
            if ($interaction->getInteractionType() === 'like') {
                $likedIds[] = $interaction->getContent()->getId();
            } elseif ($interaction->getInteractionType() === 'dislike') {
                $dislikedIds[] = $interaction->getContent()->getId();
            }
        }

        return $this->json([
            'success' => true,
            'likes' => $likedIds,
            'dislikes' => $dislikedIds
        ]);
    }
}
