<?php

$_phpcs_finder = PhpCsFixer\Finder::create()
  ->exclude('ext/amazon_s3/lib')
  ->exclude('vendor')
  ->exclude('data')
  ->in(__DIR__)
;

$_phpcs_config = new PhpCsFixer\Config();
return $_phpcs_config
  ->setRules([
    '@PSR12' => true,
    // 'strict_param' => true,
    'array_syntax' => ['syntax' => 'short'],
    'no_unused_imports' => true,
    // 'modernize_strpos' => true,
    'single_import_per_statement' => false,
    'group_import' => true,
    'ordered_imports' => true,
  ])
  ->setFinder($_phpcs_finder)
  ->setCacheFile("data/cache/php-cs-fixer.cache")
  ->setUnsupportedPhpVersionAllowed(true)
  ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
;
