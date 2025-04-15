<?php

/**
 * ShrakVPN User Synchronization Script
 *
 * This script handles receiving and storing user data from the admin panel.
 * It maintains a local database of authorized users for the VPN server.
 *
 * It's called by the server_api.php script when the admin panel sends user data.
 */

// Enable error reporting for debugging but disable display
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Define constants
define('USER_SYNC_LOG', __DIR__ . '/user_sync.log');
define('USER_DB_DIR', __DIR__ . '/user_db');

// Create user database directory if it doesn't exist
if (!is_dir(USER_DB_DIR)) {
    mkdir(USER_DB_DIR, 0755, true);
}

// Function to log synchronization events
function sync_log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message\n";
    file_put_contents(USER_SYNC_LOG, $logMessage, FILE_APPEND);
}

/**
 * Add or update a user in the local database
 *
 * @param array $userData User data from the admin panel
 * @return bool Success status
 */
function add_or_update_user($userData) {
    if (!isset($userData['id']) || !isset($userData['email'])) {
        sync_log("Invalid user data, missing required fields", "ERROR");
        return false;
    }

    // Sanitize the email to use as part of the filename
    $safeEmail = preg_replace('/[^a-zA-Z0-9_-]/', '_', $userData['email']);
    $fileName = USER_DB_DIR . "/user_{$userData['id']}_$safeEmail.json";

    try {
        // Add timestamp for tracking
        $userData['synced_at'] = time();

        // Write user data to file
        file_put_contents($fileName, json_encode($userData, JSON_PRETTY_PRINT));
        sync_log("User {$userData['email']} (ID: {$userData['id']}) added/updated");

        // If using WireGuard, update the WireGuard configuration
        if (isset($userData['wg_public_key'])) {
            update_wireguard_config($userData);
        }

        return true;
    } catch (Exception $e) {
        sync_log("Error saving user data: " . $e->getMessage(), "ERROR");
        return false;
    }
}

/**
 * Remove a user from the local database
 *
 * @param array $userData User data including ID and email
 * @return bool Success status
 */
function remove_user($userData) {
    if (!isset($userData['id']) || !isset($userData['email'])) {
        sync_log("Invalid user data for removal, missing required fields", "ERROR");
        return false;
    }

    // Sanitize the email to use as part of the filename
    $safeEmail = preg_replace('/[^a-zA-Z0-9_-]/', '_', $userData['email']);
    $fileName = USER_DB_DIR . "/user_{$userData['id']}_$safeEmail.json";

    if (file_exists($fileName)) {
        unlink($fileName);
        sync_log("User {$userData['email']} (ID: {$userData['id']}) removed");

        // If using WireGuard, update the WireGuard configuration
        if (isset($userData['wg_public_key'])) {
            remove_wireguard_peer($userData);
        }

        return true;
    } else {
        sync_log("User file not found for removal: $fileName", "WARNING");
        return false;
    }
}

/**
 * Update WireGuard configuration with the user's public key
 *
 * @param array $userData User data including WireGuard public key
 */
function update_wireguard_config($userData) {
    // Load configuration
    $config = require __DIR__ . '/config.php';

    if ($config['vpn_type'] !== 'wireguard') {
        return;
    }

    $wgInterface = $config['wireguard_interface'] ?? 'wg0';
    $publicKey = $userData['wg_public_key'];
    $userId = $userData['id'];

    // Calculate a unique IP address for this user
    // This is a simple approach, would need adjustment for a real-world implementation
    $userIp = "10.8.0." . ($userId % 254 + 1);

    // Create the peer configuration
    $peerConfig = "\n# User: {$userData['email']} (ID: $userId)\n";
    $peerConfig .= "[Peer]\n";
    $peerConfig .= "PublicKey = $publicKey\n";
    $peerConfig .= "AllowedIPs = $userIp/32\n";
    $peerConfig .= "PersistentKeepalive = 25\n";

    // Check if this peer already exists (by public key)
    $existingConfig = shell_exec("wg show $wgInterface peers");

    if (strpos($existingConfig, $publicKey) !== false) {
        // Update existing peer
        sync_log("Updating WireGuard peer for user {$userData['email']}", "INFO");
        // In a real implementation, you would use `wg set` commands to update the peer
    } else {
        // Add new peer
        sync_log("Adding new WireGuard peer for user {$userData['email']}", "INFO");
        // In a real implementation, you would use `wg set` commands to add the peer
    }

    // In a real-world scenario, you would also update the WireGuard configuration file
    // to make the changes persistent across reboots
    // Example: /etc/wireguard/wg0.conf
}

/**
 * Remove a WireGuard peer when a user is removed
 *
 * @param array $userData User data to identify the peer to remove
 */
function remove_wireguard_peer($userData) {
    // Load configuration
    $config = require __DIR__ . '/config.php';

    if ($config['vpn_type'] !== 'wireguard') {
        return;
    }

    $wgInterface = $config['wireguard_interface'] ?? 'wg0';
    $publicKey = $userData['wg_public_key'] ?? '';

    if (empty($publicKey)) {
        sync_log("No WireGuard public key available for removal", "WARNING");
        return;
    }

    // In a real implementation, you would use `wg set $wgInterface peer $publicKey remove`
    // to remove the peer
    sync_log("Removing WireGuard peer for user {$userData['email']}", "INFO");

    // Also update the WireGuard configuration file to make changes persistent
}

/**
 * Process user synchronization request
 *
 * @param array $requestData Data from the admin panel
 * @return array Result of the operation
 */
function process_user_sync($requestData) {
    if (!isset($requestData['action']) || !isset($requestData['user'])) {
        return [
            'success' => false,
            'message' => 'Invalid request: missing action or user data'
        ];
    }

    $action = $requestData['action'];
    $userData = $requestData['user'];

    sync_log("Processing user sync action: $action for user: " . ($userData['email'] ?? 'unknown'));

    if ($action === 'add' || $action === 'update') {
        $result = add_or_update_user($userData);
        return [
            'success' => $result,
            'message' => $result ? 'User added/updated successfully' : 'Failed to add/update user'
        ];
    } elseif ($action === 'remove') {
        $result = remove_user($userData);
        return [
            'success' => $result,
            'message' => $result ? 'User removed successfully' : 'Failed to remove user'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Unknown action: ' . $action
        ];
    }
}

// This script can be called directly for testing or from server_api.php
if (PHP_SAPI === 'cli' && isset($argv[1])) {
    // Example for command line testing:
    // php user_sync.php '{"action":"add","user":{"id":1,"email":"test@example.com","token":"abc123"}}'
    $inputJson = $argv[1];
    $requestData = json_decode($inputJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sync_log("JSON parse error: " . json_last_error_msg(), "ERROR");
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit(1);
    }

    $result = process_user_sync($requestData);
    echo json_encode($result);
    exit($result['success'] ? 0 : 1);
}
