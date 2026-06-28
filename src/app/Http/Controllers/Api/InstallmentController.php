<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Installment\InstallmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InstallmentController extends Controller
{
    use ApiResponse;

    protected InstallmentService $installmentService;

    public function __construct(InstallmentService $installmentService)
    {
        $this->installmentService = $installmentService;
    }

    // ============================================================
    // SETTINGS
    // ============================================================

    public function getSettings(Request $request)
    {
        $clinicId = $request->get('clinic_id', 1);
        $settings = $this->installmentService->getSettings($clinicId);
        return $this->success($settings);
    }

    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required|exists:clinics,id',
            'enable_installments' => 'nullable|boolean',
            'max_installments' => 'nullable|integer|min:1|max:36',
            'min_installment_amount' => 'nullable|integer|min:1000',
            'default_interest_rate' => 'nullable|numeric|min:0|max:100',
            'default_penalty_rate' => 'nullable|numeric|min:0|max:100',
            'grace_days' => 'nullable|integer|min:0|max:30',
            'available_gateways' => 'nullable|array',
            'require_down_payment' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $settings = $this->installmentService->updateSettings(
                $request->clinic_id,
                $request->except('clinic_id')
            );
            return $this->success($settings, 'تنظیمات با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function toggleInstallments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required|exists:clinics,id',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $settings = $this->installmentService->toggleInstallments($request->clinic_id);
            $status = $settings->enable_installments ? 'فعال' : 'غیرفعال';
            return $this->success($settings, "سیستم اقساط با موفقیت {$status} شد");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // CONTRACTS
    // ============================================================

    public function createContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'clinic_id' => 'required|exists:clinics,id',
            'total_amount' => 'required|numeric|min:1000',
            'down_payment' => 'nullable|numeric|min:0',
            'number_of_installments' => 'required|integer|min:1|max:36',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'penalty_rate' => 'nullable|numeric|min:0|max:100',
            'gateway' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $contract = $this->installmentService->createContract($request->all());
            return $this->success($contract, 'قرارداد اقساط با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getContracts(Request $request)
    {
        $contracts = $this->installmentService->getContracts(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($contracts);
    }

    public function getContract($id)
    {
        try {
            $contract = $this->installmentService->getContract($id);
            return $this->success($contract);
        } catch (\Exception $e) {
            return $this->error('قرارداد یافت نشد', 404);
        }
    }

    public function patientContracts(Request $request, $patientId)
    {
        $contracts = $this->installmentService->getPatientContracts(
            $patientId,
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($contracts);
    }

    public function activateContract($id)
    {
        try {
            $contract = $this->installmentService->activateContract($id);
            return $this->success($contract, 'قرارداد با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function cancelContract($id)
    {
        try {
            $contract = $this->installmentService->cancelContract($id);
            return $this->success($contract, 'قرارداد با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // INSTALLMENTS
    // ============================================================

    public function getInstallments(Request $request)
    {
        $installments = $this->installmentService->getInstallments(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($installments);
    }

    public function patientInstallments(Request $request, $patientId)
    {
        $installments = $this->installmentService->getPatientInstallments(
            $patientId,
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($installments);
    }

    public function upcomingInstallments($patientId)
    {
        $installments = $this->installmentService->getUpcomingInstallments($patientId);
        return $this->success($installments);
    }

    public function overdueInstallments($patientId)
    {
        $installments = $this->installmentService->getOverdueInstallments($patientId);
        return $this->success($installments);
    }

    public function payInstallment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'nullable|string',
            'reference' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $installment = $this->installmentService->payInstallment($id, $request->all());
            return $this->success($installment, 'قسط با موفقیت پرداخت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function waiveInstallment($id)
    {
        try {
            $installment = $this->installmentService->waiveInstallment($id);
            return $this->success($installment, 'قسط با موفقیت بخشیده شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // SUMMARY & STATS
    // ============================================================

    public function contractSummary($id)
    {
        try {
            $summary = $this->installmentService->getContractSummary($id);
            return $this->success($summary);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function stats(Request $request)
    {
        $clinicId = $request->get('clinic_id', 1);
        $stats = $this->installmentService->getStats($clinicId);
        return $this->success($stats);
    }
}
