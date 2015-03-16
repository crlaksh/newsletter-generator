<?php

namespace Isha\NewsletterGenerator\Util;

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

    public static function resizeImage($originalImage, $modifiedImage, $new_w, $new_h, $resolution = 75, $noscale = FALSE) {
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

}

?>