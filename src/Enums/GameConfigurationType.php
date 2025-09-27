<?php

declare(strict_types=1);

namespace App\Enums;

enum GameConfigurationType: string
{
    case PAYLINES = 'paylines';
    case PAYTABLE = 'paytable';
    case REELS = 'reels';
    case ROWS_COUNT = 'rows_count';
    case BONUS_FEATURES = 'bonus_features';
    case SCATTER_CONFIG = 'scatter_config';
}
