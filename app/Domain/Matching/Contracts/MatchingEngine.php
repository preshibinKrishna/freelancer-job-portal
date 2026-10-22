<?php

declare(strict_types=1);

namespace App\Domain\Matching\Contracts;

use App\Domain\Matching\Data\MatchResult;

interface MatchingEngine
{
    /** @param array<string, float|null> $factors @param list<string> $matchedSkills @param list<string> $missingSkills */
    public function rank(array $factors, array $matchedSkills, array $missingSkills): MatchResult;
}
