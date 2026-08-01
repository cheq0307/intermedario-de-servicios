<?php

namespace App\Domain\Marketplace\Enums;

enum JobRequestStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case InConversation = 'in_conversation';
    case Assigned = 'assigned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
