<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Support\Facades\Auth;

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

        $album->append('items');

        return $album;
    }
}
