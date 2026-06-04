<?php

namespace App\Http\Middleware;

use App\Models\Album;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if (! Auth::guest()) {
            $locations = Location::all();
            $categories = Category::orderBy('order')
                ->withCount('albums')
                ->get();
        } else {
            $locations = collect();
            $categories = collect();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'csrf_token' => csrf_token(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'menu' => static::buildMenu(),
            'locations' => $locations,
            'categories' => $categories,
        ];
    }

    public static function buildMenu(): array
    {
        $categories = Category::orderBy('order')->get();
        $albums = Album::orderBy('order');
        if (Auth::guest()) {
            $albums->whereNotNull('published_at');
        }
        $albums = $albums->get();

        $cats = $categories->map(function (Category $category) {
            return collect([
                'id' => $category->id,
                'name' => $category->name,
                'order' => $category->order,
                'albums' => collect(),
                'albums_count' => 0,
            ]);
        });

        $items = collect();
        $albums->each(function (Album $album) use (&$items, $cats) {
            $alb = collect([
                'title' => $album->title,
                'slug' => $album->slug,
                'order' => $album->order,
            ]);

            if ($album->category_id) {
                $cat = $cats->firstWhere('id', $album->category_id);
                if ($cat) {
                    $cat->get('albums')->add($alb);
                    $cat->put('albums_count', $cat->get('albums_count') + 1);
                }
            } else {
                $items->add($alb);
            }
        });
        foreach ($cats as $cat) {
            if (! $cat->get('albums')->isEmpty()) {
                $items->add($cat);
            }
        }

        return $items->sortBy('order')
            ->values()
            ->toArray();
    }
}
