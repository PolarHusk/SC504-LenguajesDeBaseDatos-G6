<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ModuleController.php';
require_once dirname(__DIR__) . '/models/FinanzasModel.php';

final class FinanzasController extends ModuleController
{
    public function __construct(private FinanzasModel $model)
    {
    }

    public function label(): string
    {
        return 'Finanzas';
    }

    public function schema(): array
    {
        return [
            'facturacion_inscripciones' => [
                'title' => 'Facturacion Inscripciones',
                'pkFields' => [['key' => 'id', 'column' => 'ID_FACTURACION_INSCRIPCION', 'label' => 'ID Factura', 'type' => 'number']],
                'fields' => [
                    ['key' => 'id_jugador', 'column' => 'ID_JUGADOR', 'label' => 'ID Jugador', 'type' => 'number'],
                    ['key' => 'mes', 'column' => 'MES', 'label' => 'Mes', 'type' => 'number'],
                    ['key' => 'anio', 'column' => 'ANIO', 'label' => 'Anio', 'type' => 'number'],
                    ['key' => 'monto', 'column' => 'MONTO', 'label' => 'Monto', 'type' => 'number'],
                    ['key' => 'fecha_pago', 'column' => 'FECHA_PAGO', 'label' => 'Fecha pago', 'type' => 'date'],
                    ['key' => 'observaciones', 'column' => 'OBSERVACIONES', 'label' => 'Observaciones', 'type' => 'text'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
        ];
    }

    protected function listTable(string $table): array
    {
        return $this->model->listFacturaciones();
    }

    protected function saveTable(string $table, array $data, bool $isUpdate): void
    {
        $this->model->saveFacturacion($data, $isUpdate);
    }

    protected function deleteTable(string $table, array $data): void
    {
        $this->model->deleteFacturacion($data);
    }
}
