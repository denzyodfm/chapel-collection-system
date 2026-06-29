<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_id')->unique();
            $table->string('full_name');
            $table->string('contact_number')->nullable();
            $table->string('address_purok')->nullable();
            $table->string('status')->default('active');
            $table->date('date_joined')->nullable();
            $table->timestamps();

            $table->index(['status', 'full_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
