<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ImageController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Image $image)
    {
        // We only support updating the image description, all other properties
        // are set when images are created (uploaded)
        $image->description = $request->description;
        $image->save();

        return $image;
    }

    public function checkDuplicates(Request $request)
    {
        $toUpload = [];
        $locs = [];
        $query = Image::with(['camera', 'lens']);
        foreach ($request->images as $img) {
            $path = Image::getImagePath($img['filename']);
            $toUpload[$path] = $img['filename'];
            $query->orWhere(function (Builder $query) use ($img, $path) {
                $query->where('path', $path)
                    ->where('date_taken', $img['date_taken']);
            });

            if (isset($img['location'])) {
                $locs[] = $img['location'];
            }
        }

        $images = Image::get();
        $cameras = collect();
        $lenses = collect();
        foreach ($images as $image) {
            unset($toUpload[$image->path]);
            $cameras->add($image->camera);
            $lenses->add($image->lens);
        }

        $locations = Location::getNearby($locs);

        return [
            'images' => $images,
            'upload' => array_values($toUpload),
            'cameras' => $cameras->unique('id'),
            'lenses' => $lenses->unique('id'),
            'locations' => $locations
        ];
    }
}
