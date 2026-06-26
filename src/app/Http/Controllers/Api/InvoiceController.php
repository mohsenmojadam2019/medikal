<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Invoice\InvoiceService;
use App\Traits\ApiResponse;
use App\Models\Patient;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use ApiResponse;

    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $invoices = $this->invoiceService->list($request->all(), $request->get('per_page', 15));
        return $this->success($invoices);
    }

    public function show($id)
    {
        try {
            $invoice = $this->invoiceService->show($id);

            $user = auth()->user();
            if (!$user->isAdmin() && $invoice->patient->user_id != $user->id) {
                return $this->error('شما دسترسی به این فاکتور ندارید', 403);
            }

            return $this->success($invoice);
        } catch (\Exception $e) {
            return $this->error('فاکتور یافت نشد', 404);
        }
    }

    public function myInvoices(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $invoices = $this->invoiceService->patientInvoices($patient, $request->all(), $request->get('per_page', 15));
        return $this->success($invoices);
    }

    public function stats(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $stats = $this->invoiceService->getTotalStats();
        return $this->success($stats);
    }
}
