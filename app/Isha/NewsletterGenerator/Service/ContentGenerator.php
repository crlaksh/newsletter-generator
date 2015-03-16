<?php

namespace Isha\NewsletterGenerator\Service;
use Isha\NewsletterGenerator\Util\Helper as Helper;
use Twig_Loader_Filesystem;
use Twig_Environment;

class ContentGenerator extends Helper {

    public static function execute($newsletterTemplate, $data, $outputFile) {
        $loader = new Twig_Loader_Filesystem(dirname($newsletterTemplate));
        $twig = new Twig_Environment($loader);
        $content = $twig->render(basename($newsletterTemplate), $data);
        file_put_contents($outputFile, $content);
    }

}

?>