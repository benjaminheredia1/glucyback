<?php

namespace App\Support\Auth0;

use RuntimeException;

/** El tenant no esta configurado o no responde. Es un fallo nuestro, no del cliente. -> 503 */
class Auth0NoDisponible extends RuntimeException {}
