<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Pro AI akce
Broadcast::channel('ai-actions.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
