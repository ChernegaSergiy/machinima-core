<?php

namespace App\Service\Recommendation;

use App\Entity\Content;
use App\Entity\User;
use App\Service\Recommendation\DTO\CandidatePost;
use App\Service\Recommendation\Filter\PostFilterInterface;
use App\Service\Recommendation\Generator\CandidateGeneratorInterface;
use App\Service\Recommendation\Scorer\PostScorerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class RecommendationPipeline
{
    /** @var iterable<CandidateGeneratorInterface> */
    private iterable $generators;

    /** @var iterable<PostScorerInterface> */
    private iterable $scorers;

    /** @var iterable<PostFilterInterface> */
    private iterable $filters;

    public function __construct(
        #[AutowireIterator('app.candidate_generator')] iterable $generators,
        #[AutowireIterator('app.post_scorer')] iterable $scorers,
        #[AutowireIterator('app.post_filter')] iterable $filters
    ) {
        $this->generators = $generators;
        $this->scorers = $scorers;
        $this->filters = $filters;
    }

    /**
     * @return array<Content>
     */
    public function getRecommendations(?User $user, int $limit = 50): array
    {
        // 1. Candidate Generation
        $posts = [];
        foreach ($this->generators as $generator) {
            $posts = array_merge($posts, $generator->generate($user, $limit));
        }

        // Deduplicate generated posts by ID
        $uniquePosts = [];
        foreach ($posts as $post) {
            if (!isset($uniquePosts[$post->getId()])) {
                $uniquePosts[$post->getId()] = $post;
            }
        }

        // Convert to DTOs
        $candidates = array_map(
            fn(Content $post) => new CandidatePost($post),
            array_values($uniquePosts)
        );

        // 2. Scoring
        foreach ($candidates as $candidate) {
            foreach ($this->scorers as $scorer) {
                $scorer->score($candidate, $user);
            }
        }

        // Sort by score descending
        usort($candidates, function (CandidatePost $a, CandidatePost $b) {
            return $b->getScore() <=> $a->getScore();
        });

        // 3. Filtering and Mixing (Heuristics)
        foreach ($this->filters as $filter) {
            $candidates = $filter->filter($candidates, $user);
        }

        // Enforce limit
        $candidates = array_slice($candidates, 0, $limit);

        // Extract raw posts
        return array_map(fn(CandidatePost $dto) => $dto->getPost(), $candidates);
    }
}
