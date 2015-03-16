<?php

namespace Isha\NewsletterGenerator\Util;

/**
* Gets config file and returns an array of config
*/
class Config {
    public static function getConfig() {
        $configData = file_get_contents(__DIR__ . "/../Resource/config/config.json");
        $config = json_decode($configData, TRUE);
        return $config;
    }
}

?>