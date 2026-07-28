<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';

final class CompetenciaModel extends OracleModel
{
    public function listPartidos(): array
    {
        return $this->listView('FIDE_PARTIDOS_V', 'ID_PARTIDO');
    }

    public function savePartido(array $data, bool $isUpdate): void
    {
        $values = [
            (int) $this->required($data, 'id_categoria', 'ID Categoria'),
            (int) $this->required($data, 'id_tipopartidos', 'ID Tipo partido'),
            (int) $this->required($data, 'id_temporada', 'ID Temporada'),
            $this->required($data, 'fecha', 'Fecha'),
            $this->required($data, 'rival', 'Rival'),
            (int) $this->required($data, 'goles_favor', 'Goles favor'),
            (int) $this->required($data, 'goles_contra', 'Goles contra'),
            (int) $this->required($data, 'id_sede', 'ID Sede'),
            $this->state($data),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Partido'));
            $this->call('FIDE_PARTIDOS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el partido.', [4]);
            return;
        }

        $this->call('FIDE_PARTIDOS_TB_INSERTAR_SP', $values, 'No fue posible crear el partido.', [3]);
    }

    public function deletePartido(array $data): void
    {
        $this->call('FIDE_PARTIDOS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Partido')], 'No fue posible desactivar el partido.');
    }

    public function listAsistencias(): array
    {
        return $this->listView('FIDE_ASISTENCIA_V', 'ID_PARTIDO');
    }

    public function saveAsistencia(array $data, bool $isUpdate): void
    {
        $values = [
            (int) $this->required($data, 'id_partido', 'ID Partido'),
            (int) $this->required($data, 'id_jugador', 'ID Jugador'),
            $this->state($data),
        ];

        $this->call(
            $isUpdate ? 'FIDE_ASISTENCIA_TB_MODIFICAR_SP' : 'FIDE_ASISTENCIA_TB_INSERTAR_SP',
            $values,
            'No fue posible guardar la asistencia.'
        );
    }

    public function deleteAsistencia(array $data): void
    {
        $this->call('FIDE_ASISTENCIA_TB_ELIMINAR_SP', [
            (int) $this->required($data, 'id_partido', 'ID Partido'),
            (int) $this->required($data, 'id_jugador', 'ID Jugador'),
        ], 'No fue posible desactivar la asistencia.');
    }

    public function listEstadisticasEquipo(): array
    {
        return $this->listView('FIDE_ESTADISTICA_EQUIPO_V', 'ID_ESTADISTICA_EQUIPO');
    }

    public function saveEstadisticaEquipo(array $data, bool $isUpdate): void
    {
        $values = [
            (int) $this->required($data, 'tiros', 'Tiros'),
            (int) $this->required($data, 'tiros_porteria', 'Tiros porteria'),
            (int) $this->required($data, 'pases_exitosos', 'Pases exitosos'),
            (int) $this->required($data, 'pases_fallidos', 'Pases fallidos'),
            (int) $this->required($data, 'posesion_balon', 'Posesion balon'),
            (int) $this->required($data, 'id_partido', 'ID Partido'),
            $this->state($data),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Estadistica equipo'));
            $this->call('FIDE_ESTADISTICA_EQUIPO_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la estadistica del equipo.');
            return;
        }

        $this->call('FIDE_ESTADISTICA_EQUIPO_TB_INSERTAR_SP', $values, 'No fue posible crear la estadistica del equipo.');
    }

    public function deleteEstadisticaEquipo(array $data): void
    {
        $this->call('FIDE_ESTADISTICA_EQUIPO_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Estadistica equipo')], 'No fue posible desactivar la estadistica del equipo.');
    }

    public function listEstadisticasJugador(): array
    {
        return $this->listView('FIDE_ESTADISTICA_JUGADOR_V', 'ID_ESTADISTICA_JUGADOR');
    }

    public function saveEstadisticaJugador(array $data, bool $isUpdate): void
    {
        $values = [
            (int) $this->required($data, 'id_jugador', 'ID Jugador'),
            (int) $this->required($data, 'id_partido', 'ID Partido'),
            (int) $this->required($data, 'pases', 'Pases'),
            (int) $this->required($data, 'pases_exitosos', 'Pases exitosos'),
            (int) $this->required($data, 'regates', 'Regates'),
            (int) $this->required($data, 'regates_exitosos', 'Regates exitosos'),
            (int) $this->required($data, 'tiros', 'Tiros'),
            (int) $this->required($data, 'tiros_exitosos', 'Tiros exitosos'),
            (int) $this->required($data, 'entradas', 'Entradas'),
            (int) $this->required($data, 'entradas_exitosas', 'Entradas exitosas'),
            (int) $this->required($data, 'minutos_jugados', 'Minutos jugados'),
            (int) $this->required($data, 'goles', 'Goles'),
            (int) $this->required($data, 'asistencias', 'Asistencias'),
            $this->state($data),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Estadistica jugador'));
            $this->call('FIDE_ESTADISTICA_JUGADOR_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la estadistica del jugador.');
            return;
        }

        $this->call('FIDE_ESTADISTICA_JUGADOR_TB_INSERTAR_SP', $values, 'No fue posible crear la estadistica del jugador.');
    }

    public function deleteEstadisticaJugador(array $data): void
    {
        $this->call('FIDE_ESTADISTICA_JUGADOR_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Estadistica jugador')], 'No fue posible desactivar la estadistica del jugador.');
    }

    public function listTarjetas(): array
    {
        return $this->listView('FIDE_TARJETAS_V', 'ID_PARTIDO');
    }

    public function saveTarjeta(array $data, bool $isUpdate): void
    {
        $values = [
            (int) $this->required($data, 'id_partido', 'ID Partido'),
            (int) $this->required($data, 'id_jugador', 'ID Jugador'),
            (int) $this->required($data, 'id_tipotarjeta', 'ID Tipo tarjeta'),
            (int) $this->required($data, 'cantidad_tarjetas', 'Cantidad tarjetas'),
            $this->state($data),
        ];

        $this->call(
            $isUpdate ? 'FIDE_TARJETAS_TB_MODIFICAR_SP' : 'FIDE_TARJETAS_TB_INSERTAR_SP',
            $values,
            'No fue posible guardar la tarjeta.'
        );
    }

    public function deleteTarjeta(array $data): void
    {
        $this->call('FIDE_TARJETAS_TB_ELIMINAR_SP', [
            (int) $this->required($data, 'id_partido', 'ID Partido'),
            (int) $this->required($data, 'id_jugador', 'ID Jugador'),
            (int) $this->required($data, 'id_tipotarjeta', 'ID Tipo tarjeta'),
        ], 'No fue posible desactivar la tarjeta.');
    }
}
