<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateServerApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shrakvpn:generate-api-key {server_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a secure API key for VPN server communication';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serverId = $this->argument('server_id') ?: 'global';
        $apiKey = 'shkvpn-' . Str::random(32);

        $this->info("Generated API key for server '$serverId':");
        $this->newLine();
        $this->line("<bg=blue;fg=white> $apiKey </>");
        $this->newLine();
        $this->info("For the Admin Panel's .env file:");
        $this->line("SERVER_API_KEY=$apiKey");
        $this->newLine();
        $this->info("For the VPN server's config.php:");
        $this->line("'server_api_key' => '$apiKey',");

        return Command::SUCCESS;
    }
}
