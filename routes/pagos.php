<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Http.php';
require_once dirname(__DIR__) . '/controllers/PagosController.php';

start_app_session();
configure_json_api('GET, POST, OPTIONS');

(new PagosController(new PagosModel()))->handle();
