# Arquitectura MVC

## Estructura

```text
config/
  constants.php
  database.php

core/
  Database.php
  Http.php
  ModuleController.php
  OracleModel.php
  Session.php

controllers/
  AuthController.php
  ConfiguracionController.php
  InventarioController.php
  PersonasController.php
  UbicacionController.php
  SaludController.php
  CompetenciaController.php
  FinanzasController.php

models/
  AuthModel.php
  AddressModel.php
  ConfiguracionModel.php
  InventarioModel.php
  PersonasModel.php
  UbicacionModel.php
  SaludModel.php
  CompetenciaModel.php
  FinanzasModel.php

routes/
  auth.php
  admin.php
  consultas.php

views/
  index.html
  css/
  js/
```

## Responsabilidades

- `config/` solo contiene ajustes de la aplicacion y la conexion a Oracle.
- `routes/` son los puntos de entrada HTTP. Preparan sesion, headers y el controlador correspondiente.
- `controllers/` reciben la solicitud, exponen el esquema de su modulo y seleccionan explicitamente la operacion de cada tabla.
- `models/` contienen las consultas y llamadas concretas a los procedimientos almacenados.
- `core/` contiene infraestructura compartida: conexion, respuestas HTTP, sesiones y enlaces OCI8.
- `views/` contiene la interfaz web. Sus URLs apuntan a `routes/`, no a `config/`.

## Modulos

- `Configuracion`: estados, provincias, cantones, distritos, categorias, posiciones, cargos, especialidades, tipos de tarjeta, tipos de lesion, tipos de partido y temporadas.
- `Inventario`: categorias de inventario y articulos.
- `Ubicacion`: sedes.
- `Personas`: empleados, jugadores, telefonos y posiciones de jugadores.
- `Salud`: medicos, partes medicos, revisiones fisicas y lesiones.
- `Competencia`: partidos, asistencia, estadisticas y tarjetas.
- `Finanzas`: facturacion de inscripciones.

`DIRECCION_EXACTA` no se presenta como modulo independiente. `AddressModel` se utiliza unicamente al crear o actualizar empleados, jugadores y sedes.

## Rutas HTTP

- `routes/auth.php`: estado de sesion, inicio y cierre de sesion.
- `routes/admin.php`: esquema de modulos y operaciones autenticadas de administracion.
- `routes/consultas.php`: busquedas y resumen deportivo para clientes y entrenadores.

La carpeta `config/` no debe contener endpoints ejecutables: mover las rutas alli mezclaria configuracion con transporte HTTP y dificultaria proteger los puntos de entrada.
