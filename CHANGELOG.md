# Changelog

Todos los cambios relevantes a este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-06-08

### Added

- **Quality Gates determinísticos**
  - PHPCS con estándar PSR12
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

1. Instala: `composer require yeison/drupal-agentic-blueprint`
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

Para actualizar: `composer update yeison/drupal-agentic-blueprint`

Proyecto mantenido por **Yeison A. Suarez** y la comunidad de Drupal.
