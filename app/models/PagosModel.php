<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';

final class PagosModel extends OracleModel
{
    public function searchPlayers(string $cedula, string $nombre): array
    {
        $where = ['J.ID_ESTADO = 1'];
        $parameters = [];
        if ($cedula !== '') {
            $where[] = 'J.CEDULA LIKE :cedula';
            $parameters[':cedula'] = '%' . $cedula . '%';
        }
        if ($nombre !== '') {
            $where[] = "UPPER(J.NOMBRE || ' ' || J.APELLIDO_PATERNO || ' ' || NVL(J.APELLIDO_MATERNO, '')) LIKE UPPER(:nombre)";
            $parameters[':nombre'] = '%' . $nombre . '%';
        }

        return $this->query(
            "SELECT J.ID_JUGADOR, J.CEDULA,
                    TRIM(J.NOMBRE || ' ' || J.APELLIDO_PATERNO || ' ' || NVL(J.APELLIDO_MATERNO, '')) AS NOMBRE_COMPLETO,
                    C.NOMBRE_CATEGORIA
             FROM FIDE_JUGADORES_TB J
             INNER JOIN FIDE_CATEGORIAS_TB C ON C.ID_CATEGORIA = J.ID_CATEGORIA
             WHERE " . implode(' AND ', $where) . ' ORDER BY J.NOMBRE, J.APELLIDO_PATERNO',
            $parameters,
            'No fue posible buscar los jugadores.'
        );
    }

    public function playerPaymentDetail(int $playerId): array
    {
        $players = $this->query(
            "SELECT J.ID_JUGADOR, J.CEDULA,
                    TRIM(J.NOMBRE || ' ' || J.APELLIDO_PATERNO || ' ' || NVL(J.APELLIDO_MATERNO, '')) AS NOMBRE_COMPLETO,
                    C.NOMBRE_CATEGORIA
             FROM FIDE_JUGADORES_TB J
             INNER JOIN FIDE_CATEGORIAS_TB C ON C.ID_CATEGORIA = J.ID_CATEGORIA
             WHERE J.ID_JUGADOR = :player_id AND J.ID_ESTADO = 1",
            [':player_id' => $playerId],
            'No fue posible consultar el jugador.'
        );
        if (!$players) throw new InvalidArgumentException('No se encontró el jugador solicitado.');

        $payments = $this->query(
            "SELECT F.ID_FACTURACION_INSCRIPCION, F.MES, F.ANIO, F.MONTO,
                    TO_CHAR(F.FECHA_PAGO, 'YYYY-MM-DD') AS FECHA_PAGO,
                    F.OBSERVACIONES, E.NOMBRE_ESTADO
             FROM FIDE_FACTURACION_INSCRIPCIONES_TB F
             INNER JOIN FIDE_ESTADOS_TB E ON E.ID_ESTADO = F.ID_ESTADO
             WHERE F.ID_JUGADOR = :player_id
             ORDER BY F.ANIO DESC, F.MES DESC, F.ID_FACTURACION_INSCRIPCION DESC",
            [':player_id' => $playerId],
            'No fue posible consultar el historial de pagos.'
        );
        return ['player' => $players[0], 'payments' => $payments];
    }

    public function registerPayment(array $data): array
    {
        $playerId = (int) $this->required($data, 'jugador_id', 'el jugador');
        $month = (int) $this->required($data, 'mes', 'el mes');
        $year = (int) $this->required($data, 'anio', 'el año');
        $amount = (float) $this->required($data, 'monto', 'el monto');
        $paymentMethod = trim((string) $this->required($data, 'metodo_pago', 'el método de pago'));
        $reference = trim((string) ($data['referencia'] ?? ''));
        $notes = trim((string) ($data['observaciones'] ?? ''));

        if (!in_array($paymentMethod, ['Transferencia', 'SINPE Móvil'], true)) throw new InvalidArgumentException('Seleccione un método de pago válido.');
        if ($month < 1 || $month > 12 || $year < 2000 || $amount <= 0) throw new InvalidArgumentException('Verifique el período y el monto del pago.');

        $detail = $this->playerPaymentDetail($playerId);
        $existingInvoiceId = null;
        foreach ($detail['payments'] as $payment) {
            if ((int) $payment['MES'] === $month && (int) $payment['ANIO'] === $year && strtoupper((string) $payment['NOMBRE_ESTADO']) === 'PAGADO') {
                throw new InvalidArgumentException('Este período ya aparece como pagado para el jugador.');
            }
            if ((int) $payment['MES'] === $month && (int) $payment['ANIO'] === $year) {
                $existingInvoiceId = (int) $payment['ID_FACTURACION_INSCRIPCION'];
            }
        }

        $observation = 'Método: ' . $paymentMethod;
        if ($reference !== '') $observation .= ' | Referencia: ' . $reference;
        if ($notes !== '') $observation .= ' | ' . $notes;
        $paymentDate = date('Y-m-d');
        if ($existingInvoiceId) {
            $invoiceId = $existingInvoiceId;
            $this->call(
                'FIDE_FACTURACION_INSCRIPCIONES_TB_MODIFICAR_SP',
                [$invoiceId, $playerId, $month, $year, $amount, $paymentDate, 8, $observation],
                'No fue posible actualizar el pago pendiente.',
                [5]
            );
        } else {
            $invoiceId = $this->nextInvoiceId();
            $this->call(
                'FIDE_FACTURACION_INSCRIPCIONES_TB_INSERTAR_SP',
                [$invoiceId, $playerId, $month, $year, $amount, $paymentDate, 8, $observation],
                'No fue posible registrar el pago.',
                [5]
            );
        }

        return [
            'id' => $invoiceId,
            'player' => $detail['player'],
            'mes' => $month,
            'anio' => $year,
            'monto' => $amount,
            'fecha_pago' => $paymentDate,
            'metodo_pago' => $paymentMethod,
            'referencia' => $reference,
            'observaciones' => $notes,
        ];
    }

    private function nextInvoiceId(): int
    {
        $record = $this->query('SELECT ID_FACTURACION_INSCRIPCION_SEQ.NEXTVAL AS ID FROM DUAL', [], 'No fue posible generar el número de factura.');
        return (int) ($record[0]['ID'] ?? 0);
    }
}
