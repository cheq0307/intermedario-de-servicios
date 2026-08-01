<?php

namespace App\Domain\Marketplace\Enums;

enum ListingType: string
{
    case Product = 'product';
    case Service = 'service';
}
