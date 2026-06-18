---
policy: {{nombre-kebab-case}}
version: {{X.Y}}
layer: 1
technology: drupal
extends: {{política-de-capa-0-que-extiende}}
updated: {{YYYY-MM-DD}}
---

# {{Nombre}} — Política de Plataforma Drupal (Capa 1)

> **Política de Capa 1.**
> Extiende `core/{{nombre-politica-core}}.md` con implementaciones específicas de Drupal 11.
> Los proyectos (Capa 2) no pueden excluir esta política.

## Cómo leer esta política

{{Descripción de la relación con la política de Capa 0 que extiende}}

---

## {{Área 1 — Nombre de la categoría OWASP o estándar}}

### {{Patrón específico de Drupal}}

```php
// MALO:
{{ejemplo de código problemático}}

// BUENO:
{{ejemplo de código correcto}}
```

**Señales de fallo específicas de Drupal:**
- {{Item 1}}
- {{Item 2}}

---

## {{Área 2}}

{{...}}

---

## Checklist adicional Drupal

Además de los puntos de la política de Capa 0, verificar:

- [ ] {{Item Drupal-específico 1}}
- [ ] {{Item Drupal-específico 2}}

---

## Guía para escribir políticas de Capa 1

Esta plantilla está en `.claude/policies/platform/` del repo `kdb/drupal-agentic-blueprint`.

**Cuándo agregar una política de Capa 1:**
- Es una extensión Drupal-específica de una política de Capa 0 existente
- Aplica a todos los proyectos Drupal (no solo a uno)
- La define el equipo de arquitectura Drupal de kadabrait_uy

**Cuándo NO es Capa 1:**
- Solo aplica a un proyecto → Capa 2 (`project/`)
- Es agnóstica a Drupal → Capa 0 (PR al repo `kadabrait_uy/kadabra-core`)

**Proceso:**
1. Abrir PR en `github.com/kadabrait_uy/drupal-agentic-blueprint`
2. Seguir este template
3. El archivo se distribuye automáticamente a nuevos proyectos vía `composer update`
