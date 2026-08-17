<?php

namespace Tests\Support;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Http\Request;
use Symfony\Component\Httpkernel\Exception\AccessDeniedHttpException;

/**
 * Test-only broadcaster that evaluates the real channels.php gate closures
 * through the framework's OWN Broadcaster::verifyUserCanAccessChannel() -- the
 * identical closure-matching / parameter-binding / owner-gate code path used by
 * every production broadcaster (redis, pusher, reverb, ably).
 *
 * Why it exists: the test environment is forced to BROADCAST_CONNECTION=null
 * (see phpunit.xml), and NullBroadcaster::auth() is an empty no-op that always
 * authorizes. Under that setting the /broadcasting/auth route could NEVER return
 * 403, so authorization (denial) assertions would be impossible. Binding this
 * fake as the active driver (see BroadcastAuthTest::beforeEach) makes the real
 * HTTP /broadcasting/auth route honor the channels.php gates end-to-end.
 */
final class FakeAuthBroadcaster extends Broadcaster
{
    /**
     * Authenticate the incoming request for the requested channel(s).
     *
     * Mirrors RedisBroadcaster::auth() (signature-free) but accepts the Echo /
     * Reverb client request shape, which sends the `channels` array.
     */
    public function auth($request)
    {
        foreach ($this->channelsFromRequest($request) as $channel) {
            if (! is_string($channel) || $channel === '') {
                continue;
            }

            $name = $this->normalizeChannelName($channel);

            if ($this->isGuardedChannel($channel) &&
                ! $this->retrieveUser($request, $name)) {
                throw new AccessDeniedHttpException;
            }

            // verifyUserCanAccessChannel() invokes the registered channels.php
            // closure; it throws AccessDeniedHttpException (403) when the gate
            // returns false, and returns validAuthenticationResponse() on success.
            $this->verifyUserCanAccessChannel($request, $name);
        }

        return $this->validAuthenticationResponse($request, true);
    }

    public function validAuthenticationResponse($request, $result)
    {
        if (is_bool($result)) {
            return json_encode($result);
        }

        return json_encode(['data' => $result]);
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        // No transport persistence during tests.
    }

    /**
     * The channels() payload sent by Laravel Echo / Reverb (`channels` array),
     * falling back to the legacy single `channel_name` field.
     */
    protected function channelsFromRequest(Request $request): array
    {
        $channels = $request->input('channels');

        if (! is_array($channels)) {
            $channels = [$request->input('channel_name')];
        }

        return array_values(
            array_filter($channels, fn ($channel) => is_string($channel) && $channel !== '')
        );
    }

    /**
     * Strip the private-/presence-/client- type prefix so the channel name
     * matches the pattern registered via Broadcast::channel() (which has none).
     */
    protected function normalizeChannelName(string $channel): string
    {
        foreach (['presence-', 'private-', 'client-'] as $prefix) {
            if (str_starts_with($channel, $prefix)) {
                return substr($channel, strlen($prefix));
            }
        }

        return $channel;
    }

    /**
     * Private and presence channels require an authenticated user.
     */
    protected function isGuardedChannel(string $channel): bool
    {
        return str_starts_with($channel, 'private-') ||
            str_starts_with($channel, 'presence-');
    }
}
