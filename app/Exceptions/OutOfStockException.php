<?php

namespace App\Exceptions;

use Exception;

class OutOfStockException extends Exception
{
    public function __construct(int $productId, int $warehouseId, int $requested, int $available)
    {
        parent::__construct(__('stock.insufficient', [
            'requested' => $requested,
            'available' => $available,
        ]));
    }
}
