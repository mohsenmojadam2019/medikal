<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Event\EventService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    use ApiResponse;

    protected EventService $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function index(Request $request)
    {
        $events = $this->eventService->getEvents($request->all(), $request->get('per_page', 20));
        return $this->success($events);
    }

    public function published(Request $request)
    {
        $events = $this->eventService->getPublishedEvents($request->all(), $request->get('per_page', 20));
        return $this->success($events);
    }

    public function upcoming(Request $request)
    {
        $events = $this->eventService->getUpcomingEvents($request->get('limit', 5));
        return $this->success($events);
    }

    public function active()
    {
        $events = $this->eventService->getActiveEvents();
        return $this->success($events);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'required|in:webinar,workshop,seminar,campaign,other',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'online_link' => 'nullable|url',
            'max_participants' => 'nullable|integer|min:1',
            'is_free' => 'nullable|boolean',
            'price' => 'nullable|numeric|min:0',
            'speakers' => 'nullable|array',
            'schedule' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $event = $this->eventService->createEvent($request->all());
            return $this->success($event, 'رویداد با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show($id)
    {
        try {
            $event = Event::findOrFail($id);
            return $this->success($event);
        } catch (\Exception $e) {
            return $this->error('رویداد یافت نشد', 404);
        }
    }

    public function showBySlug($slug)
    {
        try {
            $event = Event::where('slug', $slug)->firstOrFail();
            return $this->success($event);
        } catch (\Exception $e) {
            return $this->error('رویداد یافت نشد', 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $event = Event::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('رویداد یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'sometimes|in:webinar,workshop,seminar,campaign,other',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'online_link' => 'nullable|url',
            'max_participants' => 'nullable|integer|min:1',
            'is_free' => 'nullable|boolean',
            'price' => 'nullable|numeric|min:0',
            'speakers' => 'nullable|array',
            'schedule' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $event = $this->eventService->updateEvent($event, $request->all());
            return $this->success($event, 'رویداد با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $event = Event::findOrFail($id);
            $this->eventService->deleteEvent($event);
            return $this->success(null, 'رویداد با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function publish($id)
    {
        try {
            $event = Event::findOrFail($id);
            $event = $this->eventService->publishEvent($event);
            return $this->success($event, 'رویداد با موفقیت منتشر شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function complete($id)
    {
        try {
            $event = Event::findOrFail($id);
            $event = $this->eventService->completeEvent($event);
            return $this->success($event, 'رویداد با موفقیت پایان یافت');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
            'patient_id' => 'required|exists:patients,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $registration = $this->eventService->registerPatient($request->event_id, $request->patient_id);
            return $this->success($registration, 'ثبت‌نام با موفقیت انجام شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function confirmRegistration($id)
    {
        try {
            $registration = $this->eventService->confirmRegistration($id);
            return $this->success($registration, 'ثبت‌نام با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function cancelRegistration($id)
    {
        try {
            $registration = $this->eventService->cancelRegistration($id);
            return $this->success($registration, 'ثبت‌نام با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function markAttendance($id)
    {
        try {
            $registration = $this->eventService->markAttendance($id);
            return $this->success($registration, 'حضور با موفقیت ثبت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function eventRegistrations(Request $request, $eventId)
    {
        $registrations = $this->eventService->getEventRegistrations($eventId, $request->all(), $request->get('per_page', 20));
        return $this->success($registrations);
    }

    public function patientRegistrations(Request $request, $patientId)
    {
        $registrations = $this->eventService->getPatientRegistrations($patientId, $request->all(), $request->get('per_page', 20));
        return $this->success($registrations);
    }

    public function stats()
    {
        $stats = $this->eventService->getStats();
        return $this->success($stats);
    }
}
