# Guía de Instalación

## Requisitos previos

- **PHP**: 8.2 o superior
- **Drupal**: 11.3 o superior (incluye Drupal CMS, que usa el perfil `standard`)
- **Composer**: 2.0 o superior
- **Git**: 2.0 o superior (requerido por GrumPHP para los pre-commit hooks)
- **DDEV**: recomendado pero opcional — si existe `.ddev/config.yaml` el
  instalador y los comandos `composer`/`drush` se adaptan automáticamente

## Cómo funciona la instalación

`kdb/drupal-agentic-blueprint` es un **Composer plugin**
(`"type": "composer-plugin"`, clase `DrupalAgenticBlueprint\ComposerPlugin`).
Al requerirlo o actualizarlo, se suscribe a los eventos `post-install-cmd` y
`post-update-cmd` de Composer y ejecuta automáticamente:

```bash
php vendor/kdb/drupal-agentic-blueprint/scripts/installer.php install   # primera vez
php vendor/kdb/drupal-agentic-blueprint/scripts/installer.php update    # composer update kdb/drupal-agentic-blueprint
```

No hay pasos manuales de "scaffolding": el instalador copia/mezcla todo
automáticamente. Solo hay **un paso manual obligatorio después**: instalar
las dependencias de quality gates que el instalador añade a `composer.json`
(ver [Paso obligatorio](#paso-obligatorio-instalar-las-dependencias-de-quality-gates)).

> ⚠️ **El paquete no está publicado en Packagist.** Para usarlo en un
> proyecto hay que declarar un repositorio Composer adicional (`path` o
> `vcs`) — ver [Probar el blueprint en un Drupal vacío](#probar-el-blueprint-en-un-drupal-vacío).

## Instalación rápida

```bash
composer require kdb/drupal-agentic-blueprint
```

(o `ddev composer require kdb/drupal-agentic-blueprint` si usas DDEV).

Esto dispara `installer.php install`, que copia/crea en la raíz del proyecto:

```
.claude/
├── agents/          ← 6 agentes (coordinator, drupal-architect, tdd-specialist,
│                        code-reviewer, security-reviewer, accessibility-reviewer)
└── commands/        ← 3 slash commands (create-module, create-content-type,
                          create-api-endpoint)

CLAUDE.md            ← solo si no existe (skip_existing)
phpcs.xml            ← Drupal + DrupalPractice (solo si no existe)
phpstan.neon         ← nivel 5 (solo si no existe)
grumphp.yml          ← pre-commit hooks (solo si no existe)
phpunit.xml          ← generado desde quality/phpunit.xml.dist, con
                          SIMPLETEST_BASE_URL detectado de .ddev/config.yaml
                          (solo si no existe)
scripts/
├── grumphp.sh
├── lint-php.sh
├── phpstan-wrapper.sh
├── phpcbf-wrapper.sh
└── twig-lint-wrapper.sh

docs/
├── architecture.md
├── quality-gates.md
├── activity-log/
└── requirements/      ← README.md + _TEMPLATE.md (solo si no existen)

web/modules/custom/         ← creado si no existe
web/themes/custom/          ← creado si no existe
web/profiles/custom/        ← creado si no existe
web/sites/simpletest/browser_output/.gitkeep  ← requerido por PHPUnit

.ddev/commands/web/test-coverage   ← solo si existe .ddev/config.yaml
```

Y mezcla en `composer.json` del proyecto (sin sobreescribir lo existente):

- `require`: `drush/drush ^13.7`
- `require-dev`: `drupal/coder`, `mglaman/phpstan-drupal`, `phpro/grumphp`,
  `phpstan/phpstan`, `squizlabs/php_codesniffer`, `friendsoftwig/twigcs` +
  sustitutos de `drupal/core-dev` (behat/mink, symfony/*, phpunit/phpunit,
  etc. — ver `scripts/installer.php`)
- `scripts`: `qa`, `test`, `test:coverage`, `fix`, `lint:phpcs`,
  `lint:phpstan`, `audit`
- `config.use-github-api: false`

Si el proyecto ya tenía versiones rotas de `lint:phpcs`/`lint:phpstan`
(`@php scripts/*.sh`), el instalador las corrige automáticamente a
`bash scripts/*.sh`.

## Paso obligatorio: instalar las dependencias de quality gates

`composer require` ya está corriendo cuando el plugin mezcla
`composer.json` — los nuevos paquetes de `require-dev` (PHPCS, PHPStan,
GrumPHP, PHPUnit, etc.) **no quedan instalados en esa misma corrida**. Hay
que correr:

```bash
composer update --dev
# con DDEV:
ddev composer update --dev
```

Sin este paso, `composer qa` / `composer test` fallarán porque
`vendor/bin/phpcs`, `vendor/bin/phpstan`, `vendor/bin/phpunit`, etc. no
existen todavía.

## Validar instalación

```bash
composer qa     # PHPCS (Drupal/DrupalPractice) + PHPStan nivel 5
composer test   # PHPUnit (sin cobertura)
```

Si ambos terminan sin errores (incluso sin código custom todavía,
`composer qa` debe salir limpio sobre `web/modules/custom/.gitkeep` etc.),
la instalación quedó correcta.

## Git hooks (GrumPHP)

`phpro/grumphp` (^2.21) trae su propio Composer plugin: al correr
`composer update --dev` registra automáticamente el pre-commit hook
(`grumphp git:init`), configurado para usar `./scripts/grumphp.sh`
(wrapper compatible con DDEV, definido en `grumphp.yml` →
`git_hook_variables.EXEC_GRUMPHP_COMMAND`).

Si por algún motivo no se registró:

```bash
vendor/bin/grumphp install
cat .git/hooks/pre-commit   # debe invocar ./scripts/grumphp.sh
```

## DDEV

Si existe `.ddev/config.yaml` al momento de instalar/actualizar:

- `phpunit.xml` se genera con `SIMPLETEST_BASE_URL=https://<nombre-proyecto>.ddev.site`
  (leído del campo `name:` de `.ddev/config.yaml`).
- Se copia `.ddev/commands/web/test-coverage` (cobertura vía Xdebug, ya que
  la imagen `webimage` no incluye `pcov`):

```bash
ddev xdebug on
ddev exec "XDEBUG_MODE=coverage composer test:coverage"
# o, si está disponible el comando custom:
ddev test-coverage
```

`ddev composer ...` fuerza `XDEBUG_MODE=off` internamente — para cobertura
usar siempre `ddev exec`.

Si no usas DDEV, `phpunit.xml` queda con `SIMPLETEST_BASE_URL=http://localhost`
— ajústalo manualmente a la URL real de tu entorno.

## Probar el blueprint en un Drupal vacío

Pasos completos para validar el blueprint de punta a punta en un sitio
Drupal nuevo (útil tanto para QA del blueprint como para un proyecto real).

### 1. Crear el proyecto Drupal

```bash
mkdir mi-proyecto && cd mi-proyecto
ddev config --project-type=drupal11 --docroot=web --create-docroot
ddev start
ddev composer create-project drupal/recommended-project .
```

### 2. Declarar el repositorio del blueprint

Como `kdb/drupal-agentic-blueprint` no está en Packagist, agrega un
repositorio adicional en el `composer.json` del proyecto:

**Opción A — desarrollo local del blueprint** (iteración rápida, sin
necesidad de pushear a GitHub):

```bash
ddev composer config repositories.blueprint path /ruta/absoluta/a/drupal-agentic-blueprint
```

> Con `"options": {"symlink": true}` (default de `path`), cualquier cambio
> que hagas en el blueprint local se refleja al re-correr
> `ddev composer update kdb/drupal-agentic-blueprint`.

**Opción B — repo real (GitHub)**:

```bash
ddev composer config repositories.blueprint vcs https://github.com/yasuarezclavijo/drupal-composer-ia.git
```

### 3. Instalar el blueprint

```bash
ddev composer require kdb/drupal-agentic-blueprint:@dev
ddev composer update --dev    # paso obligatorio, ver sección anterior
```

### 4. Verificar archivos instalados

```bash
ls .claude/agents .claude/commands
cat CLAUDE.md
cat phpcs.xml phpstan.neon grumphp.yml > /dev/null && echo OK
cat phpunit.xml | grep SIMPLETEST_BASE_URL
```

### 5. Quality gates en verde sobre un proyecto vacío

```bash
ddev composer qa
ddev composer test
```

### 6. (Opcional) Instalar Drupal para tests Functional

Los tests `tests/src/Functional` (p.ej. los que genera
`create-api-endpoint`) necesitan un sitio Drupal instalado:

```bash
ddev drush site:install standard --account-pass=admin -y
ddev drush cr
```

### 7. Probar un skill end-to-end

Abre Claude Code en la raíz del proyecto (`ddev ssh` no es necesario, los
agentes corren fuera del contenedor):

```bash
claude
```

Y ejecuta, por ejemplo:

```
/create-module "smoke_test"
```

Esto crea un módulo mínimo con su `Service` y test Unit — un buen "humo"
para confirmar que TDD (red→green), `composer qa` y `composer test`
funcionan de extremo a extremo antes de tocar código real.

## Actualizar el blueprint

```bash
composer update kdb/drupal-agentic-blueprint
composer update --dev   # si el update agregó nuevos paquetes a require-dev
```

`installer.php update`:
- Sobreescribe `.claude/` y `phpcs.xml`/`phpstan.neon`/`grumphp.yml`/scripts
  (son del blueprint, no del usuario).
- **No** sobreescribe `CLAUDE.md` ni `docs/*` existentes (pueden tener
  personalizaciones del proyecto).
- Vuelve a correr `merge_composer_json()` (agrega solo lo que falte, corrige
  scripts rotos de versiones previas).

## Slash commands y agentes disponibles

Una vez instalado, dentro de Claude Code (raíz del proyecto):

```bash
# Slash commands (desde .claude/commands/)
/create-module "nombre_del_modulo"
/create-content-type "nombre"
/create-api-endpoint "endpoint_name" --method="GET" --resource="..."

# Agentes (desde .claude/agents/) — vía Task tool
@coordinator implementar sistema de notificaciones
@drupal-architect diseñar API de campañas
@code-reviewer revisar web/modules/custom/campaigns
@security-reviewer auditar autenticación
@accessibility-reviewer revisar tema
```

Pulsa `←` en Claude Code para ver la lista de agentes disponibles.

## Estructura instalada (resultado final)

```
proyecto/
├── .claude/
│   ├── agents/{coordinator,drupal-architect,tdd-specialist,code-reviewer,security-reviewer,accessibility-reviewer}.md
│   └── commands/{create-module,create-content-type,create-api-endpoint}.md
├── CLAUDE.md
├── phpcs.xml
├── phpstan.neon
├── grumphp.yml
├── phpunit.xml
├── scripts/{grumphp.sh,lint-php.sh,phpstan-wrapper.sh,phpcbf-wrapper.sh,twig-lint-wrapper.sh}
├── docs/{architecture.md,quality-gates.md,activity-log/}
├── web/
│   ├── modules/{contrib,custom}/
│   ├── themes/{contrib,custom}/
│   ├── profiles/{contrib,custom}/
│   └── sites/simpletest/browser_output/.gitkeep
└── composer.json   ← require/require-dev/scripts/config mezclados
```

## Troubleshooting

### "composer qa falla: vendor/bin/phpcs not found"

No se corrió el paso obligatorio:

```bash
composer update --dev
```

### "GrumPHP no ejecuta hooks"

```bash
vendor/bin/grumphp install
cat .git/hooks/pre-commit
```

### "PHPCS: installed_paths error"

```bash
vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
composer lint:phpcs
```

### "PHPStan: Path does not exist"

```bash
touch web/modules/custom/.gitkeep
touch web/themes/custom/.gitkeep
composer lint:phpstan
```

### "Tests coverage < 70%"

```bash
ddev xdebug on
ddev exec "XDEBUG_MODE=coverage composer test:coverage"
open coverage/index.html
```

### "Configuration objects (field.storage.node.body) ... already exist"

Ver [docs/architecture.md](docs/architecture.md#7-dependencia-implícita-del-perfil-standard)
— el perfil `standard` ya provee `field.storage.node.body`; un módulo
custom no debe exportarlo de nuevo.

## Soporte y ayuda

- **Arquitectura**: `docs/architecture.md`
- **Quality gates**: `docs/quality-gates.md`
- **Agentes**: `AGENTS.md` (en el repo del blueprint) / `.claude/agents/` (instalado)
- **Skills**: `.claude/commands/` (instalado)

## Siguientes pasos

1. ✅ `composer require kdb/drupal-agentic-blueprint` + `composer update --dev`
2. ✅ `composer qa` y `composer test` en verde
3. 🚀 Crea tu primer módulo: `/create-module "nombre"`
4. 📖 Lee `docs/architecture.md` (incluye la convención
   Resource/Service/Repository para endpoints y formularios)
5. 🤖 Para features complejas: `@coordinator <requisito>`
