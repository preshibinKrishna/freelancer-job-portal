<?php

declare(strict_types=1);

namespace App\Domain\Profiles\Services;

use App\Models\UserProfile;

final class ProfileCompleteness
{
    /** @return array{percentage: int, recommendations: list<string>} */
    public function assess(?UserProfile $profile): array
    {
        if ($profile === null) {
            return [
                'percentage' => 0,
                'recommendations' => [
                    'Complete your professional headline', 'Add a concise professional summary', 'Add your primary location',
                    'Add a profile photo', 'Add at least three relevant skills', 'Add your work experience',
                    'Add your education or a certification', 'Add a resume', 'Add a portfolio or professional link',
                    'Set your availability, work arrangements, and preferred locations', 'Add the languages you work in',
                ],
            ];
        }

        $checks = [
            ['Complete your professional headline', (bool) $profile->headline, 10],
            ['Add a concise professional summary', (bool) $profile->bio, 10],
            ['Add your primary location', $profile->location_id !== null, 10],
            ['Add a profile photo', (bool) $profile->photo_path, 5],
            ['Add at least three relevant skills', $profile->skills()->count() >= 3, 15],
            ['Add your work experience', $profile->experiences()->exists(), 15],
            ['Add your education or a certification', $profile->educations()->exists() || $profile->certifications()->exists(), 10],
            ['Add a resume', $profile->resumes()->exists(), 10],
            ['Add a portfolio or professional link', $profile->portfolioLinks()->exists() || (bool) $profile->github_url || (bool) $profile->linkedin_url, 5],
            ['Set your availability, work arrangements, and preferred locations', (bool) $profile->availability && filled($profile->preferred_work_types) && $profile->preferredLocations()->exists(), 5],
            ['Add the languages you work in', $profile->languages()->exists(), 5],
        ];

        $score = 0;
        $recommendations = [];
        foreach ($checks as [$recommendation, $complete, $weight]) {
            if ($complete) {
                $score += $weight;
            } else {
                $recommendations[] = $recommendation;
            }
        }

        return ['percentage' => $score, 'recommendations' => $recommendations];
    }
}
