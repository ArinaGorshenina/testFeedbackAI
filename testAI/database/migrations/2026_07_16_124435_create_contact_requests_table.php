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
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
               $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->text('comment');
            $table->string('sentiment')->nullable();   // positive|neutral|negative
            $table->string('category')->nullable();     // жалоба|вопрос|предложение|другое
            $table->text('ai_summary')->nullable();
            $table->boolean('ai_available')->default(false);
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_requests');
    }
};
