<?php

namespace App\Providers;

use App\Models\Comunicado;
use App\Models\TareaSI;
use Illuminate\Support\Facades\URL;
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
        // En producción el SSL se atiende antes de Laravel: la petición llega como http
        // y los formularios salían con action="http://..." (Chrome: "información no segura").
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Excel: los textos que empiezan con "=" se guardan como texto, no como fórmula
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \App\Helpers\TextoSeguroValueBinder());

        View::composer('layouts.app', function ($view) {
            if (!auth()->check()) {
                return;
            }

            $desde = now()->subDays(5);

            $view->with([
                'hayComunicadoNuevo' => Comunicado::visiblePara(auth()->user())->activos()->where('created_at', '>=', $desde)->exists(),
                'hayTareaNueva'      => TareaSI::visiblePara(auth()->user())->where('created_at', '>=', $desde)->exists(),
            ]);
        });
    }
}
