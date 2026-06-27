<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventService
{
    public function getEvents(array $filters = [], int $perPage = 20)
    {
        $query = Event::query();

        if (isset($filters['search'])) {
            $query->where('title', 'LIKE', "%{$filters['search']}%")
                ->orWhere('description', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('start_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('end_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('start_date', 'desc')->paginate($perPage);
    }

    public function getPublishedEvents(array $filters = [], int $perPage = 20)
    {
        $query = Event::whereIn('status', ['published', 'ongoing']);

        if (isset($filters['search'])) {
            $query->where('title', 'LIKE', "%{$filters['search']}%")
                ->orWhere('description', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['from_date'])) {
            $query->where('start_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('end_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('start_date', 'desc')->paginate($perPage);
    }

    public function getUpcomingEvents(int $limit = 5)
    {
        return Event::upcoming()
            ->withCount('confirmedRegistrations')
            ->orderBy('start_date')
            ->limit($limit)
            ->get();
    }

    public function getActiveEvents()
    {
        return Event::active()->withCount('confirmedRegistrations')->get();
    }

    public function createEvent(array $data): Event
    {
        return Event::create($data);
    }

    public function updateEvent(Event $event, array $data): Event
    {
        $event->update($data);
        return $event->fresh();
    }

    public function deleteEvent(Event $event): void
    {
        $event->delete();
    }

    public function publishEvent(Event $event): Event
    {
        $event->update([
            'status' => 'published',
            'start_date' => $event->start_date ?? now(),
        ]);
        return $event->fresh();
    }

    public function completeEvent(Event $event): Event
    {
        $event->update(['status' => 'completed']);
        return $event->fresh();
    }

    public function registerPatient(int $eventId, int $patientId): EventRegistration
    {
        return DB::transaction(function () use ($eventId, $patientId) {
            $event = Event::findOrFail($eventId);

            $existing = EventRegistration::where('event_id', $eventId)
                ->where('patient_id', $patientId)
                ->first();

            if ($existing) {
                throw new \Exception('شما قبلاً برای این رویداد ثبت‌نام کرده‌اید');
            }

            return $event->registerPatient($patientId);
        });
    }

    public function confirmRegistration(int $registrationId): EventRegistration
    {
        $registration = EventRegistration::findOrFail($registrationId);

        if ($registration->status !== 'pending') {
            throw new \Exception('این ثبت‌نام قابل تایید نیست');
        }

        $registration->confirm();
        return $registration->fresh();
    }

    public function cancelRegistration(int $registrationId): EventRegistration
    {
        $registration = EventRegistration::findOrFail($registrationId);

        if ($registration->status === 'attended') {
            throw new \Exception('ثبت‌نام انجام شده قابل لغو نیست');
        }

        $registration->cancel();
        return $registration->fresh();
    }

    public function markAttendance(int $registrationId): EventRegistration
    {
        $registration = EventRegistration::findOrFail($registrationId);

        if ($registration->status !== 'confirmed') {
            throw new \Exception('فقط ثبت‌نام‌های تایید شده قابل حضور هستند');
        }

        $registration->markAsAttended();
        return $registration->fresh();
    }

    public function getEventRegistrations(int $eventId, array $filters = [], int $perPage = 20)
    {
        $query = EventRegistration::with(['patient'])
            ->byEvent($eventId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getPatientRegistrations(int $patientId, array $filters = [], int $perPage = 20)
    {
        $query = EventRegistration::with(['event'])
            ->byPatient($patientId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['upcoming'])) {
            $query->whereHas('event', function ($q) {
                $q->where('start_date', '>', now());
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getStats(): array
    {
        return [
            'total_events' => Event::count(),
            'published_events' => Event::whereIn('status', ['published', 'ongoing'])->count(),
            'active_events' => Event::active()->count(),
            'upcoming_events' => Event::upcoming()->count(),
            'total_registrations' => EventRegistration::count(),
            'confirmed_registrations' => EventRegistration::where('status', 'confirmed')->count(),
            'total_participants' => Event::sum('current_participants'),
            'by_type' => $this->getStatsByType(),
        ];
    }

    private function getStatsByType(): array
    {
        return Event::selectRaw('type, count(*) as total, sum(current_participants) as participants')
            ->groupBy('type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'total' => $item->total,
                    'participants' => $item->participants,
                ];
            })
            ->toArray();
    }
}
