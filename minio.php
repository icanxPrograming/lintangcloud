<?php
require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

class MinioClient
{
    private $client;
    private $bucket;

    public function __construct()
    {
        $this->bucket = $_ENV['MINIO_BUCKET'];

        try {
            $this->client = new S3Client([
                'version' => 'latest',
                'region'  => $_ENV['MINIO_REGION'],
                'endpoint' => $_ENV['MINIO_ENDPOINT'],
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key'    => $_ENV['MINIO_KEY'],
                    'secret' => $_ENV['MINIO_SECRET'],
                ],
            ]);
        } catch (AwsException $e) {
            die("Backblaze Connection Error: " . $e->getMessage());
        }
    }

    // ============================
    // UPLOAD
    // ============================
    public function upload($key, $filePath)
    {
        return $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'SourceFile' => $filePath,
        ]);
    }

    // ============================
    // DELETE FILE
    // ============================
    public function delete($key)
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            return ['success' => true, 'message' => "Deleted: {$key}"];
        } catch (\Aws\S3\Exception\S3Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ============================
    // RENAME = COPY + DELETE
    // ============================
    public function rename($oldKey, $newKey)
    {
        $this->client->copyObject([
            'Bucket' => $this->bucket,
            'Key' => $newKey,
            'CopySource' => "{$this->bucket}/{$oldKey}",
        ]);

        $this->delete($oldKey);
        return true;
    }

    // ============================
    // COPY
    // ============================
    public function copy($sourceKey, $targetKey)
    {
        if (empty($sourceKey)) {
            throw new Exception("Source key kosong");
        }

        try {
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $targetKey,
                'CopySource' => "{$this->bucket}/{$sourceKey}",
            ]);
            return true;
        } catch (AwsException $e) {
            throw new Exception("Gagal copy {$sourceKey} → {$targetKey}: " . $e->getMessage());
        }
    }

    // ============================
    // LIST OBJECTS
    // ============================
    public function listObjects($prefix)
    {
        try {
            $objects = $this->client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => rtrim($prefix, '/') . '/'
            ]);

            $keys = [];
            if (!empty($objects['Contents'])) {
                foreach ($objects['Contents'] as $obj) {
                    $keys[] = $obj['Key'];
                }
            }

            return $keys;
        } catch (AwsException $e) {
            return [];
        }
    }

    // ============================
    // MAKE FOLDER SIMULASI PREFIX
    // ============================
    public function makeFolder($key)
    {
        $key = rtrim($key, '/') . '/';

        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => '',
            ]);
            return true;
        } catch (AwsException $e) {
            throw new Exception("Gagal membuat folder {$key}: " . $e->getMessage());
        }
    }

    // ============================
    // DELETE FOLDER
    // ============================
    public function deleteFolder($prefix)
    {
        $objects = $this->listObjects($prefix);
        foreach ($objects as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getBucket()
    {
        return $this->bucket;
    }


    // ==============================================================
    // DISABLED getUrl() karena bucket private — tetap dibuat agar tidak error
    // ==============================================================
    public function getUrl($key)
    {
        return null; // tidak digunakan lagi
    }

    // ==============================================================
    // PRESIGNED URL — Masih boleh, tapi tidak akan dipakai preview
    // ==============================================================
    public function getPresignedUrl($key, $expiry = '+5 minutes')
    {
        try {
            $cmd = $this->client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $request = $this->client->createPresignedRequest($cmd, $expiry);
            return (string) $request->getUri();
        } catch (AwsException $e) {
            return null;
        }
    }

    // ==============================================================
    // FUNGSI BARU — getObject() untuk preview via PHP Proxy
    // ==============================================================
    public function getObject($key)
    {
        try {
            return $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
        } catch (AwsException $e) {
            return null;
        }
    }

    // ============================
    // AMBIL ISI FILE UNTUK DOWNLOAD
    // ============================
    public function downloadObject($key)
    {
        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            // Ambil body sebagai string
            return (string) $result['Body'];
        } catch (Aws\S3\Exception\S3Exception $e) {
            throw new Exception("Gagal download file {$key}: " . $e->getMessage());
        }
    }
}
