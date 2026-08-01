<?php

namespace App\Domain\Marketplace\Enums;

enum AccountType: string
{
    case Client = 'client';
    case Provider = 'provider';
}
