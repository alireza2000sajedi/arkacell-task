<?php

namespace App\Enums;

enum StockMovementType: string
{
    case IN = 'in';
    case OUT = 'out';
    case TRANSFER = 'transfer';
}