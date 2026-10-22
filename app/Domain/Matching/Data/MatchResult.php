<?php

declare(strict_types=1);

namespace App\Domain\Matching\Data;

final readonly class MatchResult
{
    /** @param list<string> $matchedSkills @param list<string> $missingSkills @param array<string, float> $factorScores @param list<string> $explanation */
    public function __construct(
        public int $score,
        public array $matchedSkills,
        public array $missingSkills,
        public array $factorScores,
        public array $explanation,
    ) {}

    /** @return array{match_score: int, matched_skills: list<string>, missing_skills: list<string>, factor_scores: array<string, float>, explanation: list<string>} */
    public function toArray(): array
    {
        return ['match_score' => $this->score, 'matched_skills' => $this->matchedSkills, 'missing_skills' => $this->missingSkills, 'factor_scores' => $this->factorScores, 'explanation' => $this->explanation];
    }
}
