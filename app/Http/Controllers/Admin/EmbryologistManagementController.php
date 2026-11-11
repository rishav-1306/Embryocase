<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SpecialKeyHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmbryologistManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'embryologist')
            ->withCount('embryoCases');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'LIKE', "%{$s}%")
                  ->orWhere('email', 'LIKE', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $embryologists = $query->latest('id')->paginate(15)->withQueryString();

        return view('admin.embryologists.index', compact('embryologists'));
    }

    public function create()
    {
        return view('admin.embryologists.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // Generate unique cryptographically random Special Key
        $plainSpecialKey = 'EMB-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));

        $embryologist = User::create([
            'name' => trim($request->name),
            'email' => trim($request->email),
            'password' => Hash::make($request->password),
            'role' => 'embryologist',
            'status' => 'active',
            'special_key_hash' => Hash::make($plainSpecialKey),
            'created_by' => auth()->id(),
            'email_verified_at' => now(),
        ]);

        SpecialKeyHistory::create([
            'user_id' => $embryologist->id,
            'key_hash' => $embryologist->special_key_hash,
            'action' => 'generated',
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        ActivityLog::record(auth()->id(), 'embryologist_created', null, [
            'created_user_id' => $embryologist->id,
            'created_user_email' => $embryologist->email,
        ]);

        return redirect()->route('admin.embryologists.show', $embryologist->id)->with([
            'status' => 'Embryologist account successfully provisioned.',
            'new_credentials' => [
                'name' => $embryologist->name,
                'email' => $embryologist->email,
                'special_key' => $plainSpecialKey,
                'password' => $request->password,
            ],
        ]);
    }

    public function show($id)
    {
        $embryologist = User::where('role', 'embryologist')
            ->with(['embryoCases' => function ($q) {
                $q->latest('id')->take(10);
            }, 'specialKeyHistory.creator'])
            ->findOrFail($id);

        $recentLogs = ActivityLog::where('user_id', $embryologist->id)
            ->latest('id')
            ->take(15)
            ->get();

        return view('admin.embryologists.show', compact('embryologist', 'recentLogs'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $embryologist = User::where('role', 'embryologist')->findOrFail($id);
        $oldStatus = $embryologist->status;
        $embryologist->status = $request->status;
        $embryologist->save();

        ActivityLog::record(auth()->id(), "account_{$request->status}", null, [
            'target_user_id' => $embryologist->id,
            'target_email' => $embryologist->email,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
        ]);

        return back()->with('status', "Account status for {$embryologist->name} changed to {$request->status}.");
    }

    public function regenerateKey(Request $request, $id)
    {
        $embryologist = User::where('role', 'embryologist')->findOrFail($id);

        // Mark existing key revoked in history if present
        if ($embryologist->special_key_hash) {
            SpecialKeyHistory::where('user_id', $embryologist->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }

        $plainSpecialKey = 'EMB-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        $keyHash = Hash::make($plainSpecialKey);

        $embryologist->special_key_hash = $keyHash;
        $embryologist->save();

        SpecialKeyHistory::create([
            'user_id' => $embryologist->id,
            'key_hash' => $keyHash,
            'action' => 'regenerated',
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        ActivityLog::record(auth()->id(), 'special_key_regenerated', null, [
            'target_user_id' => $embryologist->id,
            'target_email' => $embryologist->email,
        ]);

        return back()->with([
            'status' => "Special Key successfully regenerated for {$embryologist->name}.",
            'regenerated_key' => $plainSpecialKey,
        ]);
    }

    public function revokeKey(Request $request, $id)
    {
        $embryologist = User::where('role', 'embryologist')->findOrFail($id);

        if ($embryologist->special_key_hash) {
            SpecialKeyHistory::where('user_id', $embryologist->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }

        $embryologist->special_key_hash = null;
        $embryologist->save();

        SpecialKeyHistory::create([
            'user_id' => $embryologist->id,
            'key_hash' => 'REVOKED',
            'action' => 'revoked',
            'created_by' => auth()->id(),
            'revoked_at' => now(),
            'created_at' => now(),
        ]);

        ActivityLog::record(auth()->id(), 'special_key_revoked', null, [
            'target_user_id' => $embryologist->id,
            'target_email' => $embryologist->email,
        ]);

        return back()->with('status', "Special Key for {$embryologist->name} has been revoked. Login is immediately blocked until a new key is issued.");
    }
}
