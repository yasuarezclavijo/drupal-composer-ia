# Arquitectura del Drupal Agentic Blueprint

## Visión general

El **Drupal Agentic Blueprint** es una arquitectura de desarrollo asistida por IA que integra:

1. **Quality Gates determinísticos** (PHPCS, PHPStan, PHPUnit, GrumPHP)
2. **Arquitectura multiagente** (Coordinador, Arquitecto, TDD Specialist, Revisores especializados)
3. **Skills reutilizables** (workflows automatizados)
4. **Documentación as Code** (AGENTS.md, CLAUDE.md, skills/)
5. **Convenciones de Drupal 11** (Entity API, Services, Plugins)

## Componentes principales

### 1. Quality Gates (Validaciones determinísticas)

```
Git Push
   ↓
[Pre-commit hooks via GrumPHP]
   ├─ composer validation
   ├─ PHP syntax lint
   ├─ PHPCS (Drupal + DrupalPractice)
   ├─ Debug code detection
   ├─ Merge conflict detection
   └─ Commit message format
   ↓
[Pull Request]
   ├─ PHPStan level 5 (static analysis)
   ├─ PHPUnit 70%+ coverage
   ├─ Security Review (manual)
   ├─ Accessibility Review (manual)
   └─ Code Review (manual)
   ↓
[Merge to main]
```

### 2. Arquitectura Multiagente

```
User Request
   ↓
┌─────────────────────────────────────┐
│      Coordinador (Orquestador)      │
└─────────────────────────────────────┘
   │
   ├─→ [Drupal Architect]
   │   ├─ Entity design
   │   ├─ API design
   │   └─ Performance
   │
   ├─→ [TDD Specialist]
   │   ├─ Diseño de casos de prueba
   │   ├─ Tests Unit/Kernel/Functional (red)
   │   └─ Acompaña fases green/refactor
   │
   ├─→ [Code Reviewer]
   │   ├─ PHPCS
   │   ├─ PHPStan
   │   └─ PHPUnit
   │
   ├─→ [Security Reviewer]
   │   ├─ SQL injection
   │   ├─ XSS
   │   └─ Permissions
   │
   └─→ [Accessibility Reviewer]
       ├─ WCAG 2.1
       ├─ Semantic HTML
       └─ ARIA
   ↓
Solution delivered
```

### 3. Skills (Workflows automatizados)

Cada skill es un procedimiento completamente documentado:

- **create-module**: Crear módulo custom con estructura completa
- **create-content-type**: Content type con campos y vistas
- **create-api-endpoint**: REST endpoint con tests y docs, separado en capas Resource (HTTP) / Service (negocio) / Repository (datos)
- **review-pr**: Revisar pull request contra todos los gates

Invocación:
```bash
/skill:create-module "mi_modulo"
/skill:create-api-endpoint "campaigns" --method="GET"
/skill:review-pr  # Current PR
```

### 4. Documentación as Code

```
Blueprint repository
├── AGENTS.md              # Qué agentes disponibles
├── CLAUDE.md              # Cómo invocar agentes
├── agents/
│   ├── coordinator.md
│   ├── drupal-architect.md
│   ├── code-reviewer.md
│   ├── security-reviewer.md
│   └── accessibility-reviewer.md
├── skills/
│   ├── create-module.md
│   ├── create-content-type.md
│   ├── create-api-endpoint.md
│   └── review-pr.md
├── quality/
│   ├── phpcs.xml
│   ├── phpstan.neon
│   └── grumphp.yml
├── docs/
│   ├── architecture.md (este archivo)
│   ├── coding-standards.md
│   ├── quality-gates.md
│   ├── installation.md
│   └── activity-log/      # Registro de actividad por tarea
│       ├── README.md
│       └── _TEMPLATE.md
└── .blueprint/
    └── manifest.json      # Metadata de versión
```

### 5. Integración con Drupal 11

