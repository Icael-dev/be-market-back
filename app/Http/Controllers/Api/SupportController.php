<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportController extends Controller
{
    public function storeTicket(Request $request): JsonResponse
    {
        $data = $request->only(['subject', 'message', 'priority']);
        $validator = Validator::make($data, [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
        ]);

        if (! empty($data['message'])) {
            SupportMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'message' => $data['message'],
                'is_from_admin' => false,
            ]);
        }

        return response()->json($ticket->load('messages'), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)->with('messages.user.profile')->get();

        return response()->json($tickets);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::where('user_id', $request->user()->id)->with('messages.user.profile')->find($id);
        if (! $ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        return response()->json($ticket);
    }

    public function storeMessage(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::where('user_id', $request->user()->id)->find($id);
        if (! $ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        $data = $request->only(['message']);
        $validator = Validator::make($data, [
            'message' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $message = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $data['message'],
            'is_from_admin' => false,
        ]);

        return response()->json($message, 201);
    }
}
