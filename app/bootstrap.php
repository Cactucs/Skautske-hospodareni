<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$tempDir = dirname(__DIR__) . '/temp';

putenv('TMPDIR=' . $tempDir);

$configurator = new Nette\Configurator();
$configurator->setDebugMode(getenv('DEVELOPMENT_MACHINE') === 'true' ?: '94.113.119.27');
$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTempDirectory($tempDir);

$configurator->createRobotLoader()
    ->addDirectory(__DIR__)
    ->addDirectory(__DIR__ . '/../vendor/others')
    ->register(true);

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
