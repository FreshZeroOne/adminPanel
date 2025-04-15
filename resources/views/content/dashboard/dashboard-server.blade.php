@extends('layouts/contentNavbarLayout')

@section('title', 'Server Dashboard - ShrakVPN Admin')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/dashboards-analytics.js')}}"></script>
@endsection

@section('content')
<div class="row">
  <!-- Server Status Overview -->
  <div class="col-lg-8 mb-4">
    <div class="card">
      <div class="d-flex align-items-end row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="card-title text-primary">Server Status Overview</h5>
            <p class="mb-4">
              Total Servers: <span class="fw-bold">{{ $servers->count() }}</span> |
              Online: <span class="fw-bold text-success">{{ $servers->where('status', 1)->count() }}</span> |
              Offline: <span class="fw-bold text-danger">{{ $servers->where('status', 0)->count() }}</span>
            </p>
            <a href="{{ route('server.create') }}" class="btn btn-sm btn-primary">Add New Server</a>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-4">
            <img src="{{asset('assets/img/illustrations/man-with-laptop-light.png')}}" height="140" alt="View Badge User" data-app-dark-img="illustrations/man-with-laptop-dark.png" data-app-light-img="illustrations/man-with-laptop-light.png">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4 col-md-4 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <img src="{{asset('assets/img/icons/unicons/chart-success.png')}}" alt="chart success" class="rounded">
          </div>
          <div class="dropdown">
            <button class="btn p-0" type="button" id="activeUsersDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="ri-more-fill"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="activeUsersDropdown">
              <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
              <a class="dropdown-item" href="javascript:void(0);">View Details</a>
            </div>
          </div>
        </div>
        <span class="fw-medium d-block mb-1">Average Server Load</span>
        <h3 class="card-title mb-2">
          {{ $servers->count() > 0 ? round($servers->avg('load')) : 0 }}%
        </h3>
        <small class="text-success fw-medium"><i class="ri-arrow-up-circle-line"></i> +{{ $servers->where('status', 1)->count() }} servers online</small>
      </div>
    </div>
  </div>

  <!-- Servers Table -->
  <div class="col-12 mb-4">
    <div class="card">
      <h5 class="card-header d-flex justify-content-between align-items-center">
        Server List
        <a href="{{ route('server.create') }}" class="btn btn-sm btn-primary">
          <i class="ri-add-line me-1"></i> Add Server
        </a>
      </h5>
      <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible mb-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Load</th>
                <th>VPN Type</th>
                <th>Countries</th>
                <th>Domain</th>
                <th>Tier</th>
                <th>Features</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($servers as $server)
              <tr>
                <td><strong>{{ $server->id }}</strong></td>
                <td>{{ $server->name }}</td>
                <td>
                  <span class="badge bg-label-{{ $server->status_color }}">{{ $server->status_label }}</span>
                </td>
                <td>
                  <div class="progress" style="height: 8px; width: 80px">
                    <div class="progress-bar" role="progressbar" style="width: {{ $server->load }}%;"
                      aria-valuenow="{{ $server->load }}" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <small>{{ $server->load }}%</small>
                </td>
                <td>
                  <span class="badge rounded-pill bg-label-{{ $server->vpn_type == 'wireguard' ? 'success' : 'warning' }}">
                    {{ $server->vpn_type_label }}
                  </span>
                </td>
                <td><span class="text-info">{{ $server->entry_country }}</span> → <span class="text-success">{{ $server->exit_country }}</span></td>
                <td>{{ $server->domain }}</td>
                <td>
                  @if($server->tier == 1)
                    <span class="badge bg-label-secondary">Free</span>
                  @elseif($server->tier == 2)
                    <span class="badge bg-label-info">Plus</span>
                  @else
                    <span class="badge bg-label-primary">Pro</span>
                  @endif
                </td>
                <td>
                  @php
                    $features = $server->getEnabledFeatures();
                    $featureCount = count($features);
                  @endphp
                  <span class="badge rounded-pill bg-label-primary">{{ $featureCount }}</span>
                  @if($featureCount > 0)
                    <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-html="true"
                       title="@foreach($features as $feature)<div>{{ $feature }}</div>@endforeach">
                      <i class="ri-information-line text-info"></i>
                    </a>
                  @endif
                </td>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                      <i class="ri-more-fill"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('server.edit', $server->id) }}">
                        <i class="ri-edit-box-line me-1"></i> Edit
                      </a>
                      <form action="{{ route('server.destroy', $server->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Are you sure you want to delete this server?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item">
                          <i class="ri-delete-bin-6-line me-1"></i> Delete
                        </button>
                      </form>
                    </div>
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="10" class="text-center">No servers found. <a href="{{ route('server.create') }}">Create your first server</a>.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
