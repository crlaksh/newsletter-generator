<?php

namespace Tools\NewsletterGenerator\Util;

class CLI {

    function __construct($args) {
        $this->args = $args;
    }

    function get($dataType) {
        $rawInput = $this->args;
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

}

?>