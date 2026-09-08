<?php

namespace App\Domain\Administration;

enum AdminRole: string
{
    case Admin = 'admin';
    case Superadmin = 'superadmin';
}
