<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Recommendation;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Service\Recommendation\DTO\CandidatePost;
use Morfeditorial\MachinimaCoreBundle\Service\Recommendation\Filter\PostFilterInterface;
use Morfeditorial\MachinimaCoreBundle\Service\Recommendation\Generator\CandidateGeneratorInterface;
use Morfeditorial\MachinimaCoreBundle\Service\Recommendation\Scorer\PostScorerInterface;
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
        #[AutowireIterator('app.post_filter')] iterable $filters,
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
            fn (Content $post) => new CandidatePost($post),
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
        return array_map(fn (CandidatePost $dto) => $dto->getPost(), $candidates);
    }
}
