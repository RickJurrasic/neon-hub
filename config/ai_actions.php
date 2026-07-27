<?php

return [

    'actions' => [

        'friend_request' => [
            'params' => ['sender_id', 'recipient_id'],
            'description' => 'Send a friend request from one user to another',
            'min_interval' => 60,
            'max_per_minute' => 3,
        ],

        'send_message' => [
            'params' => ['sender_id', 'recipient_id', 'content'],
            'description' => 'Send a message between users',
            'min_interval' => 60,
            'max_per_minute' => 3,
        ],

        'create_post' => [
            'params' => ['user_id', 'content', 'type'],
            'description' => 'Create a new post (AI-generated content)',
            'min_interval' => 120,
            'max_per_minute' => 2,
        ],

        'like_post' => [
            'params' => ['user_id', 'post_id'],
            'description' => 'Like a post by user',
            'min_interval' => 30,
            'max_per_minute' => 5,
        ],

        'comment_post' => [
            'params' => ['user_id', 'post_id', 'content'],
            'description' => 'Add a comment to a post',
            'min_interval' => 45,
            'max_per_minute' => 4,
        ],

    ],

    'cooldown_actions' => [
        'friend_request',
        'send_message',
        'create_post',
        'like_post',
        'comment_post',
    ],

];
