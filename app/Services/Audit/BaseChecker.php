<?php

namespace App\Services\Audit;

abstract class BaseChecker
{
    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function run(): array;

    protected function result(int $score, array $items = [], array $recommendations = []): array
    {
        $score = max(0, min(100, $score));

        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'score' => $score,
            'status' => $this->statusFromScore($score),
            'items' => $items,
            'recommendations' => $recommendations,
        ];
    }

    protected function statusFromScore(int $score): string
    {
        return match (true) {
            $score >= 80 => 'ok',
            $score >= 50 => 'warning',
            default => 'critical',
        };
    }
}
