<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hugpong_banays', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('current_leader_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('hugpong_banay_id')->nullable()->after('address_purok')->constrained('hugpong_banays')->nullOnDelete();
            $table->index(['hugpong_banay_id', 'status']);
        });

        Schema::create('hugpong_banay_leader_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hugpong_banay_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['hugpong_banay_id', 'started_at']);
            $table->index(['member_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hugpong_banay_leader_histories');

        Schema::table('members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hugpong_banay_id');
        });

        Schema::dropIfExists('hugpong_banays');
    }
};
