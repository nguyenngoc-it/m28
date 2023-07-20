<?php

namespace Modules\Tenant\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Tenant\Models\Tenant;

class TestConnectionCommand extends Command
{
    protected $signature = 'tenant:test
        {tenant_code? : Tenant code} 
        {--email_to= : Email nhận khi test gửi email} 
    ';

    protected $description = 'Test tenant connections';

    public function handle()
    {
        $code = $this->argument('tenant_code');

        $tenant = $code
            ? Tenant::query()->where('code', $code)->first()
            : Tenant::query()->first();

        if (!$tenant) {
            $this->error("Tenant [{$code}] does not exists");
            return;
        }

        $this->info('Start test connections');
        $this->testStorage($tenant);
        $this->testEmail($tenant, $this->option('email_to'));
        $this->info('Finish test connections');
    }

    protected function testStorage(Tenant $tenant)
    {
        $this->warn("Storage: Connecting");
        $tenant->storage()->put('test.log', Carbon::now()->toDateTimeString(), 'public');
        $file = $tenant->storage()->url('test.log');
        $this->info("Storage: Connected - File: {$file}");
    }

    protected function testEmail(Tenant $tenant, $to = null)
    {
        $this->warn("Email: Sending");
        if ($tenant->email()->send($to ?: 'nguyensontung@gobiz.vn', 'Test', 'Test...')) {
            $this->info("Email: Sent");
        } else {
            $this->error("Email: Error");
        }
    }
}