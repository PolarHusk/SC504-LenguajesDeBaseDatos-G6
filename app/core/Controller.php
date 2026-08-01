<?php

declare(strict_types=1);

/**
 * Clase base para los controladores de la aplicación.
 *
 * Los controladores existentes siguen funcionando desde /controllers, pero
 * los nuevos controladores pueden extender esta clase y usar una API MVC
 * consistente.
 */
class Controller
{
    public function model(string $model): object
    {
        $modelFile = dirname(__DIR__) . '/models/' . $model . '.php';

        if (!is_file($modelFile)) {
            throw new RuntimeException("El modelo $model no existe.");
        }

        require_once $modelFile;
        return new $model();
    }

    public function view(string $view, array $data = []): void
    {
        $viewFile = dirname(__DIR__) . '/views/' . ltrim($view, '/') . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException("La vista $view no existe.");
        }

        extract($data, EXTR_SKIP);
        require $viewFile;
    }

    public function redirect(string $url): never
    {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/';
        header('Location: ' . rtrim($baseUrl, '/') . '/' . ltrim($url, '/'));
        exit;
    }
}
