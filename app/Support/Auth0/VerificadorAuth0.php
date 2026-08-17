<?php

namespace App\Support\Auth0;

interface VerificadorAuth0
{
    /**
     * @throws TokenAuth0Invalido si la firma, el emisor, la audiencia o la
     *         vigencia no cuadran.
     * @throws Auth0NoDisponible si el tenant no esta configurado o no responde.
     */
    public function verificar(string $accessToken): PerfilAuth0;
}
