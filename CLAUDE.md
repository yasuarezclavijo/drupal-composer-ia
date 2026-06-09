# Configuración para Claude Code

Este archivo define cómo Claude Code y los agentes deben operar en tu proyecto Drupal.

## 🎯 Contexto del proyecto

```yaml
project:
  name: "Drupal CMS 2.0"
  description: "Sistema de gestión de campañas de marketing"
  drupal_version: "11.3+"
  php_version: "8.2+"
  
  # Cuáles son los directorios principales
  structure:
    modules_custom: web/modules/custom
    themes_custom: web/themes/custom
    profiles_custom: web/profiles/custom
    api_endpoints: web/modules/custom/*/src/Plugin/rest/resource
```

## 🤖 Agentes habilitados

```yaml
agents:
  # Agente principal - orquesta el resto
  coordinator:
    enabled: true
    auto_invoke_on:
      - complex_features
      - architecture_decisions
      - cross_module_changes
    escalation_threshold: "medium"
    required_approvals:
      - code-review
      - security-review
  
  # Diseño arquitectónico
  drupal-architect:
    enabled: true
    expertise:
      - entity-design
      - api-design
      - performance-optimization
      - migrations
    strictness: "high"
    allowed_contrib:
      - search_api
      - webform
      - views
      - field_group
      - linkit
      - entity_usage
      - rules
      - hook_event_dispatcher
  
  # Validación de quality gates
  code-reviewer:
    enabled: true
    auto_validate:
      - phpcs
      - phpstan
      - phpunit
      - docblocks
    phpstan_level: 5
    test_coverage_min: 70
    must_pass_before_commit: true
  
  # Auditoría de seguridad
  security-reviewer:
    enabled: true
    strictness: "high"
    validations:
      - input_validation
      - output_sanitization
      - sql_injection
      - xss
      - permissions
      - secrets_management
      - dependency_audit
    auto_tools:
      - composer audit
  
  # WCAG compliance
  accessibility-reviewer:
    enabled: true
    wcag_standard: "2.1 AA"
    contrast_ratio_min: 4.5
    auto_validate:
      - semantic_html
      - alt_text
      - contrast_ratio
      - keyboard_navigation
      - focus_indicators
      - aria_implementation
```

## 📋 Skills (Capacidades especializadas)

Cada skill es un workflow completamente definido que Claude Code puede ejecutar:

```yaml
skills:
  # Crear un módulo Drupal completo
  create-module:
    file: skills/create-module.md
    trigger: "crear módulo", "new module"
    tools_required:
      - file creation
      - drupal commands
      - testing
  
  # Crear content type
  create-content-type:
    file: skills/create-content-type.md
    trigger: "crear content type", "new entity"
    tools_required:
      - drupal config
      - field management
  
  # API endpoint
  create-api-endpoint:
    file: skills/create-api-endpoint.md
    trigger: "crear endpoint", "new api"
    tools_required:
      - rest plugin creation
      - security review required
  
  # Pull request review
  review-pr:
    file: skills/review-pr.md
    trigger: "revisar PR", "code review"
    tools_required:
      - git commands
      - all reviewers
```

## 🛠️ Configuración de herramientas

```yaml
tools:
  # Git configuration
  git:
    commit_message_language: spanish  # Mensajes en español
    author_name: "Yeison A. Suarez"
    author_email: "yasuarezclavijo@gmail.com"
    require_commit_message_format: true
    format: "[TYPE]: descripción breve"
    # Types: feat, fix, docs, refactor, test, perf, chore
  
  # PHP Quality Gates
  php:
    phpcs:
      standard: "PSR12"
      paths:
        - "web/modules/custom"
        - "web/themes/custom"
        - "web/profiles/custom"
      ignore:
        - "*/vendor/*"
        - "*/tests/*"
    
    phpstan:
      level: 5
      paths:
        - "web/modules/custom"
        - "web/themes/custom"
        - "web/profiles/custom"
    
    phpunit:
      min_coverage: 70
      test_suites:
        - unit
        - kernel
        - functional
    
    phpcbf:
      auto_fix: true
      on_commit: true
  
  # Drupal specific
  drupal:
    version: "11.3"
    multisite: false
    cache_enabled: true
    debug_mode: false
  
  # DDEV
  ddev:
    enabled: true
    auto_detect: true
    services:
      - db
      - web
      - redis
```

## 📊 Quality Gates (Validaciones obligatorias)

```yaml
quality_gates:
  # Pre-commit hooks
  pre_commit:
    - name: "composer validation"
      tool: "composer validate --strict"
      fail_behavior: "block"
    
    - name: "PHP syntax"
      tool: "php -l"
      fail_behavior: "block"
    
    - name: "PHPCS standards"
      tool: "composer lint:phpcs"
      fail_behavior: "block"
    
    - name: "Debug code detection"
      tool: "grep: die, var_dump, print_r, dpm, syslog"
      fail_behavior: "block"
    
    - name: "Merge conflict markers"
      tool: "grep: <<<<<<, =====, >>>>>>"
      fail_behavior: "block"
    
    - name: "Commit message format"
      tool: "validate spanish capital first letter"
      fail_behavior: "block"
  
  # Code review (pre-merge)
  pre_merge:
    - name: "PHPStan level 5"
      tool: "composer lint:phpstan"
      fail_behavior: "block"
    
    - name: "Unit tests passing"
      tool: "composer test"
      fail_behavior: "block"
    
    - name: "Test coverage >= 70%"
      tool: "phpunit --coverage"
      fail_behavior: "block"
    
    - name: "Security review"
      required: true
      fail_behavior: "block"
    
    - name: "Accessibility review"
      required_for: ["temas", "componentes UI"]
      fail_behavior: "block"
```

