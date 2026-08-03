# Flujos funcionales y técnicos del sistema

Este documento describe qué hace el código del Sistema de Gestión Deportiva de Academia Leiva / Los Sauces, cómo circula la información entre la interfaz, PHP y Oracle, y qué ocurre ante un error.

## Arquitectura y regla de acceso a datos

La aplicación utiliza una arquitectura MVC:

```text
Navegador
  -> vistas HTML y JavaScript
  -> rutas HTTP en public/routes
  -> controladores PHP
  -> modelos PHP y OracleModel
  -> procedimientos almacenados, vistas, triggers y secuencias Oracle
```

El navegador no contiene SQL ni se conecta directamente a Oracle. La interfaz solicita información en formato JSON; los modelos son los únicos componentes PHP que invocan vistas y procedimientos almacenados mediante OCI8.

Los controladores exponen los campos que debe mostrar el formulario. Los modelos validan los datos de negocio y llaman a procedimientos del paquete `FIDE_PROYECTOFINALACADEMIALEIVA_PCK`. Las operaciones de escritura se concentran en Oracle para mantener las reglas de integridad en la base de datos.

## Flujo común del panel administrativo

1. El navegador abre `app/views/index.html` y carga `app/views/js/app.js`.
2. El JavaScript consulta `public/routes/auth.php?action=status` para saber si existe una sesión.
3. Si la sesión es válida, solicita `public/routes/admin.php?accion=esquema`. La respuesta contiene los módulos, tablas, campos, tipos de control y llaves primarias que deben renderizarse.
4. Al elegir una tabla, el navegador hace `GET public/routes/admin.php?modulo=<modulo>&tabla=<tabla>`.
5. `ModuleController` verifica la sesión y delega el listado al controlador y modelo correspondientes. Los modelos leen la vista Oracle asociada.
6. Para crear, el navegador envía un JSON por `POST`; para editar, un JSON por `PUT`; para desactivar, un JSON por `DELETE`.
7. El modelo llama al procedimiento `..._INSERTAR_SP`, `..._MODIFICAR_SP` o `..._ELIMINAR_SP` correspondiente.
8. Al terminar, el navegador recarga la tabla y muestra un mensaje de éxito. Si la operación falla, muestra un mensaje claro y conserva el detalle técnico en el log de PHP/Apache.

Las eliminaciones funcionales se implementan como desactivación mediante los procedimientos de Oracle; no deben asumirse como borrado físico.

## Autenticación

**Ruta:** `public/routes/auth.php`  
**Componentes:** `AuthController`, `AuthModel`, `Session`.

### Inicio de sesión

1. El usuario ingresa usuario y contraseña.
2. La interfaz envía `POST ?action=login` con ambos valores.
3. `AuthController` comprueba que los datos no estén vacíos.
4. `AuthModel` valida las credenciales en Oracle.
5. Si son correctas, se guarda `$_SESSION['empleado']` y se responde `Ingreso correcto.`
6. Si no son correctas, se responde código HTTP 401 con `Usuario o contraseña incorrectos.`

### Estado y cierre de sesión

- `GET ?action=status` responde si existe una sesión autenticada.
- `POST ?action=logout` destruye la sesión actual.
- Las rutas administrativas y de consultas exigen sesión; de lo contrario devuelven un error de autenticación.

## Módulos administrativos

**Ruta común:** `public/routes/admin.php`.

| Módulo | Qué administra |
| --- | --- |
| Configuración | Estados, provincias, cantones, distritos, categorías, posiciones, cargos, especialidades, tipos de tarjeta, tipos de lesión, tipos de partido y temporadas. |
| Inventario | Categorías de inventario y artículos. |
| Ubicación | Sedes y su dirección exacta. |
| Personas | Empleados, jugadores, teléfonos y relación jugador-posiciones. |
| Salud | Médicos, partes médicos, revisiones físicas y lesiones. |
| Competencia | Partidos, asistencias, estadísticas de equipo y jugador, y tarjetas. |
| Finanzas | Registros de facturación de inscripciones. |

Los controladores de cada módulo (`app/controllers/*Controller.php`) definen el esquema visible: título, campos, catálogos, llaves y validaciones de entrada. Los modelos homónimos (`app/models/*Model.php`) seleccionan la vista y el procedimiento almacenado de cada operación.

## Flujo de registro y mantenimiento de jugadores

**Archivos principales:** `PersonasController`, `PersonasModel`, `AddressModel`, `sql/factura_inicial_jugador.sql`.

### Crear un jugador

