<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminSupportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::with('user', 'messages.user.profile')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'ilike', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('email', 'ilike', "%{$search}%");
                    });
            });
        }

        return response()->json($query->paginate((int) $request->query('per_page', 10)));
    }

    public function show(string $id): JsonResponse
    {
        $ticket = SupportTicket::with('user', 'messages.user.profile')->findOrFail($id);

        return response()->json($ticket);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        $data = $request->validate([
            'status' => ['sometimes', 'in:open,in_progress,resolved,closed'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
        ]);

        $ticket->update($data);

        return response()->json($ticket->load('user', 'messages.user.profile'));
    }

    public function storeMessage(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $message = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->input('message'),
            'is_from_admin' => true,
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return response()->json($message->load('user'), 201);
    }
}
