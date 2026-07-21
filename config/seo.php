<?php

$defaultTitle = 'Mummish | Marketplace for mothers & kids in Ghana';
$defaultDescription = 'Marketplace for the modern mother. Shop baby clothes, kids products, and family essentials from trusted local sellers across Ghana.';

$title = env('SEO_TITLE');
$description = env('SEO_DESCRIPTION');

return [

    /*
    |--------------------------------------------------------------------------
    | Default search title & description
    |--------------------------------------------------------------------------
    |
    | Shown in Google results and social previews. Keep the description under
    | ~155 characters. Edit these anytime — no code changes needed elsewhere.
    |
    | Empty SEO_TITLE / SEO_DESCRIPTION env values are ignored so a blank
    | production .env cannot wipe the Google snippet.
    |
    */

    'title' => (is_string($title) && trim($title) !== '') ? trim($title) : $defaultTitle,

    'description' => (is_string($description) && trim($description) !== '')
        ? trim($description)
        : $defaultDescription,

    /*
    |--------------------------------------------------------------------------
    | Sample taglines / alternate names
    |--------------------------------------------------------------------------
    |
    | Phrases people might search for. Used in JSON-LD so Google can connect
    | these names to the brand. Add or remove lines freely.
    |
    */

    'taglines' => [
        'Marketplace for the modern mother',
        'Kids marketplace Ghana',
        'Baby and kids shop Ghana',
        'Buy and sell kids clothes Ghana',
        'Pre-loved kids gear Ghana',
        'Moms marketplace Ghana',
        'Mummish Ghana',
        'Marketplace for mothers & kids in Ghana',
    ],

];
