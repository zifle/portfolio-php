<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Database\Eloquent\Builder;
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
            return redirect('/');
        }

        $album->append(['items', 'tags'])
            ->load(['location']);

        return Inertia::render('album/Show', [
            'album' => $album,
        ]);
    }

    public function welcome()
    {
        $album = Album::whereHas(
            'category',
            function (Builder $query) {
                $query->where('name', 'Welcome');
            }
        )->first()?->append(['items']);

        if (! $album) {
            // To set up the "welcome" / homepage, create an album with the
            // category of "Welcome", unpublished.
            $album = new Album;
            $album->title = 'Welcome';
            $album->description = '';
        }

        return Inertia::render('Welcome', [
            'album' => $album,
        ]);
    }
}
