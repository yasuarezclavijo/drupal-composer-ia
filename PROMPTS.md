# 🧭 PROMPTS.md — Guía rápida de comandos y prompts

Referencia directa de **qué escribir o ejecutar** para cada flujo del Drupal Agentic Blueprint. Pensado para copiar/pegar.

> **Convención**:
> - `composer ...` → se ejecuta en la **raíz del proyecto Drupal** (no en este repo del blueprint).
> - `/agent:...` y `/skill:...` → se escriben directamente en el chat de Claude Code, dentro del proyecto Drupal.
> - `vendor/bin/...` → ejecutables instalados por Composer en el proyecto Drupal.

---

## 0. Instalación del blueprint en un proyecto Drupal

### 0.1 Instalación estándar
```bash
composer require kdb/drupal-agentic-blueprint
```

### 0.2 Instalación interactiva (personaliza nombre, organización y strictness)
```bash
composer require kdb/drupal-agentic-blueprint -- --interactive
```

### 0.3 Activar pre-commit hooks (GrumPHP)
```bash
vendor/bin/grumphp install
```

### 0.4 Verificar instalación
```bash
composer lint:php
composer lint:phpcs
```

### 0.5 (Opcional) DDEV
```bash
ddev config   # selecciona Drupal 11
ddev start
```

> ⚠️ **Puesta a punto requerida en proyectos nuevos**: los comandos `composer lint:*`, `qa`, `test` y `fix` deben existir en el `composer.json` **del proyecto Drupal**. Ver sección 8 para el bloque exacto a copiar.

---

## 1. Agentes (`/agent:...`)

| Agente | Prompt directo | Úsalo cuando... |
|---|---|---|
| 🎯 Coordinador | `/agent:coordinator "implementar sistema de notificaciones multicanal"` | el requisito toca varios módulos o aspectos (código + seguridad + UI) |
| 🏗️ Drupal Architect | `/agent:drupal-architect "diseñar API REST para gestionar campañas"` | hay decisiones de entidades, API, performance o migraciones |
| 🧪 TDD Specialist | `/agent:tdd-specialist "diseñar tests para CampaignService"` | ANTES de implementar: escribe casos de prueba y tests que fallan (red) |
| 🔍 Code Reviewer | `/agent:code-reviewer "revisar web/modules/custom/campaigns"` | antes de commit/PR, o para auditar código existente |
| 🔐 Security Reviewer | `/agent:security-reviewer "auditar el módulo de autenticación"` | hay datos sensibles, APIs públicas o autenticación |
| ♿ Accessibility Reviewer | `/agent:accessibility-reviewer "revisar el tema custom_theme"` | hay cambios de UI, temas o componentes |

📄 Detalle: [AGENTS.md](AGENTS.md) · [agents/](agents/)

---

## 2. Skills (`/skill:...`)

### 2.1 Crear módulo
```
/skill:create-module "campaigns" --description="Gestión de campañas de marketing"
```
Variantes:
```
/skill:create-module "campaigns_api" --api-only
/skill:create-module "campaigns" --with-tests
```
📄 [skills/create-module.md](skills/create-module.md)

### 2.2 Crear content type
```
/skill:create-content-type "campaign" --machine-name="campaign" --with-views --with-forms
```
📄 [skills/create-content-type.md](skills/create-content-type.md)

### 2.3 Crear API endpoint
```
/skill:create-api-endpoint "campaigns" --method="GET" --resource="node" --returns="json"
```
📄 [skills/create-api-endpoint.md](skills/create-api-endpoint.md)

### 2.4 Revisar PR
```
/skill:review-pr
```
> ⚠️ **Pendiente**: referenciado en `AGENTS.md`/`CLAUDE.md`/docs pero `skills/review-pr.md` aún no existe. Mientras tanto usa el flujo manual de la sección 3.3.

---

## 3. Flujos completos (end-to-end)

### 3.1 Feature nueva (TDD: Red → Green → Refactor)
```
1. /agent:coordinator "implementar <requisito>"
2. /agent:drupal-architect "diseñar <componente>"
3. /agent:tdd-specialist "diseñar casos de prueba y tests para <componente>"
   # red: composer test debe fallar (clase/método aún no existe)
4. /skill:create-module "<nombre_modulo>"
   # green: implementar lo mínimo para que los tests pasen (máx. 3 intentos)
5. composer test   # confirmar que ahora pasan (verde)
   # si tras 3 intentos sigue en rojo: generar "Resumen de bloqueo" y
   # esperar indicación del usuario (ver agents/tdd-specialist.md)
6. composer fix
7. composer qa
8. /agent:security-reviewer "revisar <nombre_modulo>"
9. /agent:accessibility-reviewer "revisar <tema>"   # solo si hay UI
10. git add web/modules/custom/<nombre_modulo>/
11. git commit -m "feat: crear módulo <nombre_modulo>"
```

### 3.2 Bug fix (TDD: reproducir antes de corregir)
```
1. /agent:coordinator "analizar bug: <descripción>"
2. /agent:tdd-specialist "escribir test que reproduzca: <descripción>"
   # red: el test debe fallar reproduciendo el bug
3. (corregir código hasta que el test pase)   # green, máx. 3 intentos
   # si tras 3 intentos sigue en rojo: generar "Resumen de bloqueo" y
   # esperar indicación del usuario (ver agents/tdd-specialist.md)
4. composer fix && composer qa && composer test
5. /agent:security-reviewer "revisar <archivo afectado>"   # si toca auth/datos
6. git commit -m "fix: <descripción breve>"
```

