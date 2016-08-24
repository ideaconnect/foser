<?php
$regexp = [
    'title' => '<div class="entry-title-wrap">.*?<h1>(.*?)<\/h1>',
    'logo' => '<span class="easy-thumbnail">.*?<img src="(.*?)"',
    'description' => '<div class="entry-content-wrap" itemprop="description">(.*?)<div id=\'jp-relatedposts\''
];

function parseEntity($agency) {
    global $regexp, $document;

    $url = $agency->url;

    $page = file_get_contents($url);
    $title = getRegexp($regexp['title'], $page);
    $logo = getRegexp($regexp['logo'], $page);
    if($logo !== null && strlen($logo) > 0) {
        $extension = substr($logo, -3, 3);
        $logoFile = 'logos/' . md5($url) . '.' . $extension;
        file_put_contents($logoFile, file_get_contents(fixImgUrl($logo)));
    } else {
        $logoFile = null;
    }
    $description = strip_tags((getRegexp($regexp['description'], $page)));

    $section = $document->createSection();

    // Add text elements
    $section->addText($title, array('name'=>'Calibri', 'bold' => true, 'size' => 16,'align'=>'center'));
    $section->addTextBreak(1);
    if($logoFile !== null) {
        $section->addImage($logoFile, array('align'=>'center', 'width'=>200, 'height'=>240));
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
    $section->addTextBreak(1);
    $section->addText(trim($description), array('name'=>'Calibri', 'size' => 12, 'align'=>'justify'));
}
