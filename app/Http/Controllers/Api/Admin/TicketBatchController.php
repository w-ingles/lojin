<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TicketBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketBatchController extends Controller
{
    public function store(Request $request, Event $event): JsonResponse
    {
        $data = $request->validate([
            'name'             => ['required','string','max:100'],
            'description'      => ['nullable','string'],
            'price'            => ['required','numeric','min:0'],
            'quantity'         => ['required','integer','min:1'],
            'is_active'        => ['boolean'],
            'available_from'   => ['nullable','date'],
            'available_until'  => ['nullable','date'],
            'max_per_order'    => ['nullable','integer','min:1'],
        ]);

        $batch = $event->ticketBatches()->create([...$data, 'tenant_id' => $event->tenant_id]);

        return response()->json($batch, 201);
    }

    public function update(Request $request, TicketBatch $batch): JsonResponse
    {
        $data = $request->validate([
            'name'            => ['sometimes','required','string','max:100'],
            'description'     => ['nullable','string'],
            'price'           => ['sometimes','required','numeric','min:0'],
            'quantity'        => ['sometimes','required','integer'],
            'is_active'       => ['boolean'],
            'available_from'  => ['nullable','date'],
            'available_until' => ['nullable','date'],
            'max_per_order'   => ['nullable','integer','min:1'],
        ]);

        $batch->update($data);
        return response()->json($batch);
    }

    public function destroy(TicketBatch $batch): JsonResponse
    {
        $batch->delete();
        return response()->json(null, 204);
    }
}
