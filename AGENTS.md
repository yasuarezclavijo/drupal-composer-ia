# Agentes Disponibles

Este blueprint define un equipo de agentes especializados que trabajan en
conjunto para mantener calidad, seguridad y accesibilidad en el proyecto.

Cada agente es un archivo Markdown plano con frontmatter (`name`,
`description`) e instrucciones de rol, ubicado en `.claude/agents/`. No usan
sintaxis propietaria — son texto + ejemplos de código, así que **cualquier
asistente de IA** (no solo Claude Code) puede leerlos y adoptarlos como
prompt de sistema para una fase del trabajo. La sección
[Cómo adoptar un rol](#-cómo-adoptar-un-rol) explica ambos caminos.

## Comandos esenciales

> Si existe `.ddev/config.yaml`, anteponer `ddev` a `composer`/`drush`
> (ej. `ddev composer qa`).

```bash
composer qa     # PHPCS (Drupal/DrupalPractice) + PHPStan nivel 5
composer test   # PHPUnit
composer fix    # Auto-fijar PHPCS
```

Detalle completo (cobertura, Xdebug, Drush 13, etc.): [CLAUDE.md](CLAUDE.md).

## Arquitectura por capas (no negociable)

Cualquier clase expuesta al exterior (REST Resource, Controller, Form, Drush
command) es una capa **delgada**: permisos, parseo de input, mapeo de
excepciones y respuesta. Prohibido `getQuery()`, `loadMultiple()`,
`EntityTypeManagerInterface` o reglas de negocio ahí.

- **Service** (`src/Service/`): lógica de negocio, orquesta Repository.
- **Repository** (`src/Repository/`): único lugar con acceso a datos.

Ambos en `MODULO.services.yml` por DI. Detalle y ejemplo completo:
[.claude/commands/create-api-endpoint.md](.claude/commands/create-api-endpoint.md#arquitectura-de-capas-obligatoria)
y [docs/architecture.md](docs/architecture.md#6-resource-delgado--service--repository-vs-lógica-de-negocio-en-el-resource).

## Skills / workflows

Procedimientos reutilizables en `.claude/commands/`:

- [create-module.md](.claude/commands/create-module.md) — módulo Drupal completo con tests
- [create-content-type.md](.claude/commands/create-content-type.md) — content type con campos
- [create-api-endpoint.md](.claude/commands/create-api-endpoint.md) — REST endpoint en capas Resource/Service/Repository

En Claude Code se invocan como slash commands (`/create-module "nombre"`).
En otra IA: leer el archivo y seguirlo como receta paso a paso.

---

## 🎯 Coordinador

**Archivo**: [.claude/agents/coordinator.md](.claude/agents/coordinator.md)

Orquesta el trabajo de todos los agentes. Responsable de:
- Analizar requisitos nuevos y descomponerlos en subtareas
- Asignar tareas a agentes especializados en el orden correcto
- Validar que la solución es completa
- Resolver conflictos entre constraints
- Registrar cada tarea en [docs/activity-log/](docs/activity-log/)

**Cuándo usarlo**: Al inicio de una tarea grande o cuando hay múltiples
aspectos (código, seguridad, accesibilidad).

**Invocación**:
- Claude Code: Task tool con `subagent_type: coordinator` (o mencionar `@coordinator`)
- Otra IA: lee el archivo completo y usa su sección "Flujo de trabajo" como
  plan de fases, ejecutando tú mismo cada rol en el orden indicado

---

## 🏗️ Drupal Architect

**Archivo**: [.claude/agents/drupal-architect.md](.claude/agents/drupal-architect.md)

Especializado en arquitectura Drupal 11. Responsable de:
- Diseño de módulos y temas
- Decisiones de API y data structures (Entity vs. tabla custom, REST vs. JSON:API)
- Definir, para cada clase expuesta, qué va en Resource/Controller/Form,
  qué en Service y qué en Repository (ver [Arquitectura por capas](#arquitectura-por-capas-no-negociable))
- Integración con Drupal core y performance/cache strategy

**Cuándo usarlo**: Para diseño de features, refactoring, decisiones
arquitectónicas — siempre ANTES de escribir tests o código.

**Invocación**:
- Claude Code: Task tool con `subagent_type: drupal-architect`
- Otra IA: lee el archivo y produce el "Diseño técnico" usando su template

---

## 🧪 TDD Specialist

**Archivo**: [.claude/agents/tdd-specialist.md](.claude/agents/tdd-specialist.md)

Diseña casos de prueba y escribe tests ANTES de la implementación
(Red-Green-Refactor). Responsable de:
- Diseñar casos de prueba (happy path, edge cases, permisos) a partir del
  diseño del Architect
- Mapear el tipo de test por capa: **Service → Unit** (mock del Repository),
  **Repository → Kernel** (DB real), **Resource/Controller/Form → Functional**
  (HTTP/rutas/permisos)
- Escribir tests que fallen primero (red) sin errores de sintaxis
- Acompañar la fase Green con un límite de **3 intentos**: si al tercero
  sigue en rojo, generar un "Resumen de bloqueo" para el usuario
- Sugerir refactors una vez los tests están en verde

**Cuándo usarlo**: Justo después del diseño y ANTES de escribir código de
producción, para cualquier feature nueva o bug fix.

**Invocación**:
- Claude Code: Task tool con `subagent_type: tdd-specialist`
- Otra IA: lee el archivo y escribe los tests en
  `tests/src/{Unit,Kernel,Functional}/` siguiendo el mapeo por capa

---

## 🔍 Code Reviewer

**Archivo**: [.claude/agents/code-reviewer.md](.claude/agents/code-reviewer.md)

Valida código contra quality gates. Responsable de:
- PHPCS compliance (Drupal Coding Standards + DrupalPractice)
- PHPStan type safety (nivel 5)
- PHPUnit coverage ≥70%
- Estándares de documentación (docblocks)
- Separación de capas: ninguna lógica de negocio o acceso a datos fuera de
  `src/Service/`/`src/Repository/`

**Cuándo usarlo**: Después de implementar, antes de commit o PR, o para
auditar código existente.

**Invocación**:
- Claude Code: Task tool con `subagent_type: code-reviewer`
- Otra IA: corre `composer qa` y `composer test`, y usa el checklist del
  archivo para revisar manualmente lo que esos comandos no detectan
  (separación de capas, docblocks)

---

## 🔐 Security Reviewer

**Archivo**: [.claude/agents/security-reviewer.md](.claude/agents/security-reviewer.md)

Audita seguridad del código (OWASP Top 10). Responsable de:
- Inyección SQL y XSS
- Validación de entrada/salida
- Permisos (`$entity->access()`) antes de retornar datos
- Secretos y credenciales (nunca hardcodeados)
- Confirmar que las queries (`->select()`, `->getQuery()`, `EntityTypeManager`)
  solo existan en `src/Repository/` — si aparecen en
  Resource/Controller/Form es también un hallazgo de separación de capas

**Cuándo usarlo**: Features con datos sensibles, APIs públicas, formularios,
autenticación o integraciones externas.

**Invocación**:
- Claude Code: Task tool con `subagent_type: security-reviewer`
- Otra IA: corre `composer audit` y revisa el checklist del archivo punto
  por punto sobre los archivos modificados

---

## ♿ Accessibility Reviewer

**Archivo**: [.claude/agents/accessibility-reviewer.md](.claude/agents/accessibility-reviewer.md)

Valida accesibilidad Web (WCAG 2.1 AA). Responsable de:
- Semantic HTML, ARIA labels y roles
- Contrast ratios
- Keyboard navigation
- Accesibilidad en templates Twig y formularios

**Cuándo usarlo**: Cambios en UI/UX, temas, componentes interactivos,
formularios. **Skip** en módulos backend puros sin templates.

**Invocación**:
- Claude Code: Task tool con `subagent_type: accessibility-reviewer`
- Otra IA: usa el checklist WCAG 2.1 AA del archivo sobre los `.twig`/`.css`
  modificados

---

## 🚀 Flujo de trabajo recomendado

1. **Planificación**: Coordinador analiza el requisito
2. **Diseño**: Drupal Architect define la solución, incluyendo la separación
   Resource/Controller/Form → Service → Repository
3. **Tests primero (TDD)**: TDD Specialist escribe tests que fallan (red),
   mapeados por capa
4. **Desarrollo**: Código se escribe hasta pasar los tests (green, máx. 3
   intentos; si no se logra, "Resumen de bloqueo" para el usuario)
5. **Revisión**: Code Reviewer + Security Reviewer + Accessibility Reviewer
6. **Integración**: Coordinador valida que todo funciona junto y cierra el
   activity log

## 📋 Tabla de capacidades

| Agente | PHPCS | PHPStan | PHPUnit | Seguridad | Accesibilidad | Drupal API |
|--------|-------|---------|---------|-----------|---------------|-----------|
| Coordinador | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Drupal Architect | ✓ | ✓ | - | - | - | ✓✓ |
| TDD Specialist | - | - | ✓✓ | - | - | ✓ |
| Code Reviewer | ✓✓ | ✓✓ | ✓✓ | - | - | ✓ |
| Security Reviewer | ✓ | ✓ | - | ✓✓ | - | ✓ |
| Accessibility Reviewer | - | - | - | - | ✓✓ | ✓ |

## 🔄 Cómo adoptar un rol

**Claude Code**: usa el Task tool con `subagent_type: <nombre-del-agente>`
(coordinator, drupal-architect, tdd-specialist, code-reviewer,
security-reviewer, accessibility-reviewer). Cada uno corre en una instancia
separada con contexto limpio. El Coordinador puede orquestarlos por ti.

**Cualquier otra IA / herramienta** (Codex, Cursor, Aider, etc.): no hay
mecanismo de sub-agentes, así que el patrón es secuencial:

1. Abre `.claude/agents/<nombre-del-agente>.md` correspondiente a la fase actual.
2. Pega su contenido como instrucciones de sistema (o pídele a tu asistente
   que "actúe según estas instrucciones para esta tarea").
3. Al terminar esa fase, pasa al siguiente archivo según el
   [flujo recomendado](#-flujo-de-trabajo-recomendado) — el mismo archivo
   funciona como prompt de rol independientemente de la herramienta.

## 📚 Configuración por proyecto

`.claude/agents/` y `.claude/commands/` son la fuente de verdad portable —
funcionan igual en cualquier proyecto donde se instale el blueprint. Para
configuración específica de Claude Code (comandos del proyecto, reglas de
git, contrib modules aprobados) ver [CLAUDE.md](CLAUDE.md).
