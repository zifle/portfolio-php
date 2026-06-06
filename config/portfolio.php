<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image sizes
    |--------------------------------------------------------------------------
    |
    | The sizes to resize uploaded images to, by width. Images will be resized
    | to the given _widths_, keeping aspect ratio. Portrait images will thus
    | be larger than landscape ones.
    |
    */
    'image_sizes' => explode(',', env('PORTFOLIO_IMAGE_SIZES', '400,800,1200,2000')),

    /*
    |--------------------------------------------------------------------------
    | Uploads disk
    |--------------------------------------------------------------------------
    |
    | The storage disk used for uploaded images. The disk must be set up in the
    | filesystems.php config file
    |
    */
    'uploads_disk' => env('PORTFOLIO_UPLOAD_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | JPEG quality
    |--------------------------------------------------------------------------
    |
    | The quality setting used when saving resized jpegs. Accepts
    | comma-separated values to use different settings for each size (will
    | fall-back to last element)
    |
    */
    'jpeg_quality' => env('PORTFOLIO_JPEG_QUALITY', '93'),

    /*
    |--------------------------------------------------------------------------
    | Socials
    |--------------------------------------------------------------------------
    |
    | Set up the links to your socials to include them in the main nav.
    |
    */
    'social_links' => [
        'facebook' => env('FACEBOOK_LINK'),
        'instagram' => env('INSTAGRAM_LINK'),
    ],
];
