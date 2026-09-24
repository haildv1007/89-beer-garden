<?php

namespace App\Enums;

enum RecommendationSource: string
{
    case Ai = 'ai';
    case RuleBased = 'rule_based';
    case Popular = 'popular';
}
