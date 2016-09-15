<?php
include "vendor/autoload.php";
include "lib/PHPWord.php";
include "agency.php";
include "log.php";
$document = new PHPWord();
include "entity.php";
$outTax = array(
 'Całodobowe' => array('k'=>'Oddziaływania całodobowe',
   'Dom Pomocy Społecznej' => array('exclude'=>'dzienny','match'=>'dom pomocy,pomoc spoleczna', 'elems'=>array()),
   'Szpitale - Oddziały Stacjonarne' => array('match'=>'szpital', 'elems'=>array()),
   'Mieszkania chronione' => array('match'=>'chronione', 'elems'=>array()),
   'Hotel Sitowie, Hostel Sitowie' => array('match'=>'hotel,hostel,sitowie', 'elems'=>array()),
   'Pozostałe' => array('elems'=>array())
 ),
 'Dzienne' => array('k'=>'Całodzienne formy',
   'Klub Samopomocy' => array('match'=>'klub', 'elems'=>array()), 
   'Środowiskowy Dom Samopomocy' => array('match'=>'sdś,środowiskowy', 'elems'=>array()),
   'Warsztat Terapii Zajęciowej' => array('match'=>'warsztat terapii,terapia zajęciowa', 'elems'=>array()),
   'Oddział dzienny' => array('match'=>'oddzial dzienny', 'elems'=>array()),
   'Dzienny Dom Pomocy Społecznej' => array('match'=>'dom pomocy,pomoc spoleczna', 'elems'=>array()),
   'Pozostałe' => array('elems'=>array())
 ),
 'Doraźne' => array('k'=>'Doraźne wsparcie',
   'Poradnia Psychologiczno - Pedagogiczna' => array('match'=>'poradnia psychologiczno,pedagogiczna', 'elems'=>array()),
   'Poradnia Rodzinna' => array('match'=>'poradnia rodzinna', 'elems'=>array()),
   'Poradnia Leczenia Uzależnień' => array('match'=>'poradnia leczenia uzależnie', 'elems'=>array()),
   'Poradnia Zdrowia Psychicznego' => array('match'=>'poradnia zdrowia', 'elems'=>array()),
   'Poradnia Psychologiczna' => array('match'=>'poradnia psychologniczna', 'elems'=>array()),
   'Specjalistyczne usługi opiekuńcze' => array('match'=>'specjalistyczne,usługi opiekuńcze', 'elems'=>array()),
   'Pozostałe' => array('elems'=>array())
 ),
);


function getRegexp($regexp, $text) {
   $matches = array();
   $c=preg_match_all ("/".$regexp."/is", $text, $matches);
   if($c === false || $c === 0) {
       return null;
   }
   $string=$matches[1][0];
   return html_entity_decode($string);
}

