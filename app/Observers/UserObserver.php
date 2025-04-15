<?php

namespace App\Observers;

use App\Models\User;
use App\Services\VpnConfigService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    protected $vpnConfigService;

    public function __construct(VpnConfigService $vpnConfigService)
    {
        $this->vpnConfigService = $vpnConfigService;
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // When a new user is created, sync with all active servers
        try {
            Log::info("Syncing newly created user {$user->id} with all servers");
            $this->vpnConfigService->syncUserWithAllServers($user);
        } catch (\Exception $e) {
            Log::error("Error syncing new user with servers: {$e->getMessage()}");
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // If relevant user fields were changed, sync with all servers
        if ($user->isDirty(['email', 'api_key', 'role'])) {
            try {
                Log::info("Syncing updated user {$user->id} with all servers");
                $this->vpnConfigService->syncUserWithAllServers($user);

                // Clear any cached configurations
                $this->vpnConfigService->clearConfigCache($user);
            } catch (\Exception $e) {
                Log::error("Error syncing updated user with servers: {$e->getMessage()}");
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // When a user is deleted, remove them from all servers
        try {
            Log::info("Removing deleted user {$user->id} from all servers");
            $this->vpnConfigService->syncUserWithAllServers($user, true); // true = remove

            // Clear any cached configurations
            $this->vpnConfigService->clearConfigCache($user);
        } catch (\Exception $e) {
            Log::error("Error removing deleted user from servers: {$e->getMessage()}");
        }
    }
}
