<?php

namespace Tools\NewsletterGenerator\Util;
use \PHPExcelReader\SpreadsheetReader as Reader;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Exception;
use DOMDocument;
use DOMXpath;
use Tidy;

class Helper {

    function getConfig() {
        $configData = file_get_contents(__DIR__ . "/../Resource/config/config.json");
        $config = json_decode($configData, TRUE);
        return $config;
    }

    function downloadWebPage($url) {
        $ch = curl_init(); // Create a URL handle.
        curl_setopt($ch, CURLOPT_URL, $url); // Tell curl what URL we want.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // We want to return the web page from curl_exec, not print it.
        curl_setopt($ch, CURLOPT_HEADER, 0); // Set this if you don't want the content header.
        $content = curl_exec($ch); // Download the HTML from the URL.
        if (curl_errno($ch)) { // Check to see if there were errors.
            echo curl_error($ch); // We have an error. Show the error message.
            curl_close($ch); // Close the curl handle.
        }
        else { // No error. Return the page content.
            curl_close($ch); // Close the curl handle.
            return $content;
        }
    }

    function getHtml($url) {
        $content = $this->downloadWebPage($url);
        $doc = new DOMDocument();
        @$doc->loadHTML($content);
        $xpath = new DOMXpath($doc);
        return $xpath;
    }

    function downloadImage($src, $dest) {
        $imageData = $this->downloadWebPage($src);
        file_put_contents($dest, $imageData);
    }

    function resizeImage(
        $originalImage,
        $modifiedImage,
        $new_w = FALSE,
        $new_h = FALSE,
        $resolution = 75,
        $noscale = FALSE
    ) {
        $system = explode(".", $originalImage);
        if (preg_match("/jpg|jpeg/", $system[1])) {
            $src_img = imagecreatefromjpeg($originalImage);
        }
        if (preg_match("/png|PNG/", $system[1])) {
            $src_img = imagecreatefrompng($originalImage);
        }
        if (preg_match("/gif|GIF/", $system[1])) {
            $src_img = imagecreatefromgif($originalImage);
        }
        $old_x = imageSX($src_img);
        $old_y = imageSY($src_img);
        if (!$new_w) {
            $new_w = $old_x;
        }
        if (!$new_h) {
            $new_h = $old_y;
        }
        if ($noscale) {
            $thumb_w = $new_w;
            $thumb_h = $new_h;
        }
        else {
            if ($old_x > $old_y) {
                $thumb_w = $new_w;
                $thumb_h = $old_y * ($new_h / $old_x);
            }
            if ($old_x < $old_y) {
                $thumb_w = $old_x * ($new_w / $old_y);
                $thumb_h = $new_h;
            }
            if ($old_x  ==  $old_y) {
                $thumb_w = $new_w;
                $thumb_h = $new_h;
            }
        }
        $dst_img = ImageCreateTrueColor($thumb_w, $thumb_h);
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
        imageinterlace($dst_img, true);
        if (preg_match("/png|PNG/", $system[1])) {
            imagepng($dst_img, $modifiedImage, $resolution * (9 / 100));
        }
        else if (preg_match("/gif|GIF/", $system[1])) {
            imagegif($dst_img, $modifiedImage, $resolution);
        }
        else {
            imagejpeg($dst_img, $modifiedImage, $resolution);
        }
        imagedestroy($dst_img);
        imagedestroy($src_img);
    }

