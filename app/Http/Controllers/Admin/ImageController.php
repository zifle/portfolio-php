<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Library\Exif;
use App\Models\Image;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ImageController extends Controller
{
    public function index(Request $request)
    {
        $query = Image::orderBy('id')
            ->with(['camera', 'lens', 'albums']);

        if ($request->has('filter')) {
            $filter = $request->input('filter');

            if ($filter['used'] === 'false') {
                $query->whereDoesntHave('albums');
            } elseif ($filter['used'] === 'true') {
                $query->whereHas('albums');
            }
        }

        return Inertia::render('admin/Images', [
            'pagination' => Inertia::scroll(
                fn () => $query->cursorPaginate(30)
            ),
            'unused_count' => Image::whereDoesntHave('albums')->count(),
            'total_count' => Image::count(),
            'filter' => $request->input('filter') ?? ['used' => null],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        if (! $request->hasFile('img')) {
            return response('Missing file "img" in request.', 404);
        }
        $file = $request->file('img');

        $exif = new Exif($file);

        $filename = $file->getClientOriginalName();
        $dateTaken = $exif->dateTaken() ?? now();
        $path = Image::getImagePath($filename);
        $image = Image::whereLike('path', '%'.$path)
            ->where('date_taken', $dateTaken)
            ->firstOrNew();
        if (! $image->exists) {
            $image->path = $path;
            $image->date_taken = $dateTaken;
            $image->setExif($exif);
            $res = $this->resizeUploadedImage($file, $path, $exif->get('Orientation', 1));

            $image->available_res = $res['available_sizes'];
            $image->max_width = $res['max_width'];
            $image->max_height = $res['max_height'];

            $image->save();
        }

        $cameras = [];
        if ($image->camera_id) {
            $cameras[] = $image->camera;
        }
        $lenses = [];
        if ($image->lens_id) {
            $lenses[] = $image->lens;
        }

        return [
            'success' => true,
            'cameras' => $cameras,
            'lenses' => $lenses,
            'images' => [$image],
            'coords' => [], // We get gps client-side
            'date' => $image->date_taken->format('Y-m-d'),
        ];
    }

    protected function resizeUploadedImage(UploadedFile $file, string $filename, int $orientation = 1): array
    {
        $return = [
            'available_sizes' => [],
            'max_width' => 0,
            'max_height' => 0,
        ];

        $gd = imagecreatefromjpeg($file->getRealPath());

        switch ($orientation) {
            case 3: // Rotate image 180deg
                $gd = imagerotate($gd, 180, 0);
                break;
            case 6: // Rotate image 270deg
                $gd = imagerotate($gd, 270, 0);
                break;
            case 8: // Rotate image 90deg
                $gd = imagerotate($gd, 90, 0);
                break;
        }

        $disk = Storage::disk(config('portfolio.uploads_disk'));
        $widths = config('portfolio.image_sizes');
        $qualities = explode(',', config('portfolio.jpeg_quality', 93));
        $quality = intval($qualities[0]);
        $org_width = imagesx($gd);
        $org_height = imagesy($gd);

        foreach ($widths as $i => $width) {
            $width = intval($width);

            if ($width >= $org_width) {
                $width = $org_width; // Never scale up images
            }
            $ratio = $width / $org_width;
            $dst_h = floor($ratio * $org_height);
            $im = imagecreatetruecolor($width, $dst_h);
            imagecopyresampled($im, $gd, 0, 0, 0, 0, $width, $dst_h, $org_width, $org_height);
            if ($im === false) {
                Log::error('Couldn\'t scale image '.$filename);

                continue;
            }

            $fname = str_replace('{0}', $width, $filename);
            $quality = intval($qualities[$i] ?? $quality);

            if (imagejpeg($im, $disk->path($fname), $quality)) {
                $disk->setVisibility($fname, 'public');
                $return['available_sizes'][] = $width;
                if ($width > $return['max_width']) {
                    $height = imagesy($im);
                    $return['max_width'] = $width;
                    $return['max_height'] = $height;
                }
            }

            if ($width >= $org_width) {
                break; // Never scale up images
            }
        }

        return $return;
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
        if (empty($request->input('images'))) {
            return [
                'images' => [],
                'upload' => [],
                'cameras' => [],
                'lenses' => [],
                'locations' => [],
            ];
        }

        $toUpload = [];
        $locs = [];
        $query = Image::with(['camera', 'lens']);
        $subFolderLength = 0;
        foreach ($request->images as $img) {
            $path = Image::getImagePath($img['filename']);
            [$subFolder, $path] = explode('/', $path);
            $subFolderLength = strlen($subFolder);
            $toUpload[$path] = $img['filename'];
            $query->orWhere(function (Builder $query) use ($img, $path) {
                $query->whereLike('path', '%'.$path)
                    ->where('date_taken', Carbon::parse($img['date_taken']));
            });

            if (isset($img['location'])) {
                $locs[] = $img['location'];
            }
        }

        /** @var Collection<Image> $images */
        $images = $query->get();
        $cameras = collect();
        $lenses = collect();
        foreach ($images as $image) {
            // Strip the subFolder to get the proper path
            $path = substr($image->path, $subFolderLength + 1);
            unset($toUpload[$path]);

            if ($image->camera_id) {
                $cameras->add($image->camera);
            }
            if ($image->lens_id) {
                $lenses->add($image->lens);
            }
        }

        $locations = Location::getNearby($locs);

        return [
            'images' => $images,
            'upload' => array_values($toUpload),
            'cameras' => $cameras->unique('id'),
            'lenses' => $lenses->unique('id'),
            'locations' => $locations,
        ];
    }

    public function destroy(Image $image)
    {
        // Detach image from albums
        $image->albums()->detach();

        // Delete image files
        $disk = $image::getDisk();
        foreach ($image->available_res as $w) {
            $fname = str_replace('{0}', $w, $image->path);
            $disk->delete($fname);
        }

        // Delete the model
        $image->delete();
    }

    public function destroyUnused()
    {
        Image::whereDoesntHave('albums')->delete();
    }
}
