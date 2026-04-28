<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::with('ticketBatches')
            ->active()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->orderBy('starts_at')
            ->get()
            ->append(['banner_url', 'total_capacity', 'total_sold']);

        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        abort_if($event->status !== 'active', 404);
        return response()->json(
            $event->load('ticketBatches')->append(['banner_url','total_capacity','total_sold'])
        );
    }
}
