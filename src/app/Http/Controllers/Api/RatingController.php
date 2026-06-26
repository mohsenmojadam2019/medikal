<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Rating\RatingService;
use App\Models\Doctor;
use App\Models\Patient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    use ApiResponse;

    protected RatingService $ratingService;

    public function __construct(RatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
            'categories' => 'nullable|array',
            'categories.behavior' => 'nullable|integer|min:1|max:5',
            'categories.knowledge' => 'nullable|integer|min:1|max:5',
            'categories.punctuality' => 'nullable|integer|min:1|max:5',
            'is_anonymous' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['patient_id'] = $patient->id;
            $rating = $this->ratingService->create($data);
            return $this->success($rating, 'امتیاز با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function doctorRatings(Request $request, $doctorId)
    {
        try {
            $doctor = Doctor::findOrFail($doctorId);
            $ratings = $this->ratingService->getDoctorRatings($doctorId, $request->all(), $request->get('per_page', 15));
            $stats = $this->ratingService->getDoctorStats($doctorId);
            return $this->success(['stats' => $stats, 'ratings' => $ratings]);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    public function doctorStats($doctorId)
    {
        try {
            $doctor = Doctor::findOrFail($doctorId);
            $stats = $this->ratingService->getDoctorStats($doctorId);
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    public function topDoctors(Request $request)
    {
        $limit = $request->get('limit', 10);
        $doctors = $this->ratingService->getTopDoctors($limit);
        return $this->success($doctors);
    }

    /**
     * پاسخ به نظر (فقط ادمین)
     */
    public function reply(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $rating = $this->ratingService->replyToRating($id, $request->reply);
            return $this->success($rating, 'پاسخ با موفقیت ثبت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * حذف پاسخ (فقط ادمین)
     */
    public function deleteReply($id)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        try {
            $rating = $this->ratingService->deleteReply($id);
            return $this->success($rating, 'پاسخ با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
