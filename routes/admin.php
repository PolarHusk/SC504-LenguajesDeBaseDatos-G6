<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Http.php';
require_once dirname(__DIR__) . '/controllers/ConfiguracionController.php';
require_once dirname(__DIR__) . '/controllers/InventarioController.php';
require_once dirname(__DIR__) . '/controllers/PersonasController.php';
require_once dirname(__DIR__) . '/controllers/UbicacionController.php';
require_once dirname(__DIR__) . '/controllers/SaludController.php';
require_once dirname(__DIR__) . '/controllers/CompetenciaController.php';
require_once dirname(__DIR__) . '/controllers/FinanzasController.php';

start_app_session();
configure_json_api('GET, POST, PUT, DELETE, OPTIONS');

$modules = [
    'configuracion' => new ConfiguracionController(new ConfiguracionModel()),
    'inventario' => new InventarioController(new InventarioModel()),
    'personas' => new PersonasController(new PersonasModel()),
    'ubicacion' => new UbicacionController(new UbicacionModel()),
    'salud' => new SaludController(new SaludModel()),
    'competencia' => new CompetenciaController(new CompetenciaModel()),
    'finanzas' => new FinanzasController(new FinanzasModel()),
];

$action = $_GET['accion'] ?? '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && $action === 'esquema') {
    require_authenticated_session('Debe iniciar sesion para consultar el esquema.');

    $schema = [];
    foreach ($modules as $key => $controller) {
        $schema[$key] = [
            'label' => $controller->label(),
            'tables' => $controller->schema(),
        ];
    }

    send_json(['message' => 'Esquema de modulos cargado correctamente.', 'modules' => $schema]);
}

$moduleKey = $_GET['modulo'] ?? '';
if (!isset($modules[$moduleKey])) {
    send_json(['error' => 'Modulo no permitido.'], 400);
}

if ($moduleKey === 'configuracion' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && $action === 'estados') {
    require_authenticated_session('Debe iniciar sesion para consultar los estados.');
    send_json(['message' => 'Estados cargados correctamente.', 'records' => $modules[$moduleKey]->states()]);
}

$modules[$moduleKey]->handle();