1. El usuario completa datos personales, categoría, dorsal, dirección y una o varias posiciones.
2. La cédula se restringe a exactamente nueve dígitos, sin guiones ni espacios. El formulario elimina caracteres que no sean numéricos y el modelo vuelve a validar el valor con la expresión `^\d{9}$`.
3. Se valida que se haya seleccionado al menos una posición. El campo `id_posiciones` se envía como una lista de identificadores, no como nombres de posición.
4. `PersonasModel` verifica que no haya una cédula duplicada; para compatibilidad, normaliza registros históricos que pudieran contener guiones.
5. Si es necesario, `AddressModel` crea primero la dirección y obtiene su identificador.
6. El modelo invoca `FIDE_JUGADORES_TB_INSERTAR_SP`.
7. Una vez creado el jugador, identifica el ID generado y llama `FIDE_JUGADOR_POSICIONES_TB_INSERTAR_SP` por cada posición seleccionada.
8. El trigger `FIDE_JUGADORES_TB_FACTURA_INICIAL_TRG` puede crear la factura inicial del jugador mediante `FIDE_FACTURACION_INSCRIPCIONES_TB_CREAR_INICIAL_SP`.
9. Si todo finaliza correctamente, la API responde que el registro se agregó correctamente; si falla cualquier inserción, la transacción/procedimiento debe propagar el error para impedir un alta incompleta.

### Actualizar o desactivar un jugador

- Una actualización usa `FIDE_JUGADORES_TB_MODIFICAR_SP`; si se editó la dirección, se actualiza la dirección relacionada.
- El formulario carga las posiciones activas que el jugador ya tiene marcadas. Al guardar, el sistema exige al menos una posición, desactiva las que se quitaron y asigna o reactiva las nuevas. La sincronización se realiza con `FIDE_JUGADOR_POSICIONES_TB_ELIMINAR_SP` y `FIDE_JUGADOR_POSICIONES_TB_INSERTAR_SP` usando IDs de posición.
- La desactivación usa `FIDE_JUGADORES_TB_ELIMINAR_SP` y no debe borrar relaciones históricas.
- El mantenimiento directo de una relación jugador-posiciones se realiza con los procedimientos de `FIDE_JUGADOR_POSICIONES_TB`.

## Flujo de pagos y factura de inscripción

**Ruta:** `public/routes/pagos.php`  
**Componentes:** `PagosController`, `PagosModel`, vistas de jugadores y facturas pendientes.

### Buscar jugador

1. El usuario busca por cédula de nueve dígitos o por nombre completo.
2. `GET ?action=buscar` consulta jugadores activos desde `FIDE_JUGADORES_V`.
3. Al seleccionar un jugador, `GET ?action=detalle&jugador_id=<id>` obtiene su información y sus mensualidades pendientes desde `FIDE_FACTURACION_INSCRIPCIONES_PENDIENTES_V`.

### Registrar un pago

1. El usuario selecciona una mensualidad pendiente, método de pago (Transferencia o SINPE Móvil), referencia y observaciones opcionales.
2. `PagosModel` valida jugador, mes, año y método de pago.
3. El modelo comprueba que la factura pendiente corresponda al jugador y período seleccionado.
4. Se llama a `FIDE_FACTURACION_INSCRIPCIONES_TB_MODIFICAR_SP` con la fecha de pago, monto, observación y el estado **Pagado** (ID 8).
5. Oracle actualiza la factura. Si no se modificó ninguna fila, el procedimiento debe lanzar `-20004`; así el sistema nunca informa un pago exitoso que no se guardó.
6. La API devuelve el detalle de la factura y la interfaz muestra/imprime el comprobante con la paleta visual de la aplicación.

### Número de factura

El identificador de factura se almacena como `NUMBER` en Oracle y puede componerse de fecha, hora y secuencia. Por su tamaño puede superar el máximo entero de JavaScript y PHP. Por esa razón, al leerlo o enviarlo desde PHP se trata como texto: nunca debe convertirse a `int`, ya que se redondearía y se actualizaría una factura incorrecta o ninguna.

## Flujo de facturación inicial y mensual

### Factura inicial

Al insertar un jugador, el trigger de jugadores llama el procedimiento de factura inicial. Este crea una factura pendiente para el mes y año actuales. Se debe usar el ID de estado 9 (**Pendiente de pago**) en lugar de buscar el estado por nombre, ya que los nombres pueden variar.

### Facturación mensual automática

**Archivo:** `G6_SC504_LN_EntregaFinal_Job.sql`.

