# REQ-{{NNN}} — {{Título breve del requerimiento}}

**Fecha**: {{YYYY-MM-DD}}
**Solicitado por**: {{nombre / rol}}
**Prioridad**: {{Alta | Media | Baja}}
**Estado**: 📝 Borrador
**Módulo(s) propuesto(s)**: {{machine_name del módulo, convención `<área>_<funcionalidad>`}}

> 📌 Estados posibles: `📝 Borrador` → `✅ Listo para ejecutar` → `🟡 En progreso` → `✅ Completado` / `⛔ Bloqueado`.
> El Coordinador solo debe iniciar el flujo multiagente cuando el estado sea `✅ Listo para ejecutar`.

---

## 0. Cómo usar este documento

Esta tabla indica qué agente consume cada sección. No es obligatorio leerla, pero ayuda a entender por qué el documento está estructurado así: **cada sección existe porque un agente concreto la necesita** para no tener que volver a preguntar al usuario.

| Sección | Consumida por | Para qué |
|---|---|---|
| 1–4 | Coordinador | Descomposición inicial, activity log |
| 5, 8, 9 | Drupal Architect | Diseño técnico (modelo de datos, vistas, cron, cache) |
| 6, 7, 10 | TDD Specialist | Diseño de casos de prueba (fase roja) |
| 5 | Implementación | Código de producción (config, plugins, servicios) |
| 8.2 | Security Reviewer | Auditoría OWASP |
| 8.3 | Accessibility Reviewer | Auditoría WCAG 2.1 AA |
| 11 | Todos | Decisiones ya acordadas — no se vuelven a preguntar |
| 12 | Coordinador | Checklist de cierre / Definición de "Hecho" |
| 13 | Agente(s) específico(s) | Instrucciones puntuales adicionales |
| 14 | Usuario / Coordinador | Comando exacto para arrancar el flujo |

---

## 1. Resumen ejecutivo

{{1-3 frases: qué se construye y qué valor de negocio aporta. Debe poder leerse solo y entenderse.}}

## 2. Contexto y objetivo de negocio

- **Problema actual**: {{qué dolor o ausencia existe hoy}}
- **Objetivo**: {{qué se busca lograr, en términos de negocio, no técnicos}}
- **Métrica de éxito** (opcional): {{cómo se sabrá que funcionó}}

## 3. Alcance

### 3.1 Incluye

- {{ítem 1}}
- {{ítem 2}}

### 3.2 No incluye (out of scope)

- {{ítem explícitamente fuera de alcance, para evitar scope creep}}

## 4. Actores y permisos

| Rol | Puede | No puede |
|---|---|---|
| {{Anónimo}} | {{ver contenido publicado}} | {{editar / ver contenido sin publicar}} |
| {{Editor de X}} | {{crear/editar sus propios nodos de tipo X}} | {{publicar sin revisión / editar de otros}} |
| {{Administrador}} | {{todo lo anterior + administración de configuración}} | — |

## 5. Especificación funcional

### 5.1 Modelo de contenido

#### Content type: `{{machine_name}}` ({{Nombre legible}})

| Campo (label) | Machine name | Tipo | Cardinalidad | Requerido | Configuración | Notas |
|---|---|---|---|---|---|---|
| {{Título}} | `title` | string (core) | 1 | Sí | max 255 | — |
| {{...}} | `field_{{...}}` | {{text_long / entity_reference / image / datetime / boolean / ...}} | {{1 / ilimitado}} | {{Sí/No}} | {{settings relevantes}} | {{para qué se usa}} |

#### Vocabulario de taxonomía (si aplica): `{{machine_name}}` ({{Nombre legible}})

| Término de ejemplo | Notas |
|---|---|
| {{Término 1}} | {{...}} |

### 5.2 Vistas

#### Vista: `{{machine_name}}` — {{descripción}}

- **Display(s)**: {{Página en /ruta | Bloque | Feed}}
- **Tabla base**: `node_field_data` (u otra)
- **Filtros**:

  | Campo | Tipo de filtro | Expuesto | Operador | Valor por defecto |
  |---|---|---|---|---|
  | {{status}} | Publicado = Sí | No | = | 1 |
  | {{field_x}} | {{select / texto / rango de fechas / boolean}} | {{Sí/No}} | {{=, contains, between...}} | {{...}} |

- **Campos/columnas mostrados**: {{...}}
- **Orden**: {{campo, dirección, criterio de desempate}}
- **Paginación**: {{items por página, tipo de pager}}
- **Modo de visualización (row)**: {{teaser / campos / vista personalizada}}
- **Acceso**: {{permiso requerido}}
- **Cache**: {{tags, contexts, max-age}}

### 5.3 Tareas programadas (cron)

