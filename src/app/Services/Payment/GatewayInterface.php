<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use Illuminate\Http\Request;

interface GatewayInterface
{
    /**
     * شروع پرداخت
     */
    public function initiate(Invoice $invoice): array;

    /**
     * تایید پرداخت
     */
    public function verify(Request $request): array;

    /**
     * دریافت نام درگاه
     */
    public function getGatewayName(): string;
}
