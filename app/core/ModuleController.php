<?php

declare(strict_types=1);

require_once __DIR__ . '/Http.php';

abstract class ModuleController
{
    abstract public function label(): string;

    abstract public function schema(): array;

    abstract protected function listTable(string $table): array;

    abstract protected function saveTable(string $table, array $data, bool $isUpdate): void;

    abstract protected function deleteTable(string $table, array $data): void;

    public function handle(): void
    {
        handle_options_request();
        require_authenticated_session('Debe iniciar sesion para usar el panel administrativo.');

        $table = $_GET['tabla'] ?? array_key_first($this->schema());
        if (!is_string($table) || !array_key_exists($table, $this->schema())) {
            send_json(['error' => 'Tabla no permitida en este modulo.'], 400);
        }

        try {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            if ($method === 'GET') {
                send_json([
                    'message' => 'Registros cargados correctamente.',
                    'table' => $this->schema()[$table]['title'],
                    'records' => $this->listTable($table),
                ]);
            }

            if ($method === 'POST' || $method === 'PUT') {
                $isUpdate = $method === 'PUT';
                $this->saveTable($table, read_json_body(), $isUpdate);
                send_json([
                    'message' => $isUpdate ? 'Registro actualizado correctamente.' : 'Registro insertado correctamente.',
                ], $isUpdate ? 200 : 201);
            }

            if ($method === 'DELETE') {
                $data = read_json_body();
                $this->deleteTable($table, $data ?: $_GET);
                send_json(['message' => 'Registro desactivado correctamente.']);
            }

            send_json(['error' => 'Metodo no permitido.'], 405);
        } catch (InvalidArgumentException $exception) {
            send_json(['error' => $exception->getMessage()], 400);
        } catch (Throwable $exception) {
            send_json(['error' => $exception->getMessage()], 500);
        }
    }
}
