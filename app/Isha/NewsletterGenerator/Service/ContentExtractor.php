<?php

namespace Isha\NewsletterGenerator\Service;
use Isha\NewsletterGenerator\Util\Helper as Helper;
use DOMDocument;
use DOMXpath;

class ContentExtractor extends Helper {

    public static function getBlogContent(
            $data,
            $newsletterPath, $url, $image, $imagePath,
            $titleElement, $imageElement, $videoElement,
            $blurbElement, $videoImagePathTmpl, $videoGDataPathTmpl
        ) {
        $newsletterImagePath = $newsletterPath . $imagePath;
        $imageFileName = preg_replace("/http:\/\/tamilblog.ishafoundation.org\/(.*)\//", "$1", $url);
        $newsletterImageFileName = $newsletterImagePath . $imageFileName;
        $imagesrc = FALSE;
        $imageFileExtension = ".jpg";
        $mediaType = "image";
        $title = $data['title'];
        $blurb = $data['blurb'];
        $image = $data['media']['image']['url'];
        $minutes = $data['media']['duration'];

        if (!$title || !$blurb || !$image) {
            $content = Helper::downloadWebPage($url);
            $doc = new DOMDocument();
            $doc->loadHTML($content);
            $xpath = new DOMXpath($doc);
        }

        if (!$title) {
            $titleNode = $xpath->query($titleElement);
            if ($titleNode && $titleNode->item(0)) {
                $title = $titleNode->item(0)->textContent;
                $title = utf8_decode($title);
            }
        }
        if (!$blurb) {
            $blurbNode = $xpath->query($blurbElement);
            if ($blurbNode && $blurbNode->item(0)) {
                $blurb = $blurbNode->item(0)->textContent;
                $blurb = utf8_decode($blurb);
            }
        }
        if (!$image) {
            $imageNode = $xpath->query($imageElement);
            if ($imageNode && $imageNode->item(0)) {
                $imagesrc = $imageNode->item(0)->getAttribute('src');
            }
            else {
                $videoNode = $xpath->query($videoElement);
                if ($videoNode && $imageNode->item(0)) {
                    $mediaType = "video";
                    $videoSrc = $videoNode->item(0)->getAttribute('src');
                    $videoId = preg_replace("/http\:\/\/www\.youtube\.com\/embed\/(.*)\?feature\=oembed/", "$1", $videoSrc);
                    $imagesrc = sprintf($videoImagePathTmpl, $videoId);
                    if (!$minutes) {
                        $gdataPath = sprintf($videoGDataPathTmpl, $videoId);
                        $gdataString = Helper::downloadWebPage($videoGDataPathTmpl);
                        $gdata = json_decode($gdataString);
                        $seconds = $gdata['entry']['media\$group']['yt\$duration']['seconds'];
                        $minutes = round($seconds / 60, 2);
                    }
                }
            }
            if ($imagesrc) {
                $imageFileExtension =  "." . pathinfo($imagesrc, PATHINFO_EXTENSION);
                $newsletterImageFile = $newsletterImageFileName . $imageFileExtension;
                file_put_contents($newsletterImageFile, Helper::downloadWebPage($imagesrc));

                Helper::resizeImage(
                    $newsletterImageFile,
                    $newsletterImageFile,
                    $data['media']['image']['width'],
                    $data['media']['image']['height'],
                    $data['media']['image']['resolution'],
                    TRUE
                );
                $image = $imageFileName . $imageFileExtension;
            }
        }

        $blogInfo = array(
            "url" => $url,
            "media_type" => $mediaType,
            "image" => $image,
            "title" => $title,
            "blurb" => $blurb,
            "duration" => $minutes
        );

        return $blogInfo;
    }

    function embedPlayButton($playButtonFile, $img) {
        $png = imagecreatefrompng($playButtonFile);
        $jpeg = imagecreatefromjpeg($img);
        list($width, $height) = getimagesize($img);
        list($newwidth, $newheight) = getimagesize($playButtonFile);
        $out = imagecreatetruecolor($width, $height);
        imagecopyresampled($out, $jpeg, 0, 0, 0, 0, $width, $height, $width, $height);
        imagecopyresampled($out, $png, 138, 100, 0, 0, $newwidth, $newheight, $newwidth, $newheight);
        imagejpeg($out, $img, 100);
        Helper::resizeImage($img, $img, $width, $height, 75, TRUE);
    }

}

?>