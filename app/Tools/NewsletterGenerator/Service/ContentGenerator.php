<?php

namespace Tools\NewsletterGenerator\Service;
use Tools\NewsletterGenerator\Util\Helper as Helper;

class ContentGenerator extends Helper {

    function execute($newsletterTemplate, $data, $outputFile) {
        $html = $this->renderTemplate($newsletterTemplate, $data);
        $html = $this->tidyHTML($html);
        file_put_contents($outputFile, $html);
        array_pop($data['blocks']);
        $html = $this->renderTemplate($newsletterTemplate, $data);
        $html = $this->tidyHTML($html);
        file_put_contents($outputFile . ".tn.html", $html);
    }

    function saveNewData($newsletterDataTemplate, $data, $outputFile) {
        $this->renderTemplate($newsletterDataTemplate, $data, $outputFile);
    }

    function getNewsletterFilename($fileName, $config) {
        $fileName = implode('_', explode(' ', $fileName));
        $dir = $config['data_path'] . $fileName . "/";
        $file = $dir . $fileName . ".html";
        return $file;
    }

    function fillBlocksData($newsletterData, $newsletterPath) {
        $config = $newsletterData['config'];
        $details = $newsletterData['details'];
        $blocks = $newsletterData['blocks'];
        foreach ($blocks as $key => $block) {
            $blockData = $block['data'];
            $blockType = $block['type'];
            foreach ($blockData as $dataIndex => $data) {
                echo "Processing: [" . $blockType . "] " . $data['url'] . "\n";
                $blocks[$key]['data'][$dataIndex] = $this->fillBlockData(
                    $blockType, $data, $newsletterPath, $config, $details
                );
            }
        }
        return $blocks;
    }

    function fillBlockData($blockType, $data, $newsletterPath, $config, $details) {
        $url = $data['url'];
        $title = preg_replace("/http:\/\/isha\.sadhguru\.org\/blog\/ta\/(.*)\//", "$1", $url);
        $imageSrc = FALSE;
        $imageNewsletterDest = FALSE;
        if ((
                ($data['title'] === "") ||
                ($data['blurb'] === "") ||
                ($data['media']['image']['url'] === "")
            ) && in_array($blockType, $config['blogs'])
        ) {
            $html = $this->getHtml($url);
            if ($data['title'] === "") {
                $data['title'] = $this->getElementTextContent($html, $config['blog_data']['title_element']);
            }
            if ($data['blurb'] === "") {
                $data['blurb'] = $this->getElementTextContent($html, $config['blog_data']['blurb_element']);
            }
            if ($data['media']['image']['url'] === "") {
                $imageSrc = $this->getElementAttribute($html, $config['blog_data']['image_element'], 'src');
                if ($imageSrc === "") {
                    $data['media']['type'] = 'video';
                    $videoSrc = $this->getElementAttribute($html, $config['blog_data']['video_element'], 'src');
                    $videoId = $this->getVideoId($videoSrc);
                    $imageSrc = $this->getVideoImage($videoId, $config['blog_data']['video_image_link']);
                    /*if ($data['media']['duration'] === "" && $videoId !== "") {
                        $data['media']['duration'] = $this->getVideoDuration($videoId, $config['blog_data']['video_gdata_link']);
                    }*/
                }
                $imageFileExtension =  "." . pathinfo($imageSrc, PATHINFO_EXTENSION);
                $imageFileName = $title . $imageFileExtension;
                $imageNewsletterLink = $config['images_path'] . $imageFileName;
                $imageNewsletterDest = $newsletterPath . $config['images_path'] . $imageFileName;
                $imageDownloadDest = $newsletterPath . $config['original_images_path'] . $imageFileName;
                $this->downloadImage($imageSrc, $imageDownloadDest);
                copy($imageDownloadDest, $imageNewsletterDest);
                $data['media']['image']['url'] = $imageNewsletterDest;
            }
        }
        if (
            $data['media']['image']['url'] !== "" &&
            preg_match('/^http/i', $data['media']['image']['url']) !== 1
        ) {
            $imageSrc = $data['media']['image']['url'];
            $imageFileExtension =  "." . pathinfo($imageSrc, PATHINFO_EXTENSION);
            $imageFileName = basename($imageSrc);
            $imageNewsletterLink = $config['images_path'] . $imageFileName;
            $imageNewsletterDest = $newsletterPath . $config['images_path'] . $imageFileName;
            $imageDownloadDest = $newsletterPath . $config['original_images_path'] . $imageFileName;
            if (is_dir(dirname($imageSrc)) === TRUE) {
                if (!is_file($imageSrc) === TRUE) {
                    $imageSrc = $imageDownloadDest;
                }
            }
            copy($imageDownloadDest, $imageNewsletterDest);
            $data['media']['image']['url'] = $imageNewsletterLink;
        }
        if ($imageNewsletterDest && in_array($blockType, $config['blogs'])) {
            if (
                $data['media']['image']['crop_x'] !== "" ||
                $data['media']['image']['crop_y'] !== "" ||
                $data['media']['image']['crop_width'] !== "" ||
                $data['media']['image']['crop_height'] !== ""
            ) {
                $this->cropImage(
                    $imageNewsletterDest,
                    $imageNewsletterDest,
                    $data['media']['image']['crop_x'],
                    $data['media']['image']['crop_y'],
                    $data['media']['image']['crop_width'],
                    $data['media']['image']['crop_height']
                );
            }
            $data['media']['image']['width'] = $data['media']['image']['width'] !== "" ? $data['media']['image']['width'] : $config[$blockType]['defaults']['image']['width'];
            $data['media']['image']['height'] = $data['media']['image']['height'] !== "" ? $data['media']['image']['height'] : $config[$blockType]['defaults']['image']['height'];
            $data['media']['image']['resolution'] = $data['media']['image']['resolution'] !== "" ? $data['media']['image']['resolution'] : $config[$blockType]['defaults']['image']['resolution'];
            $this->resizeImage(
                $imageNewsletterDest,
                $imageNewsletterDest,
                $data['media']['image']['width'],
                $data['media']['image']['height'],
                $data['media']['image']['resolution'],
                TRUE
            );
            if (
                $data['media']['type'] === 'video'
            ) {
                $this->embedImage(
                    $imageNewsletterDest,
                    $imageNewsletterDest,
                    $config['blog_data']['youtube_icon']
                );
                /*
                if ($data['media']['duration'] !== '') {
                    $this->embedText(
                        $imageNewsletterDest,
                        $imageNewsletterDest,
                        $data['media']['duration'],
                        $config['newsletter_time_font']
                    );
                }
                */
            }
        }
        return $data;
    }

    function getVideoId($videoSrc) {
        $videoId = preg_replace("/http.*\:\/\/www\.youtube\.com\/embed\/(.*)\?feature\=oembed/", "$1", $videoSrc);
        return $videoId;
    }

    function getVideoImage($videoId, $videoImagePathTmpl) {
        $imageSrc = sprintf($videoImagePathTmpl, $videoId);
        return $imageSrc;
    }

    function getVideoDuration($videoId, $videoGDataPathTmpl) {
        $gdataPath = sprintf($videoGDataPathTmpl, $videoId);
        $gdataString = $this->downloadWebPage($gdataPath);
        $gdata = json_decode($gdataString, TRUE);
        $seconds = $gdata['entry']['media$group']['yt$duration']['seconds'];
        $duration = gmdate("H:i:s", $seconds);
        return $duration;
    }
}

?>