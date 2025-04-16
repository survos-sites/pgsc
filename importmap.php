<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    '@tabler/core' => [
        'version' => '1.1.1',
    ],
    'autosize' => [
        'version' => '6.0.1',
    ],
    'imask' => [
        'version' => '7.6.1',
    ],
    '@tabler/core/dist/css/tabler.min.css' => [
        'version' => '1.1.1',
        'type' => 'css',
    ],
    'leaflet' => [
        'version' => '1.9.4',
    ],
    'leaflet/dist/leaflet.min.css' => [
        'version' => '1.9.4',
        'type' => 'css',
    ],
    '@symfony/ux-leaflet-map' => [
        'path' => './vendor/symfony/ux-leaflet-map/assets/dist/map_controller.js',
    ],
    'simple-datatables' => [
        'version' => '9.2.2',
    ],
    'simple-datatables/dist/style.min.css' => [
        'version' => '9.2.2',
        'type' => 'css',
    ],
    'd3-graphviz' => [
        'version' => '5.6.0',
    ],
    'd3-selection' => [
        'version' => '3.0.0',
    ],
    'd3-dispatch' => [
        'version' => '3.0.1',
    ],
    'd3-transition' => [
        'version' => '3.0.1',
    ],
    'd3-timer' => [
        'version' => '3.0.1',
    ],
    'd3-interpolate' => [
        'version' => '3.0.1',
    ],
    'd3-zoom' => [
        'version' => '3.0.0',
    ],
    '@hpcc-js/wasm/graphviz' => [
        'version' => '2.20.0',
    ],
    'd3-format' => [
        'version' => '3.1.0',
    ],
    'd3-path' => [
        'version' => '3.1.0',
    ],
    'd3-color' => [
        'version' => '3.1.0',
    ],
    'd3-ease' => [
        'version' => '3.0.1',
    ],
    'd3-drag' => [
        'version' => '3.0.0',
    ],
    'd3' => [
        'version' => '7.9.0',
    ],
    'd3-array' => [
        'version' => '3.2.4',
    ],
    'd3-axis' => [
        'version' => '3.0.0',
    ],
    'd3-brush' => [
        'version' => '3.0.0',
    ],
    'd3-chord' => [
        'version' => '3.0.1',
    ],
    'd3-contour' => [
        'version' => '4.0.2',
    ],
    'd3-delaunay' => [
        'version' => '6.0.4',
    ],
    'd3-dsv' => [
        'version' => '3.0.1',
    ],
    'd3-fetch' => [
        'version' => '3.0.1',
    ],
    'd3-force' => [
        'version' => '3.0.0',
    ],
    'd3-geo' => [
        'version' => '3.1.1',
    ],
    'd3-hierarchy' => [
        'version' => '3.1.2',
    ],
    'd3-polygon' => [
        'version' => '3.0.1',
    ],
    'd3-quadtree' => [
        'version' => '3.0.1',
    ],
    'd3-random' => [
        'version' => '3.0.1',
    ],
    'd3-scale' => [
        'version' => '4.0.2',
    ],
    'd3-scale-chromatic' => [
        'version' => '3.1.0',
    ],
    'd3-shape' => [
        'version' => '3.2.0',
    ],
    'd3-time' => [
        'version' => '3.1.0',
    ],
    'd3-time-format' => [
        'version' => '4.1.0',
    ],
    'internmap' => [
        'version' => '2.0.3',
    ],
    'delaunator' => [
        'version' => '5.0.0',
    ],
    'robust-predicates' => [
        'version' => '3.0.0',
    ],
];
