<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ValidateTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class TicketValidationController extends Controller
{
    public function validate(ValidateTicketRequest $request): JsonResponse
    {
        $code   = strtoupper(trim($request->code));
        $ticket = Ticket::where('code', $code)
            ->with([
                'batch:id,event_id,name,price',
                'batch.event:id,name,starts_at,location',
            ])
            ->first();

        if (!$ticket) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Ingresso nao encontrado. Verifique o codigo e tente novamente.',
            ], 404);
        }

        return match ($ticket->status) {
            'used' => response()->json([
                'status'  => 'already_used',
                'message' => 'Este ingresso ja foi utilizado.',
                'ticket'  => $this->formatTicket($ticket),
            ], 422),

            'cancelled' => response()->json([
                'status'  => 'cancelled',
                'message' => 'Este ingresso foi cancelado.',
                'ticket'  => $this->formatTicket($ticket),
            ], 422),

            'reserved' => response()->json([
                'status'  => 'not_paid',
                'message' => 'Este ingresso ainda nao foi pago.',
                'ticket'  => $this->formatTicket($ticket),
            ], 422),

            'paid' => $this->liberar($ticket),

            default => response()->json(['status' => 'error', 'message' => 'Status desconhecido.'], 422),
        };
    }

    private function liberar(Ticket $ticket): JsonResponse
    {
        $ticket->update(['status' => 'used', 'used_at' => now()]);
        return response()->json([
            'status'  => 'ok',
            'message' => 'Ingresso valido! Entrada liberada.',
            'ticket'  => $this->formatTicket($ticket->fresh(['batch', 'batch.event'])),
        ]);
    }

    private function formatTicket(Ticket $ticket): array
    {
        return [
            'id'          => $ticket->id,
            'code'        => $ticket->code,
            'status'      => $ticket->status,
            'used_at'     => $ticket->used_at,
            'lote'        => $ticket->batch?->name,
            'preco'       => $ticket->batch?->price,
            'evento'      => $ticket->batch?->event?->name,
            'data_evento' => $ticket->batch?->event?->starts_at,
            'local'       => $ticket->batch?->event?->location,
        ];
    }
}