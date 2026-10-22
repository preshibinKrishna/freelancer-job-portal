<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingPlanCategory: string
{
    case EmployerSubscription = 'employer_subscription';
    case PremiumJob = 'premium_job';
    case RecruitmentService = 'recruitment_service';
    case FreelancerPlatformFee = 'freelancer_platform_fee';
    case ProjectFee = 'project_fee';
    case PremiumFreelancerFeature = 'premium_freelancer_feature';
}
