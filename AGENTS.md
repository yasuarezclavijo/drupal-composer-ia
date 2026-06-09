# Agentes Disponibles

Este blueprint define un equipo de agentes especializados que trabajan en conjunto para mantener calidad, seguridad y accesibilidad en el proyecto. Cada agente es un conjunto de instrucciones y capacidades que Claude Code puede invocar.

## 🎯 Coordinador

**Archivo**: [agents/coordinator.md](agents/coordinator.md)

Orquesta el trabajo de todos los agentes. Responsable de:
- Analizar requisitos nuevos
- Asignar tareas a agentes especializados
- Validar que la solución es completa
- Revisar conflictos entre constraints

**Cuándo usarlo**: En inicio de una tarea grande o cuando hay múltiples aspectos (código, seguridad, accesibilidad).

```bash
claude-code /agent:coordinator "implementar sistema de notificaciones"
```

---

## 🏗️ Drupal Architect

**Archivo**: [agents/drupal-architect.md](agents/drupal-architect.md)

Especializado en arquitectura Drupal 11. Responsable de:
- Diseño de módulos y temas
- Decisiones de API y data structures
- Integración con Drupal core
- Performance y escalabilidad

**Cuándo usarlo**: Para diseño de features, refactoring, decisiones arquitectónicas.

```bash
claude-code /agent:drupal-architect "diseñar API para gestionar campañas"
```

---

## 🔍 Code Reviewer

**Archivo**: [agents/code-reviewer.md](agents/code-reviewer.md)

Valida código contra quality gates. Responsable de:
- PHPCS compliance (Drupal Coding Standards)
- PHPStan type safety (nivel 5+)
- PHPUnit test coverage
- Estándares de documentación

**Cuándo usarlo**: Pre-commit, antes de PR, para auditar código existente.

```bash
claude-code /agent:code-reviewer "revisar web/modules/custom/nuevo_modulo"
```

---

## 🔐 Security Reviewer

**Archivo**: [agents/security-reviewer.md](agents/security-reviewer.md)

Audita seguridad del código. Responsable de:
- Inyección SQL y XSS
- Validación de entrada/salida
- Autenticación y autorización
- Secretos y credenciales

**Cuándo usarlo**: Features con datos sensibles, APIs públicas, autenticación.

```bash
claude-code /agent:security-reviewer "revisar módulo de login"
```

---

## ♿ Accessibility Reviewer

**Archivo**: [agents/accessibility-reviewer.md](agents/accessibility-reviewer.md)

Valida accesibilidad Web (WCAG 2.1 AA). Responsable de:
- Semantic HTML
- ARIA labels y roles
- Contrast ratios
- Keyboard navigation
- Twig template accessibility

**Cuándo usarlo**: Cambios en UI/UX, temas, componentes.

```bash
claude-code /agent:accessibility-reviewer "revisar nuevo tema de inicio"
```

---

## 🚀 Flujo de trabajo recomendado

1. **Planificación**: Coordinador analiza el requisito
2. **Diseño**: Drupal Architect define la solución
3. **Desarrollo**: Código se escribe con quality gates
4. **Revisión**: Code Reviewer + Security Reviewer + Accessibility Reviewer
5. **Integración**: Coordinador valida que todo funciona junto

## 📋 Tabla de capacidades

| Agente | PHPCS | PHPStan | PHPUnit | Seguridad | Accesibilidad | Drupal API |
|--------|-------|---------|---------|-----------|---------------|-----------|
| Coordinador | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Drupal Architect | ✓ | ✓ | - | - | - | ✓✓ |
| Code Reviewer | ✓✓ | ✓✓ | ✓✓ | - | - | ✓ |
| Security Reviewer | ✓ | ✓ | - | ✓✓ | - | ✓ |
| Accessibility Reviewer | - | - | - | - | ✓✓ | ✓ |

## 🔄 Cómo invocar agentes

En Claude Code, usa el prefijo `/agent:` para invocar directamente:

```
/agent:drupal-architect "diseñar endpoint /api/campañas"
/agent:code-reviewer "revisar web/modules/custom"
/agent:security-reviewer "auditar autenticación"
```

O deja que el **Coordinador** decida automáticamente:

```
/agent:coordinator "implementar carrito de compras"
```

## 📚 Configuración por proyecto

Cada proyecto puede customizar:
- Qué agentes están activos
- Qué reglas aplican cada agente
- Qué tools tiene acceso cada uno

Ver [CLAUDE.md](CLAUDE.md) para configuración específica.
