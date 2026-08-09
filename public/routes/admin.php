<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/Session.php';
require_once dirname(__DIR__, 2) . '/app/core/Http.php';
require_once dirname(__DIR__, 2) . '/app/controllers/ConfiguracionController.php';
require_once dirname(__DIR__, 2) . '/app/controllers/InventarioController.php';
require_once dirname(__DIR__, 2) . '/app/controllers/PersonasController.php';
require_once dirname(__DIR__, 2) . '/app/controllers/UbicacionController.php';
require_once dirname(__DIR__, 2) . '/app/controllers/SaludController.php';
require_once dirname(__DIR__, 2) . '/app/controllers/CompetenciaController.php';
require_once dirname(__DIR__, 2) . '/app/controllers/FinanzasController.php';

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

if ($moduleKey === 'personas' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && $action === 'validar_edad') {
    require_authenticated_session('Debe iniciar sesion para validar la edad del jugador.');
    $fechaNacimiento = trim((string) ($_GET['fecha_nacimiento'] ?? ''));
    $categoriaId = filter_input(INPUT_GET, 'id_categoria', FILTER_VALIDATE_INT);
    if ($fechaNacimiento === '' || !$categoriaId) {
        send_json(['error' => 'Debe indicar la fecha de nacimiento y la categoria.'], 400);
    }
    send_json(['valid' => $modules['personas']->isPlayerAgeValid($fechaNacimiento, $categoriaId)]);
}

$modules[$moduleKey]->handle();
