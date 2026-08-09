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

    public function listEmpleadoOptions(): array
    {
        return $this->listView('FIDE_EMPLEADOS_V', 'ID_EMPLEADO', false);
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
        $jugadores = $this->listView('FIDE_JUGADORES_V', 'ID_JUGADOR', true, 'FIDE_JUGADORES_CON_DIRECCION_V');
        $relaciones = $this->query(
            'SELECT ID_JUGADOR, ID_POSICION FROM FIDE_JUGADOR_POSICIONES_TB WHERE ID_ESTADO = :estado',
            [':estado' => ESTADO_ACTIVO],
            'No fue posible cargar las posiciones de los jugadores.'
        );

        $posicionesPorJugador = [];
        foreach ($relaciones as $relacion) {
            $jugadorId = (int) $relacion['ID_JUGADOR'];
            $posicionesPorJugador[$jugadorId][] = (int) $relacion['ID_POSICION'];
        }

        foreach ($jugadores as &$jugador) {
            $jugador['ID_POSICIONES'] = $posicionesPorJugador[(int) $jugador['ID_JUGADOR']] ?? [];
        }
        unset($jugador);

        return $jugadores;
    }

    public function saveJugador(array $data, bool $isUpdate): void
    {
        $positionIds = array_values(array_unique(array_filter(
            array_map('intval', is_array($data['id_posiciones'] ?? null) ? $data['id_posiciones'] : []),
            static fn(int $id): bool => $id > 0
        )));
        if ($positionIds === []) {
            throw new InvalidArgumentException('Debe seleccionar al menos una posicion.');
        }

        $cedula = $this->validCedula($data);
        $categoriaId = (int) $this->required($data, 'id_categoria', 'ID Categoria');
        $fechaNacimiento = $this->required($data, 'fecha_nacimiento', 'Fecha nacimiento');
        $this->validatePlayerAge((string) $fechaNacimiento, $categoriaId);
        $dorsal = (int) $this->required($data, 'dorsal', 'Dorsal');
        if (!$isUpdate) {
            $this->validateNewJugador($cedula, $categoriaId, $dorsal);
        }

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
            $cedula,
            $categoriaId,
            $this->required($data, 'nombre', 'Nombre'),
            $this->required($data, 'apellido_materno', 'Apellido materno'),
            $this->required($data, 'apellido_paterno', 'Apellido paterno'),
            $dorsal,
            $fechaNacimiento,
            $this->state($data),
            $addressId,
        ];

        if ($isUpdate) {
            $jugadorId = (int) $this->required($data, 'id', 'ID Jugador');
            array_unshift($values, $jugadorId);
            $this->call('FIDE_JUGADORES_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el jugador.', [7]);
            $this->syncJugadorPosiciones($jugadorId, $positionIds, $this->state($data));
            return;
        }

        $this->call('FIDE_JUGADORES_TB_INSERTAR_SP', $values, 'No fue posible crear el jugador.', [6]);

        $jugadorId = 0;
        foreach ($this->listView('FIDE_JUGADORES_V', 'ID_JUGADOR', false) as $jugador) {
            if ((string) ($jugador['CEDULA'] ?? '') === (string) $cedula) {
                $jugadorId = (int) ($jugador['ID_JUGADOR'] ?? 0);
                break;
            }
        }
        if ($jugadorId <= 0) {
            throw new RuntimeException('No fue posible identificar el jugador creado para asignar sus posiciones.');
        }

        foreach ($positionIds as $positionId) {
            $this->call('FIDE_JUGADOR_POSICIONES_TB_INSERTAR_SP', [
                $jugadorId,
                $positionId,
                $this->state($data),
            ], 'No fue posible asignar una posicion al jugador.');
        }
    }

    private function syncJugadorPosiciones(int $jugadorId, array $positionIds, int $estado): void
    {
        $actuales = $this->query(
            'SELECT ID_POSICION FROM FIDE_JUGADOR_POSICIONES_TB WHERE ID_JUGADOR = :jugador_id AND ID_ESTADO = :estado',
            [':jugador_id' => $jugadorId, ':estado' => ESTADO_ACTIVO],
            'No fue posible consultar las posiciones actuales del jugador.'
        );
        $actualesIds = array_map(static fn(array $row): int => (int) $row['ID_POSICION'], $actuales);

        foreach (array_diff($actualesIds, $positionIds) as $positionId) {
            $this->call('FIDE_JUGADOR_POSICIONES_TB_ELIMINAR_SP', [$jugadorId, $positionId], 'No fue posible quitar una posicion del jugador.');
        }

        foreach (array_diff($positionIds, $actualesIds) as $positionId) {
            $this->call('FIDE_JUGADOR_POSICIONES_TB_INSERTAR_SP', [$jugadorId, $positionId, $estado], 'No fue posible asignar una posicion al jugador.');
        }
    }

    private function validateNewJugador(string $cedula, int $categoriaId, int $dorsal): void
    {
        foreach ($this->listView('FIDE_JUGADORES_V', 'ID_JUGADOR', false) as $jugador) {
            $existingCedula = preg_replace('/\D/', '', (string) ($jugador['CEDULA'] ?? ''));
            if ($existingCedula === $cedula) {
                throw new InvalidArgumentException('Ya existe un jugador registrado con esa cedula.');
            }

            if ((int) ($jugador['ID_CATEGORIA'] ?? 0) === $categoriaId
                && (int) ($jugador['DORSAL'] ?? 0) === $dorsal) {
                throw new InvalidArgumentException('El dorsal seleccionado ya está asignado a otro jugador de esa categoria.');
            }
        }
    }

    public function isPlayerAgeValid(string $fechaNacimiento, int $categoriaId): bool
    {
        return $this->callNumberFunction(
            'FIDE_JUGADORES_TB_VALIDAR_EDAD_CATEGORIA_FN',
            [$fechaNacimiento, $categoriaId],
            'No fue posible validar la edad del jugador.',
            [0]
        ) === 1;
    }

    private function validatePlayerAge(string $fechaNacimiento, int $categoriaId): void
    {
        if (!$this->isPlayerAgeValid($fechaNacimiento, $categoriaId)) {
            throw new InvalidArgumentException('La edad del jugador no corresponde a la categoria seleccionada.');
        }
    }

    private function validCedula(array $data): string
    {
        $cedula = trim((string) $this->required($data, 'cedula', 'Cedula'));
        if (!preg_match('/^\d{9}$/', $cedula)) {
            throw new InvalidArgumentException('La cedula debe contener exactamente 9 digitos, sin guiones ni espacios.');
        }

        return $cedula;
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

    public function listCorreos(): array
    {
        return $this->listView('FIDE_CORREOS_V', 'ID_CORREO');
    }

    public function saveCorreo(array $data, bool $isUpdate): void
    {
        $correo = trim((string) $this->required($data, 'correo_electronico', 'Correo electronico'));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Ingrese un correo electronico valido.');
        }

        $values = [$correo, (int) $this->required($data, 'id_empleado', 'ID Empleado'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Correo'));
            $this->call('FIDE_CORREOS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el correo.');
            return;
        }

        $this->call('FIDE_CORREOS_TB_INSERTAR_SP', $values, 'No fue posible crear el correo.');
    }

    public function deleteCorreo(array $data): void
    {
        $this->call('FIDE_CORREOS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Correo')], 'No fue posible desactivar el correo.');
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
