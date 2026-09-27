<?php

use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Services\ActiveDemoUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @see https://laravel.com/docs/testing
 */
class CleanupStaleDemoSessionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAgentConversation(?int $userId = null, array $overrides = []): string
    {
        $id = Str::uuid()->toString();

        DB::table('agent_conversations')->insert([
            'id' => $id,
            'user_id' => $userId,
            'agent_user_id' => null,
            'title' => 'Test conversation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function makeDemoUser(array $overrides = []): object
    {
        return User::create(array_merge([
            'name' => 'Demo User',
            'email' => 'demo-'.uniqid().'@example.com',
            'handle' => 'demo-'.uniqid(),
            'password' => bcrypt('password'),
            'is_ai' => false,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function makeBotUser(array $overrides = []): object
    {
        return User::create(array_merge([
            'name' => 'AI Bot',
            'email' => 'bot-'.uniqid().'@example.com',
            'handle' => 'bot-'.uniqid(),
            'password' => bcrypt('password'),
            'is_ai' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function insertPosts(int $count, array $overrides = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = DB::table('posts')->insertGetId(array_merge([
                'content' => 'Post '.$i,
                'type' => 'SYSTEM_LOG',
                'user_id' => null,
                'demo_owner_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], $overrides));
        }
        return $ids;
    }

    private function insertComments(int $count, array $overrides = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = DB::table('comments')->insertGetId(array_merge([
                'content' => 'Comment '.$i,
                'user_id' => null,
                'post_id' => null,
                'demo_owner_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], $overrides));
        }
        return $ids;
    }

    private function insertLikes(int $count, array $overrides = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = DB::table('likes')->insertGetId(array_merge([
                'user_id' => null,
                'post_id' => null,
                'demo_owner_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], $overrides));
        }
        return $ids;
    }

    private function insertFriendships(int $senderId, int $recipientId, array $overrides = []): int
    {
        return DB::table('friendships')->insertGetId(array_merge([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function makeAgentConversationMessages(string $conversationId, int $count, array $overrides = []): void
    {
        for ($i = 0; $i < $count; $i++) {
            DB::table('agent_conversation_messages')->insert(array_merge([
                'id' => Str::uuid()->toString(),
                'conversation_id' => $conversationId,
                'user_id' => null,
                'agent' => 'sentinel',
                'role' => 'user',
                'content' => 'Message '.$i,
                'attachments' => '',
                'tool_calls' => '',
                'tool_results' => '',
                'usage' => '',
                'meta' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ], $overrides));
        }
    }

    private function insertNotifications(int $count, array $overrides = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = DB::table('notifications')->insertGetId(array_merge([
                'id' => Str::uuid()->toString(),
                'type' => 'TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => null,
                'data' => json_encode(['test' => $i]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], $overrides));
        }
        return $ids;
    }

    public function test_it_removes_stale_demo_sessions_and_related_data(): void
    {
        $this->makeBotUser(); // Ensure demo user ID != LEGACY_GLOBAL_USER_ID (1)
        $staleUser = $this->makeDemoUser(['handle' => 'demo-stale1']);
        Cache::forget(ActiveDemoUsers::ACTIVE_KEY.':'.$staleUser->id);

        $this->insertPosts(2, ['user_id' => $staleUser->id, 'demo_owner_id' => $staleUser->id]);
        $this->insertFriendships($staleUser->id, $staleUser->id);
        $convId = $this->makeAgentConversation($staleUser->id);
        $this->makeAgentConversationMessages($convId, 2);
        $this->insertNotifications(1, ['notifiable_id' => $staleUser->id]);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('posts', 2);
        $this->assertDatabaseCount('friendships', 1);
        $this->assertDatabaseCount('agent_conversations', 1);
        $this->assertDatabaseCount('agent_conversation_messages', 2);
        $this->assertDatabaseCount('notifications', 1);

        $this->artisan('cleanup:stale-demo-sessions');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('friendships', 0);
        $this->assertDatabaseCount('agent_conversations', 0);
        $this->assertDatabaseCount('agent_conversation_messages', 0);
        $this->assertFalse(Cache::has(ActiveDemoUsers::ACTIVE_KEY.':'.$staleUser->id));
        $this->assertFalse(Cache::has(ActiveDemoUsers::SCHED_NEXT_KEY.':'.$staleUser->id));
    }

    public function test_it_does_not_remove_active_demo_sessions(): void
    {
        $activeUser = $this->makeDemoUser(['handle' => 'demo-active1']);
        ActiveDemoUsers::record($activeUser->id);

        $this->insertPosts(2, ['user_id' => $activeUser->id, 'demo_owner_id' => $activeUser->id]);
        $this->insertFriendships($activeUser->id, $activeUser->id);
        $this->makeAgentConversation();
        $this->insertNotifications(1, ['notifiable_id' => $activeUser->id]);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('posts', 2);

        $this->artisan('cleanup:stale-demo-sessions');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('posts', 2);
        $this->assertDatabaseCount('friendships', 1);
        $this->assertDatabaseCount('agent_conversations', 1);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertTrue(Cache::has(ActiveDemoUsers::ACTIVE_KEY.':'.$activeUser->id));
    }

    public function test_it_handles_empty_state_gracefully(): void
    {
        $this->artisan('cleanup:stale-demo-sessions');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_does_not_touch_scheduler_processing_lock(): void
    {
        $this->makeBotUser(); // Push demo user id past LEGACY_GLOBAL_USER_ID (1)
        $staleUser = $this->makeDemoUser(['handle' => 'demo-stale2']);
        Cache::forget(ActiveDemoUsers::ACTIVE_KEY.':'.$staleUser->id);

        // Simulate scheduler processing lock
        Cache::put(ActiveDemoUsers::SCHED_LOCK_KEY.':'.$staleUser->id, 1, 10);

        $this->artisan('cleanup:stale-demo-sessions');

        // SCHED_LOCK_KEY must survive cleanup
        $this->assertTrue(Cache::has(ActiveDemoUsers::SCHED_LOCK_KEY.':'.$staleUser->id));
        // Bot user remains; stale demo user is deleted
        $this->assertDatabaseCount('users', 1);
    }

    public function test_heartbeat_contention_returns_false(): void
    {
        $user = $this->makeDemoUser(['handle' => 'demo-contention']);
        ActiveDemoUsers::record($user->id); // Acquire and release lock

        // Now hold the lifecycle lock
        $lock = Cache::lock(ActiveDemoUsers::LIFECYCLE_KEY.':'.$user->id, 60);
        $lock->block(3); // Blocks until lock acquired

        // record() should return false because lock is held
        $result = ActiveDemoUsers::record($user->id);
        $this->assertFalse($result);

        $lock->release();
    }
}
