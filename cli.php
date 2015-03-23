<?php

require_once 'vendor/autoload.php';
use Isha\NewsletterGenerator\Util\Config as Config;
use Isha\NewsletterGenerator\Util\CLI as CLI;
use Isha\NewsletterGenerator\Util\Helper as Helper;
use Isha\NewsletterGenerator\Service\ContentExtractor as ContentExtractor;
use Isha\NewsletterGenerator\Service\ContentGenerator as ContentGenerator;

$config = Config::getConfig();

$cli = new CLI($argv);
$dataFile = $cli->get($argv, 'data');

$contentExtractor = new ContentExtractor();
$inputData = $contentExtractor->getData($dataFile);
$newsletterData['details'] = $contentExtractor->getDetailsFromData($inputData);
$newsletterData['blocks'] = $contentExtractor->getBlocksFromData($inputData);

$contentGenerator = new ContentGenerator();
$newsletterFile = $contentGenerator->getNewsletterFilename($newsletterData['details'], $config);
$newsletterPath = dirname($newsletterFile) . '/';
$newsletterData['blocks'] = $contentGenerator->fillBlocksData($newsletterData['blocks'], $newsletterPath, $config);

Helper::createDirectories(array($newsletterPath . $config['original_images_path']));

$contentGenerator->execute($config['newsletter_template'], $newsletterData, $newsletterFile);

?>