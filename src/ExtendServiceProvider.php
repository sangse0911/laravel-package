<?php

namespace Vision\Plugin;

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
        if ($this->checkIsDirectory(__DIR__ . "/../../../../platform/modules")) {
            $modulePath = __DIR__ . "/../../../../platform/modules";
            $listModule = array_map('basename', File::directories($modulePath));
            foreach ($listModule as $module) {
                $publishName = Str::of($module)->before("Module")->lower();
                // load config
                if ($this->checkIsDirectory($modulePath . "/" . $module . "/config")) {
                    $listConfig = $this->getRecursiveFileInDirectory($modulePath . "/" . $module . "/config");
                    foreach ($listConfig as $config) {
                        $this->mergeConfigFrom($config, "{$publishName}_config");
                    }
                }
                // load router
                if ($this->checkIsDirectory($modulePath . "/" . $module . "/routes")) {
                    $listRoute = $this->getRecursiveFileInDirectory($modulePath . "/" . $module . "/routes");
                    foreach ($listRoute as $route) {
                        if (Str::match('/.*web.php$/', $route)) {
                            $this->loadRoutesFrom($route);
                        }
                        if (Str::match('/.*api.php$/', $route)) {
                            Route::prefix("api")->group(fn () => $this->loadRoutesFrom($route));
                        }
                    }
                }
                // load database migration
                if ($this->checkIsDirectory($modulePath . "/" . $module . "/database/migrations")) {
                    $listMigration = $this->getRecursiveFileInDirectory($modulePath . "/" . $module . "/database/migrations");
                    foreach ($listMigration as $migration) {
                        $this->loadMigrationsFrom($migration);
                    }
                }
                // load database seeders
                if ($this->checkIsDirectory($modulePath . "/" . $module . "/database/seeders")) {
                    $listSeeder = $this->getRecursiveFileInDirectory($modulePath . "/" . $module . "/database/seeders");
                    foreach ($listSeeder as $seeder) {
                        $this->loadMigrationsFrom($seeder);
                    }
                }
                // load view
                if ($this->checkIsDirectory($modulePath . "/" . $module . "/resources/views")) {
                    $this->loadViewsFrom($modulePath . "/" . $module . "/resources/views", "{$publishName}_view");
                }
                // $this->publishes([__DIR__. '/plugins/' . $module . '/resources/views' .'/public' => public_path('vendor/courier')], 'public');
                // load providers
                if ($this->checkIsDirectory($modulePath . "/" . $module . "/app/Providers")) {
                    $listProvider = $this->getRecursiveFileInDirectory($modulePath . "/" . $module . "/app/Providers");
                    foreach ($listProvider as $provider) {
                        $provider = preg_replace("/\//", "\\", $provider);
                        // dd($provider);
                        // $provider = preg_replace("/\..*/", "", Str::afterLast($provider, "/"));
                        // dd($modulePath . "/" . $module . $provider);
                        // $this->app->register($provider);
                    }
                }
                // load function helper
                if ($this->checkIsDirectory($modulePath . "/" . $module . "/app/Helpers/FunctionHelper")) {
                    $listHelper = $this->getRecursiveFileInDirectory($modulePath . "/" . $module . "/app/Helpers/FunctionHelper");
                    foreach ($listHelper as $helper) {
                        require_once($helper);
                    }
                }
                // load lang
                if ($this->checkIsDirectory($modulePath . "/" . $module . "/resources/lang/")) {
                    $this->loadTranslationsFrom($modulePath . "/" . $module . "/resources/lang/", "{$publishName}_lang");
                    // $this->publishes([
                    //     $modulePath . "/" . $module . "/resources/lang/"  => resource_path("lang/vendor/{$publishName}"),
                    // ]);
                }
            }
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

    private function checkIsDirectory(string $path)
    {
        return is_dir($path);
    }
}
