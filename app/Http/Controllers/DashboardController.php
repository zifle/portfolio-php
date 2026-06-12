<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Image;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $welcomeAlbum = AlbumController::welcomeAlbum();
        $frontpageViews = 0;

        if ($welcomeAlbum) {
            $frontpageViews = views($welcomeAlbum)
                ->remember(now()->addMinutes(10))
                ->count();
        }

        $albumViews = views(Album::class)
            ->remember(now()->addMinutes(10))
            ->count();
        $imageViews = views(Image::class)
            ->remember(now()->addMinutes(10))
            ->count();

        $albumsByViews = Album::orderByViews()
            ->limit(10)
            ->get();
        $imagesByViews = Image::orderByViews()
            ->limit(12)
            ->get();

        return Inertia::render('Dashboard', [
            'views' => [
                'frontpage' => $frontpageViews,
                'albums' => $albumViews,
                'images' => $imageViews,
            ],
            'top10' => [
                'albums' => $albumsByViews,
                'images' => $imagesByViews,
            ],
        ]);
    }
}
