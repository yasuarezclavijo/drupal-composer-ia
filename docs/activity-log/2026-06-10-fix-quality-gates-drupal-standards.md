# 2026-06-10 — Corrección de quality gates y cambio a estándares Drupal/DrupalPractice

**Estado**: ✅ Completado
**Agentes involucrados**: Ninguno vía Task tool — trabajo directo de
infraestructura/configuración sobre el propio paquete
`kdb/drupal-agentic-blueprint`, a partir de hallazgos validados en
`blueprint-update-prompt.md` (generado en un proyecto consumidor tras la
primera ejecución real de `composer qa`/`composer test`).

## Requisito original

> Necesito actualizar el paquete de arranque kdb/drupal-agentic-blueprint
> (repo: https://github.com/yasuarezclavijo/drupal-composer-ia.git) para
> corregir varios problemas de infraestructura detectados en la primera
> ejecución real de `composer qa` y `composer test` sobre un proyecto
> generado a partir de él (Drupal 11.3 / PHP 8.4 / DDEV v1.25.2): 10 issues —
> ver sección 1.
>
> Adicional: "no quiero PSR12 quiero Drupal y DrupalPractice, así que
> ajustemos también la instalación de estos paquetes así podemos garantizar
> trabajar con estos estándares".
>
> Adicional (mismo hilo, tras completar lo anterior): "Esto es general
> drupal, eventualmente podrías retirar lo de DRUPAL CMS 2.0 donde sea que se
> referencie, porque al final aplica para él y cualquier drupal core".
>
> Nota de proceso: antes de implementar se leyó `blueprint-update-prompt.md`
> (raíz del proyecto), que contiene correcciones ya validadas y verificadas en
> un proyecto generado desde este blueprint (`composer qa` y `composer test`
> en verde tras aplicarlas). Se tomó como fuente autoritativa para versiones
> de paquetes, contenido de `phpunit.xml` y valores de configuración exactos
> (p.ej. `use-github-api: false`).

## 1. Análisis y descomposición

10 problemas de infraestructura + 2 cambios transversales:

1. **CRÍTICO** — `composer qa` no-op silencioso (`@php scripts/*.sh` sobre
   scripts bash, que `php` interpreta como texto y sale 0 sin ejecutar nada).
2. `phpcs.xml` con falsos positivos en archivos no-PHP (extensions +
   ruleset PSR12-only sin tokenizers CSS/JS).
3. Conflicto `drupal/coder ^9.0` (PHPCS 4.x) vs `drupal/core-dev ^11.3`
   (pin a `drupal/coder ^8.3.30`).
4. `phpunit.xml` ausente en la raíz del proyecto destino.
5. `web/sites/simpletest/browser_output/` ausente (requerido por
   `HtmlOutputLogger`).
6. Tests Kernel generados sin `#[RunTestsInSeparateProcesses]`
   (deprecated en Drupal 11.3+, excepción en Drupal 12).
7. Cobertura de código: `pcov` no disponible en la imagen webimage de
   DDEV v1.25.2; documentar flujo Xdebug y la limitación de
   `ddev composer <script>` (fuerza `XDEBUG_MODE=off`).
8. `drush/drush` ausente de `require`; documentar que Drush 13 retiró
   `block:create` (usar `drush php:eval` + Entity API).
9. Rate-limiting de la API de GitHub al resolver el VCS propio del
   blueprint (`use-github-api`).
10. Colisión `field.storage.node.body.yml` cuando un módulo custom
    declara un content type con campo `body` bajo el perfil `standard`.
11. **Cambio de estándar**: PSR12 → Drupal + DrupalPractice (`drupal/coder`),
    ajustando `phpcs.xml` (`installed_paths`, ruleset, `extensions`) y
    `require-dev`.
12. **Generalización**: retirar referencias a "Drupal CMS 2.0" en favor de
    "Drupal 11" / "Drupal core" genérico (el blueprint no es específico de
    Drupal CMS).

- **Áreas de impacto**: instalador (`scripts/installer.php`), quality gates
  (`quality/phpcs.xml`, nuevo `quality/phpunit.xml.dist`), plantillas DDEV
  (`templates/ddev-test-coverage`), skills
  (`.claude/commands/create-content-type.md`), documentación (`CLAUDE.md`,
  `README.md`, `INSTALLATION.md`, `docs/architecture.md`,
  `docs/quality-gates.md`, `CHANGELOG.md`, `DELIVERY.md`, `PROMPTS.md`,
  `.claude/agents/code-reviewer.md`), `composer.json`.
- **Constraints y supuestos**:
  - `blueprint-update-prompt.md` es la fuente autoritativa para versiones de
    paquetes, contenido de `phpunit.xml` y `use-github-api: false` (en vez de
    `github-protocols`). Se mantiene sin modificar como registro histórico.
  - El requisito explícito de Drupal/DrupalPractice prevalece sobre el punto
    5 de `blueprint-update-prompt.md` (que sugería restringir `extensions` a
    solo PHP bajo PSR12): se usa ruleset `Drupal` + `DrupalPractice` con
    `installed_paths` y `extensions` ampliado a
    `php,module,inc,install,profile,theme,info,yml,js,css,txt,md`.
  - `Drupal\Tests\`, `Drupal\KernelTests\`, etc. se registran en runtime vía
    `core/tests/bootstrap.php` + ClassLoader de Composer, por lo que no
    requerir `drupal/core-dev` no rompe el autoload de tests.
- **Agentes asignados**: ninguno (no aplica Task tool; cambios de
  configuración/documentación directos sobre el propio repo del blueprint).

## 2. Diseño

- **No requerir `drupal/core-dev`**: se listan explícitamente sus paquetes de
  testing transitivos en `require-dev` de `scripts/installer.php`
  (`behat/mink*`, `symfony/*`, `mikey179/vfsstream`, `phpunit/phpunit`,
  etc.), manteniendo `drupal/coder ^9.0`.
- **`quality/phpunit.xml.dist`** (nuevo) con placeholder
  `__SIMPLETEST_BASE_URL__`, sustituido por `ensure_phpunit_config()` /
  `detect_ddev_base_url()` (lee `.ddev/config.yaml`, fallback
  `https://default.ddev.site` o `http://localhost`).
- **`broken_scripts_fixes`**: mapa de force-overwrite en
  `merge_composer_json()` para que `composer update
  kdb/drupal-agentic-blueprint` corrija automáticamente los scripts
  `lint:phpcs`/`lint:phpstan` rotos (`@php` → `bash`) en proyectos ya
  generados con versiones previas del blueprint.
- **Cobertura**: `composer test` queda como `vendor/bin/phpunit` (sin
  cobertura, no requiere Xdebug/pcov); `composer test:coverage` añade
  `XDEBUG_MODE=coverage ... --coverage-html=coverage --coverage-text`; nuevo
  comando `.ddev/commands/web/test-coverage`
  (`templates/ddev-test-coverage`, copiado por `copy_ddev_coverage_command()`
  solo si existe `.ddev/config.yaml`).
- **`phpcs.xml`**: `<rule ref="Drupal"/>` + `<rule ref="DrupalPractice"/>`,
  `<config name="installed_paths" value="../../drupal/coder/coder_sniffer"/>`
  (resuelto desde `vendor/squizlabs/php_codesniffer/`).
- **Generalización Drupal CMS**: se reemplazan menciones puntuales a
  "Drupal CMS 2.0" por "Drupal 11" / "Drupal core", aclarando donde aplica
  que Drupal CMS también usa el perfil `standard` (no se elimina la mención a
  Drupal CMS por completo, se generaliza para no implicar exclusividad).

## 3. TDD — Diseño de pruebas (Red-Green-Refactor)

_Omitido_: esta tarea es de infraestructura/configuración del propio paquete
`kdb/drupal-agentic-blueprint` (un composer-plugin), no añade comportamiento
de aplicación Drupal verificable con PHPUnit. La validación se hizo con
`php -l` y `composer validate` (ver sección 4).

## 4. Implementación

- **Archivos creados**:
  - `quality/phpunit.xml.dist`
  - `templates/ddev-test-coverage`
  - `docs/activity-log/2026-06-10-fix-quality-gates-drupal-standards.md`
    (este archivo)
- **Archivos modificados**:
  - `scripts/installer.php` — `$require` (`drush/drush ^13.7`),
    `$require_dev` (sustitutos de `drupal/core-dev`), `$composer_scripts`
    (`bash` en `lint:phpcs`/`lint:phpstan`, `test` sin cobertura,
    `test:coverage`, `audit`), `$broken_scripts_fixes`, nuevos métodos
    `ensure_test_dirs()`, `ensure_phpunit_config()`,
    `detect_ddev_base_url()`, `copy_ddev_coverage_command()`;
    `merge_composer_json()` (force-fix de scripts rotos +
    `config.use-github-api`); `next_steps()` ampliado.
  - `quality/phpcs.xml` — Drupal + DrupalPractice, `installed_paths`,
    `extensions` ampliado, nombre de ruleset generalizado.
  - `.claude/commands/create-content-type.md` —
    `#[RunTestsInSeparateProcesses]` en ejemplo Kernel + excepción
    `field.storage.node.body` + generalización "Drupal CMS" → "Drupal core".
  - `docs/architecture.md` — nueva sección 7 (dependencia del perfil
    `standard`) + referencias PSR12 → Drupal/DrupalPractice + generalización
    "Drupal CMS 2.0" → "Drupal 11".
  - `CLAUDE.md` — comandos esenciales (cobertura Xdebug, Drush 13) +
    título generalizado a "Drupal 11".
  - `README.md`, `INSTALLATION.md`, `composer.json`, `DELIVERY.md` —
    generalización "Drupal CMS 2.0" → "Drupal 11 (incluye Drupal CMS)".
  - `docs/quality-gates.md`, `CHANGELOG.md`, `PROMPTS.md`,
    `.claude/agents/code-reviewer.md` — referencias PSR12 →
    Drupal/DrupalPractice.
- **Comandos ejecutados**:

  | Comando | Resultado |
  |---|---|
  | `php -l scripts/installer.php` | ✅ PASS — sin errores de sintaxis |
  | `composer validate --no-check-publish` | ✅ PASS — JSON válido (warnings preexistentes sobre `version`, sin relación con esta tarea) |

## 5. Code Review

_Omitido_: cambios de configuración/documentación del blueprint, sin código
de aplicación Drupal nuevo (no aplica `composer qa`/`composer test` de un
proyecto consumidor).

## 6. Security Review

_Omitido_: sin manejo de datos sensibles, autenticación ni APIs públicas.

## 7. Accessibility Review

_Omitido_: sin cambios de UI ni templates Twig.

## 8. Resultado final

- **Estado**: ✅ Completado
- **Commit(s)**: pendiente de confirmación del usuario.
- **Pendientes / próximos pasos**:
  - Verificar en un proyecto consumidor real
    (`composer update kdb/drupal-agentic-blueprint`) que `composer qa` y
    `composer test` corren en verde con el nuevo `phpcs.xml`
    (Drupal/DrupalPractice) y el `require-dev` ampliado.
  - Confirmar que `composer test` (sin `--coverage-text`) no rompe en
    entornos con `failOnWarning`/`failOnPhpunitWarning` activos cuando no
    hay driver de cobertura (debería ser un no-op silencioso ahora que no se
    solicita cobertura por defecto).
