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
        'version' => '10.2.0',
    ],
    'simple-datatables/dist/style.min.css' => [
        'version' => '10.2.0',
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
    'twig' => [
        'version' => '1.17.1',
    ],
    'locutus/php/strings/sprintf' => [
        'version' => '2.0.16',
    ],
    'locutus/php/strings/vsprintf' => [
        'version' => '2.0.16',
    ],
    'locutus/php/math/round' => [
        'version' => '2.0.16',
    ],
    'locutus/php/math/max' => [
        'version' => '2.0.16',
    ],
    'locutus/php/math/min' => [
        'version' => '2.0.16',
    ],
    'locutus/php/strings/strip_tags' => [
        'version' => '2.0.16',
    ],
    'locutus/php/datetime/strtotime' => [
        'version' => '2.0.16',
    ],
    'locutus/php/datetime/date' => [
        'version' => '2.0.16',
    ],
    'locutus/php/var/boolval' => [
        'version' => '2.0.16',
    ],
    'debug' => [
        'version' => '4.4.3',
    ],
    'ms' => [
        'version' => '2.1.3',
    ],
    'stimulus-attributes' => [
        'version' => '1.0.2',
    ],
    'escape-html' => [
        'version' => '1.0.3',
    ],
    'fos-routing' => [
        'version' => '0.0.6',
    ],
    'instantsearch.js' => [
        'version' => '4.94.0',
    ],
    '@swc/helpers/cjs/_sliced_to_array.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_to_consumable_array.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_define_property.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_extends.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_object_destructuring_empty.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_object_spread.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_object_spread_props.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_type_of.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_instanceof.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_object_without_properties.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_call_super.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_class_call_check.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_create_class.cjs' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/cjs/_inherits.cjs' => [
        'version' => '0.5.18',
    ],
    '@algolia/events' => [
        'version' => '4.0.1',
    ],
    'algoliasearch-helper' => [
        'version' => '3.28.1',
    ],
    'qs' => [
        'version' => '6.15.1',
    ],
    'algoliasearch-helper/types/algoliasearch.js' => [
        'version' => '3.28.1',
    ],
    'side-channel' => [
        'version' => '1.1.0',
    ],
    'es-errors/type' => [
        'version' => '1.3.0',
    ],
    'object-inspect' => [
        'version' => '1.13.3',
    ],
    'side-channel-list' => [
        'version' => '1.0.0',
    ],
    'side-channel-map' => [
        'version' => '1.0.1',
    ],
    'side-channel-weakmap' => [
        'version' => '1.0.2',
    ],
    'get-intrinsic' => [
        'version' => '1.2.5',
    ],
    'call-bound' => [
        'version' => '1.0.2',
    ],
    'es-errors' => [
        'version' => '1.3.0',
    ],
    'es-errors/eval' => [
        'version' => '1.3.0',
    ],
    'es-errors/range' => [
        'version' => '1.3.0',
    ],
    'es-errors/ref' => [
        'version' => '1.3.0',
    ],
    'es-errors/syntax' => [
        'version' => '1.3.0',
    ],
    'es-errors/uri' => [
        'version' => '1.3.0',
    ],
    'gopd' => [
        'version' => '1.2.0',
    ],
    'es-define-property' => [
        'version' => '1.0.1',
    ],
    'has-symbols' => [
        'version' => '1.1.0',
    ],
    'dunder-proto/get' => [
        'version' => '1.0.0',
    ],
    'call-bind-apply-helpers/functionApply' => [
        'version' => '1.0.0',
    ],
    'call-bind-apply-helpers/functionCall' => [
        'version' => '1.0.0',
    ],
    'function-bind' => [
        'version' => '1.1.2',
    ],
    'hasown' => [
        'version' => '2.0.2',
    ],
    'call-bind' => [
        'version' => '1.0.8',
    ],
    'call-bind-apply-helpers' => [
        'version' => '1.0.0',
    ],
    'set-function-length' => [
        'version' => '1.2.2',
    ],
    'call-bind-apply-helpers/applyBind' => [
        'version' => '1.0.0',
    ],
    'define-data-property' => [
        'version' => '1.1.4',
    ],
    'has-property-descriptors' => [
        'version' => '1.0.2',
    ],
    'instantsearch.js/es/widgets' => [
        'version' => '4.94.0',
    ],
    'instantsearch-ui-components' => [
        'version' => '0.24.0',
    ],
    'preact' => [
        'version' => '10.29.1',
    ],
    'hogan.js' => [
        'version' => '3.0.2',
    ],
    'htm/preact' => [
        'version' => '3.1.1',
    ],
    'preact/hooks' => [
        'version' => '10.29.1',
    ],
    'markdown-to-jsx' => [
        'version' => '7.7.17',
    ],
    'htm' => [
        'version' => '3.1.1',
    ],
    'react' => [
        'version' => '19.2.0',
    ],
    'instantsearch.css/themes/algolia.min.css' => [
        'version' => '8.14.0',
        'type' => 'css',
    ],
    '@meilisearch/instant-meilisearch' => [
        'version' => '0.30.0',
    ],
    'meilisearch' => [
        'version' => '0.54.0',
    ],
    '@stimulus-components/dialog' => [
        'version' => '1.0.1',
    ],
    '@andypf/json-viewer' => [
        'version' => '2.3.2',
    ],
    'pretty-print-json' => [
        'version' => '3.0.7',
    ],
    'pretty-print-json/dist/css/pretty-print-json.min.css' => [
        'version' => '3.0.7',
        'type' => 'css',
    ],
    'dexie' => [
        'version' => '4.4.2',
    ],
    '@tacman1123/twig-browser' => [
        'version' => '0.4.18',
    ],
    '@tacman1123/twig-browser/src/compat/compileTwigBlocks.js' => [
        'version' => '0.4.18',
    ],
    '@tacman1123/twig-browser/adapters/symfony' => [
        'version' => '0.4.18',
    ],
    '@swc/helpers/esm/_sliced_to_array.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_to_consumable_array.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_define_property.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_extends.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_object_destructuring_empty.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_object_spread.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_object_spread_props.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_type_of.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_instanceof.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_object_without_properties.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_call_super.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_class_call_check.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_create_class.js' => [
        'version' => '0.5.18',
    ],
    '@swc/helpers/esm/_inherits.js' => [
        'version' => '0.5.18',
    ],
];
