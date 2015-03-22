<?php

namespace Isha\NewsletterGenerator\Service;
use Isha\NewsletterGenerator\Util\Helper as Helper;
use DOMDocument;
use DOMXpath;

class ContentExtractor extends Helper {

    public function getBlogContent(
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
                $image = $imagePath . $imageFileName . $imageFileExtension;
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

    public function getDetailsFromExcelData($data) {
        $details = array();
        foreach ($data as $sheetdata) {
            if (strtolower($sheetdata['sheetname']) === 'details') {
                array_shift($sheetdata['rows']); // Removing Title Row
                $rows = $sheetdata['rows'];
                foreach ($rows as $row) {
                    $key = implode(
                        '_', explode(' ', strtolower($row[0]))
                    );
                    $value = $row[1];
                    $details[$key] = $value;
                }
            }
        }
        $this->validateExcelDetailsData($details);
        return $details;
    }

    public function getBlocksFromExcelData($data) {
        $blocks = array();
        $block = array();
        foreach ($data as $value) {
            if (strtolower($value['sheetname']) === 'blocks') {
                $rows = $value['rows'];
                array_shift($rows); // Removing Title Row
                $rowCount = count($rows);
                for ($key = 0; $key < $rowCount; $key++) {
                    $row = $rows[$key];
                    if ($row[0] === "double_blog") {
                        $block = $this->getDoubleBlock($row, $rows[++$key]);
                    }
                    else if ($row[0] !== "") {
                        $block = $this->getBlock($row);
                    }
                    $this->validateExcelBlockData($block);
                    array_push($blocks, $block);
                }
            }
        }
        if (!isset($blocks) || count($blocks) === 0) {
            echo "\nBlocks not found\n\n";
            exit;
        }
        return $blocks;
    }

    function getBlock($row) {
        $block = [
            "type" => $row[0],
            "data" => [
                "title" => $row[1],
                "blurb" => $row[2],
                "url" => $row[3],
                "media" => [
                    "type" => $row[4],
                    "duration" => $row[5],
                    "image" => [
                        "url" => $row[6],
                        "width" => $row[7],
                        "height" => $row[8],
                        "resolution" => $row[9],
                        "crop_x" => $row[10],
                        "crop_y" => $row[11],
                        "crop_width" => $row[12],
                        "crop_height" => $row[13]
                    ]
                ]
            ]
        ];
        return $block;
    }

    function getDoubleBlock($row1, $row2) {
        $block = [
            "type" => $row1[0],
            "data" => [
                [
                    "title" => $row1[1],
                    "blurb" => $row1[2],
                    "url" => $row1[3],
                    "media" => [
                        "type" => $row1[4],
                        "duration" => $row1[5],
                        "image" => [
                            "url" => $row1[6],
                            "width" => $row1[7],
                            "height" => $row1[8],
                            "resolution" => $row1[9],
                            "crop_x" => $row1[10],
                            "crop_y" => $row1[11],
                            "crop_width" => $row1[12],
                            "crop_height" => $row1[13]
                        ]
                    ]
                ],
                [
                    "title" => $row2[1],
                    "blurb" => $row2[2],
                    "url" => $row2[3],
                    "media" => [
                        "type" => $row2[4],
                        "duration" => $row2[5],
                        "image" => [
                            "url" => $row2[6],
                            "width" => $row2[7],
                            "height" => $row2[8],
                            "resolution" => $row2[9],
                            "crop_x" => $row2[10],
                            "crop_y" => $row2[11],
                            "crop_width" => $row2[12],
                            "crop_height" => $row2[13]
                        ]
                    ]
                ]
            ]
        ];
        return $block;
    }

    function validateExcelDetailsData($details) {
        if (!isset($details) || count($details) === 0) {
            echo "\nDetails not found\n\n";
            exit;
        }
        if (!isset($details['newsletter_title'])) {
            echo "\nNewsletter Title not found in Details\n\n";
            exit;
        }
        if (!isset($details['date'])) {
            echo "\nDate not found in Details\n\n";
            exit;
        }
        if (!isset($details['display_date'])) {
            echo "\nDisplay Date not found in Details\n\n";
            exit;
        }
    }

    function validateExcelBlockData($block) {
        $blockType = $block['type'];
        $singleBlogs = array('single_blog', 'split_blog', 'split_blog_mirror', 'quote', 'banner');
        $doubleBlogs = array('double_blog');
        if ($blockType === "") {
            echo "\nBlock type missing for a block\n\n";
            exit;
        }
        switch (TRUE) {
            case in_array($blockType, $singleBlogs):
                if ($block['data']['url'] === "") {
                    echo "\nURL missing for a block\n\n";
                    exit;
                }
                break;
            case in_array($blockType, $doubleBlogs):
                if (
                    !isset($block['data'][0]['url']) ||
                    $block['data'][0]['url'] === "" ||
                    !isset($block['data'][1]['url']) ||
                    $block['data'][1]['url'] === ""
                ) {
                    echo "\nURL missing for a block\n\n";
                    exit;
                }
                break;

            default:
                # code...
                break;
        }
    }

}

?>