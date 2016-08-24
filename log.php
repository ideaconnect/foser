<?php

function logInfo($text) {
    echo "[ " . date('H:i:s') . " ] " . $text . "\r\n";
    @flush();
    @ob_flush();
}
