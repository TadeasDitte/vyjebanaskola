<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('color', 9)->default('#64748b');
            $table->smallInteger('sentiment')->default(0); // -1 bad, 0 neutral, 1 good
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flairs');
    }
};
