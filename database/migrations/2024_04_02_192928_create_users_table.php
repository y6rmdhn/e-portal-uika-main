<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users') && !Schema::hasTable('tb_users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('nip')->nullable()->unique();
                $table->string('nidn')->nullable()->unique();
                $table->string('npm')->nullable()->unique();
                $roleTable = config('permission.table_names.roles', 'roles');
                $table->foreignId('role_id')->nullable()->constrained($roleTable)->onDelete('cascade');
                $table->boolean('is_active')->default(false);
                $table->string('password');
                $table->bigInteger('phone')->nullable();
                $table->string('location')->nullable();
                $table->string('about_me')->nullable();
                $table->text('image')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
