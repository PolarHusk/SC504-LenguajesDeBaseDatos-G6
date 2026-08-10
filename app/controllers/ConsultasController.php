<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Http.php';
require_once dirname(__DIR__) . '/models/ConsultasModel.php';

// API de lectura para la vista deportiva: traduce cada action HTTP a una consulta del modelo.
final class ConsultasController
{
    public function __construct(private ConsultasModel $model)
    {
    }

    public function handle(): void
    {
        // Este controlador recibe `action` desde fetch del frontend y lo redirige al método del modelo.
        handle_options_request();
        require_authenticated_session('Debe iniciar sesion para consultar la informacion deportiva.');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            send_json(['error' => 'Metodo no permitido.'], 405);
        }

        try {
            $action = $_GET['action'] ?? '';
            if ($action === 'catalogos') {
                // action=catalogos -> tres funciones de catálogo del PACKAGE.
                send_json([
                    'seasons' => $this->model->listSeasons(),
                    'categories' => $this->model->listCategories(),
                    'positions' => $this->model->listPositions(),
                ]);
            }

            if ($action === 'matches') {
                // action=matches -> ConsultasModel::searchMatches() -> FIDE_CONSULTAS_PARTIDOS_BUSCAR_FN.
                send_json(['records' => $this->model->searchMatches($_GET)]);
            }

            if ($action === 'players') {
                // action=players -> ConsultasModel::searchPlayers() -> FIDE_CONSULTAS_JUGADORES_BUSCAR_FN.
                send_json(['records' => $this->model->searchPlayers($_GET)]);
            }

            if ($action === 'player') {
                // action=player -> resumen acumulado de un jugador.
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if (!$id) {
                    send_json(['error' => 'Debe indicar un jugador valido.'], 400);
                }

                send_json(['record' => $this->model->playerSummary($id)]);
            }

            if ($action === 'compare') {
                // action=compare -> función de comparación de hasta tres jugadores.
                $ids = array_values(array_unique(array_filter(
                    array_map('intval', explode(',', (string) ($_GET['ids'] ?? ''))),
                    static fn(int $id): bool => $id > 0
                )));
                if (count($ids) < 2) {
                    send_json(['error' => 'Debe seleccionar al menos dos jugadores.'], 400);
                }

                send_json(['records' => $this->model->comparePlayers($ids)]);
            }

            if ($action === 'match') {
                // action=match -> detalle del partido más estadísticas de sus jugadores.
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if (!$id) {
                    send_json(['error' => 'Debe indicar un partido valido.'], 400);
                }

                send_json($this->model->matchDetail($id));
            }

            if ($action === 'player_match') {
                // action=player_match -> estadísticas de un jugador dentro de un partido.
                $playerId = filter_input(INPUT_GET, 'player_id', FILTER_VALIDATE_INT);
                $matchId = filter_input(INPUT_GET, 'match_id', FILTER_VALIDATE_INT);
                if (!$playerId || !$matchId) {
                    send_json(['error' => 'Debe indicar un jugador y un partido validos.'], 400);
                }

                send_json(['record' => $this->model->playerMatchDetail($playerId, $matchId)]);
            }

            if ($action === 'availability') {
                // action=availability -> disponibilidad calculada según lesiones vigentes.
                send_json(['records' => $this->model->availability($_GET)]);
            }

            if ($action === 'player_injuries') {
                // action=player_injuries -> historial de lesiones del jugador.
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if (!$id) {
                    send_json(['error' => 'Debe indicar un jugador valido.'], 400);
                }

                send_json(['records' => $this->model->playerInjuries($id)]);
            }

            if ($action === 'player_matches') {
                // action=player_matches -> historial deportivo del jugador.
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if (!$id) {
                    send_json(['error' => 'Debe indicar un jugador valido.'], 400);
                }

                send_json(['records' => $this->model->playerMatches($id)]);
            }

            send_json(['error' => 'Consulta no permitida.'], 400);
        } catch (InvalidArgumentException $exception) {
            send_json(['error' => $exception->getMessage()], 400);
        } catch (Throwable $exception) {
            send_json(['error' => $exception->getMessage()], 500);
        }
    }
}
