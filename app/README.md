# Estructura de la aplicación

La carpeta `app` contiene el núcleo MVC nuevo:

- `core/App.php`: front controller y resolución de rutas `?url=controlador/metodo`.
- `core/Controller.php`: carga de modelos, vistas y redirecciones.
- `config/`: configuración expuesta a la capa MVC.
- `controllers/`, `models/` y `views/`: espacios para nuevos módulos.

Los puntos de acceso HTTP están en `public/`: allí se encuentran el front
controller (`public/index.php`) y las rutas AJAX (`public/routes/`).
