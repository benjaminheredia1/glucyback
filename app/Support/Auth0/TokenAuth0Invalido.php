<?php

namespace App\Support\Auth0;

use RuntimeException;

/** El token no es de este tenant, no es para esta API, o esta caducado. -> 401 */
class TokenAuth0Invalido extends RuntimeException {}
