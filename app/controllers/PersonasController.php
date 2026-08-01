<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ModuleController.php';
require_once dirname(__DIR__) . '/models/PersonasModel.php';

final class PersonasController extends ModuleController
{
    public function __construct(private PersonasModel $model)
    {
    }

    public function label(): string
    {
        return 'Personas y jugadores';
    }

    public function schema(): array
    {
        return [
            'empleados' => [
                'title' => 'Empleados',
                'pkFields' => [['key' => 'id', 'column' => 'ID_EMPLEADO', 'label' => 'ID Empleado', 'type' => 'number']],
                'fields' => [
                    ['key' => 'nombre', 'column' => 'NOMBRE', 'label' => 'Nombre', 'type' => 'text'],
                    ['key' => 'apellido_paterno', 'column' => 'APELLIDO_PATERNO', 'label' => 'Apellido paterno', 'type' => 'text'],
                    ['key' => 'apellido_materno', 'column' => 'APELLIDO_MATERNO', 'label' => 'Apellido materno', 'type' => 'text'],
                    ['key' => 'edad', 'column' => 'EDAD', 'label' => 'Edad', 'type' => 'number'],
                    ['key' => 'nombre_de_usuario', 'column' => 'NOMBRE_DE_USUARIO', 'label' => 'Usuario', 'type' => 'text'],
                    ['key' => 'contrasenia', 'column' => 'CONTRASENIA', 'label' => 'Contrasenia', 'type' => 'text'],
                    ['key' => 'id_cargo', 'column' => 'ID_CARGO', 'label' => 'ID Cargo', 'type' => 'number'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => true,
            ],
            'jugadores' => [
                'title' => 'Jugadores',
                'pkFields' => [['key' => 'id', 'column' => 'ID_JUGADOR', 'label' => 'ID Jugador', 'type' => 'number']],
                'fields' => [
                    ['key' => 'cedula', 'column' => 'CEDULA', 'label' => 'Cedula', 'type' => 'text'],
                    ['key' => 'id_categoria', 'column' => 'ID_CATEGORIA', 'label' => 'ID Categoria', 'type' => 'number'],
                    ['key' => 'nombre', 'column' => 'NOMBRE', 'label' => 'Nombre', 'type' => 'text'],
                    ['key' => 'apellido_materno', 'column' => 'APELLIDO_MATERNO', 'label' => 'Apellido materno', 'type' => 'text'],
                    ['key' => 'apellido_paterno', 'column' => 'APELLIDO_PATERNO', 'label' => 'Apellido paterno', 'type' => 'text'],
                    ['key' => 'dorsal', 'column' => 'DORSAL', 'label' => 'Dorsal', 'type' => 'number'],
                    ['key' => 'fecha_nacimiento', 'column' => 'FECHA_NACIMIENTO', 'label' => 'Fecha nacimiento', 'type' => 'date'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => true,
            ],
            'telefonos' => [
                'title' => 'Telefonos',
                'pkFields' => [['key' => 'id', 'column' => 'ID_TELEFONO', 'label' => 'ID Telefono', 'type' => 'number']],
                'fields' => [
                    ['key' => 'numero_telefono', 'column' => 'NUMERO_TELEFONO', 'label' => 'Numero telefono', 'type' => 'text'],
                    ['key' => 'id_empleado', 'column' => 'ID_EMPLEADO', 'label' => 'ID Empleado', 'type' => 'number'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
            'jugador_posiciones' => [
                'title' => 'Jugador Posiciones',
                'pkFields' => [
                    ['key' => 'id_jugador', 'column' => 'ID_JUGADOR', 'label' => 'ID Jugador', 'type' => 'number'],
                    ['key' => 'id_posicion_actual', 'column' => 'ID_POSICION', 'label' => 'ID Posicion', 'type' => 'number'],
                ],
                'fields' => [['key' => 'id_posicion_nueva', 'column' => 'ID_POSICION', 'label' => 'ID Posicion nueva', 'type' => 'number', 'editOnly' => true, 'table' => false]],
                'hasEstado' => true, 'statusColumn' => false, 'autoId' => false, 'hasAddress' => false,
            ],
        ];
    }

    protected function listTable(string $table): array
    {
        return match ($table) {
            'empleados' => $this->model->listEmpleados(),
            'jugadores' => $this->model->listJugadores(),
            'telefonos' => $this->model->listTelefonos(),
            'jugador_posiciones' => $this->model->listJugadorPosiciones(),
        };
    }

    protected function saveTable(string $table, array $data, bool $isUpdate): void
    {
        match ($table) {
            'empleados' => $this->model->saveEmpleado($data, $isUpdate),
            'jugadores' => $this->model->saveJugador($data, $isUpdate),
            'telefonos' => $this->model->saveTelefono($data, $isUpdate),
            'jugador_posiciones' => $this->model->saveJugadorPosicion($data, $isUpdate),
        };
    }

    protected function deleteTable(string $table, array $data): void
    {
        match ($table) {
            'empleados' => $this->model->deleteEmpleado($data),
            'jugadores' => $this->model->deleteJugador($data),
            'telefonos' => $this->model->deleteTelefono($data),
            'jugador_posiciones' => $this->model->deleteJugadorPosicion($data),
        };
    }
}
