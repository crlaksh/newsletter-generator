<?php

namespace Isha\NewsletterGenerator\Util;
use \PHPExcelReader\SpreadsheetReader as Reader;
use Exception;
use DOMDocument;
use DOMXpath;

class Helper {

    public static function downloadWebPage($url) {
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

    public static function getHtml($url) {
        $content = Self::downloadWebPage($url);
        $doc = new DOMDocument();
        @$doc->loadHTML($content);
        $xpath = new DOMXpath($doc);
        return $xpath;
    }

    public static function downloadImage($src, $dest) {
        // if ()
        $imageData = Self::downloadWebPage($src);
        file_put_contents($dest, $imageData);
    }

    public static function resizeImage(
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
        if (preg_match("/png|PNG/", $system[1])) {
            imagepng($dst_img, $modifiedImage, $resolution);
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

    public static function getCommandLineArgument($rawInput, $dataType) {
        $args = array_splice($rawInput, 1);
        $values = array();
        foreach ($args as $key => $arg) {
            $arg = preg_split("/--(.*)=(.*)/", $arg, -1, PREG_SPLIT_DELIM_CAPTURE);
            if (count($arg) === 4) {
                $key = $arg[1];
                $value = $arg[2];
                if (isset($values[$key])) {
                    if (is_array($values[$key])) {
                        array_push($values[$key], $value);
                    }
                    else {
                        $values[$key] = array($values[$key], $value);
                    }
                }
                else {
                    $values[$key] = $value;
                }
            }
            else if (preg_match("/--(.*)/", $arg[0]) === 1) {
                $arg = str_replace("--", "", $arg[0]);
                $values[$arg] = TRUE;
            }
        }
        $data = isset($values['data']) ? $values['data'] : FALSE;
        return $data;
    }

    public static function getExcelData($dataFile) {
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

    public static function embedImage(
        $image, $outFile, $embedImage
    ) {
        $png = imagecreatefrompng($embedImage);
        $jpeg = imagecreatefromjpeg($image);
        list($width, $height) = getimagesize($image);
        list($newwidth, $newheight) = getimagesize($embedImage);
        $x = ($width / 2) - ($newwidth / 2);
        $y = ($height / 2) - ($newheight / 2);
        $outFile = imagecreatetruecolor($width, $height);
        imagecopyresampled($outFile, $jpeg, 0, 0, 0, 0, $width, $height, $width, $height);
        imagecopyresampled($outFile, $png, $x, $y, 0, 0, $newwidth, $newheight, $newwidth, $newheight);
        imagejpeg($outFile, $image, 80);
        imagedestroy($outFile);
    }

    public static function cropImage(
        $imageSrc, $outFile,
        $x, $y, $width, $height
    ) {
        list($originalWidth, $originalHeight) = getimagesize($imageSrc);
        $x = $x !== "" ? $x : 0;
        $y = $y !== "" ? $y : 0;
        $width = $width !== "" ? $width : $originalWidth;
        $height = $height !== "" ? $height : $originalHeight;
        $image = imagecreatefromjpeg($imageSrc);
        $image = imagecrop($image, array(
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height'=> $height
        ));
        imagejpeg($image, $outFile, 80);
        imagedestroy($image);
    }

    public static function createDirectories($dirs) {
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, TRUE);
            }
        }
    }

    public static function getElementTextContent($html, $xpath) {
        $content = "";
        $element = $html->query($xpath);
        if ($element && $element->item(0)) {
            $content = $element->item(0)->textContent;
            $content = utf8_decode($content);
        }
        return $content;
    }

    public static function getElementAttribute($html, $xpath, $attr) {
        $value = "";
        $element = $html->query($xpath);
        if ($element && $element->item(0)) {
            $value = $element->item(0)->getAttribute($attr);
            $value = utf8_decode($value);
        }
        return $value;
    }

}

?>