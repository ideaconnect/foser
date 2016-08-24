<?php
include "lib/PHPWord.php";
include "agency.php";
include "log.php";

function getRegexp($regexp, $text) {
   $matches = array();
   $c=preg_match_all ("/".$regexp."/is", $text, $matches);
   if($c === false || $c === 0) {
       return null;
   }
   $string=$matches[1][0];
   return html_entity_decode($string);
}

function cleanLogos() {
    $logos = glob('logos/*');
    foreach($logos as $logo) {
        if($logo !== '.' && $logo !== '..') {
            unlink($logo);
        }
    }
}

function getCategories($page) {
    $categories = array();

    $category_regexp = '<div class="item-box carousel-item">.*?<a href="(.*?)">.*?<div class="item-title"><h3>(.*?)<\/h3>';
    $c=preg_match_all ("/".$category_regexp."/is", $page, $matches);
    if($c === false) {
        return null;
    }

    foreach($matches[1] as $index => $url) {
        if(strstr($url,'/cat/')) {
            $categories[$url] = $matches[2][$index];
        }
    }
    return $categories;
}

function getLocations($page) {
    $categories = array();

    $category_regexp = '<div class="item-box carousel-item">.*?<a href="(.*?)">.*?<div class="item-title"><h3>(.*?)<\/h3>';
    $c=preg_match_all ("/".$category_regexp."/is", $page, $matches);
    if($c === false) {
        return null;
    }

    foreach($matches[1] as $index => $url) {
        if(strstr($url,'/loc/')) {
            $categories[$url] = $matches[2][$index];
        }
    }
    return $categories;
}

function doAgencies($agencies) {
    parseEntity($url);
    $objWriter = PHPWord_IOFactory::createWriter($document, 'Word2007');
    $objWriter->save('Text.docx');
}

logInfo("Started");
if(file_exists('fosa.docx')) {
    unlink('fosa.docx');
    logInfo('Removed old file');
}
logInfo("Getting FOSA");
$page = file_get_contents("http://wsparciewgdansku.pl/");
logInfo("Getting categories...");
$categories = getCategories($page);
logInfo("Got: " . count($categories) . " categories");
logInfo("Getting locations...");
$locations = getLocations($page);
logInfo("Got: " . count($locations) . " locations");

$agencies = array();

function fixImgUrl($url) {
    $urlparts = explode('/', $url);
    $last = count($urlparts) - 1;
    $encoded = urlencode($urlparts[$last]);
    $url = str_replace($urlparts[$last], $encoded, $url);
    return $url;
}

function getTags($tagsHtml) {
    $regex = '<span class="filter-hover">(.*?)<\/span>';
    $c=preg_match_all ("/".$regex."/is", $tagsHtml, $matches);
    $tags = array();
    foreach($matches[1] as $match) {
        $tag = trim($match);
        if(strlen($tag) > 0) {
            $tags[] = $tag;
        }
    }

    return $tags;
}

function parseTaxonomyPage($page, $type, $taxonomy) {
    global $agencies;

    //1 = url
    //2 = title
    //3 = adres
    //4 = filters html;
    $regex = '<div class="item-title">.*?<a href="(.*?)">.*?<h3>(.*?)<\/h3>.*?<span class="label">Adres:<\/span>.*?<span class="value">(.*?)<\/span>.*?<ul class="item-filters">(.*?)<\/ul>';
    $c=preg_match_all ("/".$regex."/is", $page, $matches, PREG_SET_ORDER);
    $i = 0;
    foreach($matches as $match) {
        $i++;
        $url = $match[1];
        $id = sha1($url);
        $title = html_entity_decode($match[2]);
        $address = $match[3];

        if(array_key_exists($id, $agencies)) {
            $agency = $agencies[$id];
        } else {
            $agency = new agency();
        }
        $agency->title = $title;
        $agency->url = $url;
        $agency->address = $address;
        if($type === 'loc') {
            $agency->locations[$taxonomy] = true;
        }

        if($type === 'cat') {
            $agency->categories[$taxonomy] = true;
        }

        $tags = getTags($match[4]);
        foreach($tags as $tag) {
            $agency->tags[$tag] = true;
        }

        $agencies[$id] = $agency;
    }

    return $i;
}

function parseTaxonomy($taxonomy, $taxonomyType) {
    foreach($taxonomy as $categoryUrl => $category) {
        logInfo("Parsing: " . $category);
        $url = $categoryUrl;
        while(true) {
            $page = file_get_contents($url);
            logInfo("Parsing taxonomy page.");
            $count = parseTaxonomyPage($page, $taxonomyType, $category);
            logInfo("Got: " . $count . " agencies");
            $nextPage_regexp = '<span class="nav-next"><a href="(.*?)" >Następny';
            $c=preg_match_all ("/".$nextPage_regexp."/is", $page, $matches);
            if($c === false || $c === 0) {
                break;
            }

            logInfo("getting next page...");
            $url = $matches[1][0];
        }
    }

}

logInfo(" --- CATEGORIES");
parseTaxonomy($categories, 'cat');
logInfo(" --- LOCATIONS");
parseTaxonomy($locations, 'loc');

$document = new PHPWord();
include "entity.php";
$max = count($agencies);
$i = 0;
foreach($agencies as $agency) {
    $i++;
    logInfo("Agency: [ ".$i."/".$max." ]"   . $agency->title);
    parseEntity($agency);
}
$objWriter = PHPWord_IOFactory::createWriter($document, 'Word2007');
$objWriter->save('fosa.docx');