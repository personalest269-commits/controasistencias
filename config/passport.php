<?php

return [
    'guard' => env('PASSPORT_GUARD', 'web'),

    // Para evitar problemas con la ruta "file://" (muy común en Windows),
    // entregamos el contenido de las llaves directamente cuando existan.
    'private_key' => env(
        'PASSPORT_PRIVATE_KEY',
        file_exists(storage_path('oauth-private.key')) ? file_get_contents(storage_path('oauth-private.key')) : null
    ),

    'public_key' => env(
        'PASSPORT_PUBLIC_KEY',
        file_exists(storage_path('oauth-public.key')) ? file_get_contents(storage_path('oauth-public.key')) : null
    ),
];
