<?php declare(strict_types=1);

include_once __DIR__.'/../vendor/autoload.php';

$array = ['hello' => 'world', 0 => 1, 'delete' => 'me'];

try {
    unset($array['delete']);

    throw new RuntimeException();
} catch (RuntimeException) {
}

dump($array);
