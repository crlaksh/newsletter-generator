<?php

require_once 'vendor/autoload.php';
use Isha\NewsletterGenerator\Util\Config as Config;
use Isha\NewsletterGenerator\Util\Helper as Helper;
use Isha\NewsletterGenerator\Service\ContentExtractor as ContentExtractor;
use Isha\NewsletterGenerator\Service\ContentGenerator as ContentGenerator;
// $imgsrc = "http://tamilblog.isha.ws/wp-content/uploads/2015/03/thavaru-seibavargalum-nandraga-vazhvathaen-1050x700.jpg";
// $imgsrc = "/home/crlakshmi/Downloads/moondravathu-kannal-parppathu-eppadi-1281×720.jpg";
// $imgout = '/home/crlakshmi/Downloads/moondravathu-kannal-parppathu-eppadi.jpg';
// $img = imagecreatefromjpeg($imgsrc);
// $img = imagecrop($img, array('x' =>140 , 'y' => 0, 'width' => 1000, 'height'=> 720));
// imagejpeg($img, $imgout, 100);

// Helper::resizeImage(
//     $imgout,
//     $imgout,
//     '325',
//     '239',
//     75,
//     TRUE
// );
// // exit;
// $img = imagecreatefromjpeg($imgout);

// $font = '/home/crlakshmi/Downloads/league-gothic-regular.ttf';
// $text = 'YouTube';
// $white = imagecolorallocate($img, 255, 255, 255);
// $red = imagecolorallocate($img, 255, 0, 255);
// $grey = imagecolorallocate($img, 128, 128, 128);
// $black = imagecolorallocate($img, 0, 0, 0);
// $x = 0;
// $y = 0;
// $w = imagesx($img) - 1;
// $h = imagesy($img) - 1;

// for ($i=0; $i < 25; $i++) {
//     imageline($img, $x, $y+$h-$i, $x+$w ,$y+$h-$i, $grey);
// }
// imagettftext($img, 12, 0, $w - 60, $h - 5, $white, $font, $text);
// imagejpeg($img, $imgout, 100);
// imagedestroy($img);

// exit;
// $png = imagecreatefrompng('/home/crlakshmi/Downloads/youtube.png');
// $jpeg = imagecreatefromjpeg($imgout);

// list($width, $height) = getimagesize($imgout);
// list($newwidth, $newheight) = getimagesize('/home/crlakshmi/Downloads/youtube.png');
// $out = imagecreatetruecolor($width, $height);
// imagecopyresampled($out, $jpeg, 0, 0, 0, 0, $width, $height, $width, $height);
// imagecopyresampled($out, $png, 138, 100, 0, 0, $newwidth, $newheight, $newwidth, $newheight);
// imagejpeg($out, $imgout, 100);
// print_r($width);echo "\n";
// print_r($height);echo "\n";
// print_r($newwidth);echo "\n";
// print_r($newheight);echo "\n";
// Helper::resizeImage(
//     $imgout,
//     $imgout,
//     '325',
//     '239',
//     75,
//     TRUE
// );
// // exit;
// // $imgsrc = "http://tamilblog.isha.ws/wp-content/uploads/2015/03/thavaru-seibavargalum-nandraga-vazhvathaen-1050x700.jpg";
// $imgsrc = "/home/crlakshmi/Downloads/thavaru-seibavargalum-nandraga-vazhvathaen-1050x700.jpg";
// $imgout = '/home/crlakshmi/Downloads/thavaru-seibavargalum-nandraga-vazhvathaen.jpg';
// $img = imagecreatefromjpeg($imgsrc);
// $img = imagecrop($img, array('x' =>120 , 'y' => 0, 'width' => 800, 'height'=> 700));
// imagejpeg($img, $imgout, 100);
// imagedestroy($img);

// Helper::resizeImage(
//     $imgout,
//     $imgout,
//     '325',
//     '288',
//     100,
//     TRUE
// );

