<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resource_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->text('recurrence_rule')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_absences');
    }
};
