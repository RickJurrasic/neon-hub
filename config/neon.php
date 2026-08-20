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

        // One-shot action the demo human initiates: POST /system/initialize-node
        // ("Enter System"). Kept SEPARATE from `startup` (page-load welcome
        // greeting) so a reload-heavy session cannot starve the deliberate
        // Enter-System flow. demo allows the first Enter (blocks repeat
        // presses); registered allows a refresh-then-enter.
        'enter_system' => [
            'demo'       => 1,
            'registered' => 2,
        ],

    ],

];
