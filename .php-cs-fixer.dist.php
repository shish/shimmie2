<?php

$finder = PhpCsFixer\Finder::create()
  ->exclude('ext/amazon_s3/lib')
  ->exclude('vendor')
  ->exclude('data')
  ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
        //'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;

?>
