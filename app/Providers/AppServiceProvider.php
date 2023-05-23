<?php

namespace App\Providers;

use App\Models\Room;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Page;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    
    public function boot()
    {
        Paginator::useBootstrap();
        $rooms = Room::get();
        view()->share('global_rooms',$rooms);
    }
}
