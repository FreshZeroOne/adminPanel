@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Server - ShrakVPN Admin')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <h5 class="card-header d-flex justify-content-between align-items-center">
        Edit Server: {{ $server->name }}
        <div>
          <button type="button" class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#configModal">
            <i class="ri-file-code-line me-1"></i> Generate Config Script
          </button>
          <a href="{{ route('dashboard-server') }}" class="btn btn-sm btn-secondary">
            <i class="ri-arrow-left-line me-1"></i> Back to Servers
          </a>
        </div>
      </h5>
      <div class="card-body">
        @if ($errors->any())
        <div class="alert alert-danger mb-3">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif

        <form action="{{ route('server.update', $server->id) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="row mb-4">
            <div class="col-md-6">
              <h6 class="fw-semibold">Basic Information</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="id">Server ID</label>
                  <input type="text" class="form-control" id="id" value="{{ $server->id }}" readonly disabled>
                  <div class="form-text">Server ID cannot be changed</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="name">Server Name</label>
                  <input type="text" class="form-control" id="name" value="{{ $server->name }}" readonly disabled>
                  <div class="form-text">Server Name cannot be changed</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                  <select class="form-select" id="status" name="status" required>
                    <option value="0" {{ old('status', $server->status) == 0 ? 'selected' : '' }}>Offline</option>
                    <option value="1" {{ old('status', $server->status) == 1 ? 'selected' : '' }}>Online</option>
                    <option value="2" {{ old('status', $server->status) == 2 ? 'selected' : '' }}>Maintenance</option>
                    <option value="3" {{ old('status', $server->status) == 3 ? 'selected' : '' }}>Error</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Current Server Load</label>
                  <div class="input-group" >
                    <input type="text" class="form-control" value="{{ $server->load }}%" readonly disabled>
                    <span class="input-group-text bg-label-info col-md-8">Updated by Server</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="domain">Domain Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="domain" name="domain"
                    placeholder="e.g. de-01.shrakvpn.de" required value="{{ old('domain', $server->domain) }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="vpn_type">VPN Type <span class="text-danger">*</span></label>
                  <select class="form-select" id="vpn_type" name="vpn_type" required>
                    @foreach(\App\Models\Server::getAllVpnTypes() as $value => $label)
                      <option value="{{ $value }}" {{ old('vpn_type', $server->vpn_type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="tier">Server Tier <span class="text-danger">*</span></label>
                  <select class="form-select" id="tier" name="tier" required>
                    <option value="1" {{ old('tier', $server->tier) == 1 ? 'selected' : '' }}>Free</option>
                    <option value="2" {{ old('tier', $server->tier) == 2 ? 'selected' : '' }}>Plus</option>
                    <option value="3" {{ old('tier', $server->tier) == 3 ? 'selected' : '' }}>Pro</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <h6 class="fw-semibold">Location Information</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="entry_country">Entry Country <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="entry_country" name="entry_country" placeholder="e.g. DE"
                    required value="{{ old('entry_country', $server->entry_country) }}" maxlength="2">
                  <div class="form-text">2-letter country code</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="exit_country">Exit Country <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="exit_country" name="exit_country" placeholder="e.g. DE"
                    required value="{{ old('exit_country', $server->exit_country) }}" maxlength="2">
                  <div class="form-text">2-letter country code</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="city">City</label>
                  <input type="text" class="form-control" id="city" name="city" placeholder="e.g. Berlin"
                    value="{{ old('city', $server->city) }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="lat">Latitude</label>
                  <input type="text" class="form-control" id="lat" name="lat" placeholder="e.g. 52.5200"
                    value="{{ old('lat', $server->lat) }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="long">Longitude</label>
                  <input type="text" class="form-control" id="long" name="long" placeholder="e.g. 13.4050"
                    value="{{ old('long', $server->long) }}">
                </div>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <h6 class="fw-semibold">API Configuration</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="api_key">Server API Key</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="api_key" value="{{ config('variables.server_api_key') }}" readonly>
                  <button class="btn btn-outline-primary" type="button" id="generateApiKeyBtn" onclick="generateApiKey()">
                    <i class="ri-refresh-line me-1"></i> Generate New Key
                  </button>
                </div>
                <div class="form-text">This key is used for secure communication between the admin panel and VPN servers</div>
              </div>
              <div class="col-md-6">
                <div class="alert alert-info mb-0">
                  <h6 class="alert-heading fw-bold mb-1"><i class="ri-information-line me-1"></i> Server API Key</h6>
                  <p class="mb-0">This key must be copied to the server's config.php file. You can get the full configuration from the "Generate Config Script" button above.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <h6 class="fw-semibold">Features</h6>
            <div class="row">
              @foreach($features as $value => $label)
              <div class="col-md-4">
                <div class="form-check form-check-inline my-2">
                  <input class="form-check-input" type="checkbox" id="feature_{{ $value }}" name="feature_{{ $value }}"
                    value="{{ $value }}" {{ $server->hasFeature($value) ? 'checked' : '' }}>
                  <label class="form-check-label" for="feature_{{ $value }}">{{ $label }}</label>
                </div>
              </div>
              @endforeach
            </div>
            <div class="form-check form-check-inline my-2">
              <input class="form-check-input" type="checkbox" id="select_all_features"
                onclick="toggleAllFeatures(this.checked)">
              <label class="form-check-label" for="select_all_features">Select All Features</label>
            </div>
          </div>

          <div class="text-end">
            <a href="{{ route('dashboard-server') }}" class="btn btn-outline-secondary me-1">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Server</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Config Modal -->
<div class="modal fade" id="configModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Server Configuration Script (config.php)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-info mb-2">
          <i class="ri-information-line me-1"></i>
          Copy this configuration file to your VPN server and save it as <code>/root/config.php</code>
        </p>
        <div class="bg-dark p-3 rounded position-relative">
          <pre id="configContent" class="text-white mb-0"><code>&lt;?php

return [
    // Server identification
    'server_id' => '{{ $server->id }}', // Set this to your server ID (e.g., 'de-01')

    // VPN type: 'openvpn' or 'wireguard'
    'vpn_type' => '{{ $server->vpn_type }}',

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
    'admin_panel_url' => '{{ url('/') }}',  // URL of the admin panel API
    'server_api_key' => '{{ config('variables.server_api_key') }}',        // API key for authenticating with the admin panel

    // User configuration storage
    'user_config_dir' => __DIR__ . '/user_db',        // Directory to store user configurations
    'auth_log_file' => __DIR__ . '/auth_attempts.log', // Authentication log file
];

</code></pre>
          <button id="copyConfigBtn" class="btn btn-sm btn-light position-absolute top-0 end-0 m-2" onclick="copyConfig()">
            <i class="ri-file-copy-line me-1"></i> Copy
          </button>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <div>
          <span id="copySuccess" class="text-success" style="display: none">
            <i class="ri-checkbox-circle-line me-1"></i> Copied to clipboard
          </span>
        </div>
        <div>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <a href="{{ asset('scripts/update_server_load.php') }}" download class="btn btn-primary">
            <i class="ri-download-line me-1"></i> Download Update Script
          </a>
          <a href="{{ asset('scripts/generate_api_key.php') }}" download class="btn btn-success">
            <i class="ri-key-line me-1"></i> Download API Key Generator
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
  function toggleAllFeatures(checked) {
    document.querySelectorAll('[id^="feature_"]').forEach((checkbox) => {
      checkbox.checked = checked;
    });
  }

  // Check "Select All" if all features are already selected
  document.addEventListener('DOMContentLoaded', function() {
    const featureCheckboxes = document.querySelectorAll('[id^="feature_"]');
    const selectAllCheckbox = document.getElementById('select_all_features');
    const allChecked = Array.from(featureCheckboxes).every(checkbox => checkbox.checked);
    selectAllCheckbox.checked = allChecked;
  });

  // Config copy functionality
  function copyConfig() {
    const configText = document.getElementById('configContent').textContent;
    navigator.clipboard.writeText(configText).then(() => {
      const copySuccessMsg = document.getElementById('copySuccess');
      copySuccessMsg.style.display = 'inline-block';
      setTimeout(() => {
        copySuccessMsg.style.display = 'none';
      }, 3000);
    });
  }

  // Generate new API key
  function generateApiKey() {
    if (confirm('Are you sure you want to generate a new API key? This will require updating the key on all servers using it.')) {
      // Create a random key with prefix
      const randomString = 'shkvpn-' + Array.from(crypto.getRandomValues(new Uint8Array(16)))
        .map(b => b.toString(16).padStart(2, '0')).join('');

      document.getElementById('api_key').value = randomString;

      // Update the key in the config preview as well
      const configText = document.getElementById('configContent').textContent;
      const updatedConfig = configText.replace(
        /'server_api_key' => '.*'/,
        `'server_api_key' => '${randomString}'`
      );
      document.getElementById('configContent').textContent = updatedConfig;

      // Here you would typically make an AJAX call to update the API key in the server
      // For now, we'll just show a notification to the user
      alert('A new API key has been generated. Please update your .env file with SERVER_API_KEY=' + randomString);
    }
  }
</script>
@endsection
