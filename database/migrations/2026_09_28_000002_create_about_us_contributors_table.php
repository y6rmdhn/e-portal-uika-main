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
        Schema::create('about_us_contributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_module_id')
                ->nullable()
                ->constrained('app_module')
                ->nullOnDelete();
            $table->string('name');
            $table->string('type'); // 'dosen' | 'mahasiswa'
            $table->string('angkatan')->nullable();
            $table->string('contribution')->nullable(); // ex: "Backend Developer"
            $table->string('photo')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_us_contributors');
    }
};
