<?php

namespace Isha\NewsletterGenerator\Service;
use Isha\NewsletterGenerator\Util\Helper as Helper;
use Twig_Loader_Filesystem;
use Twig_Environment;

class ContentGenerator extends Helper {

    public function execute($newsletterTemplate, $data, $outputFile) {
        $loader = new Twig_Loader_Filesystem(dirname($newsletterTemplate));
        $twig = new Twig_Environment($loader);
        $content = $twig->render(basename($newsletterTemplate), $data);
        file_put_contents($outputFile, $content);
    }

    public function getNewsletterFilename($details, $config) {
        $newsletterDate = str_replace(' ', '_', $details['date']);
        $newsletterName = str_replace(' ', '_', $details['newsletter_title']) . "_" . $newsletterDate;
        $dir = $config['data_path'] . $newsletterName . "/";
        $file = $dir . $newsletterName . ".html";
        return $file;
    }

    public function fillBlocksData($blocks, $newsletterPath, $config) {
        foreach ($blocks as $key => $block) {
            $blockData = $block['data'];
            $blockType = $block['type'];
            foreach ($blockData as $dataIndex => $data) {
                $blocks[$key]['data'][$dataIndex] = $this->fillBlockData($blockType, $data, $newsletterPath, $config);
            }
        }
        return $blocks;
    }

    public function fillBlockData($blockType, $data, $newsletterPath, $config) {
        if (
            (
                !isset($data['title']) ||
                !isset($data['blurb']) ||
                !isset($data['image']['url'])
            ) && in_array($blockType, $config['blogs'])
        ) {
            $url = $data['url'];
            $html = Helper::getHtml($url);
            $title = preg_replace("/http:\/\/tamilblog.ishafoundation.org\/(.*)\//", "$1", $url);
            $imageSrc = FALSE;
            if (!isset($data['title']) || $data['title'] === "") {
                $data['title'] = Helper::getElementTextContent($html, $config['blog_data']['title_element']);
            }
            if (!isset($data['blurb']) || $data['blurb'] === "") {
                $data['blurb'] = Helper::getElementTextContent($html, $config['blog_data']['blurb_element']);
            }
            if (!isset($data['media']['image']['url']) || $data['media']['image']['url'] === "") {
                $data['media']['type'] = 'image';
                $imageSrc = Helper::getElementAttribute($html, $config['blog_data']['image_element'], 'src');
            }
            $videoSrc = Helper::getElementAttribute($html, $config['blog_data']['video_element'], 'src');
            if (!isset($data['media']['image']['url']) || $data['media']['image']['url'] === "") {
                $data['media']['type'] = 'video';
                $videoId = $this->getVideoId($videoSrc);
                $imageSrc = $this->getVideoImage($videoId, $config['blog_data']['video_image_link']);
                $data['media']['duration'] = $this->getVideoDuration($videoId, $config['blog_data']['video_gdata_link']);
            }
            if ($imageSrc) {
                $imageFileExtension =  "." . pathinfo($imageSrc, PATHINFO_EXTENSION);
                $imageFileName = $title . $imageFileExtension;
                $imageNewsletterLink = $config['images_path'] . $imageFileName;
                $imageDownloadDest = $newsletterPath . $config['original_images_path'] . $imageFileName;
                $imageNewsletterDest = $newsletterPath . $config['images_path'] . $imageFileName;
                Helper::downloadImage($imageSrc, $imageDownloadDest);
                Helper::resizeImage(
                    $imageDownloadDest,
                    $imageNewsletterDest,
                    $data['media']['image']['width'],
                    $data['media']['image']['height'],
                    $data['media']['image']['resolution'],
                    TRUE
                );
                $data['media']['image']['url'] = $imageNewsletterLink;
            }
            if (
                (!isset($data['media']['duration']) ||
                $data['media']['duration'] === "") &&
                (
                    $data['media']['type'] === "video" ||
                    ($data['media']['type'] === "" && $videoSrc !== "")
                )
            ) {
                $data['media']['type'] = 'video';
                $videoId = $this->getVideoId($videoSrc);
                $data['media']['duration'] = $this->getVideoDuration($videoId, $config['blog_data']['video_gdata_link']);
            }
        }
        return $data;
    }

    public function getVideoId($videoSrc) {
        $videoId = preg_replace("/http\:\/\/www\.youtube\.com\/embed\/(.*)\?feature\=oembed/", "$1", $videoSrc);
        return $videoId;
    }

    public function getVideoImage($videoId, $videoImagePathTmpl) {
        $imageSrc = sprintf($videoImagePathTmpl, $videoId);
        return $imageSrc;
    }

    public function getVideoDuration($videoId, $videoGDataPathTmpl) {
        $gdataPath = sprintf($videoGDataPathTmpl, $videoId);
        $gdataString = Helper::downloadWebPage($gdataPath);
        $gdata = json_decode($gdataString, TRUE);
        $seconds = $gdata['entry']['media$group']['yt$duration']['seconds'];
        $duration = gmdate("H:i:s", $seconds);
        return $duration;
    }
}

?>