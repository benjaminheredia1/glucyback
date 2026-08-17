<?php

namespace App\Support\Auth0;

/**
 * Identidad tal y como la reporta Auth0. `email` puede faltar si el usuario
 * llego por una conexion que no lo entrega.
 */
class PerfilAuth0
{
    public function __construct(
        public readonly string $sub,
        public readonly ?string $email,
        public readonly bool $emailVerificado,
        public readonly ?string $nombre,
    ) {}
}
