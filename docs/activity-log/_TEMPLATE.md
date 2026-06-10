# {{FECHA}} — {{Título breve de la tarea}}

**Estado**: 🟡 En progreso
**Agentes involucrados**: Coordinador, ...

## Requisito original

> {{Texto literal del requisito recibido}}

## 1. Análisis y descomposición (Coordinador)

- **Subtareas identificadas**:
  - ...
- **Áreas de impacto**: código / seguridad / accesibilidad / performance
- **Constraints y supuestos**:
  - ...
- **Agentes asignados**:
  - ...

## 2. Diseño (Drupal Architect)

_Omitir si no aplica._

- ...

## 3. TDD — Diseño de pruebas (Red-Green-Refactor)

- **Casos de prueba diseñados**:
  - ...
- **Tests creados** (deben fallar al inicio — red):
  - `tests/src/Unit/...`
  - `tests/src/Kernel/...`
  - `tests/src/Functional/...`
- **Resultado `composer test` (red)**: ❌ FAIL esperado — `X` tests fallando por falta de implementación

### Intentos de implementación (green) — máx. 3

| # | Cambio realizado | Resultado `composer test` |
|---|---|---|
| 1 | ... | ... |
| 2 | ... | ... |
| 3 | ... | ... |

- **Resultado final**: ✅ PASS (verde) / ⛔ Bloqueado tras 3 intentos (completar resumen abajo)

#### Resumen de bloqueo (solo si no se llegó a verde en 3 intentos)

_Omitir si se llegó a verde._

- **Hipótesis sobre la causa raíz**: ...
- **Preguntas / posibles enfoques**: ...
- **Siguiente paso**: esperando indicación del usuario

## 4. Implementación

- **Archivos creados**:
  - `ruta/al/archivo`
- **Archivos modificados**:
  - `ruta/al/archivo`
- **Comandos ejecutados**:

  | Comando | Resultado |
  |---|---|
  | `composer qa` | ✅ PASS |
  | `composer test` | ✅ PASS (cobertura: XX%) |

## 5. Code Review

_Omitir si no aplica._

- PHPCS: ...
- PHPStan: ...
- PHPUnit: ...

## 6. Security Review

_Omitir si no aplica._

- ...

## 7. Accessibility Review

_Omitir si no aplica._

- ...

## 8. Resultado final

- **Estado**: ✅ Completado / 🟡 En progreso / ⛔ Bloqueado
- **Commit(s)**: `tipo: descripción`
- **Pendientes / próximos pasos**:
  - ...
