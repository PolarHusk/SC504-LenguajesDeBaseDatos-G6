<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ModuleController.php';
require_once dirname(__DIR__) . '/models/UbicacionModel.php';

// CRUD de sedes y de su información geográfica.
final class UbicacionController extends ModuleController
{
    public function __construct(private UbicacionModel $model)
    {
    }

    public function label(): string
    {
        return 'Ubicacion';
    }

    public function schema(): array
    {
        return [
            'sedes' => [
                'title' => 'Sedes',
                'pkFields' => [['key' => 'id', 'column' => 'ID_SEDE', 'label' => 'ID Sede', 'type' => 'number']],
                'fields' => [['key' => 'nombre_sede', 'column' => 'NOMBRE_SEDE', 'label' => 'Nombre sede', 'type' => 'text']],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => true,
            ],
        ];
    }

    protected function listTable(string $table): array
    {
        return $this->model->listSedes();
    }

    protected function saveTable(string $table, array $data, bool $isUpdate): void
    {
        $this->model->saveSede($data, $isUpdate);
    }

    protected function deleteTable(string $table, array $data): void
    {
        $this->model->deleteSede($data);
    }
}
