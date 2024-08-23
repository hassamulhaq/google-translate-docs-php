<?php

namespace Hassamulhaq\GoogleTranslateDocsPhp;

use Dotenv\Dotenv;
use Google\ApiCore\ApiException;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\DocumentInputConfig;
use Google\Cloud\Translate\V3\TranslateDocumentRequest;

//use Google\Cloud\Translate\V3\TranslateDocumentResponse;

class DocumentTranslator
{
    private TranslationServiceClient $translationServiceClient;
    private $targetLanguageCode;
    private $uploadDir;
    private string $sessionId;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $_ENV['GOOGLE_APPLICATION_CREDENTIALS']);
        $this->translationServiceClient = new TranslationServiceClient();
        $this->targetLanguageCode = $_POST['requests']['target_language_code'] ?? $_ENV['TARGET_LANGUAGE_CODE'];
        $this->uploadDir = $_ENV['UPLOAD_DIR_DOCUMENT'] ?? 'uploads/';
        $this->sessionId = uniqid();

        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    private function isValidFileType($fileType): bool
    {
        $validFileTypes = [
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/pdf",
            "application/vnd.ms-powerpoint",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        ];

        return in_array($fileType, $validFileTypes);
    }

    private function getFileExtension($fileType): string
    {
        $map = [
            "application/msword" => "doc",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
            "application/pdf" => "pdf",
            "application/vnd.ms-powerpoint" => "ppt",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation" => "pptx",
            "application/vnd.ms-excel" => "xls",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" => "xlsx"
        ];

        return $map[$fileType] ?? 'txt';
    }

    public function handleRequest()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError(405, 'Request must be POST!');
            return;
        }

        if (!isset($_POST['requests']['target_language_code'])) {
            http_response_code(403);
            $this->sendError(403, 'Invalid argument. provide requests.target_language_code argument.');
            return;
        }


        if (!isset($_POST['requests']) || !isset($_FILES['requests']['name']['document'])) {
            $this->sendError(403, 'Request contains an invalid argument. Provide requests, requests.document arguments.');
            return;
        }

        $this->processFileUpload();
    }

    private function processFileUpload()
    {
        $file = $_FILES['requests'];
        $fileName = $this->sessionId . '-' . $file['name']['document'];
        $fileTmpName = $file['tmp_name']['document'];
        $fileSize = $file['size']['document'];
        $fileError = $file['error']['document'];
        $fileType = $file['type']['document'];

        if ($fileError !== 0) {
            $this->sendError(403, 'There was an error uploading your file.');
            return;
        }

        if (!$this->isValidFileType($fileType)) {
            $this->sendError(403, 'File type not supported.');
            return;
        }

        $fileDestination = $this->uploadDir . basename($fileName);
        if (!move_uploaded_file($fileTmpName, $fileDestination)) {
            $this->sendError(403, 'Failed to upload the file.');
            return;
        }

        $this->translateDocument($fileDestination, $fileType);
    }

    private function translateDocument($filePath, $fileType)
    {
        if (!$this->isValidFileType($fileType)) {
            $this->sendError(403, 'File type not supported.');
            return;
        }

        try {
            $fileContent = file_get_contents($filePath);
            $formattedParent = $this->translationServiceClient->locationName($_ENV['GOOGLE_PROJECT_ID'], $_ENV['GOOGLE_LOCATION_ID'] ?? 'global');

            $documentInputConfig = new DocumentInputConfig([
                "content" => $fileContent,
                "mime_type" => $fileType
            ]);

            $request = new TranslateDocumentRequest([
                'parent' => $formattedParent,
                'target_language_code' => $this->targetLanguageCode,
                'document_input_config' => $documentInputConfig
            ]);

            $apiResponse = $this->translationServiceClient->translateDocument($request);

            if ($apiResponse->hasDocumentTranslation()) {
                $this->createTranslatedFile($apiResponse, $fileType);
            } else {
                $this->sendError(403, 'No document translation found.');
            }
        } catch (ApiException $e) {
            $this->sendError(403, $e->getMessage());
        } catch (\Exception $exception) {
            $this->sendError(403, $exception->getMessage());
        }
    }

    private function createTranslatedFile($apiResponse, $fileType)
    {
        $createFileName = $this->sessionId . "-translated-" . time() . "." . $this->getFileExtension($fileType);
        $filePath = $this->uploadDir . $createFileName;

        $fileHandler = fopen($filePath, "wb");
        $outputContent = $apiResponse->getDocumentTranslation()->getByteStreamOutputs();

        foreach ($outputContent as $content) {
            fwrite($fileHandler, $content);
        }

        fclose($fileHandler);

        //header('Content-Type: application/octet-stream');
        //header('Content-Disposition: attachment; filename="' . basename($createFileName) . '"');
        //header('Content-Length: ' . filesize($filePath));
        //readfile($filePath);

        // TODO: Optionally remove the file after download
        // unlink($filePath);

        http_response_code(200);
        echo json_encode(["data" => ["translated_file" => $createFileName]]);
    }


    private function sendError($code, $message)
    {
        http_response_code($code);
        echo json_encode([
            "responses" => [
                "error" => [
                    "code" => $code,
                    "message" => $message
                ]
            ]
        ]);
        exit;
    }

    public function __destruct()
    {
        $this->translationServiceClient->close();
    }
}