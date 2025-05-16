<?php
namespace tracky\scrobbler;

use DateTime;
use tracky\model\User;

class NowWatchingHelper
{
    public function __construct(
        private readonly string $storagePath,
        private readonly int $maxAge,
    ) {
        if (!is_dir($storagePath)) {
            mkdir($storagePath);
        }
    }

    public function store(array $json, User $user)
    {
        if (isset($json["timestamp"])) {
            $timestamp = new DateTime($json["timestamp"]);
        } else {
            $timestamp = new DateTime;
            $json["timestamp"] = $timestamp->format("c");
        }

        $filename = $this->getFilename($user);

        if (is_file($filename)) {
            $existingJson = json_decode(file_get_contents($filename), true);
            $existingTimestamp = $existingJson["timestamp"] ?? null;

            // Ignore current store request if existing file is more recent
            if ($existingTimestamp !== null and $timestamp < new DateTime($existingTimestamp)) {
                return;
            }
        }

        file_put_contents($filename, json_encode($json));
    }

    public function get(User $user)
    {
        $filename = $this->getFilename($user);

        if (!is_file($filename)) {
            return null;
        }

        $json = json_decode(file_get_contents($filename), true);

        if ((new DateTime)->getTimestamp() - (new DateTime($json["timestamp"]))->getTimestamp() > $this->maxAge) {
            return null;
        }

        $totalTime = $json["duration"] ?? null;
        $currentTime = $json["progress"]["time"] ?? null;

        if ($totalTime === null or $currentTime === null) {
            return null;
        }

        $json["progress"]["percent"] = (int) ($json["progress"]["time"] / $json["duration"] * 100);

        return $json;
    }

    private function getFilename(User $user)
    {
        return sprintf("%s/%d.json", $this->storagePath, $user->getId());
    }
}
