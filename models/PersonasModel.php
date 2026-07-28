<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';
require_once dirname(__DIR__) . '/models/AddressModel.php';

final class PersonasModel extends OracleModel
{
    public function listEmpleados(): array
    {
        return $this->listView('FIDE_EMPLEADOS_V', 'ID_EMPLEADO', true, 'FIDE_EMPLEADOS_CON_DIRECCION_V');
    }

    public function saveEmpleado(array $data, bool $isUpdate): void
    {
        $address = $data['direccion'] ?? null;
        if (!is_array($address)) {
            throw new InvalidArgumentException('Debe completar la direccion.');
        }

        $addressModel = new AddressModel();
        $addressId = $isUpdate
            ? (int) $this->required($data, 'id_direccion_exacta', 'ID Direccion')
            : $addressModel->create($address);

        if ($isUpdate) {
            $addressModel->update($addressId, $address);
        }

        $values = [
            $this->required($data, 'nombre', 'Nombre'),
            $this->required($data, 'apellido_paterno', 'Apellido paterno'),
            $this->required($data, 'apellido_materno', 'Apellido materno'),
            (int) $this->required($data, 'edad', 'Edad'),
            $this->required($data, 'nombre_de_usuario', 'Usuario'),
            $this->required($data, 'contrasenia', 'Contrasenia'),
            (int) $this->required($data, 'id_cargo', 'ID Cargo'),
            $addressId,
            $this->state($data),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Empleado'));
            $this->call('FIDE_EMPLEADOS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el empleado.');
            return;
        }

        $this->call('FIDE_EMPLEADOS_TB_INSERTAR_SP', $values, 'No fue posible crear el empleado.');
    }

    public function deleteEmpleado(array $data): void
    {
        $this->call('FIDE_EMPLEADOS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Empleado')], 'No fue posible desactivar el empleado.');
    }

    public function listJugadores(): array
    {
        return $this->listView('FIDE_JUGADORES_V', 'ID_JUGADOR', true, 'FIDE_JUGADORES_CON_DIRECCION_V');
    }

    public function saveJugador(array $data, bool $isUpdate): void
    {
        $address = $data['direccion'] ?? null;
        if (!is_array($address)) {
            throw new InvalidArgumentException('Debe completar la direccion.');
        }

        $addressModel = new AddressModel();
        $addressId = $isUpdate
            ? (int) $this->required($data, 'id_direccion_exacta', 'ID Direccion')
            : $addressModel->create($address);

        if ($isUpdate) {
            $addressModel->update($addressId, $address);
        }

        $values = [
            $this->required($data, 'cedula', 'Cedula'),
            (int) $this->required($data, 'id_categoria', 'ID Categoria'),
            $this->required($data, 'nombre', 'Nombre'),
            $this->required($data, 'apellido_materno', 'Apellido materno'),
            $this->required($data, 'apellido_paterno', 'Apellido paterno'),
            (int) $this->required($data, 'dorsal', 'Dorsal'),
            $this->required($data, 'fecha_nacimiento', 'Fecha nacimiento'),
            $this->state($data),
            $addressId,
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Jugador'));
            $this->call('FIDE_JUGADORES_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el jugador.', [7]);
            return;
        }

        $this->call('FIDE_JUGADORES_TB_INSERTAR_SP', $values, 'No fue posible crear el jugador.', [6]);
    }

    public function deleteJugador(array $data): void
    {
        $this->call('FIDE_JUGADORES_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Jugador')], 'No fue posible desactivar el jugador.');
    }

    public function listTelefonos(): array
    {
        return $this->listView('FIDE_TELEFONOS_V', 'ID_TELEFONO');
    }

    public function saveTelefono(array $data, bool $isUpdate): void
    {
        $values = [
            $this->required($data, 'numero_telefono', 'Numero telefono'),
            (int) $this->required($data, 'id_empleado', 'ID Empleado'),
            $this->state($data),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Telefono'));
            $this->call('FIDE_TELEFONOS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el telefono.');
            return;
        }

        $this->call('FIDE_TELEFONOS_TB_INSERTAR_SP', $values, 'No fue posible crear el telefono.');
    }

    public function deleteTelefono(array $data): void
    {
        $this->call('FIDE_TELEFONOS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Telefono')], 'No fue posible desactivar el telefono.');
    }

    public function listJugadorPosiciones(): array
    {
        return $this->listView('FIDE_JUGADOR_POSICIONES_V', 'ID_JUGADOR', false);
    }

    public function saveJugadorPosicion(array $data, bool $isUpdate): void
    {
        if ($isUpdate) {
            $this->call('FIDE_JUGADOR_POSICIONES_TB_MODIFICAR_SP', [
                (int) $this->required($data, 'id_jugador', 'ID Jugador'),
                (int) $this->required($data, 'id_posicion_actual', 'ID Posicion'),
                (int) $this->required($data, 'id_posicion_nueva', 'ID Posicion nueva'),
            ], 'No fue posible actualizar la posicion del jugador.');
            return;
        }

        $this->call('FIDE_JUGADOR_POSICIONES_TB_INSERTAR_SP', [
            (int) $this->required($data, 'id_jugador', 'ID Jugador'),
            (int) $this->required($data, 'id_posicion_actual', 'ID Posicion'),
            $this->state($data),
        ], 'No fue posible crear la posicion del jugador.');
    }

    public function deleteJugadorPosicion(array $data): void
    {
        $this->call('FIDE_JUGADOR_POSICIONES_TB_ELIMINAR_SP', [
            (int) $this->required($data, 'id_jugador', 'ID Jugador'),
            (int) $this->required($data, 'id_posicion_actual', 'ID Posicion'),
        ], 'No fue posible desactivar la posicion del jugador.');
    }
}
