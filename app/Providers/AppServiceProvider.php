<?php

namespace App\Providers;

use App\Repositories\Interfaces\LoginLogRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\LoginLogRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Pasangan 1: Untuk User
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // Pasangan 2: Untuk Login Log (Yang baru kita buat)
        $this->app->bind(
            LoginLogRepositoryInterface::class
            ,
            LoginLogRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
