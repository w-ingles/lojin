<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Event::with('ticketBatches')->latest()->get()->append(['banner_url','total_capacity','total_sold'])
        );
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('banner')) {
            $data['banner'] = $request->file('banner')->store('events', 'public');
        }
        return response()->json(Event::create($data)->load('ticketBatches'), 201);
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json($event->load('ticketBatches')->append(['banner_url','total_capacity','total_sold']));
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('banner')) {
            if ($event->banner) Storage::disk('public')->delete($event->banner);
            $data['banner'] = $request->file('banner')->store('events', 'public');
        }
        $event->update($data);
        return response()->json($event->load('ticketBatches')->append(['banner_url','total_capacity','total_sold']));
    }

    public function destroy(Event $event): JsonResponse
    {
        if ($event->banner) Storage::disk('public')->delete($event->banner);
        $event->delete();
        return response()->json(null, 204);
    }
}