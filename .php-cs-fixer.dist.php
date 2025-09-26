<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

$finder = (new Finder())
    ->in(__DIR__)
    ->exclude(['var', 'test', 'resources', 'logs', 'storage', 'vendor', 'libraries', 'user_privileges', 'cache', 'pkg/vtiger/extensions/Webservices/third-party'])
    ->append([
        __FILE__,
    ]);

$config = (new Config())
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache')
    ->setFinder($finder);

(new PhpCsFixerCodingStandard())->applyTo($config, [
    'final_class' => false,
    'final_public_method_for_abstract_class' => false,
    '@PHP80Migration:risky' => false,
    '@PhpCsFixer:risky' => false,
    '@PHPUnit84Migration:risky' => false,
]);

return $config;
