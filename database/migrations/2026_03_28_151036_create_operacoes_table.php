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
        Schema::create('operacoes', function (Blueprint $table) {
            $table->string('codigo', 20)->unique()->index();
            $table->foreignId('cliente_id')
            ->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('conveniada_id')
            ->constrained('conveniadas');
            $table->decimal('valor_requerido', 15, 2);
            $table->decimal('valor_desembolso', 15, 2);
            $table->decimal('total_juros', 15, 2)->default(0);
            $table->decimal('taxa_juros', 10, 6)->default(0);
            $table->decimal('taxa_multa', 10, 6)->default(0);
            $table->decimal('taxa_mora', 10, 6)->default(0);
            $table->tinyInteger('status_id')->default(1)->index();
            $table->string('produto', 30)->index();
            $table->date('data_criacao');
            $table->date('data_pagamento')->nullable();
            $table->boolean('assinatura_concluida')->default(false);
            $table->timestamps();
            $table->id();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operacoes');
    }
};
