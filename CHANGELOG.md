# Changelog

Todos los cambios relevantes a este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Activity Log del Coordinador**: registro completo y trazable de cada tarea en `docs/activity-log/` (un archivo por tarea + índice cronológico). El Coordinador documenta análisis, descomposición, agentes involucrados, archivos tocados, comandos ejecutados y resultado final de cada tarea. Ver [docs/activity-log/README.md](docs/activity-log/README.md).
- **PROMPTS.md**: referencia rápida de comandos y prompts para cada flujo del blueprint (instalación, agentes, skills, quality gates, git, DDEV, troubleshooting).
- **TDD Specialist**: nuevo agente (`agents/tdd-specialist.md`, `/agent:tdd-specialist`) que diseña casos de prueba y escribe tests Unit/Kernel/Functional ANTES de la implementación, siguiendo el ciclo Red-Green-Refactor. El Coordinador lo invoca como segundo paso de cualquier feature o bug fix, justo después del diseño del Drupal Architect. El activity log ahora incluye una sección dedicada a esta fase.
- **Límite de 3 intentos para la fase Green**: si tras 3 ejecuciones de `composer test` la implementación no logra ponerse en verde, el TDD Specialist y el Coordinador detienen la tarea, generan un "Resumen de bloqueo" (tests objetivo, intentos realizados, hipótesis de causa raíz, preguntas/posibles enfoques y siguiente paso) y marcan la tarea como `⛔ Bloqueado` en el activity log, a la espera de indicaciones del usuario. Plantilla en [agents/tdd-specialist.md](agents/tdd-specialist.md#template-resumen-de-bloqueo-3-intentos-sin-verde).
- **Skills reordenados a TDD**: `skills/create-module.md`, `skills/create-content-type.md` y `skills/create-api-endpoint.md` ahora escriben primero los tests (red) y luego implementan hasta verde (green, máx. 3 intentos), reflejando el ciclo Red-Green-Refactor en cada workflow.
- **`quality/phpunit.xml.dist`**: configuración de PHPUnit 11 para Drupal 11 (bootstrap `web/core/tests/bootstrap.php`, `HtmlOutputLogger`, `DebugDump`, testsuites unit/kernel/functional/functional-javascript y `<source>` con cobertura para `web/{modules,profiles,themes}/custom`). El installer la copia a `/phpunit.xml` en el proyecto destino, sustituyendo el placeholder `__SIMPLETEST_BASE_URL__` con la URL DDEV detectada (o `http://localhost` si no hay DDEV).
- **Comando `ddev test-coverage`** (`templates/ddev-test-coverage`, copiado a `.ddev/commands/web/`): ejecuta `XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html=coverage --coverage-text`, ya que `pcov` no está disponible en la imagen webimage de DDEV y `ddev composer <script>` fuerza `XDEBUG_MODE=off`.
- **`docs/architecture.md`**: nueva sección "Dependencia implícita del perfil `standard`" documentando la colisión de `field.storage.node.body` cuando un módulo custom declara un content type con campo `body`.
- **`docs/requirements/`**: el installer copia `requirements/README.md` y `requirements/_TEMPLATE.md` del blueprint a `docs/requirements/` en el proyecto destino (sin sobreescribir si ya existen), con la plantilla de especificación de requerimientos que el Coordinador consume vía `@coordinator Ejecuta el requerimiento docs/requirements/<archivo>.md ...`.

### Changed

- **PHPCS: Drupal + DrupalPractice en lugar de PSR12**. `quality/phpcs.xml` ahora usa `<rule ref="Drupal"/>` y `<rule ref="DrupalPractice"/>` (de `drupal/coder`), declara `installed_paths` para resolver los sniffs, y amplía `extensions` a `php,module,inc,install,profile,theme,info,yml,js,css,txt,md` para evitar falsos positivos en archivos no-PHP.
- **`require-dev` del blueprint**: ya no requiere `drupal/core-dev` (entra en conflicto con `drupal/coder ^9.0`, que pin a `drupal/coder ^8.3.30`). En su lugar se listan explícitamente sus paquetes de testing transitivos (`phpunit/phpunit`, `behat/mink*`, `symfony/*`, `mikey179/vfsstream`, etc.). Las namespaces `Drupal\Tests\`, `Drupal\KernelTests\`, etc. siguen funcionando porque `core/tests/bootstrap.php` las registra en runtime vía el ClassLoader de Composer.
- **`composer.json` (dependencias)**: se agrega `drush/drush ^13.7` a `require`.
- **Scripts de Composer**: `lint:phpcs` y `lint:phpstan` ahora invocan `bash scripts/*.sh` (antes `@php scripts/*.sh`, que ejecutaba el script bash como texto PHP y siempre retornaba éxito, dejando `composer qa` como un no-op silencioso). `test` queda como `vendor/bin/phpunit` (sin cobertura, funciona sin Xdebug/pcov) y se agrega `test:coverage` (`XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html=coverage --coverage-text`) para el flujo de cobertura.
- **`.claude/commands/create-content-type.md`**: el ejemplo de test Kernel ahora incluye `#[RunTestsInSeparateProcesses]` (requerido por Drupal 11.3+, será excepción en Drupal 12) y documenta la excepción del campo `body` (no exportar `field.storage.node.body.yml` bajo el perfil `standard`).
- **`config.use-github-api`**: el installer fuerza `"use-github-api": false` en el `composer.json` del proyecto destino (si no está definido) para evitar rate-limiting de la API de GitHub al resolver el propio repositorio VCS del blueprint.

### Fixed

- **Duplicación de responsabilidades con kadabra-core**: el `installer.php` ya no copia `.claude/logs/_TEMPLATE.md` ni crea `.claude/policies/project/README.md` / `_TEMPLATE.md` — esos archivos son territorio exclusivo de `kadabrait_uy/kadabra-core` (que es dependencia directa de este paquete y siempre se instala). Se eliminaron los archivos `.claude/policies/project/README.md` y `_TEMPLATE.md` del repo del blueprint. Se actualizó el roadmap en `docs/policy-layers-architecture.md` (ítem 7) para reflejar la propiedad correcta.



- **`composer qa` no-op silencioso** (CRÍTICO): los scripts `lint:phpcs`/`lint:phpstan` ejecutaban `@php scripts/*.sh`, que con scripts bash simplemente vuelca el código fuente como texto y retorna `exit 0`. Corregido a `bash scripts/*.sh`. El installer aplica este fix automáticamente a proyectos ya generados vía `composer update kdb/drupal-agentic-blueprint` (`broken_scripts_fixes`).
- **`phpunit.xml` ausente en la raíz del proyecto**: provocaba fallo inmediato de `composer test`. El installer ahora copia `quality/phpunit.xml.dist` a `/phpunit.xml` (sin sobrescribir si ya existe).
- **`web/sites/simpletest/browser_output` ausente**: requerido por `HtmlOutputLogger`; el installer crea el directorio con `.gitkeep`.
- **Documentación de cobertura (Xdebug/pcov)**: `CLAUDE.md` documenta el flujo `ddev xdebug on` + `ddev exec "XDEBUG_MODE=coverage composer test:coverage"` (o `ddev test-coverage`), señalando que `ddev composer <script>` fuerza `XDEBUG_MODE=off` y por tanto no sirve para cobertura.
- **Drush 13**: `CLAUDE.md` documenta que `drush block:create` (Drush 8/9) ya no existe en Drush 13; usar `drush php:eval` con la Entity API (`\Drupal\block\Entity\Block::create([...])->save();`).

## [1.0.0] - 2024-06-08

### Added

- **Quality Gates determinísticos**
  - PHPCS con estándares Drupal y DrupalPractice (`drupal/coder`)
  - PHPStan análisis estático nivel 5
  - PHPUnit con cobertura mínima 70%
  - TwigCS para validación de templates
  - GrumPHP como orquestador de validaciones pre-commit

- **Arquitectura Multiagente**
  - Coordinador: Orquestación de equipos
  - Drupal Architect: Diseño arquitectónico
  - Code Reviewer: Validación de estándares
  - Security Reviewer: Auditoría de seguridad
  - Accessibility Reviewer: WCAG 2.1 compliance

- **Skills (Workflows automatizados)**
  - create-module: Crear módulo Drupal completo
  - create-content-type: Crear content type con campos
  - create-api-endpoint: Crear REST endpoint
  - review-pr: Revisar PR contra todos los gates

- **Documentación completa**
  - AGENTS.md: Descripción de agentes
  - CLAUDE.md: Configuración para Claude Code
  - agents/: Detalles de cada agente
  - skills/: Procedimientos paso a paso
  - docs/architecture.md: Visión general
  - docs/quality-gates.md: Guía de validaciones
  - docs/coding-standards.md: Estándares de código

- **Configuración de validaciones**
  - phpcs.xml: Configuración de PHP CodeSniffer
  - phpstan.neon: Configuración de PHPStan
  - grumphp.yml: Configuración de GrumPHP
  - Scripts wrapper para manejo de directorios vacíos

- **Integración DDEV**
  - Scripts detectan DDEV y usan ddev php cuando disponible
  - Fallback a native php si DDEV no está disponible
  - Configuración compatible con .ddev/config.yaml

- **Git hooks**
  - Pre-commit validation
  - Commit message format enforcement
  - Debug code detection
  - Merge conflict marker detection

### Features

#### Coordinador Agente
- Orquesta trabajo de agentes especializados
- Análisis de requisitos complejos
- Validación de soluciones completas
- Detección de conflictos entre constraints

#### Drupal Architect Agente
- Diseño de módulos, temas, perfiles
- Data structure design
- API design (REST, JSON:API)
- Performance optimization
- Migration strategy

#### Code Reviewer Agente
- PHPCS compliance validation
- PHPStan type safety (nivel 5+)
- PHPUnit test coverage (70%+)
- Docblock validation (PSR-5)
- Performance review

#### Security Reviewer Agente
- SQL injection detection
- XSS vulnerability detection
- Input/output validation
- Authentication and authorization review
- Secrets management
- OWASP Top 10 compliance
- Dependency audit

#### Accessibility Reviewer Agente
- WCAG 2.1 AA compliance
- Semantic HTML validation
- ARIA labels and roles
- Color contrast validation
- Keyboard navigation
- Focus management

### Scripts Incluidos

- `scripts/lint-php.sh`: PHPCS wrapper con detección de directorios vacíos
- `scripts/phpstan-wrapper.sh`: PHPStan wrapper con validación de paths
- `scripts/phpcbf-wrapper.sh`: PHPCBF auto-fix wrapper
- `scripts/twig-lint-wrapper.sh`: TwigCS wrapper para templates
- `scripts/grumphp.sh`: DDEV environment detector

### Configuración por Proyecto

- CLAUDE.md: Personalización de agentes y validaciones
- Quality gates configurables por equipo
- Composer scripts para fácil invocación

### Compatibilidad

- Drupal 11.3+
- PHP 8.2+
- DDEV (recomendado)
- macOS, Linux, Windows (con WSL)

## Notas de la versión

### Qué incluye v1.0.0

✅ **Completamente funcional y productivo**

- Todos los quality gates implementados
- Todos los agentes documentados
- Todos los skills creados
- Documentación exhaustiva
- Listo para usar en nuevos proyectos

### Qué está planeado para v2.x

- [ ] CI/CD integration (GitHub Actions)
- [ ] Code quality dashboard
- [ ] Test coverage trends
- [ ] Performance benchmarking
- [ ] Automated security audits

### Qué está planeado para v3.x

- [ ] Self-healing (auto-fix AI)
- [ ] ML-based recommendations
- [ ] Full observability
- [ ] Multi-team orchestration

### Conocidas limitaciones

1. **Quality gates solo locales**: v1 no integra CI/CD, validaciones ejecutadas localmente
2. **Agentes manuales**: Coordinador y Reviewers requieren invocación explícita
3. **Sin métricas históricas**: No hay tracking de trends de coverage/quality
4. **DDEV recomendado**: Funciona con PHP nativo pero DDEV es preferido

### Mejoras respecto a plantilla genérica

1. **Drupal-específico**: Validaciones especializadas para Drupal 11 (PHPStan Drupal)
2. **Multiagente**: No es solo "code review", sino arquitectura completa de especialistas
3. **Determinístico**: Gates automáticos reproducibles en cualquier máquina
4. **Documentado**: Cada agente, skill y gate tiene documentación exhaustiva
5. **Testeable**: Incluye template de tests e instrucciones para PHPUnit

### Migración desde proyectos legacy

Si tienes un proyecto Drupal existente:

1. Instala: `composer require kdb/drupal-agentic-blueprint`
2. Ejecuta installer: `php scripts/installer.php install`
3. Valida configuración: `composer qa`
4. Agrega tests: `composer test`
5. Comienza a usar skills: `/skill:create-module "nombre"`

### Soporte y contribuciones

- Reportar bugs: GitHub Issues
- Sugerencias: GitHub Discussions
- Contribuciones: Pull Requests
- Email: yasuarezclavijo@gmail.com

---

## Estructura de cambios futuros

Todas las versiones futuras (v2, v3) mantendrán backwards compatibility con v1:

- Nuevas features en `/agents` o `/skills` no rompen existentes
- Cambios en configuration son additive, no breaking
- CLAUDE.md puede extenderse sin romper proyectos v1

Para actualizar: `composer update kdb/drupal-agentic-blueprint`

Proyecto mantenido por **Yeison A. Suarez** y la comunidad de Drupal.
