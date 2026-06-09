<?php

/**
 * Drupal Agentic Blueprint Installer
 *
 * Instala el blueprint en un proyecto Drupal existente.
 *
 * Usage:
 *   php scripts/installer.php install [--interactive]
 *   php scripts/installer.php update
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$command = $argv[1] ?? 'help';
$interactive = in_array('--interactive', $argv);

class BlueprintInstaller {
  private $project_root;
  private $blueprint_root;

  public function __construct() {
    $this->blueprint_root = dirname(__DIR__);
    // Project root is vendor/yeison/drupal-agentic-blueprint/../../../
    $this->project_root = realpath($this->blueprint_root . '/../../../../');

    if (!$this->project_root || !file_exists($this->project_root . '/composer.json')) {
      $this->error('Blueprint must be installed via Composer in a Drupal project');
    }
  }

  public function install($interactive = false) {
    echo "\n🚀 Installing Drupal Agentic Blueprint v1.0.0\n";
    echo "Project root: {$this->project_root}\n\n";

    if ($interactive) {
      $this->interactive_setup();
    } else {
      $this->standard_setup();
    }

    $this->success('Blueprint installed successfully!');
    $this->next_steps();
  }

  public function update() {
    echo "\n📦 Updating Drupal Agentic Blueprint\n";
    echo "Project root: {$this->project_root}\n\n";

    // Check if already installed
    if (!file_exists($this->project_root . '/CLAUDE.md')) {
      echo "❌ Blueprint not yet installed. Run: composer require yeison/drupal-agentic-blueprint\n";
      exit(1);
    }

    // v1 doesn't have breaking changes, just inform user
    echo "✓ Blueprint is up to date\n";
    echo "Current version: 1.0.0\n\n";
  }

  private function standard_setup() {
    // Copy configuration files
    $files = [
      'AGENTS.md' => '/AGENTS.md',
      'CLAUDE.md' => '/CLAUDE.md',
      'quality/phpcs.xml' => '/phpcs.xml',
      'quality/phpstan.neon' => '/phpstan.neon',
      'quality/grumphp.yml' => '/grumphp.yml',
    ];

    foreach ($files as $source => $dest) {
      $source_path = $this->blueprint_root . '/' . $source;
      $dest_path = $this->project_root . $dest;

      if (file_exists($source_path)) {
        if (!file_exists($dest_path)) {
          copy($source_path, $dest_path);
          echo "✓ Copied {$dest}\n";
        } else {
          echo "⊘ {$dest} already exists (skipping)\n";
        }
      }
    }

    // Create directories
    $dirs = [
      '/agents',
      '/skills',
      '/docs',
      '/web/modules/custom',
      '/web/themes/custom',
      '/web/profiles/custom',
    ];

    foreach ($dirs as $dir) {
      $path = $this->project_root . $dir;
      if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✓ Created directory {$dir}\n";
      }
    }

    // Copy agents
    $this->copy_directory(
      $this->blueprint_root . '/agents',
      $this->project_root . '/agents'
    );

    // Copy skills
    $this->copy_directory(
      $this->blueprint_root . '/skills',
      $this->project_root . '/skills'
    );

    // Copy docs
    $this->copy_directory(
      $this->blueprint_root . '/docs',
      $this->project_root . '/docs'
    );
  }

  private function interactive_setup() {
    echo "? Project name (e.g., 'TrazApp'): ";
    $project_name = trim(fgets(STDIN));

    echo "? Organization/Agency (e.g., 'Yeison A. Suarez'): ";
    $organization = trim(fgets(STDIN));

    echo "? Enable strict security review? [y/N]: ";
    $strict_security = strtolower(trim(fgets(STDIN))) === 'y';

    echo "? Enable accessibility review? [y/N]: ";
    $strict_a11y = strtolower(trim(fgets(STDIN))) === 'y';

    // Run standard setup
    $this->standard_setup();

    // Customize CLAUDE.md
    $claude_file = $this->project_root . '/CLAUDE.md';
    $content = file_get_contents($claude_file);

    // Replace placeholders
    $content = str_replace(
      ['PROJECT_NAME', 'ORGANIZATION'],
      [$project_name, $organization],
      $content
    );

    if ($strict_security) {
      $content = str_replace(
        'strictness: "medium"',
        'strictness: "high"',
        $content
      );
    }

    file_put_contents($claude_file, $content);
    echo "\n✓ Customized CLAUDE.md with your settings\n";
  }

  private function copy_directory($src, $dest) {
    if (!is_dir($src)) return;

    if (!is_dir($dest)) {
      mkdir($dest, 0755, true);
    }

    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($src),
      \RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
      if ($file->isDot()) continue;

      $relative = substr($file->getPathname(), strlen($src) + 1);
      $target = $dest . '/' . $relative;

      if ($file->isDir()) {
        if (!is_dir($target)) {
          mkdir($target, 0755, true);
        }
      } else {
        if (!file_exists($target)) {
          copy($file->getPathname(), $target);
        }
      }
    }
  }

  private function success($message) {
    echo "\n✅ {$message}\n";
  }

  private function error($message) {
    echo "\n❌ Error: {$message}\n";
    exit(1);
  }

  private function next_steps() {
    echo "\n📋 Next steps:\n\n";
    echo "1. Review configuration:\n";
    echo "   - AGENTS.md: Available agents\n";
    echo "   - CLAUDE.md: Configuration for your project\n";
    echo "   - phpcs.xml, phpstan.neon, grumphp.yml: Quality gates\n\n";

    echo "2. Validate installation:\n";
    echo "   composer lint:php\n";
    echo "   composer lint:phpcs\n";
    echo "   composer lint:phpstan\n\n";

    echo "3. Create your first module:\n";
    echo "   /skill:create-module 'mi_modulo'\n\n";

    echo "4. Read the documentation:\n";
    echo "   - docs/architecture.md\n";
    echo "   - docs/quality-gates.md\n\n";

    echo "Questions? See AGENTS.md for available agents!\n\n";
  }
}

// Main
$installer = new BlueprintInstaller();

switch ($command) {
  case 'install':
    $installer->install($interactive);
    break;

  case 'update':
    $installer->update();
    break;

  case 'help':
  default:
    echo "Drupal Agentic Blueprint Installer\n";
    echo "Usage:\n";
    echo "  php scripts/installer.php install [--interactive]\n";
    echo "  php scripts/installer.php update\n";
    echo "  php scripts/installer.php help\n";
    break;
}
