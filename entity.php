<?php
$regexp = [
    'title' => '<div class="entry-title-wrap">.*?<h1>(.*?)<\/h1>',
    'logo' => '<span class="easy-thumbnail">.*?<img src="(.*?)"',
    'description' => '<div class="entry-content-wrap" itemprop="description">(.*?)<div id=\'jp-relatedposts\'',
    'phone' => 'class="phone">(.*?)<\/a>',
    'www' => 'itemprop="url" >(.*?)<\/a>',
    'email' => 'itemprop="email">(.*?)<\/a>'
];

function fixname($string) {
    $string = str_replace(" ", "_", $string);
    $string = str_replace("(", "_", $string);
    $string = str_replace(")", "_", $string);
    $string = preg_replace("/[^A-Za-z0-9 ]/", '', $string);
    return $string;
}

function parseEntity($agency) {
    global $regexp, $document;

    $url = $agency->url;

    $page = file_get_contents($url);
    $agency->title = getRegexp($regexp['title'], $page);
    $logo = getRegexp($regexp['logo'], $page);
    $agency->phone = getRegexpa($regexp['phone'], $page);
    $agency->email = getRegexp($regexp['email'], $page);
    $agency->www = getRegexp($regexp['www'], $page);

    if($logo !== null && strlen($logo) > 0) {
        $extension = substr($logo, -3, 3);
        $logoFile = 'logos/' . md5($url) . '.' . $extension;
        file_put_contents($logoFile, file_get_contents(fixImgUrl($logo)));
    } else {
        $logoFile = null;
    }
    $agency->logo = $logoFile;
    $agency->description = strip_tags((getRegexp($regexp['description'], $page)));


    $screenCapture = new Screen\Capture("http://127.0.0.1/foser/fos.php?u=" . base64_encode($url));
    $screenCapture->setWidth(1366);
    $screenCapture->setHeight(4500);
    $screenCapture->setTimeout(15000);
    $screenCapture->binPath = '/opt/phantomjs-2.1.1-linux-x86_64/bin/';
    $screenCapture->setImageType(Screen\Image\Types\Png::FORMAT);
    $screenCapture->setImageType('png');


    $fileLocation = 'shoty/' . fixname($agency->title) . '.' . $screenCapture->getImageType()->getFormat();
    $screenCapture->save($fileLocation);

    return $agency;  
}

function outputEntity($agency, $section) {
//    global $document;
//    $section = $document->createSection();
    // Add text elements
    $section->addText($agency->title, array('name'=>'Calibri', 'bold' => true, 'size' => 16,'align'=>'center'));
    $section->addTextBreak(1);

    $section->addText("Wprowadzenie:", array('name'=>'Calibri', 'bold' => true, 'size' => 12));
    $section->addText(trim($agency->short), array('name'=>'Calibri', 'size' => 12, 'align'=>'justify'));

    $section->addText("Opis:", array('name'=>'Calibri', 'bold' => true, 'size' => 12));
    $section->addText(trim($agency->description), array('name'=>'Calibri', 'size' => 12, 'align'=>'justify'));

    $section->addText("Adres:", array('name'=>'Calibri', 'bold' => true, 'size' => 12));
    $section->addText($agency->address, array('name'=>'Calibri', 'bold' => false, 'size' => 12));

    $section->addText("www:", array('name'=>'Calibri', 'bold' => true, 'size' => 12));
    $section->addText($agency->www, array('name'=>'Calibri', 'bold' => false, 'size' => 12));

    $section->addText("Email:", array('name'=>'Calibri', 'bold' => true, 'size' => 12));
    $section->addText($agency->email, array('name'=>'Calibri', 'bold' => false, 'size' => 12));

    $section->addText("Telefony:", array('name'=>'Calibri', 'bold' => true, 'size' => 12));
    if(is_null($agency->phone)) {
        var_dump("No phone: " . $agency->url);
    } else {
    foreach($agency->phone as $element) {
        $section->addListItem($element, 0);
    }
    }

    $section->addText("Lokalizacje:", array('name'=>'Calibri', 'bold' => true, 'size' => 12));
    foreach(array_keys($agency->locations) as $element) {
        $section->addListItem($element, 0);
    }
    $section->addTextBreak(1);

    $section->addText("Kategorie:", array('name'=>'Calibri', 'bold' => true, 'size' => 12));
    foreach(array_keys($agency->categories) as $element) {
        $section->addListItem($element, 0);
    }
    $section->addTextBreak(1);

    $section->addText("Opcje:", array('name'=>'Calibri', 'bold' => true, 'size' => 12));
    foreach(array_keys($agency->tags) as $element) {
        $section->addListItem($element, 0);
    }
}
