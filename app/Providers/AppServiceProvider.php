<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
  public function boot(): void
  {

    User::observe(UserObserver::class);
    Password::defaults(function () {
      return Password::min(8)->letters()->mixedCase()->numbers()->symbols();
    });

    // On définit manuellement le driver 'brevo'
    Mail::extend('brevo', function () {
      return (new BrevoTransportFactory)->create(
        new Dsn('brevo+api', 'default', config('services.brevo.key'))
      );
    });
    // Rate limits gérés UNIQUEMENT par RateLimitServiceProvider
  }
}
