<?php

namespace App\Enums;

enum OrderStatus: string
{
    case NEW = 'new';
    case CONFIRMED = 'confirmed';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case READY = 'ready-to-shipping';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}