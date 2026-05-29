<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Category;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlbumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('admin/albums/Index', [
            'pagination' => Album::orderBy('order')
                ->with(['category', 'location'])
                ->withCount('images')
                ->paginate(20)
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/albums/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $album = new Album;
        return $this->update($request, $album);
    }

    public function edit(Album $album)
    {
        return Inertia::render('admin/albums/Edit', [
            'album' => $album->append('items'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Album $album)
    {
        $album->title = $request->title;
        $album->description = $request->description ?? '';
        $ds = $request->date_start;
        if ($ds) {
            $ds = Carbon::parse($ds);
        }
        $de = $request->date_end;
        if ($de) {
            $de = Carbon::parse($de);
        }
        $album->date_start = $ds;
        $album->date_end = $de;
        $album->published_at = $request->published ? ($album->published_at ?? now()) : null;
        if ($request->category && $cat = Category::find($request->category)) {
            $album->category()->associate($cat);
        }
        if ($request->location && $loc = Location::find($request->location)) {
            $album->location()->associate($loc);
        }

        $album->save();

        if ($request->items) {
            $toUpdateImages = [];
            $toUpdateTexts = [];
            foreach ($request->items as $item) {
                /** @var array{id: int, order: int, type: string} $item */
                switch ($item['type']) {
                    case 'image':
                        $toUpdateImages[$item['id']] = ['order' => $item['order']];
                        break;
                    case 'text':
                        $toUpdateTexts[$item['id']] = ['order' => $item['order']];
                        break;
                }
            }

            $album->images()->sync($toUpdateImages);
            $album->text_boxes()->sync($toUpdateTexts);
        }

        $album->append('items');

        return $album;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Album $album)
    {
        if ($album->published_at) {
            return response('Cannot delete a published album', 400);
        }
        $album->delete();
        return response('Album has been deleted', 200);
    }

    public function togglePublished(Request $request, Album $album)
    {
        $album->published_at = $request->publish ? now() : null;
        $album->save();

        return response('', 204);
    }
}
