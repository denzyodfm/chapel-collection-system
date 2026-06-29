<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->string('reference_no')->nullable()->after('collection_month');
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('fund_type');
            $table->string('entry_type')->default('credit');
            $table->decimal('amount', 12, 2);
            $table->date('entry_date');
            $table->string('reference_no')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fund_type', 'entry_date']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('fund_type')->default('general');
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('reference_no')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fund_type', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('ledger_entries');

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('reference_no');
        });
    }
};
