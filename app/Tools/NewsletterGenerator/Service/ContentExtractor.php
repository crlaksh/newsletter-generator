<?php

namespace Tools\NewsletterGenerator\Service;
use Tools\NewsletterGenerator\Util\Helper as Helper;

class ContentExtractor extends Helper {

    function getData($dataFile) {
        return $this->getExcelData($dataFile);
    }

    function getDetailsFromData($data) {
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

    function getBlocksFromData($data) {
        $blocks = array();
        $block = array();
        $emptyTypeCount = 1;
        foreach ($data as $value) {
            if (strtolower($value['sheetname']) === 'blocks') {
                $rows = $value['rows'];
                array_shift($rows); // Removing Title Row
                $rowCount = count($rows);
                for ($key = 0; $key < $rowCount; $key++) {
                    $row = $rows[$key];
                    if (implode('', $row) !== '') {
                        $block['type'] = $row[0];
                        $block['data'] = $this->getBlockData($row);
                        if ($block['type'] === "") {
                            $block['type'] = $blocks[$key - $emptyTypeCount]['type'];
                            $this->validateExcelBlockData($block);
                            array_push($blocks[$key - $emptyTypeCount]['data'], $block['data'][0]);
                            $emptyTypeCount++;
                        }
                        else {
                            $this->validateExcelBlockData($block);
                            array_push($blocks, $block);
                        }
                    }
                }
            }
        }
        if (!isset($blocks) || count($blocks) === 0) {
            echo "\nBlocks not found\n\n";
            exit;
        }
        return $blocks;
    }

    function getBlockData($row) {
        return [[
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
        ]];
    }

    function validateExcelDetailsData($details) {
        if (!isset($details) || count($details) === 0) {
            echo "\nDetails not found\n\n";
            exit;
        }
        if (!isset($details['utm_source'])) {
            echo "\nutm_source not found in Details\n\n";
            exit;
        }
        if (!isset($details['utm_medium'])) {
            echo "\utm_medium not found in Details\n\n";
            exit;
        }
        if (!isset($details['utm_campaign'])) {
            echo "\nutm_campaign not found in Details\n\n";
            exit;
        }
    }

    function validateExcelBlockData($block) {
        if (
            in_array($block['type'], array("single_blog", "double_blog", "split_blog", "split_blog_mirror", "double_split_blog")) &&
            $block['data'][0]['url'] === ""
        ) {
            echo "\nURL missing for a block\n\n";
            exit;
        }
    }

}

?>