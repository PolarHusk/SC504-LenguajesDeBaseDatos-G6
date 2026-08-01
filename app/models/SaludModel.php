<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';

final class SaludModel extends OracleModel
{
    public function listMedicos(): array
    {
        return $this->listView('FIDE_MEDICOS_V', 'ID_MEDICO');
    }

    public function saveMedico(array $data, bool $isUpdate): void
    {
        $values = [
            $this->required($data, 'nombre', 'Nombre'),
            $this->required($data, 'apellido_paterno', 'Apellido paterno'),
            $this->required($data, 'apellido_materno', 'Apellido materno'),
            (int) $this->required($data, 'id_especialidad', 'ID Especialidad'),
            $this->state($data),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Medico'));
            $this->call('FIDE_MEDICOS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el medico.');
            return;
        }

        $this->call('FIDE_MEDICOS_TB_INSERTAR_SP', $values, 'No fue posible crear el medico.');
    }

    public function deleteMedico(array $data): void
    {
        $this->call('FIDE_MEDICOS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Medico')], 'No fue posible desactivar el medico.');
    }

    public function listPartesMedicos(): array
    {
        return $this->listView('FIDE_PARTE_MEDICO_V', 'ID_PARTEMEDICO');
    }

    public function saveParteMedico(array $data, bool $isUpdate): void
    {
        $values = [
            (int) $this->required($data, 'id_jugador', 'ID Jugador'),
            $this->required($data, 'observaciones', 'Observaciones'),
            $this->required($data, 'fecha', 'Fecha'),
            (int) $this->required($data, 'id_medico', 'ID Medico'),
            $this->state($data),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Parte medico'));
            $this->call('FIDE_PARTE_MEDICO_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el parte medico.', [3]);
            return;
        }

        $this->call('FIDE_PARTE_MEDICO_TB_INSERTAR_SP', $values, 'No fue posible crear el parte medico.', [2]);
    }

    public function deleteParteMedico(array $data): void
    {
        $this->call('FIDE_PARTE_MEDICO_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Parte medico')], 'No fue posible desactivar el parte medico.');
    }

    public function listRevisionesFisicas(): array
    {
        return $this->listView('FIDE_REVISION_FISICA_V', 'ID_REVISION');
    }

    public function saveRevisionFisica(array $data, bool $isUpdate): void
    {
        $values = [
            (int) $this->required($data, 'id_jugador', 'ID Jugador'),
            $this->required($data, 'peso', 'Peso'),
            $this->required($data, 'imc', 'IMC'),
            $this->required($data, 'altura', 'Altura'),
            $this->required($data, 'fecha_revision', 'Fecha revision'),
            $this->state($data),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Revision'));
            $this->call('FIDE_REVISION_FISICA_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la revision fisica.', [5]);
            return;
        }

        $this->call('FIDE_REVISION_FISICA_TB_INSERTAR_SP', $values, 'No fue posible crear la revision fisica.', [4]);
    }

    public function deleteRevisionFisica(array $data): void
    {
        $this->call('FIDE_REVISION_FISICA_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Revision')], 'No fue posible desactivar la revision fisica.');
    }

    public function listLesiones(): array
    {
        return $this->listView('FIDE_LESIONES_V', 'ID_LESION');
    }

    public function saveLesion(array $data, bool $isUpdate): void
    {
        $values = [
            $this->required($data, 'descripcion', 'Descripcion'),
            $this->required($data, 'fecha_recuperacion', 'Fecha recuperacion'),
            (int) $this->required($data, 'id_tipolesion', 'ID Tipo lesion'),
            (int) $this->required($data, 'id_partemedico', 'ID Parte medico'),
            $this->state($data),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Lesion'));
            $this->call('FIDE_LESIONES_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la lesion.', [2]);
            return;
        }

        $this->call('FIDE_LESIONES_TB_INSERTAR_SP', $values, 'No fue posible crear la lesion.', [1]);
    }

    public function deleteLesion(array $data): void
    {
        $this->call('FIDE_LESIONES_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Lesion')], 'No fue posible desactivar la lesion.');
    }
}
