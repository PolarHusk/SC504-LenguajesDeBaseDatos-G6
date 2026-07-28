<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/models/Contracts/AuthenticatorInterface.php';

final class AuthModel implements AuthenticatorInterface
{
    public function validateCredentials(string $usuario, string $contrasenia): bool
    {
        $connection = db_connection();
        $resultado = 0;
        $sql = 'BEGIN :resultado := FIDE_PROYECTOFINALACADEMIALEIVA_PCK.FIDE_EMPLEADOS_TB_VALIDAR_LOGIN_FN(:usuario, :contrasenia); END;';
        $statement = oci_parse($connection, $sql);
        if (!$statement) {
            $error = oci_error($connection);
            throw new RuntimeException($error['message'] ?? 'No fue posible preparar la validacion del usuario.');
        }

        oci_bind_by_name($statement, ':usuario', $usuario, 100);
        oci_bind_by_name($statement, ':contrasenia', $contrasenia, 100);
        oci_bind_by_name($statement, ':resultado', $resultado, 20, SQLT_INT);
        if (!oci_execute($statement)) {
            $error = oci_error($statement);
            throw new RuntimeException($error['message'] ?? 'No fue posible validar el usuario.');
        }
        oci_free_statement($statement);

        return (int) $resultado === 1;
    }
}
