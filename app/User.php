<?php

namespace App;

/**
 * Alias de compatibilidad.
 *
 * Algunas partes del proyecto (por ejemplo, controllers antiguos) siguen
 * importando `App\User`. Esta clase delega al modelo real ubicado en
 * `App\Models\User`.
 */
class User extends \App\Models\User
{
}
