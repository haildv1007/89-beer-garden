<?php

namespace App\Enums;

enum RecommendationBudgetStatus: string
{
    case NotProvided = 'not_provided';
    case WithinBudget = 'within_budget';
    case OverBudget = 'over_budget';
}