### 3.3 Revisión de código existente / PR
```
1. /agent:code-reviewer "revisar web/modules/custom/<modulo>"
2. /agent:security-reviewer "auditar <modulo>"
3. /agent:accessibility-reviewer "revisar <tema>"   # si aplica
4. /agent:coordinator "validar que <modulo> está completo"
```

### 3.4 Consultar el registro de actividad

Cada flujo orquestado por `/agent:coordinator` queda registrado en `docs/activity-log/`.

```bash
# Ver el índice de tareas registradas
cat docs/activity-log/README.md

# Ver el detalle de una tarea específica
cat docs/activity-log/2026-06-10-crear-modulo-campaigns.md
```

📄 [docs/activity-log/README.md](docs/activity-log/README.md) · [agents/coordinator.md](agents/coordinator.md)

---

## 4. Quality Gates (`composer ...`)

| Comando | Qué valida | Cuándo |
|---|---|---|
| `composer lint:composer` | `composer.json` válido | pre-commit |
| `composer lint:php` | Sintaxis PHP (`php -l`) | pre-commit |
| `composer lint:phpcs` | Drupal + DrupalPractice coding standards (`drupal/coder`) | pre-commit |
| `composer fix` | Auto-corrección PHPCBF | antes de commit |
| `composer lint:phpstan` | Type safety nivel 5 | pre-merge |
| `composer lint:twig` | TwigCS en templates custom | pre-commit |
| `composer test` | PHPUnit + cobertura ≥70% | pre-merge |
| `composer qa` | Agrupa lint:composer + lint:php + lint:phpcs + lint:phpstan | pre-merge |

📄 Detalle: [docs/quality-gates.md](docs/quality-gates.md)

---

## 5. Git / Commits

```bash
git commit -m "feat: crear módulo campaigns"
git commit -m "fix: corregir cálculo de totales en campaigns"
git commit -m "docs: actualizar README de campaigns"
git commit -m "refactor: extraer lógica a CampaignService"
git commit -m "test: agregar pruebas unitarias de CampaignService"
```

Formato obligatorio: `Tipo: descripción` con mayúscula inicial. GrumPHP valida automáticamente: PHPCS, debug code, merge markers y formato del mensaje.

---

## 6. DDEV

```bash
ddev start
ddev composer install
ddev composer require kdb/drupal-agentic-blueprint
ddev composer qa
ddev composer test
```

---

## 7. Troubleshooting rápido

| Problema | Comando |
|---|---|
| GrumPHP no corre hooks | `vendor/bin/grumphp install` |
| PHPCS "installed_paths" error | `vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer` |
| PHPStan "Path does not exist" | `mkdir -p web/modules/custom web/themes/custom web/profiles/custom` |
| Cobertura < 70% | `composer test -- --coverage-html=coverage && open coverage/index.html` |
| Ver errores PHPCS detallados | `vendor/bin/phpcs --report=summary --standard=phpcs.xml web/modules/custom` |
| Actualizar blueprint | `composer update kdb/drupal-agentic-blueprint` |

---

## 8. Puesta a punto manual (proyecto Drupal recién instanciado)

El paquete instala `phpcs.xml`, `phpstan.neon` y `grumphp.yml` en la raíz, pero **no inyecta los scripts de Composer** ni las herramientas (`phpcs`, `phpstan`, `phpcbf`, `twigcs`, `grumphp` están en `require-dev` del blueprint y **no** se instalan de forma transitiva).

### 8.1 Instalar herramientas de calidad en el proyecto
```bash
composer require --dev drupal/coder mglaman/phpstan-drupal phpstan/phpstan \
  squizlabs/php_codesniffer friendsoftwig/twigcs phpro/grumphp
```

### 8.2 Agregar scripts a `composer.json` del proyecto
```json
{
  "scripts": {
    "lint:composer": "composer validate --strict",
    "lint:php": "find web/modules/custom web/themes/custom web/profiles/custom -name '*.php' -print0 | xargs -0 -n1 -I{} php -l {}",
    "lint:phpcs": "vendor/bin/phpcs --standard=phpcs.xml",
    "fix": "vendor/bin/phpcbf --standard=phpcs.xml",
    "lint:phpstan": "vendor/bin/phpstan analyse -c phpstan.neon",
    "lint:twig": "vendor/bin/twigcs web/modules/custom web/themes/custom",
    "qa": [
      "@lint:composer",
      "@lint:php",
      "@lint:phpcs",
      "@lint:phpstan"
    ],
    "test": "vendor/bin/phpunit"
  }
}
```

### 8.3 Crear directorios esperados (si no existen)
```bash
mkdir -p web/modules/custom web/themes/custom web/profiles/custom
```

---

## 📚 Referencias

- [README.md](README.md)
- [INSTALLATION.md](INSTALLATION.md)
- [AGENTS.md](AGENTS.md)
- [docs/architecture.md](docs/architecture.md)
- [docs/quality-gates.md](docs/quality-gates.md)
