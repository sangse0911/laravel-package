<?php

namespace Plugin;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Illuminate\Support\Facades\Route;

class ExtendServiceProvider extends IlluminateServiceProvider
{
    public function boot()
    {

    }

    public function register(): void
    {
        $listModule = array_map('basename', File::directories(__DIR__ . '/plugins'));
        foreach ($listModule as $module) {
            $publishName = Str::of($module)->before("Module")->lower();
            // load config
            $listConfig = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/config');
            foreach ($listConfig as $config) {
                $this->mergeConfigFrom($config, "{$publishName}_config");
            }
            // load router
            $listRoute = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/routes');
            foreach ($listRoute as $route) {
                if (Str::match('/.*web.php$/', $route)) {
                    $this->loadRoutesFrom($route);
                }
                if (Str::match('/.*api.php$/', $route)) {
                    Route::prefix('api')->group(fn() => $this->loadRoutesFrom($route));
                }
            }
            // load database migration
            $listMigration = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/database/migrations');
            foreach ($listMigration as $migration) {
                $this->loadMigrationsFrom($migration);
            }

            // load database seeders
            $listSeeder = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/database/seeders');
            foreach ($listSeeder as $seeder) {
                $this->loadMigrationsFrom($seeder);
            }
            // load view
            $this->loadViewsFrom(__DIR__ . '/plugins/' . $module . '/resources/views', "{$publishName}_view");
//            $this->publishes([__DIR__. '/plugins/' . $module . '/resources/views' .'/public' => public_path('vendor/courier')], 'public');
            // load providers
            $listProvider = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/app/Providers');
            foreach ($listProvider as $provider) {
                $provider = preg_replace("/\..*/", "", Str::afterLast($provider, '/'));
                $this->app->register("Plugin\plugins\\{$module}\\app\Providers\\{$provider}");
            }
            // load function helper
            $listHelper = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/app/Helpers/FunctionHelper');
            foreach ($listHelper as $helper) {
                require_once($helper);
            }
            // load lang
            $this->loadTranslationsFrom(__DIR__ . '/plugins/' . $module . '/resources/lang/', "{$publishName}_lang");
            $this->publishes([
                __DIR__ . '/modules/' . $module . '/resources/lang/'  => resource_path("lang/vendor/{$publishName}"),
            ]);
        }
    }

    /**
     * @param string $path
     * @return array
     */
    private function getRecursiveFileInDirectory(string $path): array
    {
        $extension = ['php'];
        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);
        $files = [];
        foreach ($iterator as $info) {
            if (in_array($info->getExtension(), $extension)) $files[] = $info->getRealPath();
        }

        return $files;
    }
}
