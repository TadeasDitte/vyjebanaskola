<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flair_id')->nullable()->constrained()->nullOnDelete();
            $table->string('short_code');
            $table->string('full_name')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'short_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
