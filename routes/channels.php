<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{userId}', function ($user, $userId) {
    return $user && (int) $user->id === (int) $userId;
});
