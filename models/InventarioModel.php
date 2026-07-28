<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';

final class InventarioModel extends OracleModel
{
    public function listCategoriasInventario(): array
    {
        return $this->listView('FIDE_CATEGORIA_INVENTARIO_V', 'ID_CATEGORIAINVENTARIO');
    }

    public function saveCategoriaInventario(array $data, bool $isUpdate): void
    {
        $values = [$this->required($data, 'nombre', 'Nombre de categoria inventario'), $this->state($data)];
        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Categoria Inventario'));
            $this->call('FIDE_CATEGORIA_INVENTARIO_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la categoria de inventario.');
            return;
        }

        $this->call('FIDE_CATEGORIA_INVENTARIO_TB_INSERTAR_SP', $values, 'No fue posible crear la categoria de inventario.');
    }

    public function deleteCategoriaInventario(array $data): void
    {
        $this->call('FIDE_CATEGORIA_INVENTARIO_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Categoria Inventario'), ESTADO_INACTIVO], 'No fue posible desactivar la categoria de inventario.');
    }

    public function listArticulos(): array
    {
        return $this->listView('FIDE_ARTICULOS_V', 'ID_ARTICULO');
    }

    public function saveArticulo(array $data, bool $isUpdate): void
    {
        $values = [
            (int) $this->required($data, 'id_categoriainventario', 'ID Categoria inventario'),
            $this->state($data),
            (int) $this->required($data, 'cantidad', 'Cantidad'),
            $this->required($data, 'nombre_articulo', 'Nombre articulo'),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Articulo'));
            $this->call('FIDE_ARTICULOS_TB_MODIFICAR_SP', $values, 'No fue posible actualizar el articulo.');
            return;
        }

        $this->call('FIDE_ARTICULOS_TB_INSERTAR_SP', $values, 'No fue posible crear el articulo.');
    }

    public function deleteArticulo(array $data): void
    {
        $this->call('FIDE_ARTICULOS_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Articulo')], 'No fue posible desactivar el articulo.');
    }
}
