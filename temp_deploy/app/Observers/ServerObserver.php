<?php

namespace App\Observers;

use App\Models\Server;
use App\Services\VpnConfigService;
use Illuminate\Support\Facades\Log;

class ServerObserver
{
    protected $vpnConfigService;

    public function __construct(VpnConfigService $vpnConfigService)
    {
        $this->vpnConfigService = $vpnConfigService;
    }

    /**
     * Handle the Server "created" event.
     */
    public function created(Server $server): void
    {
        // When a new server is created, clear any related cached configurations
        Log::info("New server created: {$server->id}");
        $this->vpnConfigService->clearConfigCache(null, $server);
    }

    /**
     * Handle the Server "updated" event.
     */
    public function updated(Server $server): void
    {
        // If relevant server fields changed, clear cached configurations
        if ($server->isDirty(['domain', 'status', 'tier'])) {
            Log::info("Server updated: {$server->id}");
            $this->vpnConfigService->clearConfigCache(null, $server);
        }
    }

    /**
     * Handle the Server "deleted" event.
     */
    public function deleted(Server $server): void
    {
        // When a server is deleted, clear all related configs
        Log::info("Server deleted: {$server->id}");
        $this->vpnConfigService->clearConfigCache(null, $server);
    }
}
