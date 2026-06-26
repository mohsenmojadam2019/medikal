<?php

namespace App\Services\Rating;

use App\Models\Rating;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

class RatingService
{
    public function create(array $data): Rating
    {
        return DB::transaction(function () use ($data) {
            $rating = Rating::create($data);
            $this->updateDoctorRating($data['doctor_id']);
            return $rating->load(['patient.user', 'doctor.user']);
        });
    }

    public function getDoctorRatings(int $doctorId, array $filters = [], int $perPage = 15)
    {
        $query = Rating::where('doctor_id', $doctorId)
            ->with(['patient.user']);

        if (isset($filters['min_score'])) {
            $query->where('score', '>=', $filters['min_score']);
        }

        if (isset($filters['max_score'])) {
            $query->where('score', '<=', $filters['max_score']);
        }

        if (isset($filters['has_reply'])) {
            if ($filters['has_reply']) {
                $query->whereNotNull('reply');
            } else {
                $query->whereNull('reply');
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getDoctorStats(int $doctorId): array
    {
        $ratings = Rating::where('doctor_id', $doctorId);
        $total = $ratings->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'average' => 0,
                'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'recent' => [],
            ];
        }

        $distribution = [
            1 => $ratings->where('score', 1)->count(),
            2 => $ratings->where('score', 2)->count(),
            3 => $ratings->where('score', 3)->count(),
            4 => $ratings->where('score', 4)->count(),
            5 => $ratings->where('score', 5)->count(),
        ];

        return [
            'total' => $total,
            'average' => round($ratings->avg('score'), 1),
            'distribution' => $distribution,
            'recent' => $ratings->with(['patient.user'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    public function getTopDoctors(int $limit = 10)
    {
        return Doctor::with(['user', 'specialty'])
            ->where('is_active', true)
            ->where('is_verified', true)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * پاسخ به نظر بیمار (ادمین)
     */
    public function replyToRating(int $ratingId, string $reply): Rating
    {
        $rating = Rating::findOrFail($ratingId);

        $rating->update([
            'reply' => $reply,
            'replied_at' => now(),
        ]);

        return $rating->fresh();
    }

    /**
     * حذف پاسخ (ادمین)
     */
    public function deleteReply(int $ratingId): Rating
    {
        $rating = Rating::findOrFail($ratingId);
        $rating->update([
            'reply' => null,
            'replied_at' => null,
        ]);
        return $rating->fresh();
    }

    private function updateDoctorRating(int $doctorId): void
    {
        $avg = Rating::where('doctor_id', $doctorId)->avg('score');
        $count = Rating::where('doctor_id', $doctorId)->count();

        Doctor::where('id', $doctorId)->update([
            'rating' => round($avg ?? 0, 1),
            'total_reviews' => $count,
        ]);
    }
}
