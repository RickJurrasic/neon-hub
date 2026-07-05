<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{userId}', fn($user, $userId) => (int) $user->id === (int) $userId);

// Pro AI akce
Broadcast::channel('ai-actions.{userId}', fn($user, $userId) => (int) $user->id === (int) $userId);