```
web/modules/custom/
├── campaign_module/
│   ├── campaign_module.info.yml
│   ├── campaign_module.services.yml
│   ├── src/
│   │   ├── Entity/Campaign.php
│   │   ├── Controller/CampaignController.php
│   │   ├── Service/CampaignService.php
│   │   ├── Repository/CampaignRepository.php
│   │   ├── Form/CampaignForm.php
│   │   └── Plugin/rest/resource/CampaignsResource.php
│   ├── config/install/
│   ├── templates/
│   ├── tests/
│   │   ├── src/Unit/
│   │   └── src/Functional/
│   └── README.md

web/themes/custom/
├── tema_custom/
│   ├── tema_custom.info.yml
│   ├── templates/
│   ├── css/
│   ├── js/
│   ├── webpack.config.js
│   └── package.json
```

### 6. Activity Log (Registro de actividad)

Cada tarea orquestada por el Coordinador queda registrada en `docs/activity-log/`:

```
docs/activity-log/
├── README.md                              # Índice cronológico
├── _TEMPLATE.md                           # Plantilla de cada entrada
└── YYYY-MM-DD-<slug-del-requisito>.md     # Una entrada por tarea
```

Cada archivo documenta: requisito original → descomposición → agentes involucrados → archivos creados/modificados → comandos ejecutados (PASS/FAIL) → resultado final (commits, pendientes).

Esto convierte la capacidad "Generar documentación de decisiones" del Coordinador en un artefacto persistente y versionado en Git, navegable entre sesiones de Claude Code.

