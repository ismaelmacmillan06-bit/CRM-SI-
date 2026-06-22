# Base de Datos — MacmillanSI CRM

> Laravel 11 · MySQL 8.0  
> Actualizado: 2026-06-22

---

## Índice

1. [Resumen general](#resumen-general)
2. [Diagrama de módulos](#diagrama-de-módulos)
3. [Módulo: Usuarios y Roles](#módulo-usuarios-y-roles)
4. [Módulo: Colegios](#módulo-colegios)
5. [Módulo: Docentes y Estudiantes](#módulo-docentes-y-estudiantes)
6. [Módulo: Bundles y Adopciones](#módulo-bundles-y-adopciones)
7. [Módulo: Consultoría](#módulo-consultoría)
8. [Módulo: Tareas y Bitácora](#módulo-tareas-y-bitácora)
9. [Tablas de sistema](#tablas-de-sistema)
10. [ENUMs definidos](#enums-definidos)
11. [Claves foráneas y acciones](#claves-foráneas-y-acciones)

---

## Resumen general

| # | Tabla | Módulo | Propósito |
|---|-------|--------|-----------|
| 1 | `users` | Usuarios | Cuentas de acceso al sistema |
| 2 | `roles` | Usuarios | Roles del sistema (Spatie) |
| 3 | `permissions` | Usuarios | Permisos del sistema (Spatie) |
| 4 | `model_has_roles` | Usuarios | Pivot usuario ↔ rol |
| 5 | `model_has_permissions` | Usuarios | Pivot usuario ↔ permiso |
| 6 | `role_has_permissions` | Usuarios | Pivot rol ↔ permiso |
| 7 | `schools` | Colegios | Colegios clientes |
| 8 | `levels` | Colegios | Niveles educativos |
| 9 | `school_level` | Colegios | Pivot colegio ↔ nivel |
| 10 | `processes` | Colegios | Catálogo de procesos de adopción |
| 11 | `school_level_process` | Colegios | Seguimiento de procesos por colegio-nivel |
| 12 | `visits` | Colegios | Visitas de consultores |
| 13 | `tickets` | Colegios | Solicitudes y soporte |
| 14 | `mee_admins` | Colegios | Cuentas administrador MEE por colegio |
| 15 | `teachers` | Docentes | Docentes de cada colegio |
| 16 | `teacher_roles` | Docentes | Roles de un docente (director, coordinador…) |
| 17 | `students` | Docentes | Alumnos registrados en MEE |
| 18 | `adoptions` | Bundles | Catálogo de libros/materiales |
| 19 | `school_adoption` | Bundles | Pivot colegio ↔ adopción |
| 20 | `teacher_books` | Bundles | Libros asignados a docentes |
| 21 | `bundles` | Bundles | Catálogo de bundles Macmillan |
| 22 | `school_bundle` | Bundles | Pivot colegio ↔ bundle (con cantidad) |
| 23 | `bundle_resurtidos` | Bundles | Historial de reabastecimiento de bundles |
| 24 | `consultants` | Consultoría | Perfil de consultores |
| 25 | `school_consultants` | Consultoría | Asignación consultor ↔ colegio por rol |
| 26 | `tareas_si` | Tareas | Tareas globales creadas por el equipo SI |
| 27 | `tarea_si_colegio` | Tareas | Estado de una tarea en un colegio específico |
| 28 | `activity_logs` | Bitácora | Registro de acciones del sistema |
| 29 | `sessions` | Sistema | Sesiones activas |
| 30 | `cache` / `cache_locks` | Sistema | Caché de la aplicación |
| 31 | `jobs` / `job_batches` / `failed_jobs` | Sistema | Cola de trabajos asíncronos |
| 32 | `password_reset_tokens` | Sistema | Tokens de recuperación de contraseña |
| 33 | `migrations` | Sistema | Historial de migraciones Laravel |

---

## Diagrama de módulos

```
users ──────────────────────────────────────────────────────┐
  └── model_has_roles ──── roles ──── role_has_permissions ──┤
                                 └── permissions              │
                                                              │
consultants (→ users)                                         │
  └── school_consultants (rol: digital/eca/elt/ventas)        │
         └── schools ─────────────────────────────────────────┘
               ├── school_level ──── levels
               │     └── school_level_process ──── processes
               ├── visits
               ├── tickets
               ├── mee_admins
               ├── teachers
               │     ├── teacher_roles
               │     └── teacher_books ──── adoptions
               ├── students
               ├── school_adoption ──── adoptions
               ├── school_bundle ──── bundles
               │     └── bundle_resurtidos
               ├── tarea_si_colegio ──── tareas_si (→ users)
               └── activity_logs (→ users)
```

---

## Módulo: Usuarios y Roles

### `users`
Cuentas de acceso al sistema. Cada usuario tiene uno o más roles de Spatie.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `name` | varchar(255) | No | Nombre completo |
| `email` | varchar(255) UNIQUE | No | Email de acceso |
| `email_verified_at` | timestamp | Sí | Fecha de verificación |
| `password` | varchar(255) | No | Contraseña hasheada (bcrypt) |
| `remember_token` | varchar(100) | Sí | Token "recuérdame" |
| `created_at` / `updated_at` | timestamp | Sí | — |

**Roles del CRM** (asignados vía Spatie):

| Rol | Acceso |
|-----|--------|
| `admin` | Acceso total |
| `consultor_digital` | Solo sus colegios asignados |
| `eca` | Acceso a módulos ECA |
| `elt` | Acceso a módulos ELT |
| `ventas` | Acceso a módulos de ventas |

**Relaciones:** `hasMany` ActivityLog, TareaSI, BundleResurtido

---

### `consultants`
Perfil extendido de un usuario con rol consultor.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `user_id` | bigint FK → users | No | Cuenta de acceso |
| `phone` | varchar(255) | Sí | Teléfono |
| `zone` | varchar(255) | Sí | Zona geográfica |
| `photo` | varchar(255) | Sí | Ruta de foto de perfil |
| `created_at` / `updated_at` | timestamp | Sí | — |

**Relaciones:** `belongsTo` User · `hasMany` Visit, Ticket · `hasMany` SchoolConsultant

---

### Tablas Spatie Permission

| Tabla | Propósito |
|-------|-----------|
| `roles` | Catálogo de roles (`admin`, `consultor_digital`, etc.) |
| `permissions` | Catálogo de permisos granulares |
| `model_has_roles` | Asigna roles a un modelo (Users) por polimorfismo |
| `model_has_permissions` | Asigna permisos directos a un modelo |
| `role_has_permissions` | Permisos que tiene cada rol |

Todas usan `guard_name` = `'web'` e índice único `(name, guard_name)`.

---

## Módulo: Colegios

### `schools`
Colegios clientes de Macmillan.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `name` | varchar(255) | No | Nombre del colegio |
| `nexus_id` | varchar(255) | Sí | ID en sistema Nexus (externo) |
| `address` | varchar(255) | Sí | Dirección |
| `city` | varchar(255) | Sí | Ciudad / Estado |
| `phone` | varchar(255) | Sí | Teléfono |
| `email` | varchar(255) | Sí | Email de contacto |
| `status` | enum | Sí | `prospecto` · `activo` · `inactivo` (default: `prospecto`) |
| `notes` | text | Sí | Observaciones generales |
| `consultant_id` | bigint FK → consultants | Sí | Consultor principal (nullable) |
| `created_at` / `updated_at` | timestamp | Sí | — |

**Relaciones:** `belongsTo` Consultant · `hasMany` Teacher, Student, MeeAdmin, Visit, Ticket, ActivityLog, BundleResurtido · `belongsToMany` Level, Adoption, Bundle · `hasMany` SchoolConsultant, TareaColegio

---

### `levels`
Niveles educativos soportados.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `name` | varchar(255) | No | Nombre: Maternal, Preescolar, Primaria, Secundaria, Bachillerato |

**Relaciones:** `belongsToMany` School via `school_level`

---

### `school_level`
Pivot con identidad propia: combinación colegio + nivel sobre la que se rastrean procesos.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | — |
| `school_id` | bigint FK → schools | No | Colegio |
| `level_id` | bigint FK → levels | No | Nivel |

**Relaciones:** `hasMany` SchoolLevelProcess

---

### `processes`
Catálogo de procesos que debe completar cada colegio-nivel (capacitación, implementación, etc.)

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `name` | varchar(255) | No | Nombre del proceso |
| `slug` | varchar(255) | No | Slug único |
| `order` | tinyint unsigned | No | Orden de presentación |

---

### `school_level_process`
Seguimiento del estado de cada proceso para cada combinación colegio-nivel.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `school_level_id` | bigint FK → school_level | No | Colegio-nivel |
| `process_id` | bigint FK → processes | No | Proceso |
| `status` | enum | No | `pending` · `in_progress` · `done` · `reopened` (default: `pending`) |
| `completed_at` | timestamp | Sí | Fecha de finalización |
| `completed_by` | bigint FK → consultants | Sí | Consultor que marcó como completado |
| `notes` | text | Sí | Notas del proceso |
| `evidence` | varchar(255) | Sí | Archivo de evidencia |

---

### `visits`
Visitas programadas o realizadas de consultores a colegios.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `school_id` | bigint FK → schools | No | Colegio visitado |
| `consultant_id` | bigint FK → consultants | No | Consultor que visita |
| `visit_date` | date | No | Fecha de la visita |
| `scheduled_date` | date | Sí | Fecha programada |
| `status` | enum | No | `pendiente` · `en_curso` · `terminada` (default: `pendiente`) |
| `summary` | text | Sí | Resumen de la visita |
| `evidence` | varchar(255) | Sí | Foto o archivo de evidencia |
| `notes` | text | Sí | Notas adicionales |
| `next_visit_date` | date | Sí | Próxima fecha sugerida |

---

### `tickets`
Solicitudes de soporte o incidencias de colegios.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `school_id` | bigint FK → schools | No | Colegio que genera el ticket |
| `consultant_id` | bigint FK → consultants | No | Consultor asignado |
| `title` | varchar(255) | No | Título del ticket |
| `description` | text | No | Descripción del problema |
| `status` | enum | No | `open` · `in_progress` · `closed` (default: `open`) |
| `priority` | enum | No | `low` · `medium` · `high` (default: `medium`) |
| `medium` | enum | No | `salesforce` · `whaticket` · `whatsapp` (default: `whatsapp`) |
| `resolved_at` | timestamp | Sí | Fecha de cierre |

---

### `mee_admins`
Cuentas de administrador de la plataforma MEE por colegio.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `school_id` | bigint FK → schools | No | Colegio al que pertenece |
| `username` | varchar(255) | No | Usuario MEE del administrador |
| `password_plain` | varchar(255) | No | Contraseña en texto plano (requerimiento MEE) |

---

## Módulo: Docentes y Estudiantes

### `teachers`
Docentes registrados en cada colegio.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `school_id` | bigint FK → schools | No | Colegio al que pertenece |
| `name` | varchar(255) | No | Nombre |
| `last_name` | varchar(255) | No | Apellido |
| `email` | varchar(255) | Sí | Email |
| `grade` | varchar(255) | Sí | Grado que imparte |
| `subject` | enum | Sí | `ECA` · `ELT` · `ambos` · `ninguno` (default: `ninguno`) |
| `mee_username` | varchar(255) | Sí | Usuario en plataforma MEE |
| `mee_password` | varchar(255) | Sí | Contraseña en plataforma MEE |

**Relaciones:** `hasMany` TeacherRole, TeacherBook

---

### `teacher_roles`
Roles específicos de un docente dentro del colegio. Un docente puede tener múltiples roles.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `teacher_id` | bigint FK → teachers | No | Docente |
| `role` | varchar(255) | No | `docente` · `director_general` · `director_nivel` · `coordinador` · `dueno` · `admin_mee` · `coord_ingles` · `coord_espanol` |

**Índice:** UNIQUE `(teacher_id, role)`

---

### `students`
Alumnos importados de la plataforma MEE.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `school_id` | bigint FK → schools | No | Colegio al que pertenece |
| `name` | varchar(255) | No | Nombre |
| `last_name` | varchar(255) | No | Apellido |
| `mee_username` | varchar(255) | No | Usuario en plataforma MEE |
| `mee_password` | varchar(255) | No | Contraseña en plataforma MEE |
| `grade` | varchar(255) | Sí | Grado (ej: "2°", "3er") |
| `level` | varchar(255) | Sí | Nivel (ej: "Primaria", "Secundaria") |

---

## Módulo: Bundles y Adopciones

### `bundles`
Catálogo de bundles (paquetes de materiales) de Macmillan SI.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `serie` | varchar(255) | No | Serie o colección (ej: "Achieve", "Prime") |
| `name` | varchar(255) | No | Nombre del bundle |
| `grade` | varchar(255) | Sí | Grado al que corresponde |
| `level` | varchar(255) | Sí | Nivel educativo |
| `role` | enum | No | `student` · `teacher` (default: `student`) |
| `type` | enum | No | `ELT` · `Plan Lector` · `Imagina` · `Wikids` · `Pienso Contigo` · `Complemento` (default: `ELT`) |

**Relaciones:** `belongsToMany` School via `school_bundle` · `hasMany` BundleResurtido

---

### `school_bundle`
Pivot que registra qué bundles tiene asignados cada colegio y en qué cantidad.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `school_id` | bigint FK → schools | No | Colegio |
| `bundle_id` | bigint FK → bundles | No | Bundle asignado |
| `quantity` | int | No | Cantidad de paquetes (default: 1) |
| `acquired_at` | date | Sí | Fecha de adquisición |

---

### `bundle_resurtidos`
Historial de reabastecimientos de bundles. Cada registro es un evento de resurtido.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `school_id` | bigint FK → schools | No | Colegio al que se resurtió |
| `bundle_id` | bigint FK → bundles | No | Bundle resurtido |
| `cantidad_anterior` | int | No | Cantidad antes del resurtido |
| `cantidad_resurtido` | int | No | Cantidad agregada |
| `cantidad_nueva` | int | No | Cantidad total después del resurtido |
| `autorizado_por` | varchar(255) | Sí | Nombre de quien autorizó |
| `fecha` | date | No | Fecha del resurtido |
| `user_id` | bigint FK → users | Sí | Usuario del CRM que registró (SET NULL si se elimina) |

**Relaciones:** `belongsTo` School, Bundle, User

---

### `adoptions`
Catálogo de libros/materiales para adopción.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `name` | varchar(255) | No | Título del libro o material |
| `level` | varchar(255) | Sí | Nivel educativo |
| `subject` | varchar(255) | Sí | Materia/asignatura |

---

### `school_adoption`
Adopciones de libros por colegio.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `school_id` | bigint FK → schools | No | Colegio |
| `adoption_id` | bigint FK → adoptions | No | Adopción |
| `level_id` | bigint FK → levels | Sí | Nivel asociado (SET NULL si se elimina) |
| `quantity` | int | No | Ejemplares (default: 0) |
| `acquired_at` | date | Sí | Fecha de adquisición |

---

### `teacher_books`
Libros/adopciones asignadas a docentes específicos.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `teacher_id` | bigint FK → teachers | No | Docente |
| `adoption_id` | bigint FK → adoptions | No | Adopción asignada |

---

## Módulo: Consultoría

### `school_consultants`
Asignación de consultores a colegios con un rol específico. Un colegio puede tener hasta 4 consultores (uno por rol).

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `school_id` | bigint FK → schools | No | Colegio |
| `consultant_id` | bigint FK → consultants | No | Consultor asignado |
| `role` | enum | No | `digital` · `eca` · `elt` · `ventas` |

**Relaciones:** `belongsTo` School, Consultant

> Los accesos del dashboard se filtran por `role = 'digital'` para `consultor_digital`.

---

## Módulo: Tareas y Bitácora

### `tareas_si`
Tareas o proyectos creados por el equipo Macmillan que aplican a múltiples colegios.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `titulo` | varchar(255) | No | Título de la tarea |
| `descripcion` | text | Sí | Descripción detallada |
| `created_by` | bigint FK → users | No | Usuario que creó la tarea |

**Relaciones:** `hasMany` TareaColegio

---

### `tarea_si_colegio`
Estado de una tarea global en un colegio específico.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `tarea_si_id` | bigint FK → tareas_si | No | Tarea global |
| `school_id` | bigint FK → schools | No | Colegio |
| `status` | enum | No | `pendiente` · `en_proceso` · `realizada` (default: `pendiente`) |
| `updated_by` | bigint FK → users | Sí | Último usuario que actualizó el estado |

**Índice:** UNIQUE `(tarea_si_id, school_id)`

---

### `activity_logs`
Bitácora de acciones del sistema. Se registra automáticamente en 6 controladores.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | bigint PK | No | Auto-incremental |
| `user_id` | bigint FK → users | Sí | Usuario que realizó la acción (NULL si fue eliminado) |
| `school_id` | bigint FK → schools | Sí | Colegio relacionado (NULL si no aplica) |
| `tipo` | varchar(30) | No | `proceso` · `visita` · `ticket` · `bundle` · `docente` · `arranque` · etc. |
| `icono` | varchar(10) | No | Emoji del tipo de acción (default: `📝`) |
| `descripcion` | text | No | Texto descriptivo de la acción |
| `created_at` | timestamp IDX | Sí | Fecha del evento (indexada) |

**Índices:** `created_at` · `(school_id, created_at)` · `(tipo, created_at)`

**Uso:** `ActivityLog::log($tipo, $descripcion, $schoolId, $icono)`

---

## Tablas de sistema

| Tabla | Propósito |
|-------|-----------|
| `sessions` | Sesiones activas (driver: database). Columnas: `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity` |
| `cache` | Caché de aplicación. Columnas: `key`, `value`, `expiration` |
| `cache_locks` | Locks distribuidos de caché |
| `jobs` | Cola de trabajos asíncronos |
| `job_batches` | Lotes de jobs agrupados |
| `failed_jobs` | Jobs que fallaron (con excepción y timestamp) |
| `password_reset_tokens` | Tokens de recuperación (PK = email) |
| `migrations` | Historial de migraciones ejecutadas por Laravel |

---

## ENUMs definidos

| Tabla | Columna | Valores posibles | Default |
|-------|---------|-----------------|---------|
| `schools` | `status` | `prospecto`, `activo`, `inactivo` | `prospecto` |
| `school_level_process` | `status` | `pending`, `in_progress`, `done`, `reopened` | `pending` |
| `visits` | `status` | `pendiente`, `en_curso`, `terminada` | `pendiente` |
| `tickets` | `status` | `open`, `in_progress`, `closed` | `open` |
| `tickets` | `priority` | `low`, `medium`, `high` | `medium` |
| `tickets` | `medium` | `salesforce`, `whaticket`, `whatsapp` | `whatsapp` |
| `teachers` | `subject` | `ECA`, `ELT`, `ambos`, `ninguno` | `ninguno` |
| `bundles` | `role` | `student`, `teacher` | `student` |
| `bundles` | `type` | `ELT`, `Plan Lector`, `Imagina`, `Wikids`, `Pienso Contigo`, `Complemento` | `ELT` |
| `school_consultants` | `role` | `digital`, `eca`, `elt`, `ventas` | — |
| `tarea_si_colegio` | `status` | `pendiente`, `en_proceso`, `realizada` | `pendiente` |

---

## Claves foráneas y acciones

| Tabla | Columna | Referencia | ON DELETE |
|-------|---------|-----------|-----------|
| `consultants` | `user_id` | `users(id)` | CASCADE |
| `schools` | `consultant_id` | `consultants(id)` | SET NULL |
| `school_level` | `school_id` | `schools(id)` | CASCADE |
| `school_level` | `level_id` | `levels(id)` | CASCADE |
| `school_level_process` | `school_level_id` | `school_level(id)` | CASCADE |
| `school_level_process` | `process_id` | `processes(id)` | CASCADE |
| `school_level_process` | `completed_by` | `consultants(id)` | SET NULL |
| `visits` | `school_id` | `schools(id)` | CASCADE |
| `visits` | `consultant_id` | `consultants(id)` | CASCADE |
| `tickets` | `school_id` | `schools(id)` | CASCADE |
| `tickets` | `consultant_id` | `consultants(id)` | CASCADE |
| `teachers` | `school_id` | `schools(id)` | CASCADE |
| `teacher_roles` | `teacher_id` | `teachers(id)` | CASCADE |
| `teacher_books` | `teacher_id` | `teachers(id)` | CASCADE |
| `teacher_books` | `adoption_id` | `adoptions(id)` | CASCADE |
| `students` | `school_id` | `schools(id)` | CASCADE |
| `mee_admins` | `school_id` | `schools(id)` | CASCADE |
| `school_adoption` | `school_id` | `schools(id)` | CASCADE |
| `school_adoption` | `adoption_id` | `adoptions(id)` | CASCADE |
| `school_adoption` | `level_id` | `levels(id)` | SET NULL |
| `school_bundle` | `school_id` | `schools(id)` | CASCADE |
| `school_bundle` | `bundle_id` | `bundles(id)` | CASCADE |
| `bundle_resurtidos` | `school_id` | `schools(id)` | CASCADE |
| `bundle_resurtidos` | `bundle_id` | `bundles(id)` | CASCADE |
| `bundle_resurtidos` | `user_id` | `users(id)` | SET NULL |
| `school_consultants` | `school_id` | `schools(id)` | CASCADE |
| `school_consultants` | `consultant_id` | `consultants(id)` | CASCADE |
| `tareas_si` | `created_by` | `users(id)` | CASCADE |
| `tarea_si_colegio` | `tarea_si_id` | `tareas_si(id)` | CASCADE |
| `tarea_si_colegio` | `school_id` | `schools(id)` | CASCADE |
| `tarea_si_colegio` | `updated_by` | `users(id)` | SET NULL |
| `activity_logs` | `user_id` | `users(id)` | SET NULL |
| `activity_logs` | `school_id` | `schools(id)` | SET NULL |

> **CASCADE** = si el registro padre se elimina, los hijos también se eliminan.  
> **SET NULL** = si el padre se elimina, la FK queda en NULL (registro hijo se conserva).
