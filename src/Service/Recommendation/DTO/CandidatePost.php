<?php

namespace App\Service\Recommendation\DTO;

use App\Entity\Post;

class CandidatePost
{
    private Post $post;
    private float $score;

    public function __construct(Post $post, float $initialScore = 0.0)
    {
        $this->post = $post;
        $this->score = $initialScore;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function addScore(float $amount): void
    {
        $this->score += $amount;
    }

    public function subtractScore(float $amount): void
    {
        $this->score -= $amount;
    }

    public function setScore(float $score): void
    {
        $this->score = $score;
    }
}
