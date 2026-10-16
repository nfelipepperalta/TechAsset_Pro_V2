# Estilo de Código y Herramientas Reutilizables - techasset-pro

Este archivo le indica al Agente qué herramientas globales ya existen en el proyecto para evitar reescribir código y mantener el estilo limpio.

## 1. Uso de Helpers Obligatorios (Carpeta Helpers/)

Antes de validar o limpiar cualquier dato en los Servicios o Controladores, debes invocar estas clases estáticas del proyecto:

- **`Helpers\Validator`:** Utilízalo para validar correos, campos requeridos, longitudes o formatos de datos (ej. `Validator::email($data['email'])`).
- **`Helpers\Sanitizer`:** Utilízalo para limpiar datos de entrada y evitar ataques XSS antes de renderizar vistas (ej. `Sanitizer::clean($output)`).
- **`Helpers\DateHelper`:** Utilízalo para formatear fechas antes de enviarlas a la base de datos de Azure o mostrarlas en la vista.

## 2. Gestión de Sesiones y Permisos (Carpeta core/)

Para el manejo de usuarios en los controladores, usa siempre:

- `Core\Auth::check()`: Para verificar si hay una sesión activa.
- `Core\Auth::requireRole(int $roleId)`: Para restringir el acceso a páginas específicas (ej. solo administradores).

## 3. Manejo de Respuestas y Errores

- **Respuestas HTTP:** Mantén el uso de redirecciones nativas o respuestas JSON uniformes según lo estructurado en los controladores actuales.
- ** logs de error:** Si una consulta en un repositorio falla, el método `execute()` en `BaseRepository.php` debe manejar la excepción PDO, pero si necesitas capturar un error crítico en la lógica de negocio, utiliza bloques `try-catch` dentro de la capa de **Services**.

## 4. Estilo de Escritura (Clean Code)

- **Nombres de métodos:** Usa siempre `camelCase` (ej. `obtenerActivosVencidos`).
- **Variables:** Usa siempre `camelCase` o `snake_case` manteniendo la consistencia con el archivo de la capa correspondiente.
- **Comentarios:** Documenta brevemente el propósito de cada método nuevo al inicio del bloque.
