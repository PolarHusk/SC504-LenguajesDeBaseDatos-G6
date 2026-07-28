<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Http.php';
require_once dirname(__DIR__) . '/models/PagosModel.php';

final class PagosController
{
    public function __construct(private PagosModel $model)
    {
    }

    public function handle(): void
    {
        handle_options_request();

        try {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $action = $_GET['action'] ?? '';

            if ($method === 'GET' && $action === 'buscar') {
                $cedula = trim((string) ($_GET['cedula'] ?? ''));
                $nombre = trim((string) ($_GET['nombre'] ?? ''));
                if ($cedula === '' && $nombre === '') {
                    send_json(['error' => 'Digite una cédula o el nombre completo del jugador.'], 400);
                }
                send_json(['records' => $this->model->searchPlayers($cedula, $nombre)]);
            }

            if ($method === 'GET' && $action === 'detalle') {
                $playerId = filter_input(INPUT_GET, 'jugador_id', FILTER_VALIDATE_INT);
                if (!$playerId) send_json(['error' => 'Debe indicar un jugador válido.'], 400);
                send_json($this->model->playerPaymentDetail($playerId));
            }

            if ($method === 'POST' && $action === 'pagar') {
                $data = read_json_body();
                send_json(['message' => 'Pago registrado y factura generada correctamente.', 'invoice' => $this->model->registerPayment($data)], 201);
            }

            send_json(['error' => 'Acción de pagos no permitida.'], 400);
        } catch (InvalidArgumentException $exception) {
            send_json(['error' => $exception->getMessage()], 400);
        } catch (Throwable $exception) {
            send_json(['error' => $exception->getMessage()], 500);
        }
    }
}
