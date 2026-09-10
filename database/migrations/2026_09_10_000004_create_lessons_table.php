<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = Monday ... 5 = Friday
            $table->unsignedTinyInteger('period');       // 0 ... 8
            $table->string('subject_code');
            $table->string('subject_name')->nullable();
            $table->string('room')->nullable();
            $table->string('group_label')->nullable();
            $table->string('week_parity')->default('every'); // every | odd | even
            $table->timestamps();

            $table->index(['user_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
