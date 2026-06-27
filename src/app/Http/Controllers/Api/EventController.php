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

    public function index()
    {
        $events = $this->eventService->getAll();
        return $this->success($events);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'slug' => 'required|string|unique:events,slug',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'required|string',
            'max_attendees' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $event = $this->eventService->create($request->all());
        return $this->success($event, 'رویداد با موفقیت ایجاد شد', 201);
    }

    public function show($id)
    {
        $event = $this->eventService->find($id);
        if (!$event) {
            return $this->error('رویداد یافت نشد', 404);
        }
        return $this->success($event);
    }

    public function showBySlug($slug)
    {
        $event = $this->eventService->findBySlug($slug);
        if (!$event) {
            return $this->error('رویداد یافت نشد', 404);
        }
        return $this->success($event);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'slug' => 'sometimes|string|unique:events,slug,' . $id,
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'location' => 'sometimes|string',
            'max_attendees' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $event = $this->eventService->update($id, $request->all());
        if (!$event) {
            return $this->error('رویداد یافت نشد', 404);
        }
        return $this->success($event, 'رویداد با موفقیت بروزرسانی شد');
    }

    public function destroy($id)
    {
        $event = $this->eventService->delete($id);
        if (!$event) {
            return $this->error('رویداد یافت نشد', 404);
        }
        return $this->success(null, 'رویداد با موفقیت حذف شد');
    }

    public function publish($id)
    {
        $event = $this->eventService->publish($id);
        if (!$event) {
            return $this->error('رویداد یافت نشد', 404);
        }
        return $this->success($event, 'رویداد با موفقیت منتشر شد');
    }

    public function published()
    {
        $events = $this->eventService->getPublished();
        return $this->success($events);
    }

    public function upcoming()
    {
        $events = $this->eventService->getUpcoming();
        return $this->success($events);
    }

    public function active()
    {
        $events = $this->eventService->getActive();
        return $this->success($events);
    }

    public function getStats()
    {
        $stats = $this->eventService->getStats();
        return $this->success($stats);
    }
}
