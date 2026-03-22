<?php

/**
 * Configuración de Laravel Entrust.
 *
 * Nota: Solo se sobre-escriben los valores necesarios para que el sistema
 * trabaje con la tabla `pg_usuario` y la llave foránea `usuario_id`.
 */

return [
    'migrationSuffix' => 'laravel_entrust_setup_tables',

    'user_model' => 'App\\Models\\User',
    'user_table' => 'pg_usuario',

    'models' => [
        'role'          => 'App\\Models\\Role',
        'permission'    => 'App\\Models\\Permission',
    ],

    'defaults' => [
        'guard' => 'web',
    ],

    'tables' => [
        'roles'             => 'roles',
        'permissions'       => 'pg_permisos',
        'role_user'         => 'role_user',
        'permission_role'   => 'pg_permisos_role',
    ],

    'foreign_keys' => [
        'user' => 'usuario_id',
        'role' => 'role_id',
        'permission' => 'permission_id',
    ],

    'middleware' => [
        'register' => true,
        'handling' => 'abort',
        'handlers' => [
            'abort' => [
                'code' => 403,
                'message' => 'You don\'t Have a permission to Access this page.'
            ],
            'redirect' => [
                'url' => '/',
                'message' => [
                    'key' => 'error',
                    'content' => 'You don\'t Have a permission to Access this page'
                ]
            ],
        ],
    ],
];
