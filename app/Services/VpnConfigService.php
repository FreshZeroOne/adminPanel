<?php

namespace App\Services;

use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VpnConfigService
{
    /**
     * Cache TTL in minutes
     */
    protected const CACHE_TTL = 60;

    /**
     * Generate a VPN configuration for a specific user and server
     *
     * @param User $user
     * @param Server $server
     * @return array
     */
    public function generateConfig(User $user, Server $server): array
    {
        // Cache key using user ID and server ID
        $cacheKey = "vpn_config:{$user->id}:{$server->id}";

        // Return cached config if available
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Generate a unique client ID for this user-server pair
        $clientId = $this->generateClientId($user, $server);

        // Generate the base configuration
        $config = [
            'client_id' => $clientId,
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'domain' => $server->domain,
                'exit_country' => $server->exit_country,
                'city' => $server->city,
            ],
            'connection' => [
                'protocol' => 'udp',
                'port' => 1194,
                'encryption' => 'AES-256-GCM',
            ],
            'credentials' => [
                'username' => $user->email,
                'password' => $this->generateConnectionToken($user, $server),
            ],
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addDays(7)->toIso8601String(),
        ];

        // Cache the configuration
        Cache::put($cacheKey, $config, now()->addMinutes(self::CACHE_TTL));

        return $config;
    }

    /**
     * Generate OpenVPN configuration file content
     *
     * @param User $user
     * @param Server $server
     * @return string
     */
    public function generateOpenVpnConfig(User $user, Server $server): string
    {
        $config = $this->generateConfig($user, $server);

        // Start with client configuration directive
        $ovpnConfig = "client\n";
        $ovpnConfig .= "dev tun\n";
        $ovpnConfig .= "proto {$config['connection']['protocol']}\n";
        $ovpnConfig .= "remote {$config['server']['domain']} {$config['connection']['port']}\n";
        $ovpnConfig .= "resolv-retry infinite\n";
        $ovpnConfig .= "nobind\n";
        $ovpnConfig .= "persist-key\n";
        $ovpnConfig .= "persist-tun\n";
        $ovpnConfig .= "remote-cert-tls server\n";
        $ovpnConfig .= "cipher {$config['connection']['encryption']}\n";
        $ovpnConfig .= "auth SHA256\n";
        $ovpnConfig .= "verb 3\n";
        $ovpnConfig .= "auth-user-pass\n"; // Prompt for credentials
        $ovpnConfig .= "script-security 2\n";

        // Add ShrakVPN client identifier
        $ovpnConfig .= "# ShrakVPN Configuration\n";
        $ovpnConfig .= "# Client ID: {$config['client_id']}\n";
        $ovpnConfig .= "# Generated: {$config['created_at']}\n";
        $ovpnConfig .= "# Expires: {$config['expires_at']}\n";

        // Here you would include CA certificate embedded directly in the config
        $ovpnConfig .= "<ca>\n";
        $ovpnConfig .= "-----BEGIN CERTIFICATE-----\n";
        $ovpnConfig .= "# Place your CA certificate here\n";
        $ovpnConfig .= "-----END CERTIFICATE-----\n";
        $ovpnConfig .= "</ca>\n";

        return $ovpnConfig;
    }

    /**
     * Generate WireGuard configuration
     *
     * @param User $user
     * @param Server $server
     * @return string
     */
    public function generateWireGuardConfig(User $user, Server $server): string
    {
        $config = $this->generateConfig($user, $server);

        // Generate WireGuard keys for this user
        list($privateKey, $publicKey) = $this->generateWireGuardKeys($user, $server);

        $wgConfig = "[Interface]\n";
        $wgConfig .= "PrivateKey = $privateKey\n";
        $wgConfig .= "Address = 10.8.0." . ($user->id % 254 + 1) . "/24\n";
        $wgConfig .= "DNS = 1.1.1.1, 1.0.0.1\n\n";

        $wgConfig .= "[Peer]\n";
        $wgConfig .= "PublicKey = ServerPublicKeyPlaceholder\n"; // Replace with actual server public key
        $wgConfig .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
        $wgConfig .= "Endpoint = {$config['server']['domain']}:51820\n";
        $wgConfig .= "PersistentKeepalive = 25\n";

        // Add ShrakVPN metadata as comments
        $wgConfig .= "\n# ShrakVPN Configuration\n";
        $wgConfig .= "# Client ID: {$config['client_id']}\n";
        $wgConfig .= "# Generated: {$config['created_at']}\n";
        $wgConfig .= "# Expires: {$config['expires_at']}\n";

        return $wgConfig;
    }

    /**
     * Generate a unique client ID for the user-server pair
     *
     * @param User $user
     * @param Server $server
     * @return string
     */
    protected function generateClientId(User $user, Server $server): string
    {
        $base = Str::slug("shrakvpn-{$user->id}-{$server->id}");
        $random = Str::random(8);
        return "{$base}-{$random}";
    }

    /**
     * Generate a secure connection token
     *
     * @param User $user
     * @param Server $server
     * @return string
     */
    protected function generateConnectionToken(User $user, Server $server): string
    {
        // Use API key if available, otherwise generate a token based on user credentials
        $baseToken = $user->api_key ?? md5($user->email . $user->id . $server->id . config('app.key'));
        return hash_hmac('sha256', $baseToken, config('app.key'));
    }

    /**
     * Generate WireGuard key pair for a user and server
     *
     * @param User $user
     * @param Server $server
     * @return array [privateKey, publicKey]
     */
    protected function generateWireGuardKeys(User $user, Server $server): array
    {
        // In a real implementation, you'd use actual WireGuard key generation
        // For this example, we'll simulate it with a deterministic approach
        $seed = md5($user->id . $server->id . config('app.key'));

        // This is a placeholder - in production you'd use proper WireGuard key generation
        $privateKey = substr(hash('sha256', $seed . '-private'), 0, 44) . '=';
        $publicKey = substr(hash('sha256', $seed . '-public'), 0, 44) . '=';

        return [$privateKey, $publicKey];
    }

    /**
     * Clear cached configurations for a user or server
     *
     * @param User|null $user
     * @param Server|null $server
     * @return void
     */
    public function clearConfigCache(?User $user = null, ?Server $server = null): void
    {
        if ($user && $server) {
            // Clear specific user-server config
            Cache::forget("vpn_config:{$user->id}:{$server->id}");
        } elseif ($user) {
            // Laravel doesn't support wildcard pattern deletion directly
            // We need to handle individual server configurations for this user
            $servers = Server::all();
            foreach ($servers as $srv) {
                Cache::forget("vpn_config:{$user->id}:{$srv->id}");
            }
        } elseif ($server) {
            // Clear all user configs for this server
            $users = User::all();
            foreach ($users as $usr) {
                Cache::forget("vpn_config:{$usr->id}:{$server->id}");
            }
        } else {
            // Clear all VPN configs (for all users and servers)
            $users = User::all();
            $servers = Server::all();

            foreach ($users as $usr) {
                foreach ($servers as $srv) {
                    Cache::forget("vpn_config:{$usr->id}:{$srv->id}");
                }
            }
        }

        Log::info("VPN configuration cache cleared successfully");
    }

    /**
     * Sync user authentication data with a VPN server
     *
     * @param User $user
     * @param Server $server
     * @param bool $remove Whether to remove the user from the server
     * @return bool
     */
    public function syncUserWithServer(User $user, Server $server, bool $remove = false): bool
    {
        try {
            // Prepare user data to send to the server
            $userData = [
                'action' => $remove ? 'remove' : 'add',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->username ?? $user->email,
                    'token' => $this->generateConnectionToken($user, $server),
                    'tier' => $user->role,
                    'created_at' => $user->created_at->toIso8601String(),
                ],
            ];

            // If using WireGuard, include public key
            if ($server->hasFeature('wireguard')) {
                list(, $publicKey) = $this->generateWireGuardKeys($user, $server);
                $userData['user']['wg_public_key'] = $publicKey;
            }

            // Make API call to the VPN server
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('variables.server_api_key'),
                'Accept' => 'application/json',
            ])->post("{$server->domain}/api/user-sync", $userData);

            if ($response->successful()) {
                Log::info("User {$user->id} synced with server {$server->id}");
                return true;
            } else {
                Log::error("Failed to sync user {$user->id} with server {$server->id}: {$response->body()}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception syncing user with server: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Sync user authentication data with all servers
     *
     * @param User $user
     * @param bool $remove Whether to remove the user from all servers
     * @return array Results with server IDs as keys and success status as values
     */
    public function syncUserWithAllServers(User $user, bool $remove = false): array
    {
        $results = [];
        $servers = Server::where('status', 1)->get(); // Only sync with online servers

        foreach ($servers as $server) {
            $results[$server->id] = $this->syncUserWithServer($user, $server, $remove);
        }

        return $results;
    }

    /**
     * Generate all configuration types for a user-server pair
     * and return as an array with different formats
     *
     * @param User $user
     * @param Server $server
     * @return array
     */
    public function getAllConfigFormats(User $user, Server $server): array
    {
        $formats = [
            'json' => $this->generateConfig($user, $server),
        ];

        if ($server->hasFeature('openvpn')) {
            $formats['ovpn'] = $this->generateOpenVpnConfig($user, $server);
        }

        if ($server->hasFeature('wireguard')) {
            $formats['wireguard'] = $this->generateWireGuardConfig($user, $server);
        }

        return $formats;
    }
}
