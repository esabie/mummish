<?php

$path = __DIR__.'/ghana_cities_by_region.json';

return [
    'by_region' => is_readable($path)
        ? json_decode((string) file_get_contents($path), true)
        : [],
];
