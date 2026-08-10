<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/stubs', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // ext-pcntl / ext-posix are optional (see "suggest" in composer.json): only needed when
    // using `Yiisoft\Queue\Cli\SignalLoop` and its tests, not for the package to function.
    ->ignoreErrorsOnExtensions(['ext-pcntl', 'ext-posix'], [ErrorType::SHADOW_DEPENDENCY])
    // yiisoft/yii-debug integration in src/Debug is an optional debug panel collector, only
    // loaded when the consumer wires up yiisoft/yii-debug themselves.
    ->ignoreErrorsOnPackages(['yiisoft/yii-debug'], [ErrorType::DEV_DEPENDENCY_IN_PROD]);
