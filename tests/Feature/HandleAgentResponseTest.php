<?php

use App\Ai\Agents\AIAgent;
use App\Events\MessageReceived;
use App\Events\PostCreated;
use App\Jobs\HandleAgentResponse;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('HandleAgentResponse', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create(['name' => 'Test_User']);
        $this->agent = User::factory()->create(['name' => 'SENTINEL_01', 'is_ai' => true]);
    });

    it('generates chat response only (no feed post) in normal mode', function (): void {
        Event::fake([MessageReceived::class, PostCreated::class]);

        AIAgent::fake(['Test chat response']);

        $job = new HandleAgentResponse(
            userId: $this->user->id,
            conversationId: null,
            agentName: 'SENTINEL_01',
            createFeedPost: false
        );

        app()->call($job->handle(...));

        // Chat response should be broadcast
        Event::assertDispatched(MessageReceived::class);

        // No feed post should be created
        $this->assertDatabaseMissing('posts', [
            'user_id' => $this->agent->id,
        ]);

        Event::assertNotDispatched(PostCreated::class);
    });

    it('generates chat response AND feed post in startup mode', function (): void {
        Event::fake([MessageReceived::class, PostCreated::class]);

        AIAgent::fake([
            'Test chat response',
            'Test feed post response',
        ]);

        $job = new HandleAgentResponse(
            userId: $this->user->id,
            conversationId: null,
            agentName: 'SENTINEL_01',
            createFeedPost: true
        );

        app()->call($job->handle(...));

        // Chat response should be broadcast
        Event::assertDispatched(MessageReceived::class);

        // Feed post should be created
        $this->assertDatabaseHas('posts', [
            'user_id' => $this->agent->id,
        ]);

        Event::assertDispatched(
            PostCreated::class,
            fn (PostCreated $event) => $event->userId === $this->user->id
                && $event->post['author'] === $this->agent->name
        );
    });
});