> Omitir esta subsección completa si el requerimiento no necesita cron.

- **Disparador**: `hook_cron()` (cron de Drupal)
- **Frecuencia esperada**: {{cada ejecución de cron del sitio / cada N horas vía Ultimate Cron si está disponible}}
- **Lógica**:
  1. {{paso 1: qué se consulta}}
  2. {{paso 2: qué se hace con los resultados}}
  3. {{paso 3: efectos secundarios — logging, invalidación de cache, notificaciones}}
- **Idempotencia**: {{qué pasa si se ejecuta dos veces seguidas sobre los mismos datos}}
- **Límite de lote (batch)**: {{constante configurable, ej. máx. N registros por ejecución}}
- **Testabilidad**: la lógica debe vivir en un **servicio** con un método público invocable directamente desde un test Kernel (sin esperar al cron real ni mockear el tiempo de forma compleja).

### 5.4 Bloques / UI adicional

> Omitir si no aplica.

- {{descripción de bloques, formularios o componentes adicionales}}

## 6. Reglas de negocio y casos límite

Numerar — el TDD Specialist debe convertir cada una en al menos un caso de prueba.

1. {{regla 1}}
2. {{caso límite: valores vacíos, fechas iguales, registros ya procesados, etc.}}

## 7. Criterios de aceptación (Gherkin)

```gherkin
Feature: {{nombre de la funcionalidad}}

  Scenario: {{nombre del escenario}}
    Given {{contexto/precondición}}
    When {{acción}}
    Then {{resultado esperado}}
```

Repetir un `Scenario` por cada regla de negocio relevante de la sección 6.

## 8. Requisitos no funcionales

### 8.1 Performance y cache

- {{tamaños de página, límites de batch, estrategia de cache tags/contexts/max-age}}

### 8.2 Seguridad

- {{accessCheck en queries, sanitización de output, validación de input, permisos mínimos}}

### 8.3 Accesibilidad

- {{WCAG 2.1 AA: labels de formularios expuestos, alt text, contraste, foco, semántica}}

### 8.4 Internacionalización

- {{idiomas soportados, `langcode` de la config, o "no aplica en este alcance"}}

## 9. Dependencias

- **Módulos core requeridos**: {{node, field, text, datetime, taxonomy, views, ...}}
- **Contrib aprobados usados**: {{de la lista en CLAUDE.md, o "ninguno"}}
- **Nuevas dependencias propuestas** (requieren aprobación explícita): {{o "ninguna"}}

## 10. Plan de pruebas (para TDD Specialist)

| Capa | Archivo propuesto | Casos a cubrir |
|---|---|---|
| Kernel | `tests/src/Kernel/{{...}}Test.php` | {{existencia de content type/campos, validación de nodo, casos de la sección 6}} |
| Kernel | `tests/src/Kernel/{{...}}Test.php` | {{servicio de cron: casos de la sección 6}} |
| Functional (opcional) | `tests/src/Functional/{{...}}Test.php` | {{acceso a vistas, filtros expuestos, permisos por rol}} |

## 11. Constraints y supuestos

> Decisiones ya acordadas con el usuario. Los agentes **no deben volver a preguntar esto**; si encuentran un conflicto, lo señalan pero no bloquean por esto.

1. Nombre de módulo propuesto: `{{machine_name}}` (convención `<área>_<funcionalidad>`). El Drupal Architect puede ajustarlo si encuentra una mejor convención, documentando el motivo.
2. {{supuesto 2}}
3. Quality gates obligatorios: `composer qa` + `composer test` (cobertura ≥70%), ejecutados dentro de DDEV.

## 12. Definición de "Hecho"

- [ ] Modelo de contenido (sección 5.1) instalable vía `config/install`
- [ ] Vistas (sección 5.2) creadas y funcionando
- [ ] Cron/servicio (sección 5.3) implementado, si aplica
- [ ] Tests de la sección 10 escritos primero (rojo) y luego en verde
- [ ] `composer qa` PASS
- [ ] `composer test` PASS (≥70% cobertura)
- [ ] Code Review aprobado
- [ ] Security Review aprobado
- [ ] Accessibility Review aprobado (si hay UI)
- [ ] README + CHANGELOG del módulo
- [ ] Entrada en `docs/activity-log/` creada y completada

## 13. Notas para los agentes

{{Instrucciones puntuales adicionales, o "ninguna".}}

## 14. Cómo ejecutar este requerimiento

```
@coordinator Ejecuta el requerimiento requirements/{{archivo}}.md siguiendo el flujo obligatorio
(drupal-architect → tdd-specialist → implementación → code-reviewer → security-reviewer → accessibility-reviewer)
y genera el activity log correspondiente.
```
