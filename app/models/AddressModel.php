<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';

// Encapsula los procedimientos de creación y modificación de direcciones exactas.
final class AddressModel extends OracleModel
{
    public function create(array $address): int
    {
        return $this->callReturning(
            'FIDE_DIRECCION_EXACTA_TB_INSERTAR_SP',
            [
                $this->required($address, 'otras_senias', 'Otras senias'),
                (int) $this->required($address, 'id_distrito', 'ID Distrito'),
                (int) $this->required($address, 'id_canton', 'ID Canton'),
                (int) $this->required($address, 'id_provincia', 'ID Provincia'),
                ESTADO_ACTIVO,
            ],
            'No fue posible guardar la direccion.'
        );
    }

    public function update(int $id, array $address): void
    {
        $this->call(
            'FIDE_DIRECCION_EXACTA_TB_MODIFICAR_SP',
            [
                $id,
                $this->required($address, 'otras_senias', 'Otras senias'),
                (int) $this->required($address, 'id_distrito', 'ID Distrito'),
                (int) $this->required($address, 'id_canton', 'ID Canton'),
                (int) $this->required($address, 'id_provincia', 'ID Provincia'),
                ESTADO_ACTIVO,
            ],
            'No fue posible actualizar la direccion.'
        );
    }
}
