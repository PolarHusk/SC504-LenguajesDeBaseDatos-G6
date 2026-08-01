<?php

declare(strict_types=1);

interface AuthenticatorInterface
{
    public function validateCredentials(string $usuario, string $contrasenia): bool;
}
