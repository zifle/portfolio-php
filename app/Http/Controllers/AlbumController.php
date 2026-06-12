<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
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

        View::share('metaKeywords', implode(', ', $album->tags));
        View::share('metaDescription', $album->short_description);
        $firstImg = $album->first_image;
        if ($firstImg) {
            View::share('metaImage', $firstImg->meta_image);
            View::share('metaImageAlt', $firstImg->description);
        }
        View::share('metaTitle', $album->title);

        try {
            views($album)->record();
            if ($album->category) {
                views($album->category)->record();
            }
        } catch (\Throwable) {
        }

        return Inertia::render('album/Show', [
            'album' => $album,
        ]);
    }

    public function welcome()
    {
        $album = $this->welcomeAlbum()?->append(['items']);

        View::share('metaKeywords', config('portfolio.meta.keywords'));

        if (! $album) {
            // To set up the "welcome" / homepage, create an album with the
            // category of "Welcome", unpublished.
            $album = new Album;
            $album->title = 'Welcome';
            $album->description = '';
        } else {
            View::share('metaDescription', $album->short_description);
            $firstImg = $album->first_image;
            if ($firstImg) {
                View::share('metaImage', $firstImg->meta_image);
                View::share('metaImageAlt', $firstImg->description);
            }
            View::share('metaTitle', $album->title);

            try {
                views($album)->record();
            } catch (\Throwable) {
            }
        }

        return Inertia::render('Welcome', [
            'album' => $album,
        ]);
    }

    public static function welcomeAlbum(): ?Album
    {
        return Cache::remember('welcome_album', now()->addDay(), function () {
            return Album::whereHas(
                'category',
                function (Builder $query) {
                    $query->where('name', 'Welcome');
                }
            )->first();
        });
    }
}
