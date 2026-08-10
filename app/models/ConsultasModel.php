<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/OracleModel.php';

final class ConsultasModel extends OracleModel
{
    // Cada método de este modelo identifica explícitamente la función del PACKAGE que atiende la consulta deportiva.
    public function listSeasons(): array
    {
        return $this->decodeJsonResult(
            $this->callClobFunction('FIDE_CONSULTAS_TEMPORADAS_LISTAR_FN', [], 'No fue posible cargar las temporadas.'),
            'Oracle no devolvio las temporadas en un formato valido.'
        );
    }

    public function listPositions(): array
    {
        return $this->decodeJsonResult(
            $this->callClobFunction('FIDE_CONSULTAS_POSICIONES_LISTAR_FN', [], 'No fue posible cargar las posiciones.'),
            'Oracle no devolvio las posiciones en un formato valido.'
        );
    }

    public function listCategories(): array
    {
        return $this->decodeJsonResult(
            $this->callClobFunction('FIDE_CONSULTAS_CATEGORIAS_LISTAR_FN', [], 'No fue posible cargar las categorias.'),
            'Oracle no devolvio las categorias en un formato valido.'
        );
    }

    public function searchMatches(array $filters): array
    {
        $year = trim((string) ($filters['year'] ?? ''));
        $season = trim((string) ($filters['season'] ?? ''));
        $rival = trim((string) ($filters['rival'] ?? ''));
        $date = trim((string) ($filters['date'] ?? ''));
        $result = trim((string) ($filters['result'] ?? ''));

        return $this->decodeJsonResult(
            // Llamada a FIDE_CONSULTAS_PARTIDOS_BUSCAR_FN con los filtros normalizados.
            $this->callClobFunction(
                'FIDE_CONSULTAS_PARTIDOS_BUSCAR_FN',
                [
                    $year === '' ? null : (int) $year,
                    $season === '' ? null : (int) $season,
                    $rival === '' ? null : $rival,
                    $date === '' ? null : $date,
                    $result === '' ? null : strtolower($result),
                ],
                'No fue posible buscar los partidos.'
            ),
            'Oracle no devolvio los partidos en un formato valido.'
        );
    }

    public function searchPlayers(array $filters): array
    {
        $name = trim((string) ($filters['name'] ?? ''));
        $position = trim((string) ($filters['position'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));
        $cedula = trim((string) ($filters['cedula'] ?? ''));

        return $this->decodeJsonResult(
            // Llamada a FIDE_CONSULTAS_JUGADORES_BUSCAR_FN; los valores vacíos se convierten en NULL.
            $this->callClobFunction(
                'FIDE_CONSULTAS_JUGADORES_BUSCAR_FN',
                [
                    $name === '' ? null : $name,
                    $category === '' ? null : (int) $category,
                    $cedula === '' ? null : $cedula,
                    $position === '' ? null : (int) $position,
                ],
                'No fue posible buscar los jugadores.'
            ),
            'Oracle no devolvio los jugadores en un formato valido.'
        );
    }

    public function playerSummary(int $playerId): array
    {
        $records = $this->decodeJsonResult(
            // Llamada a FIDE_CONSULTAS_JUGADOR_RESUMEN_FN para las estadísticas acumuladas.
            $this->callClobFunction(
                'FIDE_CONSULTAS_JUGADOR_RESUMEN_FN',
                [$playerId],
                'No fue posible cargar el resumen del jugador.'
            ),
            'Oracle no devolvio el resumen en un formato valido.'
        );

        if (!$records) {
            throw new InvalidArgumentException('No se encontro el jugador seleccionado.');
        }

        return $records[0];
    }

    public function comparePlayers(array $playerIds): array
    {
        $ids = array_values(array_map('intval', array_slice($playerIds, 0, 3)));
        while (count($ids) < 3) {
            $ids[] = null;
        }

        return $this->callConsultationFunction(
            'FIDE_CONSULTAS_JUGADORES_COMPARAR_FN',
            $ids,
            'No fue posible comparar los jugadores.'
        );
    }

    public function matchDetail(int $matchId): array
    {
        $match = $this->callConsultationFunction(
            'FIDE_CONSULTAS_PARTIDO_DETALLE_FN',
            [$matchId],
            'No fue posible cargar el detalle del partido.'
        );
        if (!$match) {
            throw new InvalidArgumentException('No se encontro el partido seleccionado.');
        }

        return [
            'match' => $match[0],
            'players' => $this->callConsultationFunction(
                'FIDE_CONSULTAS_PARTIDO_JUGADORES_FN',
                [$matchId],
                'No fue posible cargar los jugadores del partido.'
            ),
        ];
    }

    public function playerMatchDetail(int $playerId, int $matchId): array
    {
        $records = $this->callConsultationFunction(
            'FIDE_CONSULTAS_JUGADOR_PARTIDO_FN',
            [$playerId, $matchId],
            'No fue posible cargar la estadistica del jugador en el partido.'
        );
        if (!$records) {
            throw new InvalidArgumentException('No se encontro la estadistica solicitada.');
        }

        return $records[0];
    }

    public function availability(array $filters): array
    {
        $category = trim((string) ($filters['category'] ?? ''));
        return $this->callConsultationFunction(
            'FIDE_CONSULTAS_DISPONIBILIDAD_FN',
            [$category === '' ? null : (int) $category],
            'No fue posible cargar la disponibilidad de los jugadores.'
        );
    }

    public function playerInjuries(int $playerId): array
    {
        return $this->callConsultationFunction(
            'FIDE_CONSULTAS_JUGADOR_LESIONES_FN',
            [$playerId],
            'No fue posible cargar las lesiones del jugador.'
        );
    }

    public function playerMatches(int $playerId): array
    {
        return $this->callConsultationFunction(
            'FIDE_CONSULTAS_JUGADOR_PARTIDOS_FN',
            [$playerId],
            'No fue posible cargar los partidos del jugador.'
        );
    }

    private function callConsultationFunction(string $function, array $arguments, string $message): array
    {
        // Adaptador común: aquí se conecta este modelo con callClobFunction() de OracleModel.
        return $this->decodeJsonResult(
            $this->callClobFunction($function, $arguments, $message),
            'Oracle no devolvio la consulta en un formato valido.'
        );
    }
}
