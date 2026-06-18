<?php

namespace Fantata\Auth;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FantataAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fantata-auth.php', 'fantata-auth');

        $this->app->singleton(FantataIdClient::class, fn () => new FantataIdClient(
            baseUrl: rtrim((string) config('fantata-auth.base_url'), '/'),
            site: (string) config('fantata-auth.site'),
            timeout: (int) config('fantata-auth.http_timeout', 10),
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'fantata-auth');

        $this->publishes([
            __DIR__.'/../config/fantata-auth.php' => config_path('fantata-auth.php'),
        ], 'fantata-auth-config');

        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/fantata-auth'),
        ], 'fantata-auth-assets');

        if (class_exists(Livewire::class)) {
            Livewire::component('fantata-passkey-login', \Fantata\Auth\Livewire\PasskeyLogin::class);
            Livewire::component('fantata-passkey-register', \Fantata\Auth\Livewire\PasskeyRegister::class);
        }
    }
}
