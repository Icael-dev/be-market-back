<?php

namespace App\Services\Payment\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentProvider
{
    public function initiate(Order $order, array $data): array;

    public function verify(string $reference): array;

    public function handleCallback(array $data): array;
}