<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EmbryoCase;
use App\Models\EmbryoImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmbryologistDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $totalCases = EmbryoCase::where('user_id', $user->id)->count();
        $casesInProgress = EmbryoCase::where('user_id', $user->id)->where('status', 'in_progress')->count();
        $casesCompleted = EmbryoCase::where('user_id', $user->id)->where('status', 'completed')->count();
        $totalImagesUploaded = EmbryoImage::where('user_id', $user->id)->count();

        $recentCases = EmbryoCase::where('user_id', $user->id)
            ->with(['images'])
            ->latest('id')
            ->take(8)
            ->get();

        $recentActivities = ActivityLog::where('user_id', $user->id)
            ->latest('id')
            ->take(10)
            ->get();

        return view('embryologist.dashboard', compact(
            'totalCases',
            'casesInProgress',
            'casesCompleted',
            'totalImagesUploaded',
            'recentCases',
            'recentActivities'
        ));
    }

    public function activity()
    {
        $user = Auth::user();
        $logs = ActivityLog::where('user_id', $user->id)
            ->latest('id')
            ->paginate(20);

        return view('embryologist.activity', compact('logs'));
    }
}

