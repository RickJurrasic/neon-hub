<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{userId}', function ($user, $userId) {
    Log::info('Broadcasting Auth Check:', [
        'authenticated_user_id' => $user ? $user->id : 'NULL',
        'requested_channel_userId' => $userId,
        'match' => $user ? ($user->id == $userId) : false
    ]);

    return $user && (int) $user->id === (int) $userId;
});