<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Image;
use Illuminate\Support\Facades\Auth;

class ViewController extends Controller
{
    public function viewedImage(Image $image)
    {
        if (Auth::guest()) {
            try {
                views($image)->record();
            } catch (\Throwable) {
            }
        }
    }

    public function viewAlbum(Album $album)
    {
        if (Auth::guest()) {
            try {
                views($album)->record();
                if ($album->category) {
                    views($album->category)->record();
                }
            } catch (\Throwable) {
            }
        }
    }
}
