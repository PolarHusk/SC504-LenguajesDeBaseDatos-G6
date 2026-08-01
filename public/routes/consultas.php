<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/Session.php';
require_once dirname(__DIR__, 2) . '/app/core/Http.php';
require_once dirname(__DIR__, 2) . '/app/controllers/ConsultasController.php';

start_app_session();
configure_json_api('GET, OPTIONS');

(new ConsultasController(new ConsultasModel()))->handle();
