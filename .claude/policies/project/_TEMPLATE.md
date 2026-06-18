---
policy: {{nombre-kebab-case}}
version: 1.0
layer: 2
project: {{nombre-del-proyecto}}
updated: {{YYYY-MM-DD}}
---

# {{Nombre de la política}} — {{Proyecto}}

## Contexto

> {{Por qué existe esta política para este proyecto.
> Ejemplo: "El cliente es una entidad financiera regulada. Tiene auditorías SOC 2 anuales."}}

---

## {{Área 1 — ej. Módulos contrib adicionales aprobados}}

Además de los módulos base del blueprint:
- `drupal/nombre-modulo`: ^X.0 — {{motivo de aprobación}}

## {{Área 2 — ej. Requisitos de performance}}

{{Descripción libre de las restricciones propias del proyecto}}

## {{Área 3 — ej. Integraciones externas}}

{{Contexto sobre APIs, sistemas legados u otras integraciones del cliente}}

---

> **Recordatorio**: Esta política solo puede AGREGAR requisitos.
> No puede excluir ni reducir las políticas de `.claude/policies/core/` o `.claude/policies/platform/`.
