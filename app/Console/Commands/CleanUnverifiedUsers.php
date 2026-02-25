<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanUnverifiedUsers extends Command
{
    protected $signature = 'clean:unverified-users';
    protected $description = 'Delete unverified users older than 24 hours';

    public function handle()
    {
        $deleted = User::whereNull('email_verified_at')
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->delete();

        $this->info("Deleted {$deleted} unverified users.");
        
        return Command::SUCCESS;
    }
}