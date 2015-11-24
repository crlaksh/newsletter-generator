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
        $newsletterDate = $newsletterData['blocks'][0]['data'][0]['media']['duration'];
        $newsletterDateSplit = explode(' ', $newsletterDate);
        $newsletterMonth = $newsletterDateSplit[0];
        $newsletterDay = explode(',', $newsletterDateSplit[1])[0];
        $newsletterYear = explode(', ', $newsletterDate)[1];
        $newsletterDayEndingNumber = substr($newsletterDay, -1);

        switch ($newsletterDayEndingNumber) {
             case '1':
                 $newsletterDaySuffix = 'st';
                 break;
             case '2':
                 $newsletterDaySuffix = 'nd';
                 break;
             case '3':
                 $newsletterDaySuffix = 'rd';
             
             default:
                 $newsletterDaySuffix = 'th';
                 break;
        }
        if ($newsletterMonth === '11') {
            $newsletterDaySuffix = 'th';
        }
        $newsletterDateEng = $newsletterDay . $newsletterDaySuffix . "_" . $newsletterData['config']['month_locale'][$newsletterMonth] . "_" . $newsletterYear;
        $newsletterFileNameSource = $newsletterData['config']['utm_campaign'] . "_" . $newsletterDateEng;
        $newsletterData['config']['utm_campaign'] = $newsletterFileNameSource;

        $newsletterFile = $contentGenerator->getNewsletterFilename($newsletterFileNameSource, $config);
        $newsletterPath = dirname($newsletterFile) . '/';

        $this->createDirectories(array($newsletterPath . $config['original_images_path']));
        $newsletterData['blocks'] = $contentGenerator->fillBlocksData(
            $newsletterData, $newsletterPath
        );


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