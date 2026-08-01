<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ModuleController.php';
require_once dirname(__DIR__) . '/models/InventarioModel.php';

final class InventarioController extends ModuleController
{
    public function __construct(private InventarioModel $model)
    {
    }

    public function label(): string
    {
        return 'Inventario';
    }

    public function schema(): array
    {
        return [
            'categoria_inventario' => [
                'title' => 'Categorias de Inventario',
                'pkFields' => [['key' => 'id', 'column' => 'ID_CATEGORIAINVENTARIO', 'label' => 'ID Categoria Inventario', 'type' => 'number']],
                'fields' => [['key' => 'nombre', 'column' => 'NOMBRE_CATEGORIA_INVENTARIO', 'label' => 'Nombre de categoria inventario', 'type' => 'text']],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
            'articulos' => [
                'title' => 'Articulos',
                'pkFields' => [['key' => 'id', 'column' => 'ID_ARTICULO', 'label' => 'ID Articulo', 'type' => 'number']],
                'fields' => [
                    ['key' => 'id_categoriainventario', 'column' => 'ID_CATEGORIAINVENTARIO', 'displayColumn' => 'NOMBRE_CATEGORIA_INVENTARIO', 'label' => 'Categoria de inventario', 'type' => 'select', 'optionsTable' => 'categoria_inventario', 'optionValue' => 'ID_CATEGORIAINVENTARIO', 'optionLabel' => 'NOMBRE_CATEGORIA_INVENTARIO'],
                    ['key' => 'cantidad', 'column' => 'CANTIDAD', 'label' => 'Cantidad', 'type' => 'number'],
                    ['key' => 'nombre_articulo', 'column' => 'NOMBRE_ARTICULO', 'label' => 'Nombre articulo', 'type' => 'text'],
                ],
                'hasEstado' => true, 'statusColumn' => true, 'autoId' => true, 'hasAddress' => false,
            ],
        ];
    }

    protected function listTable(string $table): array
    {
        return match ($table) {
            'categoria_inventario' => $this->model->listCategoriasInventario(),
            'articulos' => $this->model->listArticulos(),
        };
    }

    protected function saveTable(string $table, array $data, bool $isUpdate): void
    {
        match ($table) {
            'categoria_inventario' => $this->model->saveCategoriaInventario($data, $isUpdate),
            'articulos' => $this->model->saveArticulo($data, $isUpdate),
        };
    }

    protected function deleteTable(string $table, array $data): void
    {
        match ($table) {
            'categoria_inventario' => $this->model->deleteCategoriaInventario($data),
            'articulos' => $this->model->deleteArticulo($data),
        };
    }
}
