<?php

require_once 'vendor/autoload.php';
use Tools\NewsletterGenerator\Util\CLI as CLI;
use Tools\NewsletterGenerator\Service\NewsletterGenerator as NewsletterGenerator;

$cli = new CLI($argv);
$dataFile = $cli->get('data');

$newsletterGenerator = new NewsletterGenerator();
$newsletterGenerator->execute($dataFile);

?>