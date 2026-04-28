<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Event::with('ticketBatches')->latest()->get()->append(['banner_url','total_capacity','total_sold'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required','string','max:200'],
            'description'  => ['nullable','string'],
            'location'     => ['nullable','string','max:200'],
            'address'      => ['nullable','string','max:300'],
            'starts_at'    => ['required','date'],
            'ends_at'      => ['nullable','date','after:starts_at'],
            'status'       => ['required','in:draft,active,sold_out,finished,cancelled'],
            'minimum_age'  => ['nullable','integer','min:0'],
            'banner'       => ['nullable','image','max:4096'],
        ]);

        if ($request->hasFile('banner')) {
            $data['banner'] = $request->file('banner')->store('events','public');
        }

        return response()->json(Event::create($data)->load('ticketBatches'), 201);
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json($event->load('ticketBatches')->append(['banner_url','total_capacity','total_sold']));
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes','required','string','max:200'],
            'description' => ['nullable','string'],
            'location'    => ['nullable','string','max:200'],
            'address'     => ['nullable','string','max:300'],
            'starts_at'   => ['sometimes','required','date'],
            'ends_at'     => ['nullable','date'],
            'status'      => ['sometimes','required','in:draft,active,sold_out,finished,cancelled'],
            'minimum_age' => ['nullable','integer','min:0'],
            'banner'      => ['nullable','image','max:4096'],
        ]);

        if ($request->hasFile('banner')) {
            if ($event->banner) Storage::disk('public')->delete($event->banner);
            $data['banner'] = $request->file('banner')->store('events','public');
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
