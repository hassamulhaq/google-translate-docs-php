<?php

require 'vendor/autoload.php';

use Hassamulhaq\GoogleTranslateDocsPhp\DocumentTranslator;

$translator = new DocumentTranslator();
$translator->handleRequest();

