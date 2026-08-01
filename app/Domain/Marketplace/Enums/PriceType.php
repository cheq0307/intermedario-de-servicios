<?php

namespace App\Domain\Marketplace\Enums;

enum PriceType: string
{
    case Fixed = 'fixed';
    case Quote = 'quote';
    case StartingAt = 'starting_at';
}
