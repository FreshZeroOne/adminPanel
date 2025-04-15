<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\User;
use App\Services\VpnConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class VpnConfigController extends Controller
{
    protected $vpnConfigService;

    public function __construct(VpnConfigService $vpnConfigService)
    {
        $this->vpnConfigService = $vpnConfigService;
    }

    /**
     * Get all available server configurations for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableServers(Request $request)
    {
        $user = Auth::user();

        // Filter servers based on user tier/role
        $servers = Server::where('status', 1) // Only online servers
            ->where('tier', '<=', $user->role) // Only servers for user's tier or below
            ->get(['id', 'name', 'domain', 'entry_country', 'exit_country', 'city', 'tier', 'load']);

        return response()->json([
            'success' => true,
            'servers' => $servers
        ]);
    }

    /**
     * Get configuration for a specific server
     *
     * @param Request $request
     * @param int $serverId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function getServerConfig(Request $request, $serverId)
    {
        $user = Auth::user();

        $server = Server::find($serverId);
        if (!$server) {
            return response()->json([
                'success' => false,
                'message' => 'Server not found'
            ], 404);
        }

        // Check if user has access to this server tier
        if ($server->tier > $user->role) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this server'
            ], 403);
        }

        // If server is not online, return error
        if ($server->status != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Server is currently unavailable'
            ], 403);
        }

        // Determine requested format
        $format = strtolower($request->input('format', 'json'));
        if (!in_array($format, ['json', 'ovpn', 'wireguard'])) {
            $format = 'json'; // Default to JSON if invalid format
        }

        // Get all configurations
        $configs = $this->vpnConfigService->getAllConfigFormats($user, $server);

        // Sync user with server if needed - done in background
        if ($request->input('sync', false)) {
            $this->vpnConfigService->syncUserWithServer($user, $server);
        }

        // Return requested format
        if ($format === 'json') {
            return response()->json([
                'success' => true,
                'config' => $configs['json']
            ]);
        } elseif ($format === 'ovpn' && isset($configs['ovpn'])) {
            return Response::make($configs['ovpn'], 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $server->name . '.ovpn"'
            ]);
        } elseif ($format === 'wireguard' && isset($configs['wireguard'])) {
            return Response::make($configs['wireguard'], 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $server->name . '.conf"'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Requested format not available for this server'
            ], 400);
        }
    }

    /**
     * API endpoint for servers to verify user credentials
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyUserCredentials(Request $request)
    {
        // Validate request comes from a server with proper API key
        $apiKey = $request->header('X-Server-API-Key');
        if ($apiKey !== config('variables.server_api_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid server credentials'
            ], 403);
        }

        // Validate request data
        $request->validate([
            'username' => 'required|email',
            'token' => 'required|string',
            'server_id' => 'required|integer'
        ]);

        // Find user
        $user = User::where('email', $request->input('username'))->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Find server
        $server = Server::find($request->input('server_id'));
        if (!$server) {
            return response()->json([
                'success' => false,
                'message' => 'Server not found'
            ], 404);
        }

        // Check user has access to this tier
        if ($server->tier > $user->role) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have access to this server tier'
            ], 403);
        }

        // Generate and verify token
        $expectedToken = $this->vpnConfigService->generateConnectionToken($user, $server);
        if ($request->input('token') !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 403);
        }

        // Return user info that server might need
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'tier' => $user->role,
                'created_at' => $user->created_at->toIso8601String()
            ]
        ]);
    }
}
