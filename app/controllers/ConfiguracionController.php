<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ModuleController.php';
require_once dirname(__DIR__) . '/models/ConfiguracionModel.php';

// CRUD de catálogos de configuración: ubicaciones, categorías, cargos, temporadas y tipos.
final class ConfiguracionController extends ModuleController
{
    public function __construct(private ConfiguracionModel $model)
    {
    }

    public function label(): string
    {
        return 'Configuracion';
    }

    public function schema(): array
    {
        return [
            'estados' => $this->table('Estados', 'ID_ESTADO', 'ID Estado', [['nombre', 'NOMBRE_ESTADO', 'Nombre del estado', 'text']], false, false),
            'provincias' => $this->table('Provincias', 'ID_PROVINCIA', 'ID Provincia', [['nombre', 'NOMBRE_PROVINCIA', 'Nombre de provincia', 'text']], true),
            'cantones' => $this->table('Cantones', 'ID_CANTON', 'ID Canton', [['nombre', 'NOMBRE_CANTON', 'Nombre de canton', 'text']], true),
            'distritos' => $this->table('Distritos', 'ID_DISTRITO', 'ID Distrito', [['nombre', 'NOMBRE_DISTRITO', 'Nombre de distrito', 'text']], true),
            'categorias' => $this->table('Categorias Deportivas', 'ID_CATEGORIA', 'ID Categoria', [['nombre', 'NOMBRE_CATEGORIA', 'Nombre de categoria', 'text']], true),
            'posiciones' => $this->table('Posiciones', 'ID_POSICION', 'ID Posicion', [['nombre', 'NOMBRE_POSICION', 'Nombre de posicion', 'text']], true),
            'cargos' => $this->table('Cargos', 'ID_CARGO', 'ID Cargo', [['nombre', 'NOMBRE_CARGO', 'Nombre de cargo', 'text']], true),
            'especialidades' => $this->table('Especialidades', 'ID_ESPECIALIDAD', 'ID Especialidad', [['nombre', 'NOMBRE_ESPECIALIDAD', 'Nombre de especialidad', 'text']], true),
            'tipotarjetas' => $this->table('Tipo de Tarjetas', 'ID_TIPOTARJETA', 'ID Tipo Tarjeta', [['nombre', 'COLOR', 'Color', 'text']], true),
            'tipolesion' => $this->table('Tipo Lesion', 'ID_TIPOLESION', 'ID Tipo Lesion', [['nombre', 'NOMBRE_TIPO_LESION', 'Nombre tipo lesion', 'text']], true),
            'tipo_partidos' => $this->table('Tipo Partidos', 'ID_TIPOPARTIDOS', 'ID Tipo Partido', [['nombre', 'TIPO_DE_PARTIDO', 'Tipo de partido', 'text']], true),
            'temporadas' => $this->table('Temporadas', 'ID_TEMPORADA', 'ID Temporada', [['tipo_temporada', 'TIPO_TEMPORADA', 'Tipo temporada', 'text'], ['anio', 'ANIO', 'Anio', 'number']], true),
        ];
    }

    public function states(): array
    {
        return $this->model->listEstados();
    }

    protected function listTable(string $table): array
    {
        return match ($table) {
            'estados' => $this->model->listEstados(),
            'provincias' => $this->model->listProvincias(),
            'cantones' => $this->model->listCantones(),
            'distritos' => $this->model->listDistritos(),
            'categorias' => $this->model->listCategorias(),
            'posiciones' => $this->model->listPosiciones(),
            'cargos' => $this->model->listCargos(),
            'especialidades' => $this->model->listEspecialidades(),
            'tipotarjetas' => $this->model->listTipoTarjetas(),
            'tipolesion' => $this->model->listTipoLesiones(),
            'tipo_partidos' => $this->model->listTipoPartidos(),
            'temporadas' => $this->model->listTemporadas(),
        };
    }

    protected function saveTable(string $table, array $data, bool $isUpdate): void
    {
        match ($table) {
            'estados' => $this->model->saveEstado($data, $isUpdate),
            'provincias' => $this->model->saveProvincia($data, $isUpdate),
            'cantones' => $this->model->saveCanton($data, $isUpdate),
            'distritos' => $this->model->saveDistrito($data, $isUpdate),
            'categorias' => $this->model->saveCategoria($data, $isUpdate),
            'posiciones' => $this->model->savePosicion($data, $isUpdate),
            'cargos' => $this->model->saveCargo($data, $isUpdate),
            'especialidades' => $this->model->saveEspecialidad($data, $isUpdate),
            'tipotarjetas' => $this->model->saveTipoTarjeta($data, $isUpdate),
            'tipolesion' => $this->model->saveTipoLesion($data, $isUpdate),
            'tipo_partidos' => $this->model->saveTipoPartido($data, $isUpdate),
            'temporadas' => $this->model->saveTemporada($data, $isUpdate),
        };
    }

    protected function deleteTable(string $table, array $data): void
    {
        match ($table) {
            'estados' => $this->model->deleteEstado($data),
            'provincias' => $this->model->deleteProvincia($data),
            'cantones' => $this->model->deleteCanton($data),
            'distritos' => $this->model->deleteDistrito($data),
            'categorias' => $this->model->deleteCategoria($data),
            'posiciones' => $this->model->deletePosicion($data),
            'cargos' => $this->model->deleteCargo($data),
            'especialidades' => $this->model->deleteEspecialidad($data),
            'tipotarjetas' => $this->model->deleteTipoTarjeta($data),
            'tipolesion' => $this->model->deleteTipoLesion($data),
            'tipo_partidos' => $this->model->deleteTipoPartido($data),
            'temporadas' => $this->model->deleteTemporada($data),
        };
    }

    private function table(string $title, string $column, string $label, array $fields, bool $hasEstado = true, bool $statusColumn = true): array
    {
        return [
            'title' => $title,
            'pkFields' => [['key' => 'id', 'column' => $column, 'label' => $label, 'type' => 'number']],
            'fields' => array_map(static fn(array $field): array => ['key' => $field[0], 'column' => $field[1], 'label' => $field[2], 'type' => $field[3]], $fields),
            'hasEstado' => $hasEstado,
            'statusColumn' => $statusColumn,
            'autoId' => true,
            'hasAddress' => false,
        ];
    }
}
