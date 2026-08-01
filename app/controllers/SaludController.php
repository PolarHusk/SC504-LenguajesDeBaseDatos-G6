<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ModuleController.php';
require_once dirname(__DIR__) . '/models/SaludModel.php';

final class SaludController extends ModuleController
{
    public function __construct(private SaludModel $model)
    {
    }

    public function label(): string
    {
        return 'Salud deportiva';
    }

    public function schema(): array
    {
        return [
            'medicos' => [
                'title' => 'Medicos',
                'pkFields' => [['key' => 'id', 'column' => 'ID_MEDICO', 'label' => 'ID Medico', 'type' => 'number']],
                'fields' => [
                    ['key' => 'nombre', 'column' => 'NOMBRE', 'label' => 'Nombre', 'type' => 'text'],
                    ['key' => 'apellido_paterno', 'column' => 'APELLIDO_PATERNO', 'label' => 'Apellido paterno', 'type' => 'text'],
                    ['key' => 'apellido_materno', 'column' => 'APELLIDO_MATERNO', 'label' => 'Apellido materno', 'type' => 'text'],
                    ['key' => 'id_especialidad', 'column' => 'ID_ESPECIALIDAD', 'label' => 'ID Especialidad', 'type' => 'number'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
            'parte_medico' => [
                'title' => 'Parte Medico',
                'pkFields' => [['key' => 'id', 'column' => 'ID_PARTEMEDICO', 'label' => 'ID Parte medico', 'type' => 'number']],
                'fields' => [
                    ['key' => 'id_jugador', 'column' => 'ID_JUGADOR', 'label' => 'ID Jugador', 'type' => 'number'],
                    ['key' => 'observaciones', 'column' => 'OBSERVACIONES', 'label' => 'Observaciones', 'type' => 'text'],
                    ['key' => 'fecha', 'column' => 'FECHA', 'label' => 'Fecha', 'type' => 'date'],
                    ['key' => 'id_medico', 'column' => 'ID_MEDICO', 'label' => 'ID Medico', 'type' => 'number'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
            'revision_fisica' => [
                'title' => 'Revision Fisica',
                'pkFields' => [['key' => 'id', 'column' => 'ID_REVISION', 'label' => 'ID Revision', 'type' => 'number']],
                'fields' => [
                    ['key' => 'id_jugador', 'column' => 'ID_JUGADOR', 'label' => 'ID Jugador', 'type' => 'number'],
                    ['key' => 'peso', 'column' => 'PESO', 'label' => 'Peso', 'type' => 'number'],
                    ['key' => 'imc', 'column' => 'IMC', 'label' => 'IMC', 'type' => 'number'],
                    ['key' => 'altura', 'column' => 'ALTURA', 'label' => 'Altura', 'type' => 'number'],
                    ['key' => 'fecha_revision', 'column' => 'FECHA_REVISION', 'label' => 'Fecha revision', 'type' => 'date'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
            'lesiones' => [
                'title' => 'Lesiones',
                'pkFields' => [['key' => 'id', 'column' => 'ID_LESION', 'label' => 'ID Lesion', 'type' => 'number']],
                'fields' => [
                    ['key' => 'descripcion', 'column' => 'DESCRIPCION', 'label' => 'Descripcion', 'type' => 'text'],
                    ['key' => 'fecha_recuperacion', 'column' => 'FECHA_RECUPERACION', 'label' => 'Fecha recuperacion', 'type' => 'date'],
                    ['key' => 'id_tipolesion', 'column' => 'ID_TIPOLESION', 'label' => 'ID Tipo lesion', 'type' => 'number'],
                    ['key' => 'id_partemedico', 'column' => 'ID_PARTEMEDICO', 'label' => 'ID Parte medico', 'type' => 'number'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
        ];
    }

    protected function listTable(string $table): array
    {
        return match ($table) {
            'medicos' => $this->model->listMedicos(),
            'parte_medico' => $this->model->listPartesMedicos(),
            'revision_fisica' => $this->model->listRevisionesFisicas(),
            'lesiones' => $this->model->listLesiones(),
        };
    }

    protected function saveTable(string $table, array $data, bool $isUpdate): void
    {
        match ($table) {
            'medicos' => $this->model->saveMedico($data, $isUpdate),
            'parte_medico' => $this->model->saveParteMedico($data, $isUpdate),
            'revision_fisica' => $this->model->saveRevisionFisica($data, $isUpdate),
            'lesiones' => $this->model->saveLesion($data, $isUpdate),
        };
    }

    protected function deleteTable(string $table, array $data): void
    {
        match ($table) {
            'medicos' => $this->model->deleteMedico($data),
            'parte_medico' => $this->model->deleteParteMedico($data),
            'revision_fisica' => $this->model->deleteRevisionFisica($data),
            'lesiones' => $this->model->deleteLesion($data),
        };
    }
}
