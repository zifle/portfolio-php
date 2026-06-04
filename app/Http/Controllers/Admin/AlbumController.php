<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\TextBox;
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
            'pagination' => Inertia::scroll(
                fn () => Album::orderBy('order')
                    ->with(['category', 'location'])
                    ->withCount('images')
                    ->paginate(40)
            ),
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
            'album' => $album->append(['items', 'tags']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Album $album)
    {
        $album->title = $request->title;
        $album->description = $request->description ?? '';
        $album->order = $request->order ?? 0;
        $ds = $request->date_start;
        if ($ds) {
            $ds = Carbon::parse($ds);
        }
        $de = $request->date_end;
        if ($de) {
            $de = Carbon::parse($de);
        }
        $album->date_start = $ds?->toDateString();
        $album->date_end = $de?->toDateString();
        $album->published_at = $request->published ? ($album->published_at ?? now()) : null;
        $album->category_id = $request->category_id;
        $album->location_id = $request->location_id;

        $album->save();

        if (isset($request->items)) {
            $toUpdateImages = [];
            $toUpdateTexts = [];
            foreach ($request->items as $item) {
                /** @var array{id: int, order: int, type: 'image'|'textbox'} $item */
                switch ($item['type']) {
                    case 'image':
                        $toUpdateImages[$item['id']] = ['order' => $item['order']];
                        break;
                    case 'textbox':
                        $toUpdateTexts[$item['id']] = ['order' => $item['order']];
                        break;
                }
            }

            $album->images()->sync($toUpdateImages);
            $album->text_boxes()->sync($toUpdateTexts);

            TextBox::whereDoesntHave('albums')->delete();
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
