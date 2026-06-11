<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Image;

class ViewController extends Controller
{
    public function viewedImage(Image $image)
    {
        try {
            views($image)->record();
        } catch (\Throwable) {
        }
    }

    public function viewAlbum(Album $album)
    {
        try {
            views($album)->record();
            if ($album->category) {
                views($album->category)->record();
            }
        } catch (\Throwable) {
        }
    }
}
