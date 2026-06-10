# Drupal Agentic Blueprint v1

Blueprint reutilizable para proyectos Drupal CMS 2.0 con arquitectura multiagente, calidad de código determinística y convenciones optimizadas para Claude Code.

## ¿Qué incluye?

- **Quality Gates determinísticos**: PHPCS, PHPStan, TwigCS, GrumPHP, PHPUnit
- **Arquitectura multiagente**: Coordinador, Arquitecto, Code Reviewer, Security Reviewer, Accessibility Reviewer
- **Convenciones para Claude Code**: AGENTS.md, CLAUDE.md, skill definitions
- **Documentación base**: Arquitectura, estándares de código, quality gates
- **Estructura escalable**: Directorios listos para módulos, temas y perfiles customizados

## Instalación rápida

```bash
composer require kdb/drupal-agentic-blueprint
```

El blueprint se instalará automáticamente en la raíz de tu proyecto Drupal CMS 2.0.

## Instalación interactiva

```bash
composer require kdb/drupal-agentic-blueprint -- --interactive
```

Responde preguntas para personalizar el blueprint según tu agencia u organización.

## Archivos principales

- **AGENTS.md**: Definición de agentes disponibles
- **CLAUDE.md**: Configuración para Claude Code
- **PROMPTS.md**: Referencia rápida de comandos y prompts por flujo
- **agents/**: Detalle de cada agente
- **skills/**: Capacidades y workflows
- **quality/**: Configuración de validaciones
- **docs/**: Documentación completa

## Próximos pasos

1. Lee [docs/architecture.md](docs/architecture.md) para entender la estructura
2. Configura tus agentes en [AGENTS.md](AGENTS.md)
3. Comienza a crear módulos con [skills/create-module.md](skills/create-module.md)

## Versioning

- **v1.x**: Establece fundamentos de quality gates y arquitectura multiagente
- **v2.x**: Integrará CI/CD, testing avanzado, seguridad
- **v3.x**: Automatización completa, self-healing, observabilidad

Ver [CHANGELOG.md](CHANGELOG.md) para detalles de cada versión.

## Licencia

MIT
