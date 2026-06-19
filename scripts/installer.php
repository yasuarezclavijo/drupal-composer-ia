<?php

/**
 * Drupal Agentic Blueprint Installer
 *
 * Instala y configura el blueprint completo en un proyecto Drupal:
 *   - Agentes Claude Code (.claude/agents/)
 *   - Slash commands Claude Code (.claude/commands/)
 *   - CLAUDE.md con instrucciones del proyecto
 *   - Quality gates: PHPCS (Drupal/DrupalPractice), PHPStan, GrumPHP
 *   - Scripts wrapper compatibles con DDEV
 *   - phpunit.xml + web/sites/simpletest/browser_output/
 *   - Comando DDEV `test-coverage` (cobertura vía Xdebug)
 *   - Merge de require (drush/drush), require-dev, scripts y config
 *     (use-github-api) en composer.json del proyecto
 *   - Docs de referencia (architecture, quality-gates, activity-log)
 *   - docs/requirements/ (README.md + _TEMPLATE.md para especificar
 *     requerimientos antes de invocar al coordinador)
 *
 * Usage (auto-run via post-install-cmd):
 *   php scripts/installer.php install
 *   php scripts/installer.php update
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$command = $argv[1] ?? 'help';

class BlueprintInstaller {

  private string $project_root;
  private string $blueprint_root;

  /** Dependencias de producción que el proyecto Drupal necesita */
  private array $require = [
    'drush/drush' => '^13.7',
  ];

  /**
   * Dependencias dev que el stack determinístico necesita.
   *
   * No se requiere drupal/core-dev: drupal/core-dev ^11.3 exige
   * drupal/coder ^8.3.30, lo que entra en conflicto irresoluble con
   * drupal/coder ^9.0 (PHPCS 4.x) usado por este blueprint. En su lugar se
   * listan aquí, de forma explícita, los paquetes que drupal/core-dev
   * aportaría para que PHPUnit (Kernel/Functional/FunctionalJavascript)
   * funcione out-of-the-box.
   */
  private array $require_dev = [
    'drupal/coder'              => '^9.0',
    'mglaman/phpstan-drupal'    => '^2.0',
    'phpro/grumphp'             => '^2.21',
    'phpstan/phpstan'           => '^2.1',
    'squizlabs/php_codesniffer' => '^4.0',
    'friendsoftwig/twigcs'      => '^6.6',

    // --- Sustitutos de drupal/core-dev (ver docblock arriba) ---
    'behat/mink'                     => '^1.11',
    'behat/mink-browserkit-driver'   => '^2.2',
    'colinodell/psr-testlogger'      => '^1.2',
    'composer/composer'              => '^2.8.1',
    'justinrainbow/json-schema'      => '^5.2 || ^6.5.2',
    'lullabot/mink-selenium2-driver' => '^1.7.3',
    'lullabot/php-webdriver'         => '^2.0.7',
    'micheh/phpcs-gitlab'            => '^1.1 || ^2.0',
    'mikey179/vfsstream'             => '^1.6.11',
    'open-telemetry/exporter-otlp'   => '^1',
    'open-telemetry/sdk'             => '^1',
    'php-http/guzzle7-adapter'       => '^1.0',
    'phpspec/prophecy'               => '^1.23',
    'phpspec/prophecy-phpunit'       => '^2',
    'phpstan/phpstan-phpunit'        => '^1.4.2 || ^2.0.7',
    'phpunit/phpunit'                => '^11.5.50',
    'symfony/browser-kit'            => '^7.4',
    'symfony/css-selector'           => '^7.4',
    'symfony/dom-crawler'            => '^7.4.12',
    'symfony/error-handler'          => '^7.4',
    'symfony/lock'                   => '^7.4',
    'symfony/var-dumper'             => '^7.4',
  ];

  /**
   * Repositorios VCS que el proyecto destino necesita para resolver dependencias
   * que aún no están en Packagist. Cada entrada se agrega solo si no existe una
   * entrada con la misma URL en el composer.json del proyecto.
   */
  private array $vcs_repositories = [
    [
      'type' => 'vcs',
      'url'  => 'https://github.com/yasuarezclavijo/kadabra-core',
    ],
  ];

  /** Scripts Composer que el proyecto destino necesita */
  private array $composer_scripts = [
    'qa'            => ['@lint:phpcs', '@lint:phpstan'],
    'test'          => 'vendor/bin/phpunit',
    'test:coverage' => 'XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html=coverage --coverage-text',
    'fix'           => 'vendor/bin/phpcbf --standard=phpcs.xml',
    'lint:phpcs'    => 'bash scripts/lint-php.sh',
    'lint:phpstan'  => 'bash scripts/phpstan-wrapper.sh',
    'audit'         => 'composer audit',
  ];

  /**
   * Scripts rotos en versiones previas del blueprint que deben corregirse
   * en composer.json del proyecto destino aunque ya existan (ver
   * `fix_broken_scripts()`).
   *
   * Mapa: nombre de script => [valor_roto_anterior => valor_corregido].
   */
  private array $broken_scripts_fixes = [
    'lint:phpcs' => [
      '@php scripts/lint-php.sh' => 'bash scripts/lint-php.sh',
    ],
    'lint:phpstan' => [
      '@php scripts/phpstan-wrapper.sh' => 'bash scripts/phpstan-wrapper.sh',
    ],
  ];

  /** Scripts wrapper a copiar al proyecto (excluye installer.php) */
  private array $wrapper_scripts = [
    'grumphp.sh',
    'lint-php.sh',
    'phpstan-wrapper.sh',
    'phpcbf-wrapper.sh',
    'twig-lint-wrapper.sh',
  ];

  public function __construct() {
    $this->blueprint_root = dirname(__DIR__);
    // vendor/kdb/drupal-agentic-blueprint → 3 levels up = project root
    $candidate = realpath($this->blueprint_root . '/../../../');

    if (!$candidate || !file_exists($candidate . '/composer.json')) {
      $this->error(
        "Blueprint must be installed via Composer in a project with composer.json.\n" .
        "  Blueprint root: {$this->blueprint_root}\n" .
        "  Candidate root: {$candidate}"
      );
    }

    $this->project_root = $candidate;
  }

  // =========================================================================
  // Public commands
  // =========================================================================

  public function install(): void {
    echo "\n🚀 Installing Drupal Agentic Blueprint v1.0.0\n";
    echo "   Project root: {$this->project_root}\n\n";

    $this->copy_claude_dir();
    $this->copy_single('CLAUDE.md',            '/CLAUDE.md',         skip_existing: true);
    $this->copy_quality_configs(force: false);
    $this->copy_wrapper_scripts(force: false);
    $this->copy_docs();
    $this->copy_requirements_dir();
    $this->ensure_drupal_dirs();
    $this->ensure_test_dirs();
    $this->ensure_phpunit_config();
    $this->copy_ddev_coverage_command(force: false);
    $this->merge_composer_json();

    $this->success('Blueprint installed!');
    $this->next_steps(is_update: false);
  }

  public function update(): void {
    // Si no está instalado aún, correr install en su lugar.
    if (!file_exists($this->project_root . '/.claude/agents/coordinator.md')) {
      $this->install();
      return;
    }

    echo "\n📦 Updating Drupal Agentic Blueprint\n";
    echo "   Project root: {$this->project_root}\n\n";

    // .claude/ y quality configs se sobreescriben (son del blueprint, no del usuario)
    $this->copy_claude_dir(force: true);
    $this->copy_quality_configs(force: true);
    $this->copy_wrapper_scripts(force: true);
    // CLAUDE.md y docs NO se sobreescriben (pueden tener customizaciones)
    $this->copy_docs(skip_existing: true);
    $this->copy_requirements_dir();
    $this->ensure_test_dirs();
    $this->ensure_phpunit_config();
    $this->copy_ddev_coverage_command(force: true);
    $this->merge_composer_json();

    $this->success('Blueprint updated!');
    $this->next_steps(is_update: true);
  }

  // =========================================================================
  // Install steps
  // =========================================================================

  private function copy_claude_dir(bool $force = false): void {
    $src  = $this->blueprint_root . '/.claude';
    $dest = $this->project_root   . '/.claude';

    if ($force) {
      // En update: sobreescribir agents/, commands/ y policies/platform/
      // NO tocar policies/core/ — territorio de kadabrait_uy/kadabra-core
      // NO tocar policies/project/ — territorio del proyecto
      // NO tocar logs/ — territorio de kadabrait_uy/kadabra-core
      $this->force_copy_directory($src . '/agents',   $dest . '/agents');
      $this->force_copy_directory($src . '/commands', $dest . '/commands');
      $this->copy_platform_policies(force: true);
    } else {
      $this->copy_directory($src . '/agents',   $dest . '/agents');
      $this->copy_directory($src . '/commands', $dest . '/commands');
      $this->copy_platform_policies(force: false);
    }

    // Garantizar que el directorio de logs exista (kadabra-core lo puebla con _TEMPLATE.md)
    $this->ensure_logs_dir();

    echo "✓ .claude/ (6 agents + 3 slash commands + platform policies)\n";
  }

  private function copy_platform_policies(bool $force): void {
    $src  = $this->blueprint_root . '/.claude/policies/platform';
    $dest = $this->project_root   . '/.claude/policies/platform';

    if (!is_dir($src)) {
      return;
    }

    if ($force) {
      $this->force_copy_directory($src, $dest);
      echo ($force ? '↺' : '✓') . " .claude/policies/platform/ (Capa 1)\n";
    } else {
      $this->copy_directory($src, $dest);
      echo "✓ .claude/policies/platform/ (Capa 1)\n";
    }
  }

  private function ensure_logs_dir(): void {
    $dir = $this->project_root . '/.claude/logs';
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }

    $gitkeep = $dir . '/.gitkeep';
    if (!file_exists($gitkeep)) {
      touch($gitkeep);
    }
  }

  private function copy_quality_configs(bool $force): void {
    $configs = [
      'quality/phpcs.xml'    => '/phpcs.xml',
      'quality/phpstan.neon' => '/phpstan.neon',
      'quality/grumphp.yml'  => '/grumphp.yml',
    ];

    foreach ($configs as $src => $dest) {
      $this->copy_single($src, $dest, skip_existing: !$force);
    }
  }

  private function copy_wrapper_scripts(bool $force): void {
    $dest_dir = $this->project_root . '/scripts';
    if (!is_dir($dest_dir)) {
      mkdir($dest_dir, 0755, true);
    }

    foreach ($this->wrapper_scripts as $script) {
      $src  = $this->blueprint_root . '/scripts/' . $script;
      $dest = $dest_dir . '/' . $script;

      if (!file_exists($src)) {
        continue;
      }

      if (!file_exists($dest) || $force) {
        copy($src, $dest);
        chmod($dest, 0755);
        echo ($force ? '↺' : '✓') . " scripts/{$script}\n";
      } else {
        echo "⊘ scripts/{$script} already exists — skipped\n";
      }
    }
  }

  private function copy_docs(bool $skip_existing = false): void {
    $src  = $this->blueprint_root . '/docs';
    $dest = $this->project_root   . '/docs';

    if ($skip_existing) {
      $this->copy_directory($src, $dest);       // never overwrites
    } else {
      $this->copy_directory($src, $dest);
    }

    echo "✓ docs/ (architecture.md, quality-gates.md, activity-log/)\n";
  }

  /**
   * Copia la plantilla de requerimientos (requirements/ en el blueprint) a
   * docs/requirements/ en el proyecto destino. Nunca sobreescribe archivos
   * existentes (los requerimientos del proyecto son del usuario).
   */
  private function copy_requirements_dir(): void {
    $src  = $this->blueprint_root . '/requirements';
    $dest = $this->project_root   . '/docs/requirements';

    $this->copy_directory($src, $dest);

    echo "✓ docs/requirements/ (README.md, _TEMPLATE.md)\n";
  }

  private function ensure_drupal_dirs(): void {
    $dirs = ['/web/modules/custom', '/web/themes/custom', '/web/profiles/custom'];
    foreach ($dirs as $dir) {
      $path = $this->project_root . $dir;
      if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✓ Created {$dir}\n";
      }
    }
  }

  /**
   * Crea web/sites/simpletest/browser_output/, requerido por
   * Drupal\TestTools\Extension\HtmlLogging\HtmlOutputLogger (phpunit.xml).
   * Sin este directorio, `composer test` falla con error de "no writable".
   */
  private function ensure_test_dirs(): void {
    $dir = $this->project_root . '/web/sites/simpletest/browser_output';
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }

    $gitkeep = $dir . '/.gitkeep';
    if (!file_exists($gitkeep)) {
      touch($gitkeep);
      echo "✓ Created web/sites/simpletest/browser_output/.gitkeep\n";
    }
  }

  /**
   * Copia phpunit.xml a la raíz del proyecto (basado en
   * web/core/phpunit.xml.dist), sustituyendo el placeholder
   * __SIMPLETEST_BASE_URL__ por la URL DDEV detectada (o un fallback).
   * No sobreescribe un phpunit.xml existente (puede tener customizaciones).
   */
  private function ensure_phpunit_config(): void {
    $dest = $this->project_root . '/phpunit.xml';
    if (file_exists($dest)) {
      echo "⊘ phpunit.xml already exists — skipped\n";
      return;
    }

    $src = $this->blueprint_root . '/quality/phpunit.xml.dist';
    if (!file_exists($src)) {
      return;
    }

    $contents = file_get_contents($src);
    $contents = str_replace('__SIMPLETEST_BASE_URL__', $this->detect_ddev_base_url(), $contents);

    file_put_contents($dest, $contents);
    echo "✓ Copied phpunit.xml\n";
  }

  /**
   * Detecta la URL base de DDEV a partir de .ddev/config.yaml (campo
   * `name:`). Si no hay DDEV, retorna un fallback genérico que el usuario
   * deberá ajustar manualmente en phpunit.xml.
   */
  private function detect_ddev_base_url(): string {
    $ddev_config = $this->project_root . '/.ddev/config.yaml';
    if (file_exists($ddev_config)) {
      $contents = file_get_contents($ddev_config);
      if (preg_match('/^name:\s*(\S+)/m', $contents, $matches)) {
        return 'https://' . trim($matches[1]) . '.ddev.site';
      }
      return 'https://default.ddev.site';
    }

    return 'http://localhost';
  }

  /**
   * Copia el comando custom de DDEV `test-coverage` (cobertura vía Xdebug)
   * a .ddev/commands/web/. Solo aplica si el proyecto usa DDEV.
   */
  private function copy_ddev_coverage_command(bool $force): void {
    if (!file_exists($this->project_root . '/.ddev/config.yaml')) {
      return;
    }

    $this->copy_single(
      'templates/ddev-test-coverage',
      '/.ddev/commands/web/test-coverage',
      skip_existing: !$force
    );

    $dest = $this->project_root . '/.ddev/commands/web/test-coverage';
    if (file_exists($dest)) {
      chmod($dest, 0755);
    }
  }

  private function merge_composer_json(): void {
    $path = $this->project_root . '/composer.json';
    $json = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $changed = false;

    // --- repositories (VCS para paquetes fuera de Packagist) ---
    $json['repositories'] ??= [];
    $existing_urls = array_column($json['repositories'], 'url');
    foreach ($this->vcs_repositories as $repo) {
      if (!in_array($repo['url'], $existing_urls, strict: true)) {
        $json['repositories'][] = $repo;
        echo "✓ Added repository: {$repo['url']}\n";
        $changed = true;
      } else {
        echo "⊘ repository {$repo['url']} already present — skipped\n";
      }
    }

    // --- require ---
    $json['require'] ??= [];
    foreach ($this->require as $package => $version) {
      if (!isset($json['require'][$package])) {
        $json['require'][$package] = $version;
        echo "✓ Added require: {$package}:{$version}\n";
        $changed = true;
      } else {
        echo "⊘ require {$package} already present — skipped\n";
      }
    }

    // --- require-dev ---
    $json['require-dev'] ??= [];
    foreach ($this->require_dev as $package => $version) {
      if (!isset($json['require-dev'][$package])) {
        $json['require-dev'][$package] = $version;
        echo "✓ Added require-dev: {$package}:{$version}\n";
        $changed = true;
      } else {
        echo "⊘ require-dev {$package} already present — skipped\n";
      }
    }

    // --- scripts ---
    $json['scripts'] ??= [];
    foreach ($this->composer_scripts as $name => $cmd) {
      if (!isset($json['scripts'][$name])) {
        $json['scripts'][$name] = $cmd;
        echo "✓ Added script: composer {$name}\n";
        $changed = true;
      } else {
        echo "⊘ script '{$name}' already present — skipped\n";
      }
    }

    // --- fix scripts rotos de versiones previas (composer qa no-op) ---
    foreach ($this->broken_scripts_fixes as $name => $fixes) {
      $current = $json['scripts'][$name] ?? null;
      if (is_string($current) && isset($fixes[$current])) {
        $json['scripts'][$name] = $fixes[$current];
        echo "↺ Fixed broken script '{$name}': {$current} → {$fixes[$current]}\n";
        $changed = true;
      }
    }

    // --- config ---
    $json['config'] ??= [];
    if (!array_key_exists('use-github-api', $json['config'])) {
      // Evita "Could not authenticate against github.com" por rate-limit
      // anónimo de la API de GitHub al resolver el repositorio VCS propio.
      $json['config']['use-github-api'] = false;
      echo "✓ Added config: use-github-api=false\n";
      $changed = true;
    } else {
      echo "⊘ config.use-github-api already present — skipped\n";
    }

    if ($changed) {
      file_put_contents(
        $path,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
      );
      echo "✓ composer.json updated\n";
    }
  }

  // =========================================================================
  // File helpers
  // =========================================================================

  private function copy_single(string $src_rel, string $dest_rel, bool $skip_existing): void {
    $src  = $this->blueprint_root . '/' . $src_rel;
    $dest = $this->project_root   . $dest_rel;

    if (!file_exists($src)) {
      return;
    }

    if ($skip_existing && file_exists($dest)) {
      echo "⊘ {$dest_rel} already exists — skipped\n";
      return;
    }

    $dir = dirname($dest);
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }

    copy($src, $dest);
    echo "✓ Copied {$dest_rel}\n";
  }

  private function copy_directory(string $src, string $dest): void {
    if (!is_dir($src)) return;
    if (!is_dir($dest)) mkdir($dest, 0755, true);

    $items = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($items as $item) {
      $relative = substr($item->getPathname(), strlen($src) + 1);
      $target   = $dest . '/' . $relative;

      if ($item->isDir()) {
        if (!is_dir($target)) mkdir($target, 0755, true);
      } elseif (!file_exists($target)) {
        copy($item->getPathname(), $target);
      }
    }
  }

  private function force_copy_directory(string $src, string $dest): void {
    if (!is_dir($src)) return;
    if (!is_dir($dest)) mkdir($dest, 0755, true);

    $items = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($items as $item) {
      $relative = substr($item->getPathname(), strlen($src) + 1);
      $target   = $dest . '/' . $relative;

      if ($item->isDir()) {
        if (!is_dir($target)) mkdir($target, 0755, true);
      } else {
        copy($item->getPathname(), $target);
      }
    }
  }

  // =========================================================================
  // Output helpers
  // =========================================================================

  private function success(string $msg): void {
    echo "\n✅ {$msg}\n";
  }

  private function error(string $msg): void {
    echo "\n❌ Error: {$msg}\n";
    exit(1);
  }

  private function next_steps(bool $is_update): void {
    echo "\n📋 Next steps:\n\n";

    if (!$is_update) {
      echo "  1. Install quality gate dependencies:\n";
      echo "     composer update --dev\n\n";
      echo "     GrumPHP registrará los pre-commit hooks automáticamente.\n\n";
    }

    echo "  2. Verificar quality gates:\n";
    echo "     composer qa        # PHPCS (Drupal/DrupalPractice) + PHPStan\n";
    echo "     composer test      # PHPUnit (sin cobertura)\n";
    echo "     composer audit     # Dependencias vulnerables\n\n";

    echo "  3. Revisar phpunit.xml:\n";
    echo "     Si no usas DDEV, ajusta SIMPLETEST_BASE_URL y SIMPLETEST_DB.\n\n";

    echo "  4. Cobertura de código (requiere Xdebug, no pcov):\n";
    echo "     ddev xdebug on\n";
    echo "     ddev exec \"XDEBUG_MODE=coverage composer test:coverage\"\n";
    echo "     (ddev composer ... fuerza XDEBUG_MODE=off; usar ddev exec)\n\n";

    echo "  5. Abrir Claude Code (terminal en raíz del proyecto):\n";
    echo "     claude\n";
    echo "     Presiona ← para ver los 6 agentes disponibles.\n\n";

    echo "  6. Slash commands disponibles:\n";
    echo "     /create-module\n";
    echo "     /create-content-type\n";
    echo "     /create-api-endpoint\n\n";

    echo "  7. Primera tarea con el coordinador:\n";
    echo "     @coordinator implementar sistema de notificaciones\n\n";
  }
}

// =============================================================================
$installer = new BlueprintInstaller();

switch ($argv[1] ?? 'help') {
  case 'install': $installer->install(); break;
  case 'update':  $installer->update();  break;
  default:
    echo "Usage:\n";
    echo "  php scripts/installer.php install\n";
    echo "  php scripts/installer.php update\n";
}
