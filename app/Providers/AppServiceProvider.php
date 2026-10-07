<?php

namespace App\Providers;

use App\Models\Comunicado;
use App\Models\TareaSI;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            if (!auth()->check()) {
                return;
            }

            $desde = now()->subDays(5);

            $view->with([
                'hayComunicadoNuevo' => Comunicado::visiblePara(auth()->user())->activos()->where('created_at', '>=', $desde)->exists(),
                'hayTareaNueva'      => TareaSI::where('created_at', '>=', $desde)->exists(),
            ]);
        });
    }
}
