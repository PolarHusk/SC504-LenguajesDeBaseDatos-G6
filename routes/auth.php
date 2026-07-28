<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Http.php';
require_once dirname(__DIR__) . '/models/Contracts/AuthenticatorInterface.php';
require_once dirname(__DIR__) . '/controllers/AuthController.php';
require_once dirname(__DIR__) . '/models/AuthModel.php';

start_app_session();
configure_json_api('GET, POST, OPTIONS');

(new AuthController(new AuthModel()))->handle();
