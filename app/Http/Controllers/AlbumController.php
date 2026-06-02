<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AlbumController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Album $album)
    {

        if ($album->published_at === null && Auth::guest()) {
            return null;
        }

        $album->append(['items', 'tags'])
            ->load(['location']);

        return Inertia::render('album/Show', [
            'album' => $album,
        ]);
    }
}