function getRegexpa($regexp, $text) {
   $matches = array();
   $c=preg_match_all ("/".$regexp."/is", $text, $matches);
   if($c === false || $c === 0) {
       return null;
   }
   $string=$matches[1];
   return $string;
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
$filename = "fosa_" . date('H_i_s') . ".docx";
logInfo("Started");
if(file_exists($filename)) {
    unlink($filename);
    logInfo('Removed old file');
}
logInfo("Getting FOSA");
if(file_exists('flat.out')) {
    $flatAgencies = unserialize(file_get_contents('flat.out'));
}  else {
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
    $regex = '<div class="item-title">.*?<a href="(.*?)">.*?<h3>(.*?)<\/h3>.*?<p class="txtrows-4">(.*?)<\/p>.*?<span class="label">Adres:<\/span>.*?<span class="value">(.*?)<\/span>.*?<ul class="item-filters">(.*?)<\/ul>';
    $c=preg_match_all ("/".$regex."/is", $page, $matches, PREG_SET_ORDER);
    $i = 0;
    foreach($matches as $match) {
        $i++;
        $url = $match[1];
        $id = sha1($url);
        $title = html_entity_decode($match[2]);
        $address = $match[4];
        $short = $match[3];
        

        if(array_key_exists($id, $agencies)) {
            $agency = $agencies[$id];
        } else {
            $agency = new agency();
        }
        $agency->title = $title;
        $agency->url = $url;
        $agency->address = $address;
        $agency->short = $short;
        if($type === 'loc') {
            $agency->locations[$taxonomy] = true;
        }

        if($type === 'cat') {
            $agency->categories[$taxonomy] = true;
        }

        $tags = getTags($match[5]);
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
        $i = 0;
        while(true) {
            $page = file_get_contents($url);
            $i++;
    $screenCapture = new Screen\Capture("http://127.0.0.1/foser/fos.php?u=" . base64_encode($url));
    $screenCapture->setWidth(1366);
    $screenCapture->setHeight(6500);
    $screenCapture->setTimeout(15000);
    $screenCapture->binPath = '/opt/phantomjs-2.1.1-linux-x86_64/bin/';
    $screenCapture->setImageType(Screen\Image\Types\Png::FORMAT);
    $screenCapture->setImageType('png');


    $fileLocation = 'taxonomy/' . $taxonomyType . "_" . $category . "_" . $i . '.' . $screenCapture->getImageType()->getFormat();
    $screenCapture->save($fileLocation);

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

$max = count($agencies);
$i = 0;
$flatAgencies = array();
$inne = array();
foreach($agencies as $agency) {
    $i++;
    logInfo("Agency: [ ".$i."/".$max." ]"   . $agency->title);
    $flatAgencies[] = parseEntity($agency);
}
}

file_put_contents('flat.out', serialize($flatAgencies));
foreach($flatAgencies as $agency) {
  $matched = false;
  foreach($outTax as $category => $subcategories) {
    $categoryMatch = $subcategories['k'];
    if(in_array($categoryMatch, array_keys($agency->categories)) || in_array($categoryMatch, array_keys($agency->tags))) { //pasuje do kategorii
      $submatch = false;

  foreach($subcategories as $realCategory => $settings) {
     if($realCategory == 'k' || $realCategory == 'Pozostałe') continue;
     $title = strtolower($agency->title);
     if(array_key_exists('exclude', $settings)) {
       $exclude = $settings['exclude'];
       if(strstr($title, $exclude) !== false) {
         continue;
       }
     }

     $include = explode(',', $settings['match']);
     foreach($include as $needle) {
       if(strstr($title, $needle)) {
         $outTax[$category][$realCategory]['elems'][] = $agency;
         logInfo($realCategory . ' => ' . $agency->title);
         $submatch = true;
         break;
       }
     }
     if($submatch === true) break;
  }    

      if($submatch == false) { //nie pasowalo do zadnej subkategorii
        $outTax[$category]["Pozostałe"]['elems'][] = $agency;
      }
    }
  }

  if($matched === false) {
    $inne[] = $agency;
  }
}

foreach($outTax as $category => $subcategories) {
  $section = $document->createSection();
  $section->addText('# ' . $category, array('name'=>'Calibri', 'bold' => true, 'size' => 20));
  foreach($subcategories as $realCategory => $settings) {
    if($realCategory == 'k') continue;
    $section->addText('## ' . $realCategory, array('name'=>'Calibri', 'bold' => true, 'size' => 18));
    foreach($settings['elems'] as $agency) {
      outputEntity($agency, $section);
    }
  }
}

$section = $document->createSection();
$section->addText("# Inne", array('name'=>'Calibri', 'bold' => true, 'size' => 20));
foreach($inne as $agency) {
  outputEntity($agency, $section);
}

shell_exec("zip -9 -r shots.zip shoty/");
logInfo("Shoty: shots.zip");

logInfo("Saving...");
$objWriter = PHPWord_IOFactory::createWriter($document, 'Word2007');
$objWriter->save($filename);
logInfo("Saved");
logInfo("Open: " . $filename);
