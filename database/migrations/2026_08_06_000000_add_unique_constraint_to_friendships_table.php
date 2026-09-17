<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a composite unique index on (sender_id, recipient_id) to prevent
     * duplicate friendship records at the database level. This is the
     * safety net for the TOCTOU race condition in SendFriendRequestAction:
     * the application-level between() check can be bypassed under concurrent
     * requests, but the DB constraint guarantees at-most-once creation.
     */
    public function up(): void
    {
        Schema::table('friendships', function (Blueprint $table) {
            $table->unique(['sender_id', 'recipient_id'], 'friendships_sender_recipient_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('friendships', function (Blueprint $table) {
            $table->dropUnique('friendships_sender_recipient_unique');
        });
    }
};
