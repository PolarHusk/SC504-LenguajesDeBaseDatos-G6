<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Http.php';
require_once dirname(__DIR__) . '/models/Contracts/AuthenticatorInterface.php';

final class AuthController
{
    public function __construct(private AuthenticatorInterface $model)
    {
    }

    public function handle(): void
    {
        handle_options_request();
        $action = $_GET['action'] ?? 'status';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        try {
            if ($method === 'GET' && $action === 'status') {
                send_json(['authenticated' => isset($_SESSION['empleado']), 'user' => $_SESSION['empleado'] ?? null]);
            }

            if ($method !== 'POST') {
                send_json(['error' => 'Metodo no permitido.'], 405);
            }

            if ($action === 'login') {
                $data = read_json_body();
                $usuario = trim((string) ($data['usuario'] ?? ''));
                $contrasenia = trim((string) ($data['contrasenia'] ?? ''));
                if ($usuario === '' || $contrasenia === '') {
                    send_json(['error' => 'Debe digitar usuario y contrasenia.'], 400);
                }
                if (!$this->model->validateCredentials($usuario, $contrasenia)) {
                    send_json(['error' => 'Usuario o contrasenia incorrectos.'], 401);
                }

                $_SESSION['empleado'] = ['usuario' => $usuario];
                send_json(['message' => 'Ingreso correcto.', 'user' => $_SESSION['empleado']]);
            }

            if ($action === 'logout') {
                $_SESSION = [];
                session_destroy();
                send_json(['message' => 'Sesion cerrada correctamente.']);
            }

            send_json(['error' => 'Accion no permitida.'], 400);
        } catch (Throwable $exception) {
            send_json(['error' => $exception->getMessage()], 500);
        }
    }
}
