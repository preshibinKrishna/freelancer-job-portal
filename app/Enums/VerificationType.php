<?php

declare(strict_types=1);

namespace App\Enums;

enum VerificationType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Identity = 'identity';
    case Skill = 'skill';
    case Employment = 'employment';
    case Education = 'education';
    case Certification = 'certification';
    case Portfolio = 'portfolio';
}
