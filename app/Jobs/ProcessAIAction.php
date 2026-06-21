<?php

namespace App\Jobs;

use App\Actions\SendFriendRequestAction;
use App\Actions\SendMessageAction;
use App\Ai\Agents\AIAgent;
use App\Events\AIActionPerformed;
use App\Events\CommentCreated;
use App\Events\PostCreated;
use App\Events\PostLiked;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProcessAIAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $userId;

    protected string $actionType;

    protected array $payload;

    public function __construct(int $userId, string $actionType, array $payload = [])
    {
        $this->userId = $userId;
        $this->actionType = $actionType;
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        // Human-like delay
        usleep(mt_rand(500000, 2000000));

        if ($this->isRateLimited($user)) {
            Log::info("AI Profile [{$user->name}] is rate limited. Skipping action: {$this->actionType}");

            return;
        }

        DB::table('ai_profile_events')->insert([
            'user_id' => $user->id,
            'action_type' => $this->actionType,
            'executed_at' => now(),
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $this->executeAction($user);

            DB::table('ai_profile_events')
                ->where('user_id', $user->id)
                ->where('action_type', $this->actionType)
                ->where('status', 'processing')
                ->update(['status' => 'completed', 'updated_at' => now()]);

            event(new AIActionPerformed($user->id, $this->actionType, $this->payload));

        } catch (\Exception $e) {
            Log::error("AI Action Error [{$user->name}] - {$this->actionType}: ".$e->getMessage());

            DB::table('ai_profile_events')
                ->where('user_id', $user->id)
                ->where('action_type', $this->actionType)
                ->where('status', 'processing')
                ->update(['status' => 'failed', 'updated_at' => now()]);
        }
    }

    protected function isRateLimited(User $user): bool
    {
        return DB::table('ai_profile_events')
            ->where('user_id', $user->id)
            ->where('executed_at', '>=', now()->subMinute())
            ->count() >= 3;
    }

    protected function executeAction(User $user): void
    {
        $recruiterId = 1;

        // 🎭 Inicializace AI Agenta a nastavení jeho persony podle jména bota
        $agent = (new AIAgent)->setPersona($user->name);

        switch ($this->actionType) {
            case 'friend_request':
                if ($user->id !== $recruiterId) {
                    (new SendFriendRequestAction)->execute((int) $user->id, $recruiterId);
                }
                break;

            case 'send_message':
                $recipientId = $recruiterId;

                $conversation = DB::table('agent_conversations')
                    ->where('user_id', $user->id)
                    ->first();

                $conversationId = $conversation ? $conversation->id : null;
                $lastMessage = null;

                if ($conversationId) {
                    $lastMessage = DB::table('agent_conversation_messages')
                        ->where('conversation_id', $conversationId)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // 🧠 Načteme historii konverzace do agenta, aby LLM vědělo, na co reaguje!
                    $agent->loadConversation((string) $conversationId);
                }

                if ($lastMessage && $lastMessage->role !== 'user') {
                    Log::info("AI action skipped: Bot {$user->name} waiting for user response.");

                    return;
                }

                // 🤖 LLM vygeneruje odpověď v angličtině podle své persony a historie
                $botMessageText = $agent->prompt(
                    'Generate the next short message reply to the user. '.
                    'Keep it strictly under 2 short sentences. Do not wrap in quotes. '.
                    'Speak completely in English matching your exact persona.',
                    provider: ['groq']
                )->text;

                (new SendMessageAction)->execute((int) $user->id, $recipientId, $botMessageText, $user->name);

                Log::info("AI action broadcasted: send_message from bot [{$user->name}] to user 1");
                break;

            case 'create_post':
                // 🤖 LLM vygeneruje unikátní anglický cyberpunk příspěvek
                $postContent = $agent->prompt(
                    'Create a unique, immersive cyberpunk-themed social media post for your profile. '.
                    'Include 1-2 cool tech hashtags. Keep it under 240 characters. '.
                    'Write completely in English. Do not include your name or quotes.',
                    provider: ['groq']
                )->text;

                $post = $user->posts()->create([
                    'content' => $postContent,
                    'type' => 'ai',
                    'latency' => rand(1, 3).'.'.rand(0, 9).'ms',
                    'likes_count' => 0,
                ]);

                $formattedPost = [
                    'id' => $post->id,
                    'author' => $user->name,
                    'content' => $post->content,
                    'type' => $post->type,
                    'time' => $post->latency,
                    'likes_count' => $post->likes_count ?? 0,
                    'comments_count' => 0,
                    'image' => null,
                    'image_meta' => null,
                    'comments' => [],
                ];

                event(new PostCreated($formattedPost, 1));

                Log::info("AI action executed: create_post by bot [{$user->name}]");
                break;

            case 'like_post':
                $post = Post::inRandomOrder()->first();
                if ($post) {
                    $alreadyLiked = DB::table('likes')
                        ->where('user_id', $user->id)
                        ->where('post_id', $post->id)
                        ->exists();

                    if (! $alreadyLiked) {
                        DB::table('likes')->insert([
                            'user_id' => $user->id,
                            'post_id' => $post->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        if (Schema::hasColumn('posts', 'likes_count')) {
                            $post->increment('likes_count');
                        } elseif (Schema::hasColumn('posts', 'likes')) {
                            $post->increment('likes');
                        }

                        $likesCount = $post->fresh()->likes_count ?? $post->likes()->count();
                        $userName = $user->name ?? 'BOT';
                        event(new PostLiked($post->id, (int) $likesCount, $user, true));

                        DB::table('notifications')->insert([
                            'id' => Str::uuid(),
                            'type' => 'App\\Notifications\\PostLiked',
                            'notifiable_id' => $post->user_id,
                            'notifiable_type' => 'App\\Models\\User',
                            'data' => json_encode([
                                'type' => 'like',
                                'post_id' => $post->id,
                                'user_id' => $user->id,
                                'user_name' => $userName,
                                'message' => $userName.' liked your post.',
                            ]),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                break;

            case 'comment_post':
                $post = Post::inRandomOrder()->first();
                if ($post) {
                    // 🤖 LLM vygeneruje anglický komentář reagující na konkrétní post
                    $commentContent = $agent->prompt(
                        "Write a short, single-sentence comment reacting to this post: \"{$post->content}\". ".
                        'Match your persona. Speak in English. Do not include quotes.',
                        provider: ['groq']
                    )->text;

                    $commentId = DB::table('comments')->insertGetId([
                        'user_id' => $user->id,
                        'post_id' => $post->id,
                        'content' => $commentContent,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if (Schema::hasColumn('posts', 'comments_count')) {
                        $post->increment('comments_count');
                    }

                    event(new CommentCreated($post->id, [
                        'id' => $commentId,
                        'post_id' => $post->id,
                        'content' => $commentContent,
                        'author' => $user->name ?? 'BOT',
                        'created_at' => now()->toIso8601String(),
                    ], $post->user_id, $user->id));
                }
                break;

            default:
                Log::warning("Unknown AI action type: {$this->actionType}");
                break;
        }
    }
}
