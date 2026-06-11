# Configuración Claude Code — Drupal 11

## Proyecto

Sistema de gestión de campañas de marketing en Drupal 11.3+ / PHP 8.2+.

Directorios de código custom:
- `web/modules/custom/` — módulos
- `web/themes/custom/` — temas
- `web/profiles/custom/` — perfiles
- `web/modules/custom/*/src/Plugin/rest/resource/` — endpoints REST

## Comandos esenciales

> **Detección de DDEV**: si existe `.ddev/config.yaml` en la raíz del proyecto y el
> binario `ddev` está disponible, anteponer `ddev` a TODOS los comandos `composer` y
> `drush` de abajo (ej. `ddev composer qa`, `ddev drush cr`). Si no existe `.ddev/`,
> ejecutarlos directos tal como están.

```bash
composer qa            # PHPCS (Drupal/DrupalPractice) + PHPStan (correr antes de cualquier commit)
composer test          # PHPUnit (sin cobertura)
composer test:coverage # PHPUnit + cobertura (mínimo 70%, requiere Xdebug — ver abajo)
composer fix           # Auto-fijar PHPCS
composer lint:phpcs    # Solo PHPCS
composer lint:phpstan  # Solo PHPStan (nivel 5)
composer audit         # Auditar dependencias por vulnerabilidades

ddev start             # Levantar entorno DDEV
ddev drush cr          # Limpiar caches Drupal
ddev drush updb        # Correr updates de DB
```

> **Cobertura de código**: la imagen `webimage` de DDEV no incluye `pcov`
> (no se puede instalar vía `install-php-extensions`). Usar Xdebug:
>
> ```bash
> ddev xdebug on
> ddev exec "XDEBUG_MODE=coverage composer test:coverage"
> ```
>
> `ddev composer ...` fuerza internamente `XDEBUG_MODE=off`, por lo que
> `ddev composer test:coverage` **no** generará cobertura — usar siempre
> `ddev exec` (o el comando `ddev test-coverage` si está disponible).

> **Drush 13**: comandos como `drush block:create` (de Drush 8/9) ya NO
> existen. Para crear entidades por código (bloques, etc.) usar
> `drush php:eval` con la API de entidades, p.ej.:
> `drush php:eval "\Drupal\block\Entity\Block::create([...])->save();"`

## Agentes disponibles

Los agentes están en `.claude/agents/`. Usar siempre via Task tool para contexto aislado.

| Agente | Cuándo |
|--------|--------|
| `coordinator` | Punto de entrada para cualquier tarea compleja |
| `drupal-architect` | Antes de implementar: diseño técnico |
| `tdd-specialist` | Después del diseño: tests que fallan (red) |
| `code-reviewer` | Después de implementar: PHPCS, PHPStan, coverage |
| `security-reviewer` | Para APIs, formularios, auth, datos sensibles |
| `accessibility-reviewer` | Para templates Twig, componentes UI, formularios |

**Flujo obligatorio para features nuevas:**
1. drupal-architect (Task) → diseño
2. tdd-specialist (Task) → tests en rojo
3. Implementación (máximo 3 intentos para verde)
4. code-reviewer (Task) → quality gates
5. security-reviewer (Task) → auditoría
6. accessibility-reviewer (Task) → solo si hay UI

## Skills disponibles

- `skills/create-module.md` — crear módulo Drupal completo con tests
- `skills/create-content-type.md` — crear content type con campos
- `skills/create-api-endpoint.md` — crear REST endpoint con seguridad

## Reglas de desarrollo

**Git:**
- Mensajes en español, formato: `[TYPE]: descripción breve`
- Types: feat, fix, docs, refactor, test, perf, chore
- Autor: Yeison A. Suarez <yasuarezclavijo@gmail.com>

**Calidad (no negociable):**
- `composer qa` debe pasar antes de cualquier commit
- Tests escritos ANTES de la implementación (TDD)
- Cobertura mínima 70% para código nuevo
- No dejar debug code: die(), var_dump(), dpm(), syslog()

**Seguridad:**
- Nunca hardcodear API keys, passwords o tokens
- Usar `\Drupal::config()` o `getenv()` para secretos
- Validar siempre el input del usuario
- Verificar `$entity->access()` antes de retornar datos

**Contrib modules aprobados:**
search_api, views, webform, field_group, linkit, entity_usage, rules, hook_event_dispatcher

## Documentación

- Módulos nuevos: README en el módulo
- APIs públicas: docblocks con @param y @return
- Cambios importantes: actualizar CHANGELOG.md
- Cada tarea: entry en `docs/activity-log/` (usar `_TEMPLATE.md`)

## Arquitectura

Ver `docs/architecture.md` para decisiones de diseño.
Ver `docs/quality-gates.md` para detalle de quality gates.
