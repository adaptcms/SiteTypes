<?php

namespace Adaptcms\SiteTypes;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

use Adaptcms\SiteTypes\Models\SiteType;

class SiteTypesServiceProvider extends ServiceProvider
{
  /**
   * Perform post-registration booting of services.
   *
   * @return void
   */
  public function boot()
  {
    // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'adaptcms');
    // $this->loadViewsFrom(__DIR__.'/../resources/views', 'adaptcms');
    $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

    // Publishing is only necessary when using the CLI.
    if ($this->app->runningInConsole()) {
      $this->bootForConsole();
    }

    // set active site type settings to view
    $siteType = SiteType::where('is_active', true)->first();

    if (!empty($siteType)) {
      $settings = $siteType->settings()->all();
      $settings = $settings['config'];

      Inertia::share('siteTypeConfig', $settings);
    }
  }

  /**
   * Register any package services.
   *
   * @return void
   */
  public function register()
  {
    $this->mergeConfigFrom(__DIR__.'/../config/sitetypes.php', 'sitetypes');

    // Register the service the package provides.
    $this->app->singleton('sitetypes', function ($app) {
      return new SiteTypes;
    });
  }

  /**
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides()
  {
    return [
      'sitetypes'
    ];
  }

  /**
   * Console-specific booting.
   *
   * @return void
   */
  protected function bootForConsole()
  {
    // Collect vendor name, and package name.
    $vendorName = basename(realpath(__DIR__ . '/../../'));
    $packageName = basename(realpath(__DIR__ . '/../'));

    // Publishing the configuration file.
    $this->publishes([
        __DIR__.'/../config/sitetypes.php' => config_path('sitetypes.php'),
    ], 'sitetypes.config');

    // Publishing the views.
    /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/adaptcms'),
    ], 'sitetypes.views');*/

    // Publishing assets.
    /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/adaptcms'),
    ], 'sitetypes.views');*/

    // Publishing the translation files.
    /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/adaptcms'),
    ], 'sitetypes.views');*/

    // Register package commands.
    $commands = [];
    foreach (glob(__DIR__ . '/Console/Commands/*.php') as $row) {
      // init class path
      $classPath = '\\Adaptcms\\SiteTypes\\Console\\Commands\\';

      // class path with command file class name
      $commandFileClass = str_replace('.php', '', basename($row));

      $classPath .= $commandFileClass;

      $commands[] = $classPath;
    }

    $this->commands($commands);
  }
}
