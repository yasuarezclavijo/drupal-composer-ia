<?php

/**
 * Drupal Agentic Blueprint Installer
 *
 * Instala y configura el blueprint completo en un proyecto Drupal:
 *   - Agentes Claude Code (.claude/agents/)
 *   - Slash commands Claude Code (.claude/commands/)
 *   - CLAUDE.md con instrucciones del proyecto
 *   - Quality gates: PHPCS, PHPStan, GrumPHP
 *   - Scripts wrapper compatibles con DDEV
 *   - Merge de require-dev y scripts en composer.json del proyecto
 *   - Docs de referencia (architecture, quality-gates, activity-log)
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

  /** Dependencias dev que el stack determinístico necesita */
  private array $require_dev = [
    'drupal/coder'              => '^9.0',
    'mglaman/phpstan-drupal'    => '^2.0',
    'phpro/grumphp'             => '^2.21',
    'phpstan/phpstan'           => '^2.1',
    'squizlabs/php_codesniffer' => '^4.0',
    'friendsoftwig/twigcs'      => '^6.6',
  ];

  /** Scripts Composer que el proyecto destino necesita */
  private array $composer_scripts = [
    'qa'          => ['@lint:phpcs', '@lint:phpstan'],
    'test'        => 'vendor/bin/phpunit --coverage-text',
    'fix'         => 'vendor/bin/phpcbf --standard=phpcs.xml',
    'lint:phpcs'  => '@php scripts/lint-php.sh',
    'lint:phpstan'=> '@php scripts/phpstan-wrapper.sh',
    'audit'       => 'composer audit',
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
    $this->ensure_drupal_dirs();
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
      $this->force_copy_directory($src, $dest);
    } else {
      $this->copy_directory($src, $dest);
    }

    echo "✓ .claude/ (6 agents + 3 slash commands)\n";
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

  private function merge_composer_json(): void {
    $path = $this->project_root . '/composer.json';
    $json = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $changed = false;

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
    echo "     composer qa        # PHPCS + PHPStan\n";
    echo "     composer test      # PHPUnit + coverage\n";
    echo "     composer audit     # Dependencias vulnerables\n\n";

    echo "  3. Abrir Claude Code (terminal en raíz del proyecto):\n";
    echo "     claude\n";
    echo "     Presiona ← para ver los 6 agentes disponibles.\n\n";

    echo "  4. Slash commands disponibles:\n";
    echo "     /create-module\n";
    echo "     /create-content-type\n";
    echo "     /create-api-endpoint\n\n";

    echo "  5. Primera tarea con el coordinador:\n";
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
