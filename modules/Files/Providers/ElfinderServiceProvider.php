<?php namespace Modules\Files\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class ElfinderServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/elfinder.php';
        $this->mergeConfigFrom($configPath, 'elfinder');
        $this->publishes([$configPath => config_path('elfinder.php')], 'config');

        $this->app['command.elfinder.publish'] = $this->app->share(function($app)
        {
            $publicPath = $app['path.public'];
            return new \Barryvdh\Elfinder\Console\PublishCommand($app['files'], $publicPath);
        });
        $this->commands('command.elfinder.publish');
    }

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function boot(Router $router)
    {
        $viewPath = __DIR__.'/../resources/views';
        $this->loadViewsFrom($viewPath, 'elfinder');
        $this->publishes([
            $viewPath => base_path('resources/views/vendor/elfinder'),
        ], 'views');

        if (!defined('ELFINDER_IMG_PARENT_URL')) {
            define('ELFINDER_IMG_PARENT_URL', $this->app['url']->asset('modules/files'));
        }

        $config = $this->app['config']->get('elfinder.route', []);
        $config['namespace'] = '\\Modules\\Files\\Http\\Controllers\\';

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.elfinder.publish');
    }

}