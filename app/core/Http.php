<?php

declare(strict_types=1);

// Utilidades HTTP compartidas: CORS, sesión requerida, lectura JSON y respuestas normalizadas.
function configure_json_api(string $methods): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: ' . $methods);
    header('Access-Control-Allow-Headers: Content-Type');
}

function handle_options_request(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function require_authenticated_session(string $message): void
{
    if (!isset($_SESSION['empleado'])) {
        send_json(['error' => $message], 401);
    }
}

function read_json_body(): array
{
    $rawBody = file_get_contents('php://input');
    $data = json_decode($rawBody ?: '{}', true);

    if (!is_array($data)) {
        send_json(['error' => 'JSON invalido.'], 400);
    }

    return $data;
}

function send_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
