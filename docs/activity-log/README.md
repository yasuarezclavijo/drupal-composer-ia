# 📜 Activity Log

Registro histórico y trazable de cada tarea orquestada por el agente **Coordinador** (`/agent:coordinator "..."`).

Cada archivo de este directorio documenta una tarea de principio a fin: el requisito recibido, cómo se descompuso, qué agentes participaron, los tests diseñados antes de implementar (TDD: red-green-refactor), qué se implementó, qué validaciones (PHPCS/PHPStan/PHPUnit/Security/Accesibilidad) pasaron, y el resultado final (commits, pendientes).

## Cómo se genera

1. Al iniciar una tarea, el Coordinador crea `docs/activity-log/YYYY-MM-DD-<slug>.md` a partir de [`_TEMPLATE.md`](_TEMPLATE.md).
2. Lo va completando a medida que avanza cada fase del flujo de trabajo (ver [agents/coordinator.md](../../agents/coordinator.md)).
3. Al finalizar, agrega una fila a la tabla de abajo.

## Convención de nombres

```
docs/activity-log/YYYY-MM-DD-<slug-del-requisito>.md
```

`slug-del-requisito` es una versión corta en kebab-case del requisito (máx. 5 palabras). Si ya existe un archivo con el mismo nombre y fecha, se agrega un sufijo numérico: `-2`, `-3`, etc.

## Índice

| Fecha | Tarea | Estado | Archivo |
|---|---|---|---|
| 2026-06-10 | Fix quality gates + estándares Drupal/DrupalPractice | ✅ Completado | [2026-06-10-fix-quality-gates-drupal-standards.md](2026-06-10-fix-quality-gates-drupal-standards.md) |
| 2026-06-18 | Diseño arquitectura de capas de políticas (kadabra-core) | ✅ Completado | [2026-06-18-policy-layers-architecture.md](2026-06-18-policy-layers-architecture.md) |

## Plantilla

Ver [_TEMPLATE.md](_TEMPLATE.md).