    function getExcelData($dataFile) {
        if (!$dataFile) {
            echo "\nData file not specified!!\n\n";
            exit;
        }
        if (!is_file($dataFile)) {
            echo "\nData file '" . $dataFile . "' not found!!\n\n";
            exit;
        }
        $excelData = new Reader($dataFile, FALSE, "UTF-8");
        $sheets = array();
        // iterate sheets
        foreach ($excelData->boundsheets as $sheetIndex => $boundsheet) {
            $rowCount = $excelData->rowcount($sheetIndex);
            $columnCount = $excelData->colcount($sheetIndex);
            $sheets[$sheetIndex]['sheetname'] = $boundsheet['name'];
            $sheets[$sheetIndex]['rows'] = array();
            for ($rowIndex = 1; $rowIndex <= $rowCount; $rowIndex++) {
                $row = array();
                for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
                    $cellValue = $excelData->val($rowIndex, $columnIndex, $sheetIndex);
                    array_push($row, $cellValue);
                }
                array_push($sheets[$sheetIndex]['rows'], $row);
            }
        }
        return $sheets;
    }

    function embedImage(
        $image, $outFile, $embedImage
    ) {
        $png = imagecreatefrompng($embedImage);
        $jpeg = imagecreatefromjpeg($image);
        list($width, $height) = getimagesize($image);
        list($newwidth, $newheight) = getimagesize($embedImage);
        $x = ($width / 2) - ($newwidth / 2);
        $y = ($height / 2) - ($newheight / 2) - 1;
        $outFile = imagecreatetruecolor($width, $height);
        imagecopyresampled($outFile, $jpeg, 0, 0, 0, 0, $width, $height, $width, $height);
        imagecopyresampled($outFile, $png, $x, $y, 0, 0, $newwidth, $newheight, $newwidth, $newheight);
        imageinterlace($outFile, true);
        imagejpeg($outFile, $image, 80);
        imagedestroy($outFile);
    }

    function drawBottomBorder($img, $color, $borderColor, $width = 1, $height = 1) {
        $x2 = ImageSX($img) - 5;
        $y2 = ImageSY($img) - 4;
        $x1 = $x2 - $width;
        $y1 = $y2;

        for ($i = 0; $i < $height; $i++) {
            $y1--;
            imagerectangle($img, $x1, $y1, $x2, $y1, $color);
        }
        imagerectangle($img, $x1, $y1 - 1, $x2, $y2, $borderColor);
    }

    function embedText(
        $image, $outFile, $embedText, $font, $fontSize = 9, $x = 0, $y = 0
    ) {
        $jpeg = imagecreatefromjpeg($image);
        $color = imagecolorallocate($jpeg,  30, 30, 30);
        $borderColor = imagecolorallocate($jpeg,  60, 60, 60);
        $textColor = imagecolorallocate($jpeg, 220, 220, 220);
        $this->drawBottomBorder($jpeg, $color, $borderColor, 45, 18);
        $x = ImageSX($jpeg) - 45;
        $y = ImageSY($jpeg) - 9;
        imagettftext($jpeg, $fontSize, 0, $x, $y, $textColor, $font, $embedText);
        imagejpeg($jpeg, $outFile, 100);
        imagedestroy($jpeg);
    }

    function cropImage(
        $imageSrc, $outFile,
        $x, $y, $width, $height
    ) {
        list($originalWidth, $originalHeight) = getimagesize($imageSrc);
        $x = $x !== "" ? $x : 0;
        $y = $y !== "" ? $y : 0;
        $width = $width !== "" ? $originalWidth - $width - $x : $originalWidth - $x;
        $height = $height !== "" ? $originalHeight - $height - $y : $originalHeight - $y;
        $image = imagecreatefromjpeg($imageSrc);
        $image = imagecrop($image, array(
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height'=> $height
        ));
        imageinterlace($image, true);
        imagejpeg($image, $outFile, 80);
        imagedestroy($image);
    }

    function createDirectories($dirs) {
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, TRUE);
            }
        }
    }

    function getElementTextContent($html, $xpath) {
        $content = "";
        $element = $html->query($xpath);
        if ($element && $element->item(0)) {
            $content = $element->item(0)->textContent;
            $content = $content;
        }
        return $content;
    }

    function getElementAttribute($html, $xpath, $attr) {
        $value = "";
        $element = $html->query($xpath);
        if ($element && $element->item(0)) {
            $value = $element->item(0)->getAttribute($attr);
            $value = $value;
        }
        return $value;
    }

    function renderTemplate($template, $data, $outputFile = FALSE) {
        $loader = new Twig_Loader_Filesystem(dirname($template));
        $twig = new Twig_Environment($loader);
        $content = $twig->render(basename($template), $data);
        if ($outputFile) {
            file_put_contents($outputFile, $content);
        }
        return $content;
    }

    function tidyHTML($html) {
        $tidy = new Tidy();
        $options = array(
           'indent' => true,
           'indent-spaces' => 4,
           'output-xhtml' => true,
           'wrap' => 0,
           'vertical-space' => true
        );
        $tidy->parseString($html, $options, 'utf8');
        $tidy->cleanRepair();
        return $tidy->value;
    }

}

?>