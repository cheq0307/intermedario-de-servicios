<?php

namespace App\Domain\Marketplace\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
}
