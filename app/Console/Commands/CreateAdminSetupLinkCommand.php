<?php

namespace App\Console\Commands;

use App\Support\AdminSetupLink;
use Illuminate\Console\Command;

class CreateAdminSetupLinkCommand extends Command
{
    protected $signature = 'admin:setup-link';

    protected $description = 'Generate a one-time URL to create or reset an admin user';

    public function handle(): int
    {
        $token = AdminSetupLink::generate();
        $url = AdminSetupLink::url($token);

        $this->info('One-time admin setup link (expires in '.AdminSetupLink::TTL_HOURS.' hours):');
        $this->line($url);
        $this->newLine();
        $this->warn('This link is invalidated after the admin account is created.');

        return self::SUCCESS;
    }
}
