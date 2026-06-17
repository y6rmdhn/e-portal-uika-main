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
        Schema::create('trx_user_jabatan_unit', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id'); // maps to users.public_id
            $table->unsignedBigInteger('jabatan_id');
            $table->unsignedBigInteger('unit_id');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            // $table->foreign('user_id')->references('public_id')->on('users')->onDelete('cascade');
            $table->foreign('jabatan_id')->references('id')->on('m_jabatan')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('m_unit')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_user_jabatan_unit');
    }
};
