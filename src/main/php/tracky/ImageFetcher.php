<?php
namespace tracky;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ImageFetcher
{
    private Client $client;

    public function __construct(
        private readonly string          $storagePath,
        private readonly Filesystem      $filesystem,
        private readonly LoggerInterface $logger
    )
    {
        $this->client = new Client;
    }

    private function getFilePath(string $url): string
    {
        return $this->storagePath . DIRECTORY_SEPARATOR . hash("sha256", $url) . ".jpg";
    }

    private function download(string $url, string $path): bool
    {
        try {
            $this->filesystem->mkdir(dirname($path));

            $this->client->get($url, [
                RequestOptions::SINK => $path
            ]);

            return true;
        } catch (GuzzleException $exception) {
            $this->logger->error(sprintf("Download from URL '%s' to path '%s' failed: %s", $url, $path, $exception->getMessage()));

            return false;
        }
    }

    public function get(string $url): ?string
    {
        $path = $this->getFilePath($url);
        if ($this->filesystem->exists($path)) {
            return $path;
        }

        if ($this->download($url, $path)) {
            return $path;
        }

        return null;
    }
}