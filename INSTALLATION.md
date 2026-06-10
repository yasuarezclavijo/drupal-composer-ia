# Guía de Instalación

## Requisitos previos

- **PHP**: 8.2 o superior
- **Drupal**: 11.3 o superior
- **Composer**: 2.0 o superior
- **Git**: 2.0 o superior
- **DDEV**: Recomendado pero opcional

## Instalación rápida (1 minuto)

### Paso 1: Instalar via Composer

```bash
composer require kdb/drupal-agentic-blueprint
```

Composer ejecutará automáticamente el `post-install-cmd` que instala el blueprint.

### Paso 2: Validar instalación

```bash
composer lint:php
composer lint:phpcs
```

Si no hay errores, ¡listo! El blueprint está instalado.

## Instalación con personalización (5 minutos)

Si quieres personalizar el blueprint para tu equipo:

```bash
composer require kdb/drupal-agentic-blueprint -- --interactive
```

Te hará preguntas:
- Nombre del proyecto
- Organización/Agencia
- Nivel de strictness (seguridad, accesibilidad)

## Archivos instalados

```
proyecto/
├── AGENTS.md                    ← Qué agentes disponibles
├── CLAUDE.md                    ← Configuración del proyecto
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
├── docs/
│   ├── architecture.md
│   ├── quality-gates.md
│   └── coding-standards.md
├── phpcs.xml                    ← PHPCS configuration
├── phpstan.neon                 ← PHPStan configuration
└── grumphp.yml                  ← GrumPHP pre-commit hooks
```

## Configuración inicial

### 1. Revisar CLAUDE.md

```bash
vim CLAUDE.md
```

Personaliza:
- `project.name`: Tu proyecto
- `agents`: Qué agentes activar
- `quality_gates`: Nivel de strictness
- `tools`: Configuración por herramienta

### 2. Habilitar pre-commit hooks

GrumPHP se instala automáticamente via Composer. Para activar:

```bash
vendor/bin/grumphp install
```

Ahora los hooks se ejecutarán antes de cada commit.

### 3. (Opcional) Configurar DDEV

Si usas DDEV:

```bash
ddev config
# Selecciona Drupal 11
# El blueprint es compatible automáticamente
```

En `.ddev/config.yaml` ya está registrado `phpcs`:

```yaml
commands:
  host:
    phpcs: "vendor/bin/phpcs"
```

## Primeros pasos

### 1. Crear tu primer módulo

```bash
/skill:create-module "campaigns"
```

Esto crea:
```
web/modules/custom/campaigns/
├── campaigns.info.yml
├── src/Service/CampaignService.php
├── tests/src/Unit/CampaignServiceTest.php
└── README.md
```

### 2. Validar que pasa todos los gates

```bash
composer qa        # Todos los gates
composer test      # Tests + coverage
```

### 3. Hacer commit

```bash
git add web/modules/custom/campaigns/
git commit -m "feat: crear módulo campaigns"
```

GrumPHP valida automáticamente:
- ✓ PHPCS (estilo)
- ✓ PHP syntax (parse)
- ✓ Debug code (die, var_dump)
- ✓ Merge markers
- ✓ Commit message format (Spanish, capital letter)

## Comandos disponibles

### Quality gates

```bash
# Validaciones pre-commit (rápido)
composer lint:php           # PHP syntax
composer lint:phpcs         # PHPCS compliance

# Validaciones pre-merge (completo)
composer lint:phpstan       # Static analysis
composer test               # PHPUnit + coverage

# Todo junto
composer qa                 # lint:php + lint:phpstan + lint:twig

# Auto-fix
composer fix                # PHPCBF auto-correction
```

### Agentes

```bash
# Invocar agentes directamente en Claude Code
/agent:coordinator "crear sistema de notificaciones"
/agent:drupal-architect "diseñar API de campañas"
/agent:code-reviewer "revisar web/modules/custom/campaigns"
/agent:security-reviewer "auditar autenticación"
/agent:accessibility-reviewer "revisar tema"

# O usar skills
/skill:create-module "mi_modulo"
/skill:create-content-type "campaña"
/skill:create-api-endpoint "campaigns" --method=GET
```

