<?php

namespace Tests\Feature\Models;

use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearby_locations()
    {
        $location = Location::factory()->create();
        $lat = $location->lat;
        $lng = $location->lng;

        $nearby = Location::getNearby([
            [$lat, $lng],
        ]);

        $this->assertNotEmpty($nearby);
        $nearbyLoc = $nearby->first();

        $this->assertInstanceOf(Location::class, $nearbyLoc);
        $this->assertEquals(0, $nearbyLoc->distance);
        $this->assertEquals($location->id, $nearbyLoc->id);
    }

    public function test_no_nearby_locations()
    {
        $location = Location::factory()->create();
        $lat = $location->lat;
        $lng = $location->lng;

        if ($lat + 10 > 180) {
            $lat -= 10;
        } else {
            $lat += 10;
        }

        $nearby = Location::getNearby([
            [$lat, $lng],
        ]);

        $this->assertEmpty($nearby);
    }
}
