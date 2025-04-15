<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Server;

class ApiController extends Controller
{
    /**
     * Authenticate user and return API key
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Generate API key if not exists
            if (empty($user->api_key)) {
                $user->api_key = Str::random(64);
                $user->save();
            }

            return response()->json([
                'success' => true,
                'api_key' => $user->api_key,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    /**
     * Get servers list
     */
    public function getServers()
    {
        $servers = Server::all();
        return response()->json($servers);
    }

    /**
     * Get single server
     */
    public function getServer($id)
    {
        $server = Server::find($id);

        if (!$server) {
            return response()->json(['message' => 'Server not found'], 404);
        }

        return response()->json($server);
    }

    /**
     * Generate server config file
     */
    public function generateServerConfig($id)
    {
        $server = Server::find($id);

        if (!$server) {
            return response()->json(['message' => 'Server not found'], 404);
        }

        $config = [
            'server_id' => $server->id,
            'vpn_type' => $server->vpn_type, // Verwende den VPN-Typ aus der Datenbank
            'db_host' => '85.215.238.89',
            'db_name' => 'api_db',
            'db_user' => 'api_user',
            'db_password' => 'weilisso001',
            'db_port' => 3306,
            'interfaces' => ['wg0', 'eth0'],
            'max_connections' => 100,
            'wireguard_interface' => 'wg0',
            'weights' => [
                'connection' => 0.4,
                'bandwidth' => 0.2,
                'system' => 0.4,
            ],
            'log_file' => 'server_load_updates.log',
            'log_enabled' => true,

            // Admin panel API communication
            'admin_panel_url' => url('/'),
            'server_api_key' => config('variables.server_api_key'),

            // User configuration storage
            'user_config_dir' => '__DIR__ . \'/user_db\'',
            'auth_log_file' => '__DIR__ . \'/auth_attempts.log\'',
        ];

        return response()->json([
            'server' => $server,
            'config' => $config,
            'config_php' => $this->formatConfigAsPhp($config)
        ]);
    }

    /**
     * Format config array as PHP code
     */
    private function formatConfigAsPhp($config)
    {
        $output = "<?php\n\nreturn [\n";

        foreach ($config as $key => $value) {
            if ($key === 'weights') {
                $output .= "    // Load calculation weights\n";
                $output .= "    'weights' => [\n";
                foreach ($value as $wKey => $wValue) {
                    $output .= "        '$wKey' => $wValue,  // Weight for $wKey in load calculation\n";
                }
                $output .= "    ],\n";
            } else if (is_array($value)) {
                $arrayStr = json_encode($value);
                $arrayStr = str_replace('[', "['", $arrayStr);
                $arrayStr = str_replace(']', "']", $arrayStr);
                $arrayStr = str_replace(',', "', '", $arrayStr);
                $output .= "    '$key' => $arrayStr,\n";
            } else if (is_string($value)) {
                $output .= "    '$key' => '$value',\n";
            } else {
                $output .= "    '$key' => $value,\n";
            }
        }

        $output .= "];\n";
        return $output;
    }

    /**
     * Logout and invalidate token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
