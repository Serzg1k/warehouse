<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case RESERVED = 'reserved';
    case AWAITING_RESTOCK = 'awaiting_restock';
    case FAILED = 'failed';
}
