<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ModuleController.php';
require_once dirname(__DIR__) . '/models/CompetenciaModel.php';

final class CompetenciaController extends ModuleController
{
    public function __construct(private CompetenciaModel $model)
    {
    }

    public function label(): string
    {
        return 'Competencia';
    }

    public function schema(): array
    {
        return [
            'partidos' => [
                'title' => 'Partidos',
                'pkFields' => [['key' => 'id', 'column' => 'ID_PARTIDO', 'label' => 'ID Partido', 'type' => 'number']],
                'fields' => [
                    ['key' => 'id_categoria', 'column' => 'ID_CATEGORIA', 'label' => 'Categoria', 'type' => 'select', 'optionsTable' => 'categorias', 'optionValue' => 'ID_CATEGORIA', 'optionLabel' => 'NOMBRE_CATEGORIA', 'table' => false],
                    ['key' => 'id_tipopartidos', 'column' => 'ID_TIPOPARTIDOS', 'label' => 'Tipo de partido', 'type' => 'select', 'optionsTable' => 'tipo_partidos', 'optionValue' => 'ID_TIPOPARTIDOS', 'optionLabel' => 'TIPO_DE_PARTIDO', 'table' => false],
                    ['key' => 'id_temporada', 'column' => 'ID_TEMPORADA', 'label' => 'Temporada', 'type' => 'select', 'optionsTable' => 'temporadas', 'optionValue' => 'ID_TEMPORADA', 'optionLabel' => 'TEMPORADA_OPCION', 'optionLabelParts' => ['TIPO_TEMPORADA', 'ANIO'], 'table' => false],
                    ['key' => 'fecha', 'column' => 'FECHA', 'label' => 'Fecha', 'type' => 'date'],
                    ['key' => 'rival', 'column' => 'RIVAL', 'label' => 'Rival', 'type' => 'text'],
                    ['key' => 'goles_favor', 'column' => 'GOLES_FAVOR', 'label' => 'Goles favor', 'type' => 'number'],
                    ['key' => 'goles_contra', 'column' => 'GOLES_CONTRA', 'label' => 'Goles contra', 'type' => 'number'],
                    ['key' => 'id_sede', 'column' => 'ID_SEDE', 'label' => 'Sede', 'type' => 'select', 'optionsTable' => 'sedes', 'optionValue' => 'ID_SEDE', 'optionLabel' => 'NOMBRE_SEDE', 'table' => false],
                ],
                'displayFields' => [
                    ['key' => 'categoria', 'column' => 'CATEGORIA', 'label' => 'Categoria', 'type' => 'text'],
                    ['key' => 'tipo_partido', 'column' => 'TIPO_PARTIDO', 'label' => 'Tipo de partido', 'type' => 'text'],
                    ['key' => 'temporada', 'column' => 'TEMPORADA', 'label' => 'Temporada', 'type' => 'text'],
                    ['key' => 'sede', 'column' => 'SEDE', 'label' => 'Sede', 'type' => 'text'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
            'asistencia' => [
                'title' => 'Asistencia',
                'pkFields' => [
                    ['key' => 'id_partido', 'column' => 'ID_PARTIDO', 'label' => 'Partido', 'type' => 'select', 'optionsTable' => 'partidos', 'optionValue' => 'ID_PARTIDO', 'optionLabel' => 'PARTIDO_OPCION', 'optionLabelParts' => ['FECHA', 'RIVAL', 'TEMPORADA'], 'table' => false],
                    ['key' => 'id_jugador', 'column' => 'ID_JUGADOR', 'label' => 'Jugador', 'type' => 'select', 'optionsTable' => 'jugadores', 'optionValue' => 'ID_JUGADOR', 'optionLabel' => 'JUGADOR_OPCION', 'table' => false],
                ],
                'fields' => [],
                'displayFields' => [
                    ['key' => 'partido', 'column' => 'PARTIDO', 'label' => 'Partido', 'type' => 'text'],
                    ['key' => 'jugador', 'column' => 'JUGADOR', 'label' => 'Jugador', 'type' => 'text'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => false, 'hasAddress' => false,
            ],
            'estadistica_equipo' => [
                'title' => 'Estadistica Equipo',
                'pkFields' => [['key' => 'id', 'column' => 'ID_ESTADISTICA_EQUIPO', 'label' => 'ID Estadistica equipo', 'type' => 'number']],
                'fields' => [
                    ['key' => 'tiros', 'column' => 'TIROS', 'label' => 'Tiros', 'type' => 'number'],
                    ['key' => 'tiros_porteria', 'column' => 'TIROS_PORTERIA', 'label' => 'Tiros porteria', 'type' => 'number'],
                    ['key' => 'pases_exitosos', 'column' => 'PASES_EXITOSOS', 'label' => 'Pases exitosos', 'type' => 'number'],
                    ['key' => 'pases_fallidos', 'column' => 'PASES_FALLIDOS', 'label' => 'Pases fallidos', 'type' => 'number'],
                    ['key' => 'posesion_balon', 'column' => 'POSESION_BALON', 'label' => 'Posesion balon', 'type' => 'number'],
                    ['key' => 'id_partido', 'column' => 'ID_PARTIDO', 'label' => 'Partido', 'type' => 'select', 'optionsTable' => 'partidos', 'optionValue' => 'ID_PARTIDO', 'optionLabel' => 'PARTIDO_OPCION', 'optionLabelParts' => ['FECHA', 'RIVAL', 'TEMPORADA'], 'table' => false],
                ],
                'displayFields' => [['key' => 'partido', 'column' => 'PARTIDO', 'label' => 'Partido', 'type' => 'text']],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
            'estadistica_jugador' => [
                'title' => 'Estadistica Jugador',
                'pkFields' => [['key' => 'id', 'column' => 'ID_ESTADISTICA_JUGADOR', 'label' => 'ID Estadistica jugador', 'type' => 'number']],
                'fields' => [
                    ['key' => 'id_jugador', 'column' => 'ID_JUGADOR', 'label' => 'Jugador', 'type' => 'select', 'optionsTable' => 'jugadores', 'optionValue' => 'ID_JUGADOR', 'optionLabel' => 'JUGADOR_OPCION', 'table' => false],
                    ['key' => 'id_partido', 'column' => 'ID_PARTIDO', 'label' => 'Partido', 'type' => 'select', 'optionsTable' => 'partidos', 'optionValue' => 'ID_PARTIDO', 'optionLabel' => 'PARTIDO_OPCION', 'optionLabelParts' => ['FECHA', 'RIVAL', 'TEMPORADA'], 'table' => false],
                    ['key' => 'pases', 'column' => 'PASES', 'label' => 'Pases', 'type' => 'number'],
                    ['key' => 'pases_exitosos', 'column' => 'PASES_EXITOSOS', 'label' => 'Pases exitosos', 'type' => 'number'],
                    ['key' => 'regates', 'column' => 'REGATES', 'label' => 'Regates', 'type' => 'number'],
                    ['key' => 'regates_exitosos', 'column' => 'REGATES_EXITOSOS', 'label' => 'Regates exitosos', 'type' => 'number'],
                    ['key' => 'tiros', 'column' => 'TIROS', 'label' => 'Tiros', 'type' => 'number'],
                    ['key' => 'tiros_exitosos', 'column' => 'TIROS_EXITOSOS', 'label' => 'Tiros exitosos', 'type' => 'number'],
                    ['key' => 'entradas', 'column' => 'ENTRADAS', 'label' => 'Entradas', 'type' => 'number'],
                    ['key' => 'entradas_exitosas', 'column' => 'ENTRADAS_EXITOSAS', 'label' => 'Entradas exitosas', 'type' => 'number'],
                    ['key' => 'minutos_jugados', 'column' => 'MINUTOS_JUGADOS', 'label' => 'Minutos jugados', 'type' => 'number'],
                    ['key' => 'goles', 'column' => 'GOLES', 'label' => 'Goles', 'type' => 'number'],
                    ['key' => 'asistencias', 'column' => 'ASISTENCIAS', 'label' => 'Asistencias', 'type' => 'number'],
                ],
                'displayFields' => [
                    ['key' => 'jugador', 'column' => 'JUGADOR', 'label' => 'Jugador', 'type' => 'text'],
                    ['key' => 'partido', 'column' => 'PARTIDO', 'label' => 'Partido', 'type' => 'text'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
            'tarjetas' => [
                'title' => 'Tarjetas',
                'pkFields' => [
                    ['key' => 'id_partido', 'column' => 'ID_PARTIDO', 'label' => 'Partido', 'type' => 'select', 'optionsTable' => 'partidos', 'optionValue' => 'ID_PARTIDO', 'optionLabel' => 'PARTIDO_OPCION', 'optionLabelParts' => ['FECHA', 'RIVAL', 'TEMPORADA'], 'table' => false],
                    ['key' => 'id_jugador', 'column' => 'ID_JUGADOR', 'label' => 'Jugador', 'type' => 'select', 'optionsTable' => 'jugadores', 'optionValue' => 'ID_JUGADOR', 'optionLabel' => 'JUGADOR_OPCION', 'table' => false],
                    ['key' => 'id_tipotarjeta', 'column' => 'ID_TIPOTARJETA', 'label' => 'Tipo de tarjeta', 'type' => 'select', 'optionsTable' => 'tipotarjetas', 'optionValue' => 'ID_TIPOTARJETA', 'optionLabel' => 'COLOR', 'table' => false],
                ],
                'fields' => [['key' => 'cantidad_tarjetas', 'column' => 'CANTIDAD_TARJETAS', 'label' => 'Cantidad tarjetas', 'type' => 'number']],
                'displayFields' => [
                    ['key' => 'partido', 'column' => 'PARTIDO', 'label' => 'Partido', 'type' => 'text'],
                    ['key' => 'jugador', 'column' => 'JUGADOR', 'label' => 'Jugador', 'type' => 'text'],
                    ['key' => 'tipo_tarjeta', 'column' => 'TIPO_TARJETA', 'label' => 'Tipo de tarjeta', 'type' => 'text'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => false, 'hasAddress' => false,
            ],
        ];
    }

    protected function listTable(string $table): array
    {
        return match ($table) {
            'partidos' => $this->model->listPartidos(),
            'asistencia' => $this->model->listAsistencias(),
            'estadistica_equipo' => $this->model->listEstadisticasEquipo(),
            'estadistica_jugador' => $this->model->listEstadisticasJugador(),
            'tarjetas' => $this->model->listTarjetas(),
        };
    }

    protected function saveTable(string $table, array $data, bool $isUpdate): void
    {
        match ($table) {
            'partidos' => $this->model->savePartido($data, $isUpdate),
            'asistencia' => $this->model->saveAsistencia($data, $isUpdate),
            'estadistica_equipo' => $this->model->saveEstadisticaEquipo($data, $isUpdate),
            'estadistica_jugador' => $this->model->saveEstadisticaJugador($data, $isUpdate),
            'tarjetas' => $this->model->saveTarjeta($data, $isUpdate),
        };
    }

    protected function deleteTable(string $table, array $data): void
    {
        match ($table) {
            'partidos' => $this->model->deletePartido($data),
            'asistencia' => $this->model->deleteAsistencia($data),
            'estadistica_equipo' => $this->model->deleteEstadisticaEquipo($data),
            'estadistica_jugador' => $this->model->deleteEstadisticaJugador($data),
            'tarjetas' => $this->model->deleteTarjeta($data),
        };
    }
}
