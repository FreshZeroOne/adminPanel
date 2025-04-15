<?php

/**
 * Configuration for the VPN Server
 *
 * Edit this file to match your environment settings.
 */

return [
    // Server identification
    'server_id' => 'de-01', // Set this to your server ID (e.g., 'de-01')

    // VPN type: 'openvpn' or 'wireguard'
    'vpn_type' => 'wireguard',

    // Database connection details (for server load updates)
    'db_host'     => '85.215.238.89',      // Database host
    'db_name'     => 'api_db',       // Database name
    'db_user'     => 'api_user',        // Database username
    'db_password' => 'weilisso001',       // Database password (use environment variables in production)
    'db_port'     => 3306,             // Database port

    // VPN server settings
    'interfaces'  => ['wg0', 'eth0'], // Network interfaces to monitor for bandwidth
    'max_connections' => 100,          // Maximum number of connections the server is configured to handle

    // Load calculation weights
    'weights' => [
        'connection' => 0.5,  // Weight for connection count in load calculation
        'bandwidth'  => 0.3,  // Weight for bandwidth usage in load calculation
        'system'     => 0.2,  // Weight for system (CPU/memory) in load calculation
    ],

    // OpenVPN specific settings (only used if vpn_type is 'openvpn')
    'openvpn_management_host' => 'localhost',
    'openvpn_management_port' => 7505,

    // Wireguard specific settings (only used if vpn_type is 'wireguard')
    'wireguard_interface' => 'wg0',  // The main Wireguard interface

    // Logging settings
    'log_file' => 'server_load_updates.log',  // Log file name
    'log_enabled' => true,                     // Whether to log update operations

    // Admin panel API communication
    'admin_panel_url' => 'https://1upone.com/api',  // URL of the admin panel API
    'server_api_key' => 'YOUR_SERVER_API_KEY',        // API key for authenticating with the admin panel

    // User configuration storage
    'user_config_dir' => __DIR__ . '/user_db',        // Directory to store user configurations
    'auth_log_file' => __DIR__ . '/auth_attempts.log', // Authentication log file
];
