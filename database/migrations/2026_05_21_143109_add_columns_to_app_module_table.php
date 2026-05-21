// database/migrations/xxxx_update_app_modules_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_module', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('url');        // path gambar icon
            $table->text('description')->nullable()->after('icon');
            $table->boolean('is_active')->default(true)->after('description');
            $table->unsignedInteger('order')->default(0)->after('is_active'); // urutan tampil
        });

        Schema::create('app_module_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_module_id')->constrained('app_module')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete(); // tabel roles dari Spatie
            $table->timestamps();

            $table->unique(['app_module_id', 'role_id']); // tidak boleh duplikat
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_module_roles');
        Schema::table('app_module', function (Blueprint $table) {
            $table->dropColumn(['icon', 'description', 'is_active', 'order']);
        });
    }
};