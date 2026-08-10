<?php

declare(strict_types=1);

// Endpoint de pagos: conecta la interfaz de facturación con PagosController.

require_once dirname(__DIR__, 2) . '/app/core/Session.php';
require_once dirname(__DIR__, 2) . '/app/core/Http.php';
require_once dirname(__DIR__, 2) . '/app/controllers/PagosController.php';

start_app_session();
configure_json_api('GET, POST, OPTIONS');

(new PagosController(new PagosModel()))->handle();
