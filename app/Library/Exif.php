<?php

namespace App\Library;

use Carbon\Carbon;

class Exif
{
    protected array $exif = [];

    const array TAGS = [
        'EXIF' => [
            '0x8830' => 'SensitivityType', // 1
            '0x9010' => 'OffsetTime', // '+02:00'
            '0x9011' => 'OffsetTimeOriginal', // '+02:00'
            '0x9012' => 'OffsetTimeDigitized', // '+02:00'
            '0xA431' => 'SerialNumber',
            '0xA432' => 'LensInfo', // []
            '0xA433' => 'LensMake', // 'FUJIFILM'
            '0xA434' => 'LensModel', // 'XF50-140mmF2.8 R LM OIS WR'
            '0xA435' => 'LensSerialNumber',
            '0xA500' => 'Gamma',
        ],
        'IFD0' => [
            '0x4746' => 'Rating', // int
        ],
    ];

    public function __construct($file)
    {
        $this->load($file);
    }

    public function load($file): void
    {
        $this->exif = $this->mapExif(
            exif_read_data($file, as_arrays: true)
        );
    }

    protected function mapExif($exif): array
    {
        $sections = [];
        foreach ($exif as $key => $section) {
            $sections[$key] = [];
            foreach ($section as $name => $val) {
                if (str_starts_with($name, 'UndefinedTag:')) {
                    $name = $this->translateTagName($key, $name);
                }

                $sections[$key][$name] = $val;
            }
        }

        return $sections;
    }

    protected function translateTagName(string $section, string $name): string
    {
        $tag = substr($name, 13);

        return static::TAGS[$section][$tag] ?? $name;
    }

    public function get($name, $default = null): mixed
    {
        foreach ($this->exif as $items) {
            if (isset($items[$name])) {
                return $items[$name];
            }
        }

        return $default;
    }

    /**
     * Get the datetime of the photo being taken.
     */
    public function dateTaken(): ?Carbon
    {
        // Find the most appropriate datetime from the tags.
        // The DateTime is usually updated with image editing, so should
        // only be used if neither of the other are set.
        $dateTaken = $this->get('DateTimeOriginal')
            ?? $this->get('DateTimeDigitized')
            ?? $this->get('DateTime');

        if (! $dateTaken) {
            return null;
        }

        return Carbon::parse($dateTaken);
    }

    public function getFloat($name): ?float
    {
        $value = $this->get($name);
        if (! isset($value)) {
            return null;
        }

        $pos = strpos($value, '/');
        if ($pos === false) {
            return (float) $value;
        }
        $a = (float) substr($value, 0, $pos);
        $b = (float) substr($value, $pos + 1);

        return ($b == 0) ? ($a) : ($a / $b);
    }

    public function exposureTime(): ?string
    {
        $shutter = $this->getFloat('ExposureTime');
        if (! $shutter) {
            // If exposureTime is not set, fallback to ShutterSpeedValue
            $apex = $this->getFloat('ShutterSpeedValue');
            $shutter = pow(2, -$apex);
        }
        if ($shutter == 0) {
            return false;
        }
        if ($shutter >= 1) {
            return round($shutter);
        }

        return '1/'.round(1 / $shutter);
    }

    /**
     * Returns a latitude,longitude pair of coordinates.
     *
     * @return float[]|null
     */
    public function gps(): ?array
    {
        $lat_coord = $this->get('GPSLatitude');
        $lat_ref = $this->get('GPSLatitudeRef');
        $lng_coord = $this->get('GPSLongitude');
        $lng_ref = $this->get('GPSLongitudeRef');
        if (isset($lat_coord, $lng_coord, $lat_ref, $lng_ref)) {
            $lat = $this->gps_value($lat_coord, $lat_ref);
            $lng = $this->gps_value($lng_coord, $lng_ref);

            return [$lat, $lng];
        }

        return null;
    }

    protected function gps_value($coord, $ref): float
    {

        $parts2Float = function ($coordPart): float {
            $parts = explode('/', $coordPart);

            if (count($parts) <= 0) {
                return 0;
            }

            if (count($parts) == 1) {
                return $parts[0];
            }

            return floatval($parts[0]) / floatval($parts[1]);
        };

        $degrees = count($coord) > 0 ? $parts2Float($coord[0]) : 0;
        $minutes = count($coord) > 1 ? $parts2Float($coord[1]) : 0;
        $seconds = count($coord) > 2 ? $parts2Float($coord[2]) : 0;

        $flip = ($ref == 'W' or $ref == 'S') ? -1 : 1;

        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
    }
}
