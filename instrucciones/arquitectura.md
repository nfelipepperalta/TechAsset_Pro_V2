# Manual de Arquitectura - techasset-pro

Este archivo define las reglas estrictas de desarrollo y el flujo de trabajo con la Inteligencia Artificial. No se permiten excepciones.

## 1. Flujo de Ejecución de 5 Capas

El proyecto utiliza una arquitectura desacoplada en capas. Toda petición debe seguir este orden estricto:
Router ──> Controller ──> Service ──> Repository ──> BaseRepository ──> Azure SQL Database

## 2. Reglas por Carpeta / Capa

- **`controllers/` (Controladores):**
  - Recibe las peticiones HTTP (`$_POST`, `$_GET`).
  - Valida los permisos del usuario logueado utilizando las clases del core (ej. `Auth::check()`, `Auth::requireRole()`).
  - **Prohibición:** No deben contener lógica de negocio ni sentencias SQL directas. Delegan el trabajo al Servicio correspondiente y renderizan la vista (`views/`).

- **`services/` (Servicios):**
  - Contiene la lógica de negocio pura de la aplicación.
  - Instancia y coordina múltiples repositorios si una acción requiere alterar varias tablas (ej. guardar un activo en `AssetRepository` y registrar la bitácora en `AssetHistoryRepository`).
  - **Prohibición:** No maneja variables HTTP directas ni ejecuta código de base de datos de manera directa.

- **`repositories/` (Repositorios):**
  - **Único lugar del proyecto donde se permite escribir código SQL.**
  - Todos los repositorios heredan de `BaseRepository.php`.
  - Utilizan los métodos genéricos CRUD heredados para operaciones simples.
  - Define aquí solo las consultas complejas, JOINS relacionales o procedimientos específicos.

- **`core/` y `Helpers/`:**
  - `core/` maneja la infraestructura crítica (Enrutador, Autenticación y la conexión PDO mediante `Database::getInstance()->getConnection()`).
  - `Helpers/` provee utilidades estáticas globales (`Validator`, `Sanitizer`, `DateHelper`). Deben usarse siempre en lugar de funciones nativas sueltas.

## 3. Estándar de Base de Datos (T-SQL / MSSQL)

- Dado que el motor es **Azure SQL Database**, todas las consultas escritas en los repositorios deben usar sintaxis compatible con **Microsoft SQL Server (T-SQL)** (no MySQL).
- Es obligatorio el uso estricto de **Sentencias Preparadas (Prepared Statements) con PDO** para evitar cualquier vulnerabilidad de Inyección SQL.

## 4. Política de Respaldos Temporales (Backups)

- **Obligatorio:** Antes de sugerir una modificación o refactorización de cualquier archivo de código existente en el proyecto, debes indicarle explícitamente al usuario que realice un respaldo temporal del archivo actual.
- **Formato de Respaldo:** El respaldo debe sugerirse duplicando el archivo en la misma ruta con la extensión `.bak` seguido de la fecha (ejemplo: si vas a modificar `AssetService.php`, debes indicar que se cree un `AssetService.php.bak`).
- **Restauración:** En caso de que el nuevo código sugerido genere un error de sintaxis o rompa la aplicación, debes proveer de inmediato las instrucciones para revertir el archivo al punto funcional usando el archivo `.bak`.
