<?php

declare(strict_types=1);

/**
 * Front controller MVC.
 *
 * Acepta URLs como ?url=auth/login y mantiene como ruta predeterminada el
 * flujo de autenticación que ya utiliza el proyecto.
 */
// Enrutador base: interpreta la URL y selecciona el controlador y la acción solicitados.
class App
{
    protected string $controller = 'AuthController';
    protected string $method = 'handle';
    protected array $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        if (isset($url[0]) && $this->controllerExists((string) $url[0])) {
            $this->controller = ucfirst((string) $url[0]) . 'Controller';
            unset($url[0]);
        }

        $controller = $this->makeController($this->controller);

        if (isset($url[1]) && method_exists($controller, (string) $url[1])) {
            $this->method = (string) $url[1];
            unset($url[1]);
        }

        // Los controladores API actuales concentran su lógica en handle() y
        // reciben la acción mediante ?action=. La URL MVC también la admite.
        if ($this->method === 'handle' && isset($url[1])) {
            $_GET['action'] = (string) $url[1];
            unset($url[1]);
        }

        $this->params = array_values($url);
        call_user_func_array([$controller, $this->method], $this->params);
    }

    public function parseUrl(): array
    {
        if (!isset($_GET['url'])) {
            return [];
        }

        $url = filter_var((string) $_GET['url'], FILTER_SANITIZE_URL);
        return $url === '' ? [] : explode('/', trim($url, '/'));
    }

    protected function controllerExists(string $name): bool
    {
        $controller = ucfirst($name) . 'Controller';
        return is_file(dirname(__DIR__) . '/controllers/' . $controller . '.php');
    }

    protected function makeController(string $controller): object
    {
        $controllerFile = dirname(__DIR__) . '/controllers/' . $controller . '.php';
        require_once $controllerFile;

        $reflection = new ReflectionClass($controller);
        $constructor = $reflection->getConstructor();
        if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
            return new $controller();
        }

        $model = preg_replace('/Controller$/', 'Model', $controller);
        $modelFile = dirname(__DIR__) . '/models/' . $model . '.php';
        require_once $modelFile;
        return new $controller(new $model());
    }
}
