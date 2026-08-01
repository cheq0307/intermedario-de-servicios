<?php

namespace App\Domain\Marketplace\Enums;

enum PostType: string
{
    case Portfolio = 'portfolio';
    case JobRequest = 'job_request';
    case BusinessUpdate = 'business_update';
}
