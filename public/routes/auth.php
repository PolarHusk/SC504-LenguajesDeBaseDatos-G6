<?php

declare(strict_types=1);

// Endpoint de autenticación: recibe login/logout/estado y crea el controlador de sesión.

require_once dirname(__DIR__, 2) . '/app/core/Session.php';
require_once dirname(__DIR__, 2) . '/app/core/Http.php';
require_once dirname(__DIR__, 2) . '/app/models/Contracts/AuthenticatorInterface.php';
require_once dirname(__DIR__, 2) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/app/models/AuthModel.php';

start_app_session();
configure_json_api('GET, POST, OPTIONS');

(new AuthController(new AuthModel()))->handle();
