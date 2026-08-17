<?php

// Centralised NeonHub budget settings for the LLM rate limiter.
// Tier is resolved per-request by LlmRateLimiter::isDemo() (demo-<uuid> handle
// prefix ONLY — see app/Services/LlmRateLimiter.php). Keeping the legacy id-1
// "Recruiter Phantom" out of the demo branch is intentional: it is a registered
// user, so it must draw from the registered budget.
return [

    'llm_limits' => [

        'window_seconds' => 60,

        'demo' => [
            // Agent-initiated (startup) greetings — AutoSendAgentMessage.
            'startup'     => 2,
            // Human -> agent replies — POST /messages (MessageController::store).
            'interactive' => 3,
        ],

        'registered' => [
            'startup'     => 3,
            'interactive' => 8,
        ],

    ],

];
