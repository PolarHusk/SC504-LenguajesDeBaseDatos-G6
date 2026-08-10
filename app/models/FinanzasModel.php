<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';

// Mapea la administración de facturas de inscripción a procedimientos del PACKAGE.
final class FinanzasModel extends OracleModel
{
    public function listFacturaciones(): array
    {
        return $this->listView('FIDE_FACTURACION_INSCRIPCIONES_V', 'ID_FACTURACION_INSCRIPCION');
    }

    public function saveFacturacion(array $data, bool $isUpdate): void
    {
        $values = [
            (int) $this->required($data, 'id_jugador', 'ID Jugador'),
            (int) $this->required($data, 'mes', 'Mes'),
            (int) $this->required($data, 'anio', 'Anio'),
            $this->required($data, 'monto', 'Monto'),
            $this->required($data, 'fecha_pago', 'Fecha pago'),
            $this->state($data),
            $this->required($data, 'observaciones', 'Observaciones'),
        ];

        if ($isUpdate) {
            array_unshift($values, (string) $this->required($data, 'id', 'ID Factura'));
            $this->call('FIDE_FACTURACION_INSCRIPCIONES_TB_MODIFICAR_SP', $values, 'No fue posible actualizar la facturacion.', [5]);
            return;
        }

        $this->call('FIDE_FACTURACION_INSCRIPCIONES_TB_INSERTAR_SP', $values, 'No fue posible crear la facturacion.', [4]);
    }

    public function deleteFacturacion(array $data): void
    {
        $this->call('FIDE_FACTURACION_INSCRIPCIONES_TB_ELIMINAR_SP', [(string) $this->required($data, 'id', 'ID Factura')], 'No fue posible desactivar la facturacion.');
    }
}
