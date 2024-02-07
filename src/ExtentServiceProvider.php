<?php

namespace Plugin;

use DirectoryIterator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ExtendServiceProvider extends IlluminateServiceProvider
{
    public function boot()
    {

    }

    public function register(): void
    {

        // $this->app->register(ModuleServiceProvider::class);

        $listModule = array_map('basename', File::directories(__DIR__ . '/plugins'));
        foreach ($listModule as $module) {
            $publishName = Str::of($module)->before("Module")->lower();
            
            // load config
            $listConfig = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/config');
            foreach ($listConfig as $config) {
                $this->mergeConfigFrom(__DIR__ . '/plugins/' . $module . '/config/' . $config, "{$publishName}_config");
            }

            // load router
            $this->loadRoutesFrom(__DIR__ . '/plugins/' . $module . '/routes/web.php');
            $this->loadRoutesFrom(__DIR__ . '/plugins/' . $module . '/routes/api.php');

            // load database migration
            $listMigration = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/database/migrations');
            foreach ($listMigration as $migration) {
                $this->loadMigrationsFrom(__DIR__ . '/plugins/' . $module . '/database/migrations/' . $migration);
            }

            // load database seeders
            $listSeeder = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/database/seeders');
            foreach ($listSeeder as $seeder) {
                $this->loadMigrationsFrom(__DIR__ . '/plugins/' . $module . '/database/seeders/' . $seeder);
            }

            // load view
            $this->loadViewsFrom(__DIR__ . '/plugins/' . $module . '/resources/views', "{$publishName}_view");
            // load assets

//            $this->publishes([__DIR__. '/plugins/' . $module . '/resources/views' .'/public' => public_path('vendor/courier')], 'public');
            // load providers
            $listProvider = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/app/Providers');
            foreach ($listProvider as $provider) {
                $provider = preg_replace("/\..*/", "", $provider);
                $this->app->register("Plugin\plugins\\{$module}\\app\Providers\\{$provider}");
            }

            // load function helper
            $listHelper = $this->getRecursiveFileInDirectory(__DIR__ . '/plugins/' . $module . '/app/Helpers/FunctionHelper');
            foreach ($listHelper as $helper) {
                require_once(__DIR__ . '/plugins/' . $module . "/app/Helpers/FunctionHelper/{$helper}");
            }

            // load lang
            $this->loadTranslationsFrom(__DIR__ . '/plugins/' . $module . '/resources/lang/', "{$publishName}_lang");
            $this->publishes([
                __DIR__ . '/modules/' . $module . '/resources/lang/'  => resource_path("lang/vendor/{$publishName}"),
            ]);
//            $langDirector = new DirectoryIterator(__DIR__ . '/plugins/' . $module . '/resources/lang');
//            foreach ($langDirector as $director) {
//                if ($director->isDir() && !$director->isDot()) {
//                    $listLang = $this->getRecursiveFileInDirectory($director->getPathName(), ['json', 'php']);
//                    foreach ($listLang as $lang) {
//                        $this->loadTranslationsFrom($director->getPathName() . '/' . $lang, 'acme');
//                    }
//                }
//            }
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
            if (in_array($info->getExtension(), $extension)) $files[] = $info->getFilename();
        }

        return $files;
    }
}
