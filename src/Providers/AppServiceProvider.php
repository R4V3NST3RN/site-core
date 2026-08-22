<?php

namespace App\Providers;

use App\Console\Commands\SyncCourses;
use App\Contracts\CourseSyncProvider;
use App\Models\CourseType;
use App\Models\Trainer;
use App\Services\NullSyncProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Konkrétní sync integrace je per-web konfigurace (viz config/site.php).
        // Default je záměrně NullSyncProvider: web, kterému chybí nebo se
        // rozbije klíč 'site.sync.provider', nesmí spadnout do klientské
        // integrace — musí nesynchronizovat vůbec.
        $this->app->singleton(CourseSyncProvider::class, function ($app) {
            $provider = config('site.sync.provider');

            if (! is_string($provider) || ! is_a($provider, CourseSyncProvider::class, true)) {
                $provider = NullSyncProvider::class;
            }

            return $app->make($provider);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Migrace jádra leží mimo database_path(), takže je Laravel sám
        // nenačte — na rozdíl od per-web migrací v database/migrations,
        // které zůstávají ve výchozí cestě. Obě sady se slévají a řadí
        // podle jména souboru, pořadí se tedy přesunem nezměnilo.
        $this->loadMigrationsFrom(base_path('core/database/migrations'));

        // Auto-discovery příkazů (Kernel::load) odvozuje jméno třídy tak, že
        // ze souboru ustřihne prefix app_path() — na core/src/ tedy nesedí a
        // adresář jádra tiše přeskočí. Příkazy jádra se proto registrují
        // jmenovitě, a to tady, ne v bootstrap/app.php: ten je per-web, kdežto
        // seznam core příkazů patří k jádru. Bez toho by courses:sync zmizel
        // z Artisanu a scheduler v core/routes/console.php by ho volal naprázdno.
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncCourses::class,
            ]);
        }

        // Hlavička potřebuje typy kurzů a trenéry, ale na rozdíl od
        // activePartner() se tenhle dotaz NEDÁ nechat na controllerech:
        // activePartner() plní sekci, která na část stránek záměrně nepatří,
        // kdežto hlavička je na KAŽDÉ veřejné stránce — i na těch, kam
        // controllery data vědomě neposílají (blog, galerie, hledání,
        // kontakt). Vypisovat je tam ručně by znamenalo přidat stejné dva
        // řádky do každé metody a čekat, kdo je u příští stránky zapomene.
        //
        // Composer je líný: uzávěr se zavolá až při vykreslení té konkrétní
        // šablony, takže Filament admin ani konzole tyhle dotazy nepustí.
        //
        // Proměnné se jmenují $navCourseTypes/$navTrainers schválně —
        // prosté $trainers už do některých stránek posílají controllery
        // a composer by jim je přepsal.
        View::composer('public.layout', function ($view): void {
            $view->with([
                'navCourseTypes' => CourseType::activeOrdered()->get(),
                'navTrainers' => Trainer::activeOrdered()->get(),
            ]);
        });
    }
}
