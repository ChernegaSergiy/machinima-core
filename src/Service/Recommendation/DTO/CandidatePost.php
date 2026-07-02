<?php

namespace App\Service\Recommendation\DTO;

use App\Entity\Content;

class CandidatePost
{
    private Content $post;
    private float $score;

    public function __construct(Content $post, float $initialScore = 0.0)
    {
        $this->post = $post;
        $this->score = $initialScore;
    }

    public function getPost(): Content
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