## 🚀 Flujo de desarrollo

```yaml
workflows:
  # Feature development workflow
  feature_development:
    steps:
      1_design:
        agent: drupal-architect
        output: design document
        acceptance: "diseño aprobado"
      
      2_implementation:
        agent: code-reviewer
        auto_validate: true
        must_pass:
          - phpcs
          - phpstan
          - phpunit
      
      3_security_audit:
        agent: security-reviewer
        inputs: "código implementado"
        output: "reporte de seguridad"
      
      4_accessibility_check:
        agent: accessibility-reviewer
        inputs: "UI/templates"
        skip_if: "backend puro sin UI"
      
      5_merge:
        agent: coordinator
        requires: "todas las aprobaciones"
  
  # Bug fix workflow
  bug_fix:
    steps:
      1_analyze:
        agent: coordinator
        inputs: "descripción del bug"
      
      2_fix:
        auto_validate: true
      
      3_test:
        tool: "composer test"
        min_coverage: 70
      
      4_security_check:
        agent: security-reviewer
        skip_if: "bug no toca auth/data"
  
  # Code review workflow
  code_review:
    checklist:
      - "¿Pasó composer qa?"
      - "¿Pasó composer test?"
      - "¿Code Reviewer aprobó?"
      - "¿Security Reviewer aprobó?"
      - "¿Tiene tests nuevos?"
      - "¿Documentado en CHANGELOG.md?"
```

## 🔐 Seguridad y secretos

```yaml
security:
  # Qué NO DEBE estar en git
  no_version_control:
    - .env files with credentials
    - API keys (stripe, mailchimp, etc)
    - Private certificates
    - Database dumps
  
  # Cómo manejar secretos
  secrets_management:
    environment_variables: true
    drupal_config: true
    .env_example: true
    
  # Credenciales para desarrollo
  development_credentials:
    drupal_admin:
      username: "admin"
      password: "ddev"
    
    mailchimp_sandbox: "required"
    stripe_test_keys: "required"
```

## 📚 Documentación

```yaml
documentation:
  required_for:
    - new_modules: "README en módulo"
    - public_apis: "docblock @param @return"
    - complex_logic: "comentarios inline"
    - breaking_changes: "CHANGELOG.md"
  
  standards:
    language: "español para comentarios internos"
    style: "PSR-5 para docblocks"
    changelog_format: "Keep a Changelog"
```

## 🎯 Prioridades de desarrollo

```yaml
priorities:
  # Cuál es el orden de importancia si hay conflictos
  order:
    1: "seguridad"
    2: "quality gates"
    3: "tests"
    4: "documentación"
    5: "performance"
```

## 🔄 Configuración por agente

### Coordinator

```yaml
coordinator:
  decision_timeout: 300  # segundos antes de timeout
  require_human_approval_for:
    - database schema changes
    - breaking API changes
    - security decisions
```

### Drupal Architect

```yaml
drupal_architect:
  performance_targets:
    api_response_time_ms: 200
    page_render_time_ms: 500
  
  caching_strategy: "aggressive"
  
  default_contrib_modules:
    - search_api (para búsqueda)
    - views (para listados)
    - webform (para formularios)
```

### Code Reviewer

```yaml
code_reviewer:
  auto_fix_and_commit: false  # cambios grandes requieren revisión
  fail_on_warnings: false
  report_format: "table"  # verbose, table, minimal
```

### Security Reviewer

```yaml
security_reviewer:
  owasp_rules: "all"
  auto_tools:
    - composer audit
    - grep patterns
  
  critical_paths:
    - web/modules/custom/*/src/Controller/*
    - web/modules/custom/*/src/Plugin/rest/resource/*
```

### Accessibility Reviewer

```yaml
accessibility_reviewer:
  auto_check_on:
    - temas (themes)
    - templates (.twig)
    - componentes custom
  
  skip_on:
    - módulos backend puros
    - APIs REST
```

## 📝 Cómo usar esta configuración

```bash
# Iniciar tarea con Coordinador
claude-code /agent:coordinator "crear sistema de notificaciones"

# Iniciar tarea con especialista
claude-code /agent:drupal-architect "diseñar API de campañas"

# Ejecutar skill
claude-code /skill:create-module "nombre_del_modulo"

# Forzar validación manual
claude-code /validate:all
```

## 🔗 Archivos relacionados

- [AGENTS.md](AGENTS.md) - Descripción de agentes
- [agents/coordinator.md](agents/coordinator.md)
- [agents/drupal-architect.md](agents/drupal-architect.md)
- [agents/code-reviewer.md](agents/code-reviewer.md)
- [agents/security-reviewer.md](agents/security-reviewer.md)
- [agents/accessibility-reviewer.md](agents/accessibility-reviewer.md)
- [skills/create-module.md](skills/create-module.md)
- [docs/architecture.md](docs/architecture.md)
- [docs/quality-gates.md](docs/quality-gates.md)
