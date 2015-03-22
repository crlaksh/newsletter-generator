<?php

require_once 'vendor/autoload.php';
use Isha\NewsletterGenerator\Util\Config as Config;
use Isha\NewsletterGenerator\Util\Helper as Helper;
use Isha\NewsletterGenerator\Service\ContentExtractor as ContentExtractor;
use Isha\NewsletterGenerator\Service\ContentGenerator as ContentGenerator;

$args = Helper::getCommandLineArguments($argv);
$dataFile = isset($args['data']) ? $args['data'] : FALSE;

$contentExtractor = new ContentExtractor();
if ($dataFile) {
    $data = Helper::getExcelData($dataFile);
    $userConfig = array();
    $userConfig['details'] = $contentExtractor->getDetailsFromExcelData($data);
    $userConfig['blocks'] = $contentExtractor->getBlocksFromExcelData($data);
}
else {
    $userConfig = Config::getUserConfig();
}

$config = Config::getConfig();
$newsletterDate = str_replace(' ', '_', $userConfig['details']['date']);
$newsletterTitle = str_replace(' ', '_', $userConfig['details']['newsletter_title']) . "_" . $newsletterDate;
$newsletterPath = $config['data_path'] . $newsletterTitle . "/";
$newsletterFilename = $newsletterPath . $newsletterTitle . ".html";

if (!is_dir($newsletterPath . $config['images_path'])) {
    mkdir($newsletterPath . $config['images_path'], 0777, TRUE);
}

$blogData = $config['blog_data'];
$blocks = $userConfig['blocks'];
$singleBlogData = array('single_blog', 'split_blog', 'split_blog_mirror');
$doubleBlogData = array('double_blog');

foreach ($blocks as $key => $block) {
    $templateType = $userConfig['blocks'][$key]['type'];
    $userConfig['blocks'][$key]['type'] = $templateType . '.twig';
    switch (TRUE) {
        case in_array($templateType, $singleBlogData):
            $data = $contentExtractor->getBlogContent(
                $userConfig['blocks'][$key]['data'],
                $newsletterPath,
                $userConfig['blocks'][$key]['data']['url'],
                $userConfig['blocks'][$key]['data']['media']['image'],
                $config['images_path'],
                $blogData['title_element'],
                $blogData['image_element'],
                $blogData['video_element'],
                $blogData['blurb_element'],
                $blogData['video_image_link'],
                $blogData['video_gdata_link']
            );
            $userConfig['blocks'][$key]['data']['title'] = $data['title'];
            $userConfig['blocks'][$key]['data']['blurb'] = $data['blurb'];
            $userConfig['blocks'][$key]['data']['duration'] = $data['duration'];
            $userConfig['blocks'][$key]['data']['media']['type'] = $data['media_type'];
            $userConfig['blocks'][$key]['data']['media']['image']['url'] = $data['image'];
            break;

        case in_array($templateType, $doubleBlogData):
            $data = $contentExtractor->getBlogContent(
                $userConfig['blocks'][$key]['data'][0],
                $newsletterPath,
                $userConfig['blocks'][$key]['data'][0]['url'],
                $userConfig['blocks'][$key]['data'][0]['media']['image'],
                $config['images_path'],
                $blogData['title_element'],
                $blogData['image_element'],
                $blogData['video_element'],
                $blogData['blurb_element'],
                $blogData['video_image_link'],
                $blogData['video_gdata_link']
            );
            $userConfig['blocks'][$key]['data'][0]['title'] = $data['title'];
            $userConfig['blocks'][$key]['data'][0]['blurb'] = $data['blurb'];
            $userConfig['blocks'][$key]['data'][0]['duration'] = $data['duration'];
            $userConfig['blocks'][$key]['data'][0]['media']['type'] = $data['media_type'];
            $userConfig['blocks'][$key]['data'][0]['media']['image']['url'] = $data['image'];
            $data = $contentExtractor->getBlogContent(
                $userConfig['blocks'][$key]['data'][1],
                $newsletterPath,
                $userConfig['blocks'][$key]['data'][1]['url'],
                $userConfig['blocks'][$key]['data'][1]['media']['image'],
                $config['images_path'],
                $blogData['title_element'],
                $blogData['image_element'],
                $blogData['video_element'],
                $blogData['blurb_element'],
                $blogData['video_image_link'],
                $blogData['video_gdata_link']
            );
            $userConfig['blocks'][$key]['data'][1]['title'] = $data['title'];
            $userConfig['blocks'][$key]['data'][1]['blurb'] = $data['blurb'];
            $userConfig['blocks'][$key]['data'][1]['duration'] = $data['duration'];
            $userConfig['blocks'][$key]['data'][1]['media']['type'] = $data['media_type'];
            $userConfig['blocks'][$key]['data'][1]['media']['image']['url'] = $data['image'];
            break;
    }
}

ContentGenerator::execute($config['newsletter_template'], $userConfig, $newsletterFilename);

?>