<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('collection_type');
            $table->decimal('amount', 12, 2);
            $table->date('collection_date');
            $table->string('collection_month', 7)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->string('balik_gasa_unique_month', 7)->nullable()->storedAs("case when collection_type = 'balik_gasa' and deleted_at is null then collection_month else null end");

            $table->index(['collection_type', 'collection_month']);
            $table->index(['collection_date', 'member_id']);
            $table->unique(['member_id', 'collection_type', 'balik_gasa_unique_month'], 'collections_unique_balik_gasa_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
