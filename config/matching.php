<?php

declare(strict_types=1);
use App\Domain\Matching\Services\WeightedScoringEngine;

return [
    'engine' => WeightedScoringEngine::class,
    'weights' => [
        'skills' => 30,
        'experience' => 15,
        'location' => 10,
        'availability' => 10,
        'compensation' => 15,
        'completeness' => 10,
        'verification' => 10,
    ],
    'availability' => [
        'actively_looking' => 1.0,
        'open_to_offers' => 0.65,
        'not_looking' => 0.0,
    ],
    'experience_tolerance_years' => 3,
    'explanation_threshold' => 70,
    'explanations' => [
        'skills' => 'Strong overlap between the requested and listed skills.',
        'experience' => 'Experience is within the requested range.',
        'location' => 'Location or remote preferences align.',
        'availability' => 'Availability is compatible with the opportunity.',
        'compensation' => 'Expected compensation is within the stated range.',
        'completeness' => 'The profile has substantial supporting information.',
        'verification' => 'The profile has an approved verification.',
        'general' => 'Review the factor scores and profile details to assess this match.',
    ],
];
