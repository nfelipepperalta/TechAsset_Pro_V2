# Esquema de Base de Datos Real - Azure SQL Database

Este archivo mapea la estructura exacta de tablas disponibles en el esquema `dbo.` de nuestro servidor `dbtechassetserver.database.windows.net` (Base de datos: `TechAsset`).

El Agente Copilot debe usar obligatoriamente estos nombres exactos al construir consultas personalizadas (SQL / T-SQL):

## 1. Tabla: dbo.alert (Alertas del Sistema)

- Maneja las notificaciones de stock, vencimientos de garantías o alertas de mantenimiento.
- Campos comunes esperados: `id` (PK, IDENTITY), `message`, `status`, `created_at`.

## 2. Tabla: dbo.asset (Activos Tecnológicos)

- Almacena los equipos físicos (computadores, servidores, etc.).
- Relaciones clave:
  - `type_id` (FK -> dbo.asset_type.id)
  - `department_id` (FK -> dbo.department.id)
  - `user_id` (FK -> dbo.user.id)

## 3. Tabla: dbo.asset_history (Historial y Bitácora)

- Registra de forma auditada cada movimiento, cambio de estado o reasignación de un activo.
- Relaciones clave:
  - `asset_id` (FK -> dbo.asset.id)
  - `user_id` (FK -> dbo.user.id)

## 4. Tabla: dbo.asset_type (Categorías de Activos)

- Define si el equipo es un portátil, monitor, switch, periférico, etc.
- Campos comunes esperados: `id` (PK, IDENTITY), `name`, `description`.

## 5. Tabla: dbo.department (Departamentos / Áreas)

- Estructura organizacional (ej. Contabilidad, Sistemas, Recursos Humanos).
- Campos comunes esperados: `id` (PK, IDENTITY), `name`, `code`.

## 6. Tabla: dbo.maintenance (Gestión de Mantenimientos)

- Controla los mantenimientos preventivos y correctivos aplicados a los activos.
- Relación clave:
  - `asset_id` (FK -> dbo.asset.id)

## 7. Tabla: dbo.qr_code (Códigos QR de Inventario)

- Enlaza los activos con su etiqueta de código QR físico para inventarios rápidos.
- Relación clave:
  - `asset_id` (FK -> dbo.asset.id)

## 8. Tabla: dbo.user (Usuarios e Integrantes)

- Contiene el personal de la empresa y administradores del sistema.
- Campos comunes esperados: `id` (PK, IDENTITY), `username`, `password`, `email`, `role_id`.

---

## 📌 Reglas de Oro de Nomenclatura para el Agente:

1.  **Uso de Plurales/Singulares:** Todas las tablas están nombradas estrictamente en **singular** (`asset`, `user`, `department`, etc.). Prohibido inventar nombres en plural como `assets` o `users` porque arrojarán error inmediato.
2.  **Prefijo obligatorio:** Todas las consultas manuales deben invocar la tabla con su esquema nativo, por ejemplo: `FROM dbo.asset` o `FROM dbo.user`.
3.  **Motor T-SQL:** Al realizar búsquedas temporales usa `GETDATE()`. Al actualizar registros, básate en el historial de comandos PDO de `BaseRepository.php`.
