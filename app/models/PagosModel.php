<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';

final class PagosModel extends OracleModel
{
    public function searchPlayers(string $cedula, string $nombre): array
    {
        $cedula = $this->normalize($cedula);
        $nombre = $this->normalize($nombre);

        return array_values(array_filter(array_map([$this, 'playerRecord'], $this->listView('FIDE_JUGADORES_V', 'ID_JUGADOR', false)), function (array $player) use ($cedula, $nombre): bool {
            return (int) ($player['ID_ESTADO'] ?? 0) === ESTADO_ACTIVO
                && ($cedula === '' || str_contains($this->normalize((string) $player['CEDULA']), $cedula))
                && ($nombre === '' || str_contains($this->normalize((string) $player['NOMBRE_COMPLETO']), $nombre));
        }));
    }

    public function playerPaymentDetail(int $playerId): array
    {
        $players = array_values(array_filter(array_map([$this, 'playerRecord'], $this->listView('FIDE_JUGADORES_V', 'ID_JUGADOR', false)), fn (array $player): bool => (int) ($player['ID_JUGADOR'] ?? 0) === $playerId && (int) ($player['ID_ESTADO'] ?? 0) === ESTADO_ACTIVO));
        if (!$players) {
            throw new InvalidArgumentException('No se encontró el jugador solicitado.');
        }

        $payments = array_values(array_filter(
            $this->listView('FIDE_FACTURACION_INSCRIPCIONES_PENDIENTES_V', 'ID_FACTURACION_INSCRIPCION'),
            fn (array $payment): bool => (int) ($payment['ID_JUGADOR'] ?? 0) === $playerId
        ));

        return ['player' => $players[0], 'payments' => $payments];
    }

    public function registerPayment(array $data): array
    {
        $playerId = (int) $this->required($data, 'jugador_id', 'el jugador');
        $month = (int) $this->required($data, 'mes', 'el mes');
        $year = (int) $this->required($data, 'anio', 'el año');
        $paymentMethod = trim((string) $this->required($data, 'metodo_pago', 'el método de pago'));
        $reference = trim((string) ($data['referencia'] ?? ''));
        $notes = trim((string) ($data['observaciones'] ?? ''));

        if (!in_array($paymentMethod, ['Transferencia', 'SINPE Móvil'], true)) {
            throw new InvalidArgumentException('Seleccione un método de pago válido.');
        }
        if ($month < 1 || $month > 12 || $year < 2000) {
            throw new InvalidArgumentException('Verifique el período del pago.');
        }

        $detail = $this->playerPaymentDetail($playerId);
        $pendingPayment = null;
        foreach ($detail['payments'] as $payment) {
            if ((int) $payment['MES'] === $month && (int) $payment['ANIO'] === $year) {
                $pendingPayment = $payment;
                break;
            }
        }
        if (!$pendingPayment) {
            throw new InvalidArgumentException('Seleccione una mensualidad pendiente del jugador.');
        }

        $amount = (float) $pendingPayment['MONTO'];
        $observation = 'Método: ' . $paymentMethod;
        if ($reference !== '') {
            $observation .= ' | Referencia: ' . $reference;
        }
        if ($notes !== '') {
            $observation .= ' | ' . $notes;
        }

        // No convertir a entero: el código de factura puede ser mayor que PHP_INT_MAX.
        $invoiceId = (string) $pendingPayment['ID_FACTURACION_INSCRIPCION'];
        $paymentDate = date('Y-m-d');
        $this->call(
            'FIDE_FACTURACION_INSCRIPCIONES_TB_MODIFICAR_SP',
            [$invoiceId, $playerId, $month, $year, $amount, $paymentDate, 8, $observation],
            'No fue posible actualizar el pago pendiente.',
            [5]
        );

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

    private function playerRecord(array $player): array
    {
        $player['NOMBRE_COMPLETO'] = trim(implode(' ', array_filter([
            $player['NOMBRE'] ?? '',
            $player['APELLIDO_PATERNO'] ?? '',
            $player['APELLIDO_MATERNO'] ?? '',
        ])));
        $player['NOMBRE_CATEGORIA'] = $player['CATEGORIA'] ?? '';
        return $player;
    }

    private function normalize(string $value): string
    {
        return mb_strtoupper(trim($value), 'UTF-8');
    }
}
