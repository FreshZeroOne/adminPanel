<?php

/**
 * ShrakVPN Server API
 *
 * This script serves as the central API endpoint for the VPN server,
 * handling requests from the admin panel and client applications.
 *
 * It should be placed on your VPN server and set up with a web server (e.g., Nginx)
 * to handle incoming HTTP requests at a URL like: https://yourserver.com/api/
 */

// Enable error reporting for debugging but disable display
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Define constants
define('API_LOG_FILE', __DIR__ . '/server_api.log');

// Load configuration
$config = require __DIR__ . '/config.php';

// Check if server API key exists in configuration
if (!isset($config['server_api_key']) || empty($config['server_api_key'])) {
    api_log("Server API key not configured", "ERROR");
    send_response(false, 'Server API key not configured', 500);
    exit;
}

// Function to log API requests and responses
function api_log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message\n";
    file_put_contents(API_LOG_FILE, $logMessage, FILE_APPEND);
}

// Function to send standardized API responses
function send_response($success, $message, $statusCode = 200, $data = []) {
    http_response_code($statusCode);
    header('Content-Type: application/json');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}

// Function to verify the request comes from an authorized source
function verify_api_request() {
    global $config;

    // Get authorization header
    $headers = getallheaders();
    $authorization = $headers['Authorization'] ?? '';

    // Check for Bearer token
    if (preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
        $token = $matches[1];
        if ($token === $config['server_api_key']) {
            return true;
        }
    }

    // Alternative: Check X-Server-API-Key header
    $apiKey = $headers['X-Server-API-Key'] ?? '';
    if ($apiKey === $config['server_api_key']) {
        return true;
    }

    api_log("Unauthorized API request attempt", "WARNING");
    return false;
}

// Main API request handler
try {
    // Log the request
    $method = $_SERVER['REQUEST_METHOD'];
    $endpoint = $_SERVER['PATH_INFO'] ?? '/';
    $clientIP = $_SERVER['REMOTE_ADDR'];

    api_log("Received $method request to $endpoint from $clientIP");

    // Verify all API requests except for specific public endpoints
    $publicEndpoints = ['/ping'];

    if (!in_array($endpoint, $publicEndpoints) && !verify_api_request()) {
        send_response(false, 'Unauthorized', 401);
    }

    // Handle different API endpoints
    switch ($endpoint) {
        case '/ping':
            // Public endpoint to check if the server is online
            send_response(true, 'Server online', 200, ['server_id' => $config['server_id']]);
            break;

        case '/user-sync':
            // Handle user synchronization from admin panel
            if ($method !== 'POST') {
                send_response(false, 'Method not allowed', 405);
            }

            // Get JSON input
            $jsonInput = file_get_contents('php://input');
            $requestData = json_decode($jsonInput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                api_log("JSON parse error: " . json_last_error_msg(), "ERROR");
                send_response(false, 'Invalid JSON input', 400);
            }

            // Use the user_sync.php script to process the request
            require_once __DIR__ . '/user_sync.php';
            $result = process_user_sync($requestData);

            send_response(
                $result['success'],
                $result['message'],
                $result['success'] ? 200 : 400,
                $result['data'] ?? []
            );
            break;

        case '/server-info':
            // Return information about the server
            $serverInfo = [
                'id' => $config['server_id'],
                'vpn_type' => $config['vpn_type'],
                'active_connections' => get_active_connection_count(),
                'load' => get_current_load(),
                'uptime' => get_server_uptime()
            ];

            send_response(true, 'Server information retrieved', 200, $serverInfo);
            break;

        case '/verify-user':
            // Handle user verification requests
            if ($method !== 'POST') {
                send_response(false, 'Method not allowed', 405);
            }

            // Get JSON input
            $jsonInput = file_get_contents('php://input');
            $requestData = json_decode($jsonInput, true);

            if (json_last_error() !== JSON_ERROR_NONE ||
                !isset($requestData['username']) ||
                !isset($requestData['token'])) {
                send_response(false, 'Invalid request data', 400);
            }

            // Use the auth_verify.php script to verify credentials
            require_once __DIR__ . '/auth_verify.php';
            $result = verify_user_credentials(
                $requestData['username'],
                $requestData['token'],
                $config['server_id'],
                $config
            );

            send_response(
                $result,
                $result ? 'User verified successfully' : 'Verification failed',
                $result ? 200 : 401
            );
            break;

        default:
            // Unknown endpoint
            api_log("Request to unknown endpoint: $endpoint", "WARNING");
            send_response(false, 'Endpoint not found', 404);
            break;
    }
} catch (Exception $e) {
    api_log("Exception in API processing: " . $e->getMessage(), "ERROR");
    send_response(false, 'Internal server error', 500);
}

// Helper functions for server information

/**
 * Get the number of active VPN connections
 */
function get_active_connection_count() {
    global $config;

    if ($config['vpn_type'] === 'wireguard') {
        // Check active WireGuard connections
        $wgInterface = $config['wireguard_interface'] ?? 'wg0';
        $output = shell_exec("wg show $wgInterface peers | wc -l");
        return (int)trim($output);
    } else {
        // Check active OpenVPN connections
        $output = shell_exec("ps aux | grep openvpn | grep -v grep | wc -l");
        return (int)trim($output);
    }
}

/**
 * Get current server load
 */
function get_current_load() {
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        return $load[0];
    }

    return 0;
}

/**
 * Get server uptime
 */
function get_server_uptime() {
    $uptime = shell_exec('uptime -p');
    return trim($uptime);
}