1. El procedimiento `FIDE_FACTURACION_INSCRIPCIONES_TB_GENERAR_MENSUALES_SP` recorre los jugadores activos (estado 1).
2. Para cada jugador revisa si ya existe factura para el mes y año actuales.
3. Si no existe, inserta una factura por el monto definido y estado pendiente (ID 9).
4. El job `FIDE_FACTURACION_INSCRIPCIONES_MENSUAL_JOB` ejecuta el procedimiento el día 1 de cada mes.
5. El trigger de facturación asigna el identificador único si la inserción no lo proporciona.

La secuencia de factura debe combinar fecha/hora y el valor de secuencia definido por la base de datos. La interfaz únicamente lee y muestra el número ya generado; no calcula identificadores.

## Consultas deportivas

**Ruta:** `public/routes/consultas.php`  
**Componentes:** `ConsultasController`, `ConsultasModel`, `app/views/consulta.html`.

Todas las operaciones son de lectura y requieren autenticación.

| Acción | Resultado |
| --- | --- |
| `catalogos` | Temporadas, categorías y posiciones para filtros. |
| `matches` | Partidos según filtros de fecha, temporada, rival u otros datos disponibles. |
| `players` | Jugadores según nombre, cédula, categoría o posición. |
| `player` | Resumen deportivo de un jugador. |
| `dashboard` | Indicadores y resumen general según filtros. |
| `compare` | Comparación de al menos dos jugadores. |
| `match` | Detalle de un partido. |
| `player_match` | Desempeño de un jugador en un partido específico. |
| `availability` | Disponibilidad de jugadores. |
| `player_injuries` | Lesiones de un jugador. |
| `player_matches` | Historial de partidos del jugador. |

## Manejo de validaciones, errores y mensajes

### Validaciones de interfaz

- Los campos requeridos usan validación HTML y mensajes mostrados en el área de notificaciones.
- Los campos con `data-digits-only` eliminan letras, guiones y espacios mientras se escribe.
- La selección de posiciones exige al menos una opción.
- La interfaz muestra mensajes de éxito después de agregar o actualizar registros.

### Validaciones de servidor

- Los controladores validan método HTTP, módulo, tabla y sesión.
- Los modelos validan los campos obligatorios y las reglas específicas del dominio.
- Las acciones no permitidas responden HTTP 400 o 405.
- Los errores de datos esperados responden HTTP 400 con un mensaje entendible para la persona usuaria.

### Errores inesperados

`ModuleController` registra el detalle técnico mediante `error_log()` y responde un texto seguro, por ejemplo: `No fue posible agregar el registro. Verifique los datos e intente nuevamente.` De esta forma no se muestra SQL, nombres internos de Oracle ni trazas técnicas en la pantalla.

Los detalles técnicos se pueden consultar en XAMPP/Apache, normalmente en `C:\xampp\apache\logs\error.log`. Allí se encuentran los códigos `ORA-...`, el procedimiento y la línea reportada por Oracle.

## Base de datos Oracle

| Recurso | Función |
| --- | --- |
| `G6_SC504_LN_Avance2_EsquemaProyecto.sql` | Definición de tablas, restricciones y estructura base. |
| `G6_SC504_LN_SpecPackage.sql` | Contrato público de procedimientos del paquete Oracle. |
| `G6_SC504_LN_Avance2_BodyPackage.sql` | Implementación de inserciones, modificaciones, desactivaciones y reglas de negocio. |
| `sql/secuencias.sql` | Secuencias utilizadas para identificadores. |
| `sql/triggers_corregidos.sql` | Triggers de asignación de identificadores y otras automatizaciones. |
| `sql/factura_inicial_jugador.sql` | Procedimiento y trigger para crear la factura inicial al registrar un jugador. |
| `G6_SC504_LN_EntregaFinal_Job.sql` | Procedimiento y job de facturación mensual. |
| `G6_SC504_LN_Avance2_Vistas.sql` | Vistas que utiliza la capa PHP para listar y consultar información. |

## Orden recomendado de despliegue de base de datos

1. Ejecutar esquema y secuencias.
2. Ejecutar la especificación del paquete.
3. Ejecutar el cuerpo del paquete.
4. Crear o recompilar vistas, procedimientos independientes y triggers.
5. Crear el job mensual.
6. Comprobar objetos inválidos con `USER_ERRORS` y corregirlos antes de probar la aplicación.

Los triggers deben referenciar únicamente columnas existentes. Un trigger inválido bloquea la inserción que lo dispara, incluido el registro de jugadores si el trigger de factura inicial intenta insertar una factura.
