<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmbryoCaseInstall extends Command
{
    protected $signature = 'embryocase:install {--email= : Admin email} {--password= : Admin password} {--name= : Admin name}';

    protected $description = 'Install and initialize EmbryoCase with an Administrator account';

    public function handle()
    {
        $this->info('=============================================');
        $this->info('   EMBRYOCASE INSTALLATION & INITIALIZATION   ');
        $this->info('=============================================');

        $name = $this->option('name') ?: $this->ask('Enter Administrator Full Name', 'System Administrator');
        $email = $this->option('email') ?: $this->ask('Enter Administrator Email', 'admin@embryocase.lab');
        $password = $this->option('password') ?: $this->secret('Enter Administrator Password (leave blank for AdminPassword123!)');

        if (empty($password)) {
            $password = 'AdminPassword123!';
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        ActivityLog::record($admin->id, 'admin_account_installed', null, [
            'admin_email' => $admin->email
        ]);

        $this->info("Administrator account created/updated successfully.");
        $this->info("Email:    {$email}");
        $this->info("Role:     admin");
        $this->info("Status:   active");
        $this->info('=============================================');

        return Command::SUCCESS;
    }
}

