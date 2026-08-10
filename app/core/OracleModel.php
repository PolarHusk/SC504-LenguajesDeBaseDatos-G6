<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once dirname(__DIR__) . '/config/constants.php';

// Base de acceso a Oracle: ejecuta vistas, procedimientos y funciones parametrizadas del PACKAGE.
abstract class OracleModel
{
    protected function connection()
    {
        return db_connection();
    }

    protected function listView(string $view, string $orderColumn, bool $includeStatus = true, ?string $addressView = null): array
    {
        $connection = $this->connection();
        $sql = 'SELECT T.*';

        if ($includeStatus) {
            $sql .= ', ' . PACKAGE_NAME . '.FIDE_ESTADOS_TB_VALIDAR_NOMBRE_ESTADO_FN(T.ID_ESTADO) AS NOMBRE_ESTADO';
        }

        $source = $addressView ?? $view;
        $sql .= " FROM {$source} T ORDER BY T.{$orderColumn}";
        $statement = oci_parse($connection, $sql);
        $this->execute($statement, 'No fue posible listar los registros.');

        $records = $this->fetchAll($statement);
        oci_free_statement($statement);
        return $records;
    }

    protected function query(string $sql, array $parameters = [], string $message = 'No fue posible consultar los datos.'): array
    {
        $connection = $this->connection();
        $statement = oci_parse($connection, $sql);
        if (!$statement) {
            $error = oci_error($connection);
            throw new RuntimeException($error['message'] ?? $message);
        }

        foreach ($parameters as $placeholder => &$value) {
            oci_bind_by_name($statement, $placeholder, $value, 255);
        }
        unset($value);

        $this->execute($statement, $message);
        $records = $this->fetchAll($statement);
        oci_free_statement($statement);
        return $records;
    }

    protected function call(string $procedure, array $arguments, string $message, array $dateIndexes = []): void
    {
        // Punto único de llamada a procedimientos del PACKAGE Oracle.
        // Los modelos envían el nombre del SP y este método construye: BEGIN PAQUETE.PROCEDIMIENTO(...); END;.
        $connection = $this->connection();
        $placeholders = [];

        foreach ($arguments as $index => $value) {
            $placeholder = ':p' . $index;
            $placeholders[] = in_array($index, $dateIndexes, true)
                ? "TO_DATE({$placeholder}, 'YYYY-MM-DD')"
                : $placeholder;
        }

        $sql = 'BEGIN ' . PACKAGE_NAME . '.' . $procedure . '(' . implode(', ', $placeholders) . '); END;';
        $statement = oci_parse($connection, $sql);
        if (!$statement) {
            $error = oci_error($connection);
            throw new RuntimeException($error['message'] ?? $message);
        }

        foreach ($arguments as $index => &$value) {
            oci_bind_by_name($statement, ':p' . $index, $value, 255);
        }
        unset($value);

        $this->execute($statement, $message);
        oci_commit($connection);
        oci_free_statement($statement);
    }

    protected function callNumberFunction(string $function, array $arguments, string $message, array $dateIndexes = []): int
    {
        // Punto único para funciones Oracle que devuelven NUMBER (por ejemplo, la validación de edad).
        $connection = $this->connection();
        $placeholders = [];
        foreach ($arguments as $index => $value) {
            $placeholder = ':p' . $index;
            $placeholders[] = in_array($index, $dateIndexes, true) ? "TO_DATE({$placeholder}, 'YYYY-MM-DD')" : $placeholder;
        }

        $statement = oci_parse($connection, 'BEGIN :result := ' . PACKAGE_NAME . '.' . $function . '(' . implode(', ', $placeholders) . '); END;');
        if (!$statement) {
            $error = oci_error($connection);
            throw new RuntimeException($error['message'] ?? $message);
        }

        foreach ($arguments as $index => &$value) {
            oci_bind_by_name($statement, ':p' . $index, $value, 255);
        }
        unset($value);

        $result = 0;
        oci_bind_by_name($statement, ':result', $result, 32, SQLT_INT);
        $this->execute($statement, $message);
        oci_free_statement($statement);
        return $result;
    }

    protected function callReturning(string $procedure, array $arguments, string $message): int
    {
        $connection = $this->connection();
        $placeholders = [];

        foreach ($arguments as $index => $value) {
            $placeholders[] = ':p' . $index;
        }
        $placeholders[] = ':out_id';

        $sql = 'BEGIN ' . PACKAGE_NAME . '.' . $procedure . '(' . implode(', ', $placeholders) . '); END;';
        $statement = oci_parse($connection, $sql);
        if (!$statement) {
            $error = oci_error($connection);
            throw new RuntimeException($error['message'] ?? $message);
        }

        foreach ($arguments as $index => &$value) {
            oci_bind_by_name($statement, ':p' . $index, $value, 255);
        }
        unset($value);

        $generatedId = 0;
        oci_bind_by_name($statement, ':out_id', $generatedId, 32, SQLT_INT);
        $this->execute($statement, $message);
        oci_commit($connection);
        oci_free_statement($statement);

        if ($generatedId <= 0) {
            throw new RuntimeException('Oracle no devolvio el identificador generado.');
        }

        return $generatedId;
    }

    protected function callClobFunction(string $function, array $arguments, string $message): string
    {
        // Punto único para funciones de consulta: Oracle devuelve JSON en CLOB y PHP lo entrega al controlador.
        $connection = $this->connection();
        $placeholders = [];
        foreach ($arguments as $index => $value) {
            $placeholders[] = ':p' . $index;
        }
        $sql = 'BEGIN :result := ' . PACKAGE_NAME . '.' . $function . '(' . implode(', ', $placeholders) . '); END;';

        $statement = oci_parse($connection, $sql);
        if (!$statement) {
            $error = oci_error($connection);
            throw new RuntimeException($error['message'] ?? $message);
        }

        $bindings = array_values($arguments);
        foreach ($bindings as $index => &$value) {
            oci_bind_by_name($statement, ':p' . $index, $value, 255);
        }
        unset($value);

        $result = oci_new_descriptor($connection, OCI_D_LOB);
        oci_bind_by_name($statement, ':result', $result, -1, OCI_B_CLOB);
        $this->execute($statement, $message);

        $json = $result->load() ?: '[]';
        $result->free();
        oci_free_statement($statement);
        return $json;
    }

    protected function decodeJsonResult(string $json, string $message): array
    {
        $records = json_decode($json, true);
        if (!is_array($records)) {
            throw new RuntimeException($message);
        }

        return $records;
    }

    protected function required(array $data, string $key, string $label)
    {
        $value = $data[$key] ?? null;
        if ($value === null || $value === '') {
            throw new InvalidArgumentException("Debe enviar {$label}.");
        }

        return is_string($value) ? trim($value) : $value;
    }

    protected function state(array $data): int
    {
        $value = $data['id_estado'] ?? ESTADO_ACTIVO;
        return (int) $value;
    }

    protected function execute($statement, string $message): void
    {
        if (!$statement || !oci_execute($statement, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($statement);
            throw new RuntimeException($error['message'] ?? $message);
        }
    }

    protected function fetchAll($statement): array
    {
        $records = [];
        while (($row = oci_fetch_assoc($statement)) !== false) {
            $records[] = $row;
        }

        return $records;
    }
}
