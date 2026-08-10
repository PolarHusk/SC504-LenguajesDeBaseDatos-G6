<?php

declare(strict_types=1);

// Crea y reutiliza una conexión OCI8 persistente con Oracle usando la configuración privada.
function db_connection()
{
    static $connection = null;

    if ($connection !== null) {
        return $connection;
    }

    if (!extension_loaded('oci8')) {
        throw new RuntimeException('La extension OCI8 no esta habilitada en PHP.');
    }

    $config = require dirname(__DIR__) . '/config/database.php';

    if (!empty($config['tns_admin'])) {
        putenv('TNS_ADMIN=' . $config['tns_admin']);
    }

    $connection = @oci_pconnect(
        $config['user'],
        $config['password'],
        $config['connect_string'],
        $config['charset'] ?? 'AL32UTF8'
    );

    if (!$connection) {
        $error = oci_error();
        throw new RuntimeException($error['message'] ?? 'No fue posible conectar a Oracle.');
    }

    return $connection;
}
