<?php
$url = 'http://wsparciewgdansku.pl/wp-content/uploads/cache/images/remote/i0-wp-com/tęcza-logo-e1471347639221--1004499177.png';
$urlparts = explode('/', $url);
$last = count($urlparts) - 1;
$encoded = urlencode($urlparts[$last]);
var_dump($encoded);
$url = str_replace($urlparts[$last], $encoded, $url);
var_dump($url);
file_get_contents((($url)));