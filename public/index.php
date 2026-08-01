<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/Session.php';
require_once dirname(__DIR__) . '/app/core/Http.php';
require_once dirname(__DIR__) . '/app/bootstrap.php';

// La entrada pública sin una ruta API debe abrir el portal de login.
if (!isset($_GET['url']) || trim((string) $_GET['url']) === '') {
    header('Location: ../app/views/index.html');
    exit;
}

start_app_session();
configure_json_api('GET, POST, PUT, DELETE, OPTIONS');

new App();
