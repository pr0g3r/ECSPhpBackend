<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: X-Requested-With,Origin,Content-Type,Cookie,Accept,Authorization');

$requestBody = file_get_contents('php://input');
$requestBody = json_decode($requestBody, true);

// $requestBody = [
//     'reference' => '123',
//     'attachment_ids' => [
//         'L1173111000016533',
//     ]
// ];

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 204 No Content');
    die;
}

if ($requestBody === null && !$_FILES) {
    header('HTTP1.1 400 Bad Request');

    echo json_encode([
        'errorMessage' => 'Please Provide Valid JSON',
    ]);
    die();
}


// Save file copies
if (isset($_GET) && $_GET['action'] == "storeFile") {
    $paths = [];
    foreach ($_FILES as $file) {
        $path = "/mnt/deepthought/FESP-REFACTOR/FespMVC/ECS/assets/${_GET['location']}/${file['name']}";
        $paths[] = $path;
        move_uploaded_file($file['tmp_name'], $path);
    }

    echo json_encode($paths, JSON_PRETTY_PRINT);
}

// Save copy of a sumbitted file for future referrence.
if (isset($_GET) && $_GET['action'] == "storeFile") {
    $path = "/mnt/deepthought/FESP-REFACTOR/FespMVC/ECS/assets/${requestBody['location']}/${requestBody['name']}.${requestBody['ext']}";
    file_put_contents($path, $requestBody['file']);

    echo json_encode($path, JSON_PRETTY_PRINT);
}

// Get a collection of attachements that need to be send with the form to the courier.
// This was thrown together quickly its poorly implemented, ideally the paths for each
// order should be stored in a database.
if (isset($requestBody['action']['getFormAttachments'])) {
    $tracking_ids = $requestBody['attachment_ids'];
    $attachments = [];
    $path = '/mnt/deepthought/FESP-REFACTOR/FespMVC/ECS/assets/order_attachments/';
    $attachments_folder = $path . "${requestBody['reference']}_attachments.zip";
    $zip = new ZipArchive();
    $zip->open($attachments_folder, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $unlink = [];
    // Build attachments zip for claim.
    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            foreach ($tracking_ids as $record) {
                if (str_contains($name, $record['tracking']) && str_contains($name, $record['type'])) {
                    $filePath = $file->getRealPath();
                    $file = substr($filePath, strrpos($filePath, '/') + 1);

                    // Add current file to archive
                    $zip->addFile($filePath, $file);
                    $unlink[] = $filePath;
                }
            }
        }
    }
    $zip->close();
    // Delete the files as they are now stored in the zip.
    foreach ($unlink as $attachment) {
        unlink($attachment);
    }

    echo json_encode($attachments_folder, JSON_PRETTY_PRINT);
}

// Rename a form on serverside to match the new reference number.
if (isset($requestBody['action']['renameForm'])) {
    $base = '/mnt/deepthought/FESP-REFACTOR/FespMVC/ECS/assets/claims/';
    $target_csv =  $base . "${requestBody['oldReference']}.csv";
    if (file_exists($target_csv)) {
        rename($target_csv, $base . "${requestBody['newReference']}.csv");
    }
}
