<?php

declare(strict_types=1);

// Contrato que define la única operación requerida para autenticar un usuario.
interface AuthenticatorInterface
{
    public function validateCredentials(string $usuario, string $contrasenia): bool;
}
