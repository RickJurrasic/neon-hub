<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['posts', 'comments', 'likes'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('demo_owner_id')->nullable()->after('user_id');
                $table->index('demo_owner_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['posts', 'comments', 'likes'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['demo_owner_id']);
                $table->dropColumn('demo_owner_id');
            });
        }
    }
};