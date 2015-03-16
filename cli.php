<?php

require_once 'vendor/autoload.php';
use Isha\NewsletterGenerator\Util\Config as Config;
use Isha\NewsletterGenerator\Util\Helper as Helper;
use Isha\NewsletterGenerator\Service\ContentExtractor as ContentExtractor;
use Isha\NewsletterGenerator\Service\ContentGenerator as ContentGenerator;

$config = Config::getConfig();

$newsletterPath = $config['data_path'] . $config['newsletter_title'] . "_" . $config['date'] . "_" . $config['year'] . "/";
$newsletterFilename = $newsletterPath . $config['newsletter_title'] . "_" . $config['date'] . "_" . $config['year'] . ".html";

mkdir($newsletterPath . $config['images_path'], 0777, TRUE);

$blogData = $config['blog_data'];
$blogs = $blogData['blogs'];

foreach ($blogs as $key => $blog) {
    $config['blog_data']['blogs'][$key] = ContentExtractor::getBlogContent(
        $newsletterPath, $config['blog_data']['blogs'][$key]['url'], $config['blog_data']['blogs'][$key]['image'],
        $config['images_path'], $blogData['title_element'], $blogData['image_element'], $blogData['video_element'],
        $blogData['blurb_element'], $blogData['video_image_link'], $blogData['video_gdata_link']
    );
}

ContentGenerator::execute($config['newsletter_template'], $config, $newsletterFilename);

?>