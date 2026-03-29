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
        Schema::create('status_logs', function (Blueprint $table) {
            $table->foreignId('operacao_id')
            ->constrained('operacoes')->cascadeOnDelete();
            $table->tinyInteger('status_anterior');
            $table->tinyInteger('status_novo');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->id();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_logs');
    }
};