Ver [agents/coordinator.md](../agents/coordinator.md#registro-de-actividad-activity-log) y [docs/activity-log/README.md](activity-log/README.md).

### 7. Dependencia implícita del perfil `standard`

Cualquier sitio Drupal 11 instalado con el perfil `standard` (el perfil por
defecto de Drupal core, usado también por Drupal CMS) ya define globalmente
algunas configuraciones de campo (p.ej. `field.storage.node.body`, usado por
los content types `page` y `article` del propio perfil).

Cualquier módulo custom que declare un content type con un campo `body` y
exporte `config/install/field.storage.node.body.yml` fallará al habilitarse:

```
Configuration objects (field.storage.node.body) provided by <módulo>
already exist in active configuration
```

**Regla**: si un content type custom usa el campo `body`, el módulo debe
incluir únicamente `field.field.node.<bundle>.body.yml` (la instancia del
campo para ese bundle), referenciando `field.storage.node.body` en
`dependencies.config` **sin** exportar el `field.storage.node.body.yml`
correspondiente — ese storage ya existe en la configuración activa
provista por `standard`.

Esta regla aplica al skill [create-content-type](../.claude/commands/create-content-type.md#3-crear-configuración-tdd--green-máx-3-intentos).

## Flujo de desarrollo recomendado

### 1. Nuevo feature

```
Requisito
  ↓
Coordinador analiza
  ├─ Complejidad
  ├─ Áreas de impacto
  └─ Agentes requeridos
  ↓
Drupal Architect diseña
  ├─ Data structures
  ├─ API contracts
  └─ Cache strategy
  ↓
TDD Specialist diseña pruebas (red)
  ├─ Casos de prueba (happy path, edge cases, permisos)
  ├─ Tests Unit/Kernel/Functional
  └─ composer test → FAIL esperado
  ↓
Developer implementa (green)
  ├─ Código mínimo para pasar los tests
  ├─ Módulo / Theme / API
  ├─ composer test → PASS
  └─ Documentación
  ↓
Refactor (TDD Specialist + Developer)
  ├─ Mejoras de diseño/performance
  └─ composer test && composer qa
  ↓
Code Reviewer valida
  ├─ PHPCS
  ├─ PHPStan
  └─ PHPUnit 70%+
  ↓
Security Reviewer audita
  ├─ Input validation
  ├─ SQL injection
  └─ XSS / Permissions
  ↓
Accessibility Reviewer (si UI)
  ├─ WCAG 2.1 AA
  ├─ Semantic HTML
  └─ Focus management
  ↓
Merge to main
```

### 2. Bug fix

```
Bug report
  ↓
Reproduce
  ↓
Identificar causa
  ↓
TDD Specialist: test que reproduce el bug (red)
  ├─ composer test → FAIL esperado
  ↓
Implementar fix (green)
  ├─ Cambios mínimos
  ├─ El test anterior pasa
  └─ Documentación
  ↓
Validaciones automáticas (composer qa)
  ├─ PHPCS
  ├─ PHPStan
  └─ PHPUnit
  ↓
Security check (si necesario)
  ↓
Merge
```

## Decisiones arquitectónicas clave

### 1. Quality Gates determinísticos (vs. manual review)

**Decisión**: Automatizar todo lo automatizable.

**Razón**: Los gates manuales dependen de humanos y escalan mal. Los gates automáticos son reproducibles.

**Tools**:
- PHPCS: Estilos de código
- PHPStan: Type safety
- PHPUnit: Test coverage
- GrumPHP: Orquestación

### 2. Agentes especializados (vs. agente único)

**Decisión**: Cada agente es especialista en un aspecto.

**Razón**: Especialización mejora calidad. Security reviewer distinto de Code reviewer.

**Agentes**:
- Coordinador: Orquestación
- Architect: Diseño
- Code Reviewer: Standards
- Security: Seguridad
- Accessibility: WCAG

### 3. Skills como procedimientos (vs. documentación vaga)

**Decisión**: Cada workflow es un skill con pasos exactos.

**Razón**: Procedimientos exactos son reproducibles sin ambigüedad.

**Skills**:
- create-module
- create-content-type
- create-api-endpoint

### 4. Configuración versionada (vs. manual setup)

**Decisión**: Todo en `CLAUDE.md`, `AGENTS.md`, `quality/`.

**Razón**: Configuración versionada es reproducible entre máquinas y equipos.

**Archivos**:
- phpcs.xml
- phpstan.neon
- grumphp.yml
- CLAUDE.md

### 5. TDD: tests antes que implementación (vs. tests al final)

**Decisión**: El TDD Specialist diseña y escribe los tests (red) antes de que exista código de producción; la implementación se considera completa solo cuando los pasa (green).

**Razón**: Tests escritos después de la implementación tienden a confirmar lo que el código ya hace, no lo que debería hacer. Tests primero fuerzan a definir el contrato/comportamiento esperado y detectan errores de diseño temprano.

**Agente**: [agents/tdd-specialist.md](../agents/tdd-specialist.md)

### 6. Resource delgado + Service + Repository (vs. lógica de negocio en el Resource)

**Decisión**: las clases `*Resource` (`src/Plugin/rest/resource/`) son una
capa HTTP delgada (permisos, parseo, mapeo de excepciones, cache). Toda la
lógica de negocio vive en `src/Service/` y todo el acceso/transformación de
datos (queries, `load*()`, `create()`, `save()`, `delete()`) vive en
`src/Repository/`.

**Razón**: evita repetir el problema histórico de los `Controller` gordos
que mezclaban acceso a datos, reglas de negocio y respuesta HTTP en una sola
clase, lo que dificultaba testear la lógica sin levantar el framework
completo y favorecía la duplicación entre endpoints.

**Skill**: [create-api-endpoint](../.claude/commands/create-api-endpoint.md)

## Flujos de datos

### Creación de módulo

```
/skill:create-module "campaigns"
  ↓
  ├─ Crea estructura: web/modules/custom/campaigns/
  ├─ Genera .info.yml
  ├─ Crea Services
  ├─ Crea Tests (Unit + Functional)
  ├─ Crea README.md
  ↓
composer lint:phpcs
  ├─ Valida sintaxis
  ├─ Valida estilo Drupal/DrupalPractice
  └─ Fail if violations
  ↓
composer lint:phpstan
  ├─ Análisis estático (nivel 5)
  ├─ Type checking
  └─ Fail if errors
  ↓
composer test
  ├─ Ejecuta PHPUnit
  ├─ Calcula coverage
  └─ Fail if < 70%
  ↓
composer qa
  ├─ Agrupa todas validaciones
  └─ Exit 0 si todo OK
  ↓
git commit -m "feat: crear módulo campaigns"
  ├─ GrumPHP pre-commit hook
  ├─ Valida debug code
  ├─ Valida merge conflicts
  ├─ Valida commit message
  └─ Permite commit si OK
```

### Code Review de PR

```
git push origin feature/campaigns
  ↓
Pull Request abierto
  ↓
/skill:review-pr
  ├─ Code Reviewer
  │   ├─ PHPCS check
  │   ├─ PHPStan check
  │   ├─ Test coverage
  │   └─ Docblocks
  │
  ├─ Security Reviewer
  │   ├─ SQL injection
  │   ├─ XSS
  │   ├─ Permissions
  │   └─ Secrets
  │
  └─ Accessibility Reviewer
      ├─ WCAG check
      ├─ Semantic HTML
      └─ ARIA
  ↓
Report generado
  ├─ Issues encontrados
  ├─ Aprobación recomendada
  └─ Próximos pasos
```

## Escalabilidad

### Para un equipo pequeño (1-3 desarrolladores)

- Todos los agentes activos
- Security review manual (~30 min por PR)
- Accessibility review solo si UI changes
- Tests opcionalmente menos estrictos (60% coverage)

### Para un equipo mediano (4-10)

- Todos los agentes activos
- Security review asignado a especialista
- Accessibility review para cualquier cambio UI
- Tests obligatorios 70%+ coverage
- CI/CD automatizado

### Para un equipo grande (10+)

- Agentes especializados por equipo
- Coordinador centralizado
- Security y Accessibility reviews automáticos (tools)
- Tests críticos ejecutados en CI
- Métricas de calidad por equipo

## Integraciones futuras

### v1.x (Actual)

✓ Quality gates locales
✓ Agentes en Claude Code
✓ Skills básicos

### v2.x (Planeado)

- [ ] CI/CD (GitHub Actions)
- [ ] Métricas de code quality
- [ ] Test reporting
- [ ] Performance benchmarks

### v3.x (Visión)

- [ ] Self-healing (auto-fix de issues)
- [ ] ML-based recommendations
- [ ] Observabilidad completa
- [ ] Multi-team orchestration

## Mejores prácticas

### 1. Commit messages

```
feat: descripción breve
fix: descripción breve
docs: descripción breve
refactor: descripción breve
test: descripción breve
perf: descripción breve
```

Siempre en español, primera letra mayúscula.

### 2. Branch naming

```
feature/nombre-descripcion
bugfix/nombre-descripcion
docs/nombre-descripcion
```

### 3. PR templates

Incluye:
- Descripción del cambio
- Por qué (justificación)
- Cómo testear
- Screenshots (si aplica)

### 4. Documentación

- README en cada módulo
- Docblocks en clases y métodos
- Inline comments para lógica compleja
- CHANGELOG.md actualizado

## Troubleshooting

### PHPCS errors

```bash
# Ver errores
composer lint:phpcs

# Auto-fijar
composer fix
```

### PHPStan errors

```bash
# Analizar
composer lint:phpstan --level=5

# Ignorar temporalmente (último recurso)
// @phpstan-ignore-next-line
```

### Test coverage bajo

```bash
# Ver cobertura
composer test -- --coverage-html=coverage

# Agregar tests faltantes
# tests/src/Unit/
# tests/src/Functional/
```

### Merge conflicts

```bash
# Resolver
git merge develop

# Re-validar
composer qa
git commit
```

## Referencias

- [Drupal Architecture](https://www.drupal.org/docs/drupal-apis)
- [PHPCS & Standards](https://www.drupal.org/docs/drupal-apis/coding-standards)
- [Testing](https://www.drupal.org/docs/automated-testing)
- [Security](https://www.drupal.org/docs/drupal-apis/security)
