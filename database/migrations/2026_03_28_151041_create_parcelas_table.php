<?php

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
        Schema::create('parcelas', function (Blueprint $table) {
            $table->foreignId('operacao_id')
            ->constrained('operacoes')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->date('data_vencimento')->index();
            $table->decimal('valor', 15, 2);
            $table->string('status', 15)->default('PENDENTE');
            $table->timestamps();
            $table->id();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcelas');
    }
};
