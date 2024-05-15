<?php

// @see https://github.com/VincentLanglet/Twig-CS-Fixer/blob/main/docs/configuration.md#standard
$config = new TwigCsFixer\Config\Config();

// @see https://github.com/VincentLanglet/Twig-CS-Fixer/pull/134#issuecomment-1756924889
$config->addTokenParser(new Drupal\Core\Template\TwigTransTokenParser());

return $config;
