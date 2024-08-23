<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use Google\ApiCore\ApiException;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\DocumentInputConfig;
use Google\Cloud\Translate\V3\TranslateDocumentRequest;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode([
        "responses" => [
            "error" => [
                "code" => 405,
                "message" => "Request must be POST!"
            ]
        ]
    ]);
    exit;
}

if (!isset($_POST['requests'])) {
    http_response_code(403);
    echo json_encode([
        "responses" => [
            "error" => [
                "code" => 403,
                "message" => "Request contains an invalid argument. provide requests and requests.document arguments."
            ]
        ]
    ]);
    exit;
}

if (!isset($_POST['requests']['target_language_code'])) {
    http_response_code(403);
    echo json_encode([
        "responses" => [
            "error" => [
                "code" => 403,
                "message" => "Request contains an invalid argument. provide requests and requests.target_language_code arguments."
            ]
        ]
    ]);
    exit;
}

// translation code
if (isset($_FILES['requests']['name']['document'])) {
    $session_id = uniqid();

    $fileName = $session_id . '-' . $_FILES['requests']['name']['document'];
    $fileTmpName = $_FILES['requests']['tmp_name']['document'];
    $fileSize = $_FILES['requests']['size']['document'];
    $fileError = $_FILES['requests']['error']['document'];
    $fileType = $_FILES['requests']['type']['document'];

    if (!in_array($fileType, [
        "application/msword",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "application/pdf",
        "application/vnd.ms-powerpoint",
        "application/vnd.openxmlformats-officedocument.presentationml.presentation",
        "application/vnd.ms-excel",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"])) {
        http_response_code(403);
        echo json_encode([
            "responses" => [
                "error" => [
                    "code" => 403,
                    "message" => "File type not supported."
                ]
            ]
        ]);
        exit;
    }


    $uploadDir = $_ENV['UPLOAD_DIR_DOCUMENT'] ?? 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if ($fileError != 0) {
        http_response_code(403);
        echo json_encode([
            "responses" => [
                "error" => [
                    "code" => 403,
                    "message" => "There was an error uploading your file."
                ]
            ]
        ]);
        exit;
    }

    $fileDestination = $uploadDir . basename($fileName);
    if (!move_uploaded_file($fileTmpName, $fileDestination)) {
        http_response_code(403);
        echo json_encode([
            "responses" => [
                "error" => [
                    "code" => 403,
                    "message" => "Failed to upload the file."
                ]
            ]
        ]);
        exit;
    }

    putenv('GOOGLE_APPLICATION_CREDENTIALS=/path/to/your-service-account-file.json');

    $fileContent = file_get_contents($fileDestination);

    try {
        $targetLanguageCode = $_POST['target_language_code'] ?? $_ENV['TARGET_LANGUAGE_CODE'];

        $translationServiceClient = new TranslationServiceClient();
        $formattedParent = $translationServiceClient->locationName($_ENV['GOOGLE_PROJECT_ID'], $_ENV['GOOGLE_LOCATION_ID'] ?? 'global');

        $documentInputConfig = new DocumentInputConfig([
            "content" => $fileContent,
            "mime_type" => $fileType ?? 'text/plain'
        ]);

        $request = new TranslateDocumentRequest([
            'parent' => $formattedParent,
            'target_language_code' => $targetLanguageCode,
            'document_input_config' => $documentInputConfig
        ]);
        $apiResponse = $translationServiceClient->translateDocument($request);

        if ($apiResponse->hasDocumentTranslation()) {
            $createFileName = $session_id . "-" . "translated-" . time() . "." . $fileType;
            if (!file_exists($createFileName)) {
                $fileHandler = fopen($createFileName, "wb");
                $outputContent = $apiResponse->getDocumentTranslation()->getByteStreamOutputs();
                foreach ($outputContent as $content) {
                    fwrite($fileHandler, $content);
                }
                fclose($fileHandler);

                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($createFileName) . '"');
                header('Content-Length: ' . filesize($createFileName));
                readfile($createFileName);

                //unlink($createFileName); #TODO: remove this line to keep the file

                http_response_code(200);
                echo json_encode([
                    "data" => [
                        "translated_file" => $createFileName
                    ]
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                "responses" => [
                    "error" => [
                        "code" => 403,
                        "message" => "No document translation found."
                    ]
                ]
            ]);
        }
    } catch (ApiException $e) {
        http_response_code(403);
        echo json_encode([
            "responses" => [
                "error" => [
                    "code" => 403,
                    "message" => $e->getMessage() . "\n"
                ]
            ]
        ]);
        exit;
    }
} else {
    http_response_code(403);
    echo json_encode([
        "responses" => [
            "error" => [
                "code" => 403,
                "message" => "Request contains an invalid argument. provide requests and requests.document arguments."
            ]
        ]
    ]);
    exit;
}



/*$translate = new TranslateClient([

]);
$translation = $translate->translate($fileContent, [
    'target' => $targetLanguageCode,
]);

header('Content-Type: text/plain');
echo $translation['text'];

$translationServiceClient->close();*/
