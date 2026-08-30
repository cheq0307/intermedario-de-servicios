<?php

namespace App\Domain\Marketplace\Enums;

enum MarketplaceCapability: string
{
    case Client = 'client';
    case Provider = 'provider';
}
