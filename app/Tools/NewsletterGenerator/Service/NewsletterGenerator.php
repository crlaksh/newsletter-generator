<?php

namespace Tools\NewsletterGenerator\Service;
use Tools\NewsletterGenerator\Util\Helper as Helper;
use Tools\NewsletterGenerator\Service\ContentExtractor as ContentExtractor;
use Tools\NewsletterGenerator\Service\ContentGenerator as ContentGenerator;

class NewsletterGenerator extends Helper {

    function execute($dataFile) {
        $config = $this->getConfig();

        $contentExtractor = new ContentExtractor();
        $inputData = $contentExtractor->getData($dataFile);
        $newsletterData['config'] = $config;
        $newsletterData['details'] = $contentExtractor->getDetailsFromData($inputData);
        $newsletterData['blocks'] = $contentExtractor->getBlocksFromData($inputData);

        $contentGenerator = new ContentGenerator();
        $newsletterFileNameSource = $newsletterData['details']['utm_campaign'];

        $newsletterFile = $contentGenerator->getNewsletterFilename($newsletterFileNameSource, $config);
        $newsletterPath = dirname($newsletterFile) . '/';
        $newsletterData['blocks'] = $contentGenerator->fillBlocksData(
            $newsletterData, $newsletterPath
        );

        $this->createDirectories(array($newsletterPath . $config['original_images_path']));

        $contentGenerator->execute(
            $config['newsletter_template'], $newsletterData, $newsletterFile
        );

        $contentGenerator->saveNewData(
            $config['newsletter_data_template'],
            $newsletterData,
            $newsletterPath . $config['newsletter_data_file']
        );
    }

}

?>