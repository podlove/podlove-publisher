<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();

$c = $config->setRules([
    '@PSR2' => true,
    '@PhpCsFixer' => true,
    'yoda_style' => false,
    'array_syntax' => ['syntax' => 'short'],
    'trailing_comma_in_multiline' => false,
    'no_trailing_comma_in_singleline_array' => true,
    'blank_line_before_statement' => ['statements' => ['break', 'continue', 'declare', 'default', 'return', 'throw', 'try']],
    'visibility_required' => ['elements' => ['method', 'property']]
])
    ->setFinder($finder)
;

return $c;
