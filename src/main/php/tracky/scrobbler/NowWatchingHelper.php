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

    public function clear(DateTime $dateTime, User $user)
    {
        if (!is_file($this->getFilename($user))) {
            return;
        }

        $existingTimestamp = $this->getTimestamp($user);

        // Do not clear if existing timestamp is more recent
        if ($existingTimestamp !== null and $dateTime < $existingTimestamp) {
            return;
        }

        unlink($this->getFilename($user));
    }

    public function store(array $json, DateTime $dateTime, User $user)
    {
        $existingTimestamp = $this->getTimestamp($user);

        // Do not store if existing timestamp is more recent
        if ($existingTimestamp !== null and $dateTime < $existingTimestamp) {
            return;
        }

        $json["timestamp"] = $dateTime->format("c");

        file_put_contents($this->getFilename($user), json_encode($json));
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

    private function getTimestamp(User $user): DateTime|null
    {
        $filename = $this->getFilename($user);

        if (!is_file($filename)) {
            return null;
        }

        $json = json_decode(file_get_contents($filename), true);
        $timestamp = $json["timestamp"] ?? null;

        if ($timestamp === null) {
            return null;
        }

        return new DateTime($timestamp);
    }

    private function getFilename(User $user)
    {
        return sprintf("%s/%d.json", $this->storagePath, $user->getId());
    }
}
