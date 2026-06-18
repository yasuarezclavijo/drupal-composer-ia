# 2026-06-18 — Diseño de arquitectura de capas de políticas

**Estado**: ✅ Completado (diseño)  
**Agentes involucrados**: —  
**Tipo de tarea**: Arquitectura / documentación

## Requisito original

> Agregar a lo que tenemos actualmente kadabrait_uy/kadabra-platform un nuevo repo que sea
> transversal a todas las tecnologías. Definir una forma de sobrescribir a nivel de proyecto.
> Que exista una capa de skills o .md transversales que cada agente oriente sus desarrollos
> a una "librería" o "repo" primario que se marque como dependencia y priorice eso —
> OWASP, accesibilidad — construidas por expertos, transversales y agnósticas a la tecnología.
> Sin copiar y pegar. Con herencia. Tres capas, documentado.

## Decisiones de diseño tomadas

- **Alcance de esta tarea**: solo diseño y documentación (implementación es tarea separada)
- **Override Capa 2**: solo adiciones — OWASP y WCAG son no-negociables, no se pueden quitar
- **Contenido Capa 0**: solo políticas `.md` para agentes Claude (sin config de linters)

## Resultado

Documento de arquitectura creado: [`docs/policy-layers-architecture.md`](../policy-layers-architecture.md)

### Resumen de la arquitectura

**3 capas via Composer:**
```
kadabrait_uy/kadabra-core  →  kdb/drupal-agentic-blueprint  →  [proyecto]
     (Capa 0)                         (Capa 1)                  (Capa 2)
```

**Mecanismo de herencia:**
- Los archivos de política `.md` viven en `.claude/policies/core/` (Capa 0),
  `.claude/policies/platform/` (Capa 1) y `.claude/policies/project/` (Capa 2)
- Los agentes tienen instrucciones explícitas para leer esos archivos al inicio
- Capa 0 se instala como dependencia Composer de Capa 1 automáticamente
- Capa 2 puede solo agregar. Si intenta contradecir Capa 0, el agente lo reporta y lo ignora

**Políticas iniciales de Capa 0:**
- `owasp-top10.md` — OWASP Top 10 (2021)
- `wcag-21-aa.md` — WCAG 2.1 AA
- `auto-documentation.md` — Modelo de auto-documentación

**Agentes de Capa 1 que se actualizarán:**
- security-reviewer (lee owasp-top10)
- accessibility-reviewer (lee wcag-21-aa)
- code-reviewer (lee auto-documentation)
- drupal-architect (lee auto-documentation + platform)
- coordinator (lee todos)

## 8. Resultado final

- **Estado**: ✅ Diseño completado
- **Archivos creados**: `docs/policy-layers-architecture.md`
- **Pendientes / próximos pasos**:
  - Tarea separada: crear repo `kadabrait_uy/kadabra-core`
  - Tarea separada: actualizar drupal-blueprint para depender de kadabra-core
  - Tarea separada: actualizar los 5 agentes con sección de carga de políticas
  - Ver roadmap completo en §13 del documento de arquitectura
