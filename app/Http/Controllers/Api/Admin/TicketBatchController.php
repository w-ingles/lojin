<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTicketBatchRequest;
use App\Http\Requests\Admin\UpdateTicketBatchRequest;
use App\Models\Event;
use App\Models\TicketBatch;
use Illuminate\Http\JsonResponse;

class TicketBatchController extends Controller
{
    public function store(StoreTicketBatchRequest $request, Event $event): JsonResponse
    {
        $batch = $event->ticketBatches()->create([...$request->validated(), 'tenant_id' => $event->tenant_id]);
        return response()->json($batch, 201);
    }

    public function update(UpdateTicketBatchRequest $request, TicketBatch $batch): JsonResponse
    {
        $batch->update($request->validated());
        return response()->json($batch);
    }

    public function destroy(TicketBatch $batch): JsonResponse
    {
        $batch->delete();
        return response()->json(null, 204);
    }
}