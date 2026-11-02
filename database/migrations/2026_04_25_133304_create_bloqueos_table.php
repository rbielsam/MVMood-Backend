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
        Schema::create('bloqueos', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->uuid('bloqueador_id');
            $table->foreign('bloqueador_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('bloqueado_id');
            $table->foreign('bloqueado_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['bloqueador_id', 'bloqueado_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloqueos');
    }
};
