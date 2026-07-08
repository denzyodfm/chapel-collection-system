<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('month_locks', function (Blueprint $table) {
            $table->id();
            $table->string('lockable_type', 50);
            $table->string('month', 7);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['lockable_type', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('month_locks');
    }
};
