<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ModuleController.php';
require_once dirname(__DIR__) . '/models/FinanzasModel.php';

// CRUD de facturación de inscripciones dentro del panel administrativo.
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
                // Puede superar el límite seguro de enteros de JavaScript/PHP.
                'pkFields' => [['key' => 'id', 'column' => 'ID_FACTURACION_INSCRIPCION', 'label' => 'ID Factura', 'type' => 'text']],
                'fields' => [
                    ['key' => 'id_jugador', 'column' => 'ID_JUGADOR', 'label' => 'Jugador', 'type' => 'select', 'optionsTable' => 'jugadores', 'optionValue' => 'ID_JUGADOR', 'optionLabel' => 'JUGADOR_OPCION', 'table' => false],
                    ['key' => 'mes', 'column' => 'MES', 'label' => 'Mes', 'type' => 'select', 'options' => [
                        ['value' => 1, 'label' => 'Enero'], ['value' => 2, 'label' => 'Febrero'], ['value' => 3, 'label' => 'Marzo'],
                        ['value' => 4, 'label' => 'Abril'], ['value' => 5, 'label' => 'Mayo'], ['value' => 6, 'label' => 'Junio'],
                        ['value' => 7, 'label' => 'Julio'], ['value' => 8, 'label' => 'Agosto'], ['value' => 9, 'label' => 'Septiembre'],
                        ['value' => 10, 'label' => 'Octubre'], ['value' => 11, 'label' => 'Noviembre'], ['value' => 12, 'label' => 'Diciembre'],
                    ], 'table' => false],
                    ['key' => 'anio', 'column' => 'ANIO', 'label' => 'Anio', 'type' => 'number'],
                    ['key' => 'monto', 'column' => 'MONTO', 'label' => 'Monto', 'type' => 'number'],
                    ['key' => 'fecha_pago', 'column' => 'FECHA_PAGO', 'label' => 'Fecha pago', 'type' => 'date'],
                    ['key' => 'observaciones', 'column' => 'OBSERVACIONES', 'label' => 'Observaciones', 'type' => 'text'],
                ],
                'displayFields' => [
                    ['key' => 'jugador', 'column' => 'JUGADOR', 'label' => 'Jugador', 'type' => 'text', 'tableBefore' => true],
                    ['key' => 'cedula', 'column' => 'CEDULA', 'label' => 'Cedula', 'type' => 'text', 'tableBefore' => true],
                    ['key' => 'nombre_mes', 'column' => 'NOMBRE_MES', 'label' => 'Mes', 'type' => 'text', 'tableBefore' => true],
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
