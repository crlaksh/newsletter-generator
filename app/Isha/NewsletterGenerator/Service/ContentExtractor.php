<?php

namespace Isha\NewsletterGenerator\Service;
use Isha\NewsletterGenerator\Util\Helper as Helper;
use DOMDocument;
use DOMXpath;

class ContentExtractor extends Helper {

    public static function getBlogContent($newsletterPath, $url, $image, $imagePath, $titleElement, $imageElement, $videoElement, $blurbElement, $videoImagePathTmpl, $videoGDataPathTmpl) {
        $newsletterImagePath = $newsletterPath . $imagePath;
        $imageFileName = preg_replace("/http:\/\/tamilblog.ishafoundation.org\/(.*)\//", "$1", $url);
        $newsletterImageFileName = $newsletterImagePath . $imageFileName;
        $content = Helper::downloadWebPage($url);
        $doc = new DOMDocument();
        $doc->loadHTML($content);
        $xpath = new DOMXpath($doc);
        $title = $xpath->query($titleElement)->item(0)->textContent;
        $blurb = $xpath->query($blurbElement)->item(0)->textContent;
        $title = utf8_decode($title);
        $blurb = utf8_decode($blurb);
        $minutes = FALSE;

        if ($image['file'] === "none") {
            $imageNode = $xpath->query($imageElement);
            if ($imageNode && $imageNode->item(0)) {
                $imagesrc = $imageNode->item(0)->getAttribute('src');
            }
            else {
                $videoNode = $xpath->query($videoElement);
                if ($videoNode) {
                    $videoSrc = $videoNode->item(0)->getAttribute('src');
                    $videoId = preg_replace("/http\:\/\/www\.youtube\.com\/embed\/(.*)\?feature\=oembed/", "$1", $videoSrc);
                    $imagesrc = sprintf($videoImagePathTmpl, $videoId);
                    $gdataPath = sprintf($videoGDataPathTmpl, $videoId);
                    $gdataString = Helper::downloadWebPage($videoGDataPathTmpl);
                    $gdata = json_decode($gdataString);
                    $seconds = $gdata['entry']['media\$group']['yt\$duration']['seconds'];
                    $minutes = round($seconds / 60, 2);
                }
            }
            $imageFileExtension =  "." . pathinfo($imagesrc, PATHINFO_EXTENSION);
            $newsletterImageFile = $newsletterImageFileName . $imageFileExtension;
            file_put_contents($newsletterImageFile, Helper::downloadWebPage($imagesrc));
        }
        else {
            copy($image['file'], $newsletterImageFile);
        }

        Helper::resizeImage($newsletterImageFile, $newsletterImageFile, $image['width'], $image['height'], $image['resolution'], TRUE);

        $blogInfo = array(
            "url" => $url,
            "image" => $imageFileName . $imageFileExtension,
            "title" => $title,
            "blurb" => $blurb,
            "time" => $minutes
        );

        return $blogInfo;
    }

}

?>