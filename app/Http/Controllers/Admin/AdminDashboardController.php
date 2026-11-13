<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EmbryoCase;
use App\Models\EmbryoImage;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalCases = EmbryoCase::count();
        $totalEmbryologists = User::where('role', 'embryologist')->count();
        $totalImages = EmbryoImage::count();
        $totalEditedImages = EmbryoImage::where('has_edit', true)->count();

        $recentCases = EmbryoCase::with(['user', 'images'])
            ->latest('id')
            ->take(6)
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->latest('id')
            ->take(10)
            ->get();

        $pendingTodayCases = EmbryoCase::getCasesWithMissingTodayImage();

        return view('admin.dashboard', compact(
            'totalCases',
            'totalEmbryologists',
            'totalImages',
            'totalEditedImages',
            'recentCases',
            'recentActivities',
            'pendingTodayCases'
        ));
    }
}

