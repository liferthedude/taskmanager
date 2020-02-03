<?php

namespace Lifer\TaskManager;

use Illuminate\Support\ServiceProvider;
use Lifer\TaskManager\Providers\TaskManagerEventServiceProvider;

class TaskManagerServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'lifer');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'lifer');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/taskmanager.php', 'taskmanager');

        $this->app->register(TaskManagerEventServiceProvider::class);

        // Register the service the package provides.
        $this->app->singleton('TaskManager', function ($app) {
            return new Services\TaskManager();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['taskmanager'];
    }
    
    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/taskmanager.php' => config_path('taskmanager.php'),
        ], 'taskmanager.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/lifer'),
        ], 'taskmanager.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/lifer'),
        ], 'taskmanager.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/lifer'),
        ], 'taskmanager.views');*/

        // Registering package commands.
        $this->commands([
            Commands\Manager\Service::class,
            Commands\Manager\Start::class,
            Commands\Manager\Stop::class,
            Commands\Manager\Status::class,
            Commands\Task\Fake::class,
            Commands\Task\Kill::class,
            Commands\Task\Log::class,
            Commands\Task\Run::class,
            Commands\Task\TaskList::class,
            Commands\Misc\CleanOldTasks::class,
        ]);

        $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'migrations');
    }
}
