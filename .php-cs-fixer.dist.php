<?php

$_phpcs_finder = PhpCsFixer\Finder::create()
  ->exclude('ext/amazon_s3/lib')
  ->exclude('vendor')
  ->exclude('data')
  ->in(__DIR__)
;

$_phpcs_config = new PhpCsFixer\Config();
return $_phpcs_config->setRules([
        '@PSR12' => true,
        //'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($_phpcs_finder)
	->setCacheFile("data/php-cs-fixer.cache")
;
