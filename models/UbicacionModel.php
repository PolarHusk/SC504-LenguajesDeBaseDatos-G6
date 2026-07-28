<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';
require_once dirname(__DIR__) . '/models/AddressModel.php';

final class UbicacionModel extends OracleModel
{
    public function listSedes(): array
    {
        return $this->listView('FIDE_SEDES_V', 'ID_SEDE', true, 'FIDE_SEDES_CON_DIRECCION_V');
    }

    public function saveSede(array $data, bool $isUpdate): void
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
            $this->required($data, 'nombre_sede', 'Nombre sede'),
            $addressId,
            $this->state($data),
        ];

        if ($isUpdate) {
            array_unshift($values, (int) $this->required($data, 'id', 'ID Sede'));
            $this->call('FIDE_SEDES_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la sede.');
            return;
        }

        $this->call('FIDE_SEDES_TB_INSERTAR_SP', $values, 'No fue posible crear la sede.');
    }

    public function deleteSede(array $data): void
    {
        $this->call('FIDE_SEDES_TB_ELIMINAR_SP', [(int) $this->required($data, 'id', 'ID Sede')], 'No fue posible desactivar la sede.');
    }
}
