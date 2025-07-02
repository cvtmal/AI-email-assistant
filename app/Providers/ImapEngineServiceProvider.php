<?php

declare(strict_types=1);

namespace App\Providers;

use DirectoryTree\ImapEngine\Laravel\ImapServiceProvider as BaseImapServiceProvider;
use Illuminate\Support\ServiceProvider;

final class ImapEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(BaseImapServiceProvider::class);

        // Merge our custom ImapEngine config into the 'imap' key that the package expects
        $config = $this->app->make('config');
        $config->set('imap', $config->get('imapengine'));
    }
}
