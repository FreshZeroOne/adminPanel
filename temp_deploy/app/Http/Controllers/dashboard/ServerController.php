<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;

class ServerController extends Controller
{
    /**
     * Display the server dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $servers = Server::all();
        return view('content.dashboard.dashboard-server', compact('servers'));
    }

    /**
     * Show the form for creating a new server
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $features = Server::getAllFeatures();
        return view('content.dashboard.server-create', compact('features'));
    }

    /**
     * Store a newly created server in storage
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|min:0|max:3',
            'entry_country' => 'required|string|size:2',
            'exit_country' => 'required|string|size:2',
            'domain' => 'required|string|max:255',
            'vpn_type' => 'required|string|in:wireguard,openvpn',
            'tier' => 'required|integer|min:1|max:3',
            'city' => 'nullable|string|max:100',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->route('server.create')
                ->withErrors($validator)
                ->withInput();
        }

        // Auto-generate server ID and name based on exit country
        $exitCountry = strtoupper($request->exit_country);

        // Find the highest number for this country code
        $lastServer = Server::where('id', 'like', $exitCountry.'%')
                             ->orderByRaw('CAST(SUBSTRING(id, 3) AS UNSIGNED) DESC')
                             ->first();

        $number = 1;
        if ($lastServer) {
            // Extract number part and increment
            $lastNumber = (int)substr($lastServer->id, 2);
            $number = $lastNumber + 1;
        }

        // Format the ID as XX## (country code + number padded to at least 2 digits)
        $id = $exitCountry . str_pad($number, 2, '0', STR_PAD_LEFT);

        // Generate name based on country and city if provided
        $name = $exitCountry;
        if ($request->city) {
            $name .= ' - ' . $request->city;
        }
        $name .= ' #' . $number;

        // Calculate features based on selected checkboxes
        $features = 0;
        $featureList = Server::getAllFeatures();
        foreach ($featureList as $value => $label) {
            if ($request->has('feature_'.$value)) {
                $features |= $value;
            }
        }

        // Create the server with load set to 0 by default
        $server = Server::create([
            'id' => $id,
            'name' => $name,
            'status' => $request->status,
            'load' => 0, // Default to 0, will be updated by the VPN server itself later
            'vpn_type' => $request->vpn_type,
            'entry_country' => strtoupper($request->entry_country),
            'exit_country' => $exitCountry,
            'domain' => $request->domain,
            'features' => $features,
            'tier' => $request->tier,
            'city' => $request->city,
            'lat' => $request->lat,
            'long' => $request->long,
        ]);

        return redirect()->route('dashboard-server')
            ->with('success', 'Server created successfully!');
    }

    /**
     * Show the form for editing a server
     *
     * @param  string  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $server = Server::findOrFail($id);
        $features = Server::getAllFeatures();

        return view('content.dashboard.server-edit', compact('server', 'features'));
    }

    /**
     * Update the specified server in storage
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $server = Server::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|min:0|max:3',
            'entry_country' => 'required|string|size:2',
            'exit_country' => 'required|string|size:2',
            'domain' => 'required|string|max:255',
            'vpn_type' => 'required|string|in:wireguard,openvpn',
            'tier' => 'required|integer|min:1|max:3',
            'city' => 'nullable|string|max:100',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->route('server.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        // Calculate features based on selected checkboxes
        $features = 0;
        $featureList = Server::getAllFeatures();
        foreach ($featureList as $value => $label) {
            if ($request->has('feature_'.$value)) {
                $features |= $value;
            }
        }

        // Update server but keep the current load value
        $server->update([
            'name' => $server->name, // Verwende den bestehenden Namen statt des Eingabefelds
            'status' => $request->status,
            // 'load' field is not updated here - it's managed by the VPN server
            'vpn_type' => $request->vpn_type,
            'entry_country' => strtoupper($request->entry_country),
            'exit_country' => strtoupper($request->exit_country),
            'domain' => $request->domain,
            'features' => $features,
            'tier' => $request->tier,
            'city' => $request->city,
            'lat' => $request->lat,
            'long' => $request->long,
        ]);

        return redirect()->route('dashboard-server')
            ->with('success', 'Server updated successfully!');
    }

    /**
     * Remove the specified server from storage
     *
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $server = Server::findOrFail($id);
        $server->delete();

        return redirect()->route('dashboard-server')
            ->with('success', 'Server deleted successfully!');
    }
}
