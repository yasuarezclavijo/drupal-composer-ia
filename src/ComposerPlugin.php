<?php

declare(strict_types=1);

namespace DrupalAgenticBlueprint;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin que instala el blueprint automáticamente.
 *
 * Se activa al hacer:
 *   composer require kdb/drupal-agentic-blueprint
 *   composer update kdb/drupal-agentic-blueprint
 *
 * No requiere pasos manuales: el instalador corre solo.
 */
class ComposerPlugin implements PluginInterface, EventSubscriberInterface {

  private Composer $composer;
  private IOInterface $io;

  public function activate(Composer $composer, IOInterface $io): void {
    $this->composer = $composer;
    $this->io = $io;
  }

  public function deactivate(Composer $composer, IOInterface $io): void {}

  public function uninstall(Composer $composer, IOInterface $io): void {}

  public static function getSubscribedEvents(): array {
    return [
      ScriptEvents::POST_INSTALL_CMD => ['onPostInstall', 0],
      ScriptEvents::POST_UPDATE_CMD  => ['onPostUpdate', 0],
    ];
  }

  public function onPostInstall(Event $event): void {
    $this->runInstaller('install');
  }

  public function onPostUpdate(Event $event): void {
    $this->runInstaller('update');
  }

  private function runInstaller(string $command): void {
    $vendorDir     = $this->composer->getConfig()->get('vendor-dir');
    $installerPath = $vendorDir . '/kdb/drupal-agentic-blueprint/scripts/installer.php';

    if (!file_exists($installerPath)) {
      // Package not yet installed (e.g. composer install on a fresh clone
      // before vendor/ exists). Skip silently.
      return;
    }

    $this->io->write('');
    $this->io->write('<info>kdb/drupal-agentic-blueprint: running blueprint ' . $command . '...</info>');

    $php      = \PHP_BINARY;
    $exitCode = 0;

    passthru(escapeshellcmd($php) . ' ' . escapeshellarg($installerPath) . ' ' . escapeshellarg($command), $exitCode);

    if ($exitCode !== 0) {
      $this->io->writeError('<error>Blueprint installer failed (exit ' . $exitCode . '). Run manually:</error>');
      $this->io->writeError('  php vendor/kdb/drupal-agentic-blueprint/scripts/installer.php install');
    }
  }

}
