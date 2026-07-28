<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';

final class ConfiguracionModel extends OracleModel
{
    public function listEstados(): array
    {
        return $this->listView('FIDE_ESTADOS_V', 'ID_ESTADO', false);
    }

    public function saveEstado(array $data, bool $isUpdate): void
    {
        if ($isUpdate) {
            $this->call('FIDE_ESTADOS_TB_MODIFICAR_SP', [(int) $this->required($data, 'id', 'ID Estado'), $this->required($data, 'nombre', 'Nombre del estado')], 'No fue posible actualizar el estado.');
            return;
        }

        $this->call('FIDE_ESTADOS_TB_INSERTAR_SP', [$this->required($data, 'nombre', 'Nombre del estado')], 'No fue posible crear el estado.');
    }

    public function deleteEstado(array $data): void
    {
        $this->call('FIDE_ESTADOS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Estado'), 'Inactivo'], 'No fue posible desactivar el estado.');
    }

    public function listTipoTarjetas(): array
    {
        return $this->listView('FIDE_TIPOTARJETAS_V', 'ID_TIPOTARJETA');
    }

    public function saveTipoTarjeta(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Color'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Tipo Tarjeta'));
            $this->call('FIDE_TIPOTARJETAS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el tipo de tarjeta.');
            return;
        }

        $this->call('FIDE_TIPOTARJETAS_TB_INSERTAR_SP', $values, 'No fue posible crear el tipo de tarjeta.');
    }

    public function deleteTipoTarjeta(array $data): void
    {
        $this->call('FIDE_TIPOTARJETAS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Tipo Tarjeta'), ESTADO_INACTIVO], 'No fue posible desactivar el tipo de tarjeta.');
    }

    public function listCantones(): array
    {
        return $this->listView('FIDE_CANTONES_V', 'ID_CANTON');
    }

    public function saveCanton(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Nombre de canton'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Canton'));
            $this->call('FIDE_CANTONES_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el canton.');
            return;
        }

        $this->call('FIDE_CANTONES_TB_INSERTAR_SP', $values, 'No fue posible crear el canton.');
    }

    public function deleteCanton(array $data): void
    {
        $this->call('FIDE_CANTONES_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Canton'), ESTADO_INACTIVO], 'No fue posible desactivar el canton.');
    }

    public function listCargos(): array
    {
        return $this->listView('FIDE_CARGOS_V', 'ID_CARGO');
    }

    public function saveCargo(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Nombre de cargo'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Cargo'));
            $this->call('FIDE_CARGOS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el cargo.');
            return;
        }

        $this->call('FIDE_CARGOS_TB_INSERTAR_SP', $values, 'No fue posible crear el cargo.');
    }

    public function deleteCargo(array $data): void
    {
        $this->call('FIDE_CARGOS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Cargo'), ESTADO_INACTIVO], 'No fue posible desactivar el cargo.');
    }

    public function listCategorias(): array
    {
        return $this->listView('FIDE_CATEGORIAS_V', 'ID_CATEGORIA');
    }

    public function saveCategoria(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Nombre de categoria'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Categoria'));
            $this->call('FIDE_CATEGORIAS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la categoria.');
            return;
        }

        $this->call('FIDE_CATEGORIAS_TB_INSERTAR_SP', $values, 'No fue posible crear la categoria.');
    }

    public function deleteCategoria(array $data): void
    {
        $this->call('FIDE_CATEGORIAS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Categoria'), ESTADO_INACTIVO], 'No fue posible desactivar la categoria.');
    }

    public function listEspecialidades(): array
    {
        return $this->listView('FIDE_ESPECIALIDADES_V', 'ID_ESPECIALIDAD');
    }

    public function saveEspecialidad(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Nombre de especialidad'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Especialidad'));
            $this->call('FIDE_ESPECIALIDADES_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la especialidad.');
            return;
        }

        $this->call('FIDE_ESPECIALIDADES_TB_INSERTAR_SP', $values, 'No fue posible crear la especialidad.');
    }

    public function deleteEspecialidad(array $data): void
    {
        $this->call('FIDE_ESPECIALIDADES_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Especialidad'), ESTADO_INACTIVO], 'No fue posible desactivar la especialidad.');
    }

    public function listPosiciones(): array
    {
        return $this->listView('FIDE_POSICIONES_V', 'ID_POSICION');
    }

    public function savePosicion(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Nombre de posicion'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Posicion'));
            $this->call('FIDE_POSICIONES_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la posicion.');
            return;
        }

        $this->call('FIDE_POSICIONES_TB_INSERTAR_SP', $values, 'No fue posible crear la posicion.');
    }

    public function deletePosicion(array $data): void
    {
        $this->call('FIDE_POSICIONES_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Posicion'), ESTADO_INACTIVO], 'No fue posible desactivar la posicion.');
    }

    public function listProvincias(): array
    {
        return $this->listView('FIDE_PROVINCIAS_V', 'ID_PROVINCIA');
    }

    public function saveProvincia(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Nombre de provincia'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Provincia'));
            $this->call('FIDE_PROVINCIAS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la provincia.');
            return;
        }

        $this->call('FIDE_PROVINCIAS_TB_INSERTAR_SP', $values, 'No fue posible crear la provincia.');
    }

    public function deleteProvincia(array $data): void
    {
        $this->call('FIDE_PROVINCIAS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Provincia'), ESTADO_INACTIVO], 'No fue posible desactivar la provincia.');
    }

    public function listDistritos(): array
    {
        return $this->listView('FIDE_DISTRITOS_V', 'ID_DISTRITO');
    }

    public function saveDistrito(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Nombre de distrito'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Distrito'));
            $this->call('FIDE_DISTRITOS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el distrito.');
            return;
        }

        $this->call('FIDE_DISTRITOS_TB_INSERTAR_SP', $values, 'No fue posible crear el distrito.');
    }

    public function deleteDistrito(array $data): void
    {
        $this->call('FIDE_DISTRITOS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Distrito'), ESTADO_INACTIVO], 'No fue posible desactivar el distrito.');
    }

    public function listTipoLesiones(): array
    {
        return $this->listView('FIDE_TIPOLESION_V', 'ID_TIPOLESION');
    }

    public function saveTipoLesion(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Nombre tipo lesion'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Tipo Lesion'));
            $this->call('FIDE_TIPOLESION_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el tipo de lesion.');
            return;
        }

        $this->call('FIDE_TIPOLESION_TB_INSERTAR_SP', $values, 'No fue posible crear el tipo de lesion.');
    }

    public function deleteTipoLesion(array $data): void
    {
        $this->call('FIDE_TIPOLESION_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Tipo Lesion')], 'No fue posible desactivar el tipo de lesion.');
    }

    public function listTipoPartidos(): array
    {
        return $this->listView('FIDE_TIPO_PARTIDOS_V', 'ID_TIPOPARTIDOS');
    }

    public function saveTipoPartido(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Tipo de partido'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Tipo Partido'));
            $this->call('FIDE_TIPO_PARTIDOS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el tipo de partido.');
            return;
        }

        $this->call('FIDE_TIPO_PARTIDOS_TB_INSERTAR_SP', $values, 'No fue posible crear el tipo de partido.');
    }

    public function deleteTipoPartido(array $data): void
    {
        $this->call('FIDE_TIPO_PARTIDOS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Tipo Partido')], 'No fue posible desactivar el tipo de partido.');
    }

    public function listTemporadas(): array
    {
        return $this->listView('FIDE_TEMPORADAS_V', 'ID_TEMPORADA');
    }

    public function saveTemporada(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'tipo_temporada', 'Tipo temporada'), (int) $this->required($data, 'anio', 'Anio'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Temporada'));
            $this->call('FIDE_TEMPORADAS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la temporada.');
            return;
        }

        $this->call('FIDE_TEMPORADAS_TB_INSERTAR_SP', $values, 'No fue posible crear la temporada.');
    }

    public function deleteTemporada(array $data): void
    {
        $this->call('FIDE_TEMPORADAS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Temporada')], 'No fue posible desactivar la temporada.');
    }
}
