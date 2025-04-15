<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Server extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'status',
        'load',
        'vpn_type',
        'entry_country',
        'exit_country',
        'domain',
        'features',
        'tier',
        'city',
        'lat',
        'long',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($server) {
            // Only generate ID and name if they're not already set
            if (empty($server->id) || empty($server->name)) {
                $exitCountry = strtolower($server->exit_country);

                // Find the highest number for this country code
                $highestNumber = self::where('exit_country', $server->exit_country)
                    ->where('id', 'like', $exitCountry . '-%')
                    ->get()
                    ->map(function ($item) use ($exitCountry) {
                        // Extract the number from the ID (e.g. 'us-01' -> 1)
                        preg_match('/' . $exitCountry . '-(\d+)/', $item->id, $matches);
                        return isset($matches[1]) ? (int)$matches[1] : 0;
                    })
                    ->max();

                // Start from 1 if no servers for this country yet
                $nextNumber = $highestNumber ? $highestNumber + 1 : 1;

                // Format the number with leading zeros (01, 02, etc.)
                $formattedNumber = str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

                // Set the ID and name if they're not already set
                if (empty($server->id)) {
                    $server->id = $exitCountry . '-' . $formattedNumber;
                }

                if (empty($server->name)) {
                    $server->name = strtoupper($exitCountry) . '#' . $nextNumber;
                }
            }
        });
    }

    /**
     * Feature constants
     */
    const FEATURE_ADBLOCKER = 1;
    const FEATURE_SPLIT_TUNNELING = 2;
    const FEATURE_KILL_SWITCH = 4;
    const FEATURE_IPV6 = 8;
    const FEATURE_PORT_FORWARDING = 16;
    const FEATURE_MULTI_HOP = 32;
    const FEATURE_ALL = 63;

    /**
     * Status constants
     */
    const STATUS_OFFLINE = 0;
    const STATUS_ONLINE = 1;
    const STATUS_MAINTENANCE = 2;
    const STATUS_ERROR = 3;

    /**
     * Tier constants
     */
    const TIER_FREE = 1;
    const TIER_PLUS = 2;
    const TIER_PRO = 3;

    /**
     * VPN type constants
     */
    const VPN_TYPE_WIREGUARD = 'wireguard';
    const VPN_TYPE_OPENVPN = 'openvpn';

    /**
     * Get all available VPN types as an associative array
     *
     * @return array
     */
    public static function getAllVpnTypes(): array
    {
        return [
            self::VPN_TYPE_WIREGUARD => 'WireGuard',
            self::VPN_TYPE_OPENVPN => 'OpenVPN',
        ];
    }

    /**
     * Get VPN type label
     *
     * @return string
     */
    public function getVpnTypeLabelAttribute(): string
    {
        return match($this->vpn_type) {
            self::VPN_TYPE_WIREGUARD => 'WireGuard',
            self::VPN_TYPE_OPENVPN => 'OpenVPN',
            default => ucfirst($this->vpn_type),
        };
    }

    /**
     * Get status label
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_OFFLINE => 'Offline',
            self::STATUS_ONLINE => 'Online',
            self::STATUS_MAINTENANCE => 'Maintenance',
            self::STATUS_ERROR => 'Error',
            default => 'Unknown',
        };
    }

    /**
     * Get status color class
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_OFFLINE => 'danger',
            self::STATUS_ONLINE => 'success',
            self::STATUS_MAINTENANCE => 'warning',
            self::STATUS_ERROR => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get tier label
     *
     * @return string
     */
    public function getTierLabelAttribute(): string
    {
        return match($this->tier) {
            self::TIER_FREE => 'Free',
            self::TIER_PLUS => 'Plus',
            self::TIER_PRO => 'Pro',
            default => 'Unknown',
        };
    }

    /**
     * Check if server has a specific feature
     *
     * @param int $feature
     * @return bool
     */
    public function hasFeature(int $feature): bool
    {
        return ($this->features & $feature) === $feature;
    }

    /**
     * Get array of feature labels that are enabled for this server
     *
     * @return array
     */
    public function getEnabledFeatures(): array
    {
        $features = [];

        if ($this->hasFeature(self::FEATURE_ADBLOCKER)) {
            $features[] = 'AdBlocker';
        }

        if ($this->hasFeature(self::FEATURE_SPLIT_TUNNELING)) {
            $features[] = 'Split-Tunneling';
        }

        if ($this->hasFeature(self::FEATURE_KILL_SWITCH)) {
            $features[] = 'Kill-Switch';
        }

        if ($this->hasFeature(self::FEATURE_IPV6)) {
            $features[] = 'IPv6';
        }

        if ($this->hasFeature(self::FEATURE_PORT_FORWARDING)) {
            $features[] = 'Port-Forwarding';
        }

        if ($this->hasFeature(self::FEATURE_MULTI_HOP)) {
            $features[] = 'Multi-Hop';
        }

        return $features;
    }

    /**
     * Get all available features as an associative array
     *
     * @return array
     */
    public static function getAllFeatures(): array
    {
        return [
            self::FEATURE_ADBLOCKER => 'AdBlocker',
            self::FEATURE_SPLIT_TUNNELING => 'Split-Tunneling',
            self::FEATURE_KILL_SWITCH => 'Kill-Switch',
            self::FEATURE_IPV6 => 'IPv6',
            self::FEATURE_PORT_FORWARDING => 'Port-Forwarding',
            self::FEATURE_MULTI_HOP => 'Multi-Hop',
        ];
    }
}
