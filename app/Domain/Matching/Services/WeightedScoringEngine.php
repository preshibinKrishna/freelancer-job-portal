<?php

declare(strict_types=1);

namespace App\Domain\Matching\Services;

use App\Domain\Matching\Contracts\MatchingEngine;
use App\Domain\Matching\Data\MatchResult;

final class WeightedScoringEngine implements MatchingEngine
{
    public function rank(array $factors, array $matchedSkills, array $missingSkills): MatchResult
    {
        $weights = config('matching.weights', []);
        $weightedScore = 0.0;
        $totalWeight = 0.0;
        $factorScores = [];
        foreach ($factors as $name => $score) {
            $weight = (float) ($weights[$name] ?? 0);
            if ($score === null || $weight <= 0) {
                continue;
            }
            $normalized = max(0.0, min(1.0, $score));
            $factorScores[$name] = round($normalized * 100, 1);
            $weightedScore += $normalized * $weight;
            $totalWeight += $weight;
        }

        $score = $totalWeight > 0 ? (int) round(($weightedScore / $totalWeight) * 100) : 0;
        $explanation = [];
        foreach ($factorScores as $factor => $value) {
            if ($value >= (float) config('matching.explanation_threshold', 70)) {
                $explanation[] = (string) config("matching.explanations.{$factor}", ucfirst($factor).' is a strong fit.');
            }
        }
        if ($explanation === []) {
            $explanation[] = (string) config('matching.explanations.general', 'Review the factor scores and profile details to assess this match.');
        }

        return new MatchResult($score, array_values($matchedSkills), array_values($missingSkills), $factorScores, $explanation);
    }
}
