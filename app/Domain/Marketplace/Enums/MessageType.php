<?php

namespace App\Domain\Marketplace\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Audio = 'audio';
    case File = 'file';
    case Location = 'location';
    case System = 'system';
}