## Actualizar el blueprint

Para obtener nuevas versiones y mejoras:

```bash
composer update kdb/drupal-agentic-blueprint
```

v1.x mantiene backwards compatibility, así que actualizar no rompe nada.

## Troubleshooting

### "GrumPHP no ejecuta hooks"

```bash
# Instalar hooks
vendor/bin/grumphp install

# Verificar
cat .git/hooks/pre-commit
```

### "PHPCS: installed_paths error"

```bash
# El wrapper script lo maneja automáticamente
composer lint:php

# Si persiste, reinstalar:
vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
```

### "PHPStan: Path does not exist"

```bash
# Crear .gitkeep en directorios vacíos
touch web/modules/custom/.gitkeep
touch web/themes/custom/.gitkeep

# O usar el wrapper
composer lint:phpstan
```

### "Tests coverage < 70%"

```bash
# Ver qué no está testeado
composer test -- --coverage-html=coverage
open coverage/index.html

# Agregar tests
vim tests/src/Unit/MyTest.php

# Rerun
composer test
```

## Estructura de carpetas esperada

El blueprint asume que tu Drupal CMS tiene:

```
proyecto/
├── web/
│   ├── modules/
│   │   ├── contrib/          ← Módulos descargados
│   │   └── custom/           ← TUS módulos
│   │       └── .gitkeep
│   ├── themes/
│   │   ├── contrib/          ← Temas descargados
│   │   └── custom/           ← TUS temas
│   │       └── .gitkeep
│   └── profiles/
│       ├── contrib/          ← Perfiles descargados
│       └── custom/           ← TUS perfiles
│           └── .gitkeep
├── composer.json
├── phpcs.xml                 ← Instalado por blueprint
├── phpstan.neon              ← Instalado por blueprint
├── grumphp.yml               ← Instalado por blueprint
├── CLAUDE.md                 ← Instalado por blueprint
└── AGENTS.md                 ← Instalado por blueprint
```

## Para equipos

### Sincronizar configuración en equipo

El blueprint está completamente versionado en Git:

```bash
git clone <tu-repo>
composer install
# Ya está todo configurado!
```

### Personalizar por equipo

En CLAUDE.md, personaliza:

```yaml
agents:
  security-reviewer:
    strictness: "high"   # Tu equipo es paranoico
  
  code-reviewer:
    phpstan_level: 5      # Tu equipo quiere type safety máximo
```

Todos en el equipo ven los mismos gates.

## Integración con CI/CD (futuro v2)

Actualmente, validaciones ejecutan localmente. En v2:

```yaml
# .github/workflows/quality.yml (planeado v2)
name: Quality Gates
on: pull_request

jobs:
  code-review:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: php-actions/composer@v6
      - run: composer qa
      - run: composer test
      - run: composer lint:phpstan
```

Por ahora, GrumPHP pre-commit hooks en local son suficientes.

## Soporte y ayuda

- **Documentación**: Lee `docs/architecture.md`
- **Agentes**: Lee `AGENTS.md`
- **Skills**: Lee `skills/create-module.md` etc
- **Quality gates**: Lee `docs/quality-gates.md`

## Siguientes pasos

1. ✅ Instalaste el blueprint
2. 📋 Customiza `CLAUDE.md` para tu proyecto
3. 🚀 Crea tu primer módulo: `/skill:create-module "name"`
4. 📖 Lee la documentación en `docs/`
5. 🤖 Invoca agentes: `/agent:coordinator "requisito"`
6. 🔄 Usa GrumPHP: `git commit` validará automáticamente

¡Listo! El blueprint está funcionando.

---

¿Preguntas? Cada archivo está documentado:
- AGENTS.md → Qué agentes tienes
- CLAUDE.md → Cómo configurarlos
- docs/quality-gates.md → Cómo validar código
- docs/architecture.md → Cómo funciona todo
