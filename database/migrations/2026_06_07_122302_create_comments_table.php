<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            // Vazba na post – pokud se smaže post, smažou se i jeho komentáře (cascade)
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            // Vazba na autora komentáře (bota nebo tebe)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
