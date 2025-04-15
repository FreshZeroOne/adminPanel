@extends('layouts/contentNavbarLayout')

@section('title', 'Add New Server - ShrakVPN Admin')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <h5 class="card-header d-flex justify-content-between align-items-center">
        Add New Server
        <a href="{{ route('dashboard-server') }}" class="btn btn-sm btn-secondary">
          <i class="ri-arrow-left-line me-1"></i> Back to Servers
        </a>
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

        <div class="alert alert-info mb-3">
          <i class="ri-information-line me-1"></i> Server ID and Server Name will be automatically generated based on the Exit Country code.
        </div>

        <form action="{{ route('server.store') }}" method="POST">
          @csrf
          <div class="row mb-4">
            <div class="col-md-6">
              <h6 class="fw-semibold">Basic Information</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                  <select class="form-select" id="status" name="status" required>
                    <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Offline</option>
                    <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Online</option>
                    <option value="2" {{ old('status') == '2' ? 'selected' : '' }}>Maintenance</option>
                    <option value="3" {{ old('status') == '3' ? 'selected' : '' }}>Error</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Server Load</label>
                  <div class="input-group">
                    <input type="text" class="form-control" value="0%" readonly disabled>
                    <span class="input-group-text bg-label-info col-md-8">Updated by Server</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="domain">Domain Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="domain" name="domain"
                    placeholder="e.g. de-01.shrakvpn.de" required value="{{ old('domain') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="vpn_type">VPN Type <span class="text-danger">*</span></label>
                  <select class="form-select" id="vpn_type" name="vpn_type" required>
                    @foreach(\App\Models\Server::getAllVpnTypes() as $value => $label)
                      <option value="{{ $value }}" {{ old('vpn_type', 'wireguard') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="tier">Server Tier <span class="text-danger">*</span></label>
                  <select class="form-select" id="tier" name="tier" required>
                    <option value="1" {{ old('tier') == '1' ? 'selected' : '' }}>Free</option>
                    <option value="2" {{ old('tier', '2') == '2' ? 'selected' : '' }}>Plus</option>
                    <option value="3" {{ old('tier') == '3' ? 'selected' : '' }}>Pro</option>
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
                    required value="{{ old('entry_country') }}" maxlength="2">
                  <div class="form-text">2-letter country code</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="exit_country">Exit Country <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="exit_country" name="exit_country" placeholder="e.g. DE"
                    required value="{{ old('exit_country') }}" maxlength="2">
                  <div class="form-text">2-letter country code</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="city">City</label>
                  <input type="text" class="form-control" id="city" name="city" placeholder="e.g. Berlin"
                    value="{{ old('city') }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="lat">Latitude</label>
                  <input type="text" class="form-control" id="lat" name="lat" placeholder="e.g. 52.5200"
                    value="{{ old('lat') }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="long">Longitude</label>
                  <input type="text" class="form-control" id="long" name="long" placeholder="e.g. 13.4050"
                    value="{{ old('long') }}">
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
                    value="{{ $value }}" {{ old('feature_'.$value) ? 'checked' : '' }}>
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
            <button type="reset" class="btn btn-outline-secondary">Reset</button>
            <button type="submit" class="btn btn-primary">Add Server</button>
          </div>
        </form>
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
</script>
@endsection
