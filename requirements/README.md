# 📋 Requirements

Especificaciones de requerimientos **estructuradas y autocontenidas**, escritas *antes* de invocar al equipo de agentes. El objetivo es que un requisito complejo (content type + vistas + cron + permisos + no-funcionales) quede completamente definido en un único documento, de forma que:

- El **Coordinador** no tenga que adivinar alcance, nombres de módulo o constraints.
- El **Drupal Architect** tenga el modelo de datos, vistas y cron ya especificados (su trabajo es diseñar *cómo*, no *qué*).
- El **TDD Specialist** pueda derivar casos de prueba directamente de las secciones 6/7/10, sin inventarlos.
- Los reviewers (Code/Security/Accessibility) sepan de antemano qué deben mirar con especial cuidado.

En otras palabras: **el prompt al coordinador se reduce a una ruta de archivo**, no a una descripción libre.

## Cómo funciona

1. Copiar [`_TEMPLATE.md`](_TEMPLATE.md) a `requirements/YYYY-MM-DD-<slug>.md`.
2. Completar **todas** las secciones. Las secciones 6 (reglas de negocio), 7 (Gherkin) y 11 (constraints/supuestos) son las más importantes: ahí se toman las decisiones para que los agentes no vuelvan a preguntar.
3. Cambiar el estado de `📝 Borrador` a `✅ Listo para ejecutar`.
4. Iniciar el flujo con el comando exacto (queda también guardado, con la ruta ya resuelta, en la sección 14 del propio documento):

   ```
   @coordinator Ejecuta el requerimiento requirements/YYYY-MM-DD-<slug>.md siguiendo el flujo obligatorio
   y genera el activity log correspondiente, consulta cualquier ambiguedad que detectes durante el analisis del requerimiento.
   ```

5. El Coordinador sigue el flujo habitual (ver [.claude/agents/coordinator.md](../.claude/agents/coordinator.md)) y genera la entrada correspondiente en `docs/activity-log/`.

## Convención de nombres

```
requirements/YYYY-MM-DD-<slug-del-requisito>.md
```

`slug-del-requisito` es una versión corta en kebab-case (máx. 5 palabras). Mismo criterio que `docs/activity-log/`.

## Índice

| REQ | Fecha | Título | Estado | Archivo |
|---|---|---|---|---|
| 001 | 2026-06-10 | Módulo `header_announcements` (anuncios en header con ARIA) — construido antes de este formato, sirve como referencia | ✅ Completado | [docs/activity-log/2026-06-10-header-announcements.md](../activity-log/2026-06-10-header-announcements.md) |
| 002 | 2026-06-11 | Módulo `news_publishing`: noticias con listado filtrable y despublicación automática por cron | ✅ Listo para ejecutar | [2026-06-11-noticias-despublicacion-automatica.md](2026-06-11-noticias-despublicacion-automatica.md) |

## Plantilla

Ver [`_TEMPLATE.md`](_TEMPLATE.md) — incluye una tabla "Cómo usar este documento" que mapea cada sección al agente que la consume.
