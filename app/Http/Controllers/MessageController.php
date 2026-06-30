<?php

namespace App\Http\Controllers;

use App\Actions\SendMessageAction;
use App\Http\Resources\MessageResource;
use App\Jobs\HandleAgentResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index()
    {
        $messages = DB::table('agent_conversation_messages as m')
            ->join('agent_conversations as c', 'm.conversation_id', '=', 'c.id')
            ->join('users as u', 'c.user_id', '=', 'u.id')
            ->where('m.user_id', auth()->id())
            ->orderBy('m.created_at', 'desc')
            ->select(['m.*', 'u.name as bot_real_name', 'm.content as text'])
            ->get();

        return MessageResource::collection($messages);
    }

    public function store(Request $request, SendMessageAction $sendMessageAction)
    {
        $request->validate([
            'message_id' => 'required|string',
            'text' => 'required|string|max:2000',
        ]);

        $context = $this->findConversationContext($request->message_id);

        if (! $context) {
            return response()->json(['error' => 'NODE_NOT_FOUND'], 404);
        }

        [$original, $conversation] = $context;

        $newMessageId = $sendMessageAction->execute(
            auth()->id(),
            $conversation->user_id,
            $request->text,
            $original->agent,
            'user'
        );

        HandleAgentResponse::dispatch(auth()->id(), $conversation->id);

        return new MessageResource((object) [
            'id' => $newMessageId, 'conversation_id' => $conversation->id, 'agent' => $original->agent,
            'text' => $request->text, 'created_at' => now(), 'role' => 'user',
        ]);
    }

    public function destroy($conversationId)
    {
        $conversationExists = DB::table('agent_conversations')->where('id', $conversationId)->exists();

        if (! $conversationExists) {
            return response()->json(['error' => 'NODE_NOT_FOUND'], 404);
        }

        DB::table('agent_conversation_messages')->where('conversation_id', $conversationId)->delete();
        DB::table('agent_conversations')->where('id', $conversationId)->delete();

        return response()->json(['status' => 'NODE_PURGED']);
    }

    private function findConversationContext(string $messageId): ?array
    {
        $original = DB::table('agent_conversation_messages')
            ->where('id', $messageId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $original) {
            return null;
        }

        $conversation = DB::table('agent_conversations')->where('id', $original->conversation_id)->first();

        if (! $conversation) {
            return null;
        }

        return [$original, $conversation];
    }
}