// $png = imagecreatefrompng('/home/crlakshmi/Downloads/youtube.png');
// $jpeg = imagecreatefromjpeg('/home/crlakshmi/Downloads/moondravathu-kannal-parppathu-eppadi.jpg');

// list($width, $height) = getimagesize('/home/crlakshmi/Downloads/moondravathu-kannal-parppathu-eppadi.jpg');
// list($newwidth, $newheight) = getimagesize('/home/crlakshmi/Downloads/youtube.png');
// $out = imagecreatetruecolor($width, $height);
// imagecopyresampled($out, $jpeg, 0, 0, 0, 0, $width, $height, $width, $height);
// imagecopyresampled($out, $png, 138, 100, 0, 0, $newwidth, $newheight, $newwidth, $newheight);
// imagejpeg($out, '/home/crlakshmi/Downloads/moondravathu-kannal-parppathu-eppadi-out.jpg', 100);
// print_r($width);echo "\n";
// print_r($height);echo "\n";
// print_r($newwidth);echo "\n";
// print_r($newheight);echo "\n";
// Helper::resizeImage(
//     '/home/crlakshmi/Downloads/moondravathu-kannal-parppathu-eppadi-out.jpg',
//     '/home/crlakshmi/Downloads/moondravathu-kannal-parppathu-eppadi-out.jpg',
//     '325',
//     '239',
//     75,
//     TRUE
// );
// exit;

$config = Config::getConfig();
$userConfig = Config::getUserConfig();

$newsletterPath = $config['data_path'] . $userConfig['newsletter_title'] . "_" . $userConfig['date'] . "_" . $userConfig['year'] . "/";
$newsletterFilename = $newsletterPath . $userConfig['newsletter_title'] . "_" . $userConfig['date'] . "_" . $userConfig['year'] . ".html";

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
            $data = ContentExtractor::getBlogContent(
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
            $userConfig['blocks'][$key]['data']['media_type'] = $data['media_type'];
            $userConfig['blocks'][$key]['data']['image'] = $data['image'];
            break;

        case in_array($templateType, $doubleBlogData):
            $data = ContentExtractor::getBlogContent(
                $userConfig['blocks'][$key]['data']['left'],
                $newsletterPath,
                $userConfig['blocks'][$key]['data']['left']['url'],
                $userConfig['blocks'][$key]['data']['left']['media']['image'],
                $config['images_path'],
                $blogData['title_element'],
                $blogData['image_element'],
                $blogData['video_element'],
                $blogData['blurb_element'],
                $blogData['video_image_link'],
                $blogData['video_gdata_link']
            );
            $userConfig['blocks'][$key]['data']['left']['title'] = $data['title'];
            $userConfig['blocks'][$key]['data']['left']['blurb'] = $data['blurb'];
            $userConfig['blocks'][$key]['data']['left']['duration'] = $data['duration'];
            $userConfig['blocks'][$key]['data']['left']['media_type'] = $data['media_type'];
            $userConfig['blocks'][$key]['data']['left']['image'] = $data['image'];
            $data = ContentExtractor::getBlogContent(
                $userConfig['blocks'][$key]['data']['right'],
                $newsletterPath,
                $userConfig['blocks'][$key]['data']['right']['url'],
                $userConfig['blocks'][$key]['data']['right']['media']['image'],
                $config['images_path'],
                $blogData['title_element'],
                $blogData['image_element'],
                $blogData['video_element'],
                $blogData['blurb_element'],
                $blogData['video_image_link'],
                $blogData['video_gdata_link']
            );
            $userConfig['blocks'][$key]['data']['right']['title'] = $data['title'];
            $userConfig['blocks'][$key]['data']['right']['blurb'] = $data['blurb'];
            $userConfig['blocks'][$key]['data']['right']['duration'] = $data['duration'];
            $userConfig['blocks'][$key]['data']['right']['media_type'] = $data['media_type'];
            $userConfig['blocks'][$key]['data']['right']['image'] = $data['image'];
            break;
    }
}

ContentGenerator::execute($config['newsletter_template'], $userConfig, $newsletterFilename);

?>