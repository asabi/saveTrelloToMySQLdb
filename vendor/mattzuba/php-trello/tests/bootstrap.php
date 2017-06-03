<?php
global $keys;

$keys = null;

is_readable(__DIR__ . '/keys.json') && $keys = json_decode(file_get_contents(__DIR__ . '/keys.json'));

require_once __DIR__ . '/../src/Trello/Trello.php';
