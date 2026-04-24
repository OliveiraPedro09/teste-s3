<?php

// Método completamente meia bomba feita via chatgpt, mas que funciona B)
require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$s3 = new S3Client([
    'version' => 'latest',
    'region' => $_ENV['AWS_REGION'],
    'credentials' => [
        'key' => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
    ],
    // se o ssl estiver enchendo o saco, descomente isso mas qualquer coisa coloque o certificado cacert.pem no php.ini
    // curl.cainfo = "C:\php\cacert.pem ou qualquer outro caminho"
    // openssl.cafile = "C:\php\cacert.pem"
    // 'http' => [
    //     'verify' => false
    // ],
]);

$bucket = $_ENV['AWS_BUCKET'];

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json');

// =========================
// POST /upload
// =========================
if ($method === 'POST' && $path === '/upload') {
    try {
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Arquivo obrigatório']);
            exit;
        }

        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'Erro no upload local']);
            exit;
        }

        $originalName = basename($file['name']);
        $tmpPath = $file['tmp_name'];
        $mimeType = mime_content_type($tmpPath);

        $key = 'uploads/user/' . time() . '-' . $originalName;

        $s3->putObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'SourceFile' => $tmpPath,
            'ContentType' => $mimeType,
        ]);

        echo json_encode([
            'message' => 'Upload realizado com sucesso',
            'key' => $key,
        ]);
        exit;
    } catch (S3Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Erro ao enviar para o S3',
            'details' => $e->getMessage(),
        ]);
        exit;
    }
}

// =========================
// GET /file?key=uploads/user/arquivo.txt
// =========================
if ($method === 'GET' && $path === '/file') {
    try {
        $key = $_GET['key'] ?? null;

        if (!$key) {
            http_response_code(400);
            echo json_encode(['error' => 'Key obrigatória']);
            exit;
        }

        // Segurança mínima: impede acessar qualquer caminho fora de uploads/user/
        if (!str_starts_with($key, 'uploads/user/')) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            exit;
        }

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        $request = $s3->createPresignedRequest($cmd, '+5 minutes');

        $url = (string) $request->getUri();

        echo json_encode(['url' => $url]);
        exit;
    } catch (S3Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Erro ao gerar URL',
            'details' => $e->getAwsErrorMessage(),
        ]);
        exit;
    }
}

// =========================
// GET /teste
// =========================
if ($method === 'GET' && $path === '/teste') {
    try {
        $key = 'uploads/user/teste.txt';

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        $request = $s3->createPresignedRequest($cmd, '+5 minutes');

        $url = (string) $request->getUri();

        echo json_encode([
            'file' => $key,
            'url' => $url,
        ]);
        exit;
    } catch (S3Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Erro no teste',
            'details' => $e->getAwsErrorMessage(),
        ]);
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada']);
