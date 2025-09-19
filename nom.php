<?php

require 'vendor/autoload.php';

use MongoDB\Client;

function normalizeString(string $str): string
{
    $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[^a-z0-9\- ]/', '', $str);
    $str = preg_replace('/\s+/', ' ', $str);

    return trim($str);
}

$client = new Client('mongodb://localhost:27017');
$collection = $client->villes->villes;

$cursor = $collection->find();

foreach ($cursor as $ville) {
    $nom = $ville['nom'];
    $nom_normalise = normalizeString($nom);

    $collection->updateOne(
        ['_id' => $ville['_id']],
        ['$set' => ['nom_normalise' => $nom_normalise]]
    );

    echo "Updated {$nom} -> {$nom_normalise}\n";
}
