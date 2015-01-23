<?php

/**
 * Migrates saves from /var/saves to postgres.
 */

use Symfony\Component\Finder\Finder;

require __DIR__ . '/../vendor/autoload.php';

$pdo = new PDO('pgsql:host=localhost;dbname=isaac');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE savegame (
                id serial primary key,
                hash text,
                data text,
                uploaded timestamp
            );");

$pdo->exec("CREATE UNIQUE INDEX ON savegame (hash);");

$dirPath = __DIR__ . '/../var/saves';

$sql = "INSERT INTO savegame (hash, data, uploaded) VALUES (:hash, :data, :uploaded)";
$stmt = $pdo->prepare($sql);

$finder = new Finder();
$finder->files()->in($dirPath);

foreach ($finder as $file) {
    $time = $file->getCTime();
    $time = date('c', $time);

    $content = $file->getContents();
    $content = base64_encode($content);

    $data = [
        "hash" => $file->getFilename(),
        "data" => $content,
        "uploaded" => $time,
    ];

    $stmt->execute($data);
    echo ".";
}

echo "\nDone\n";
