<?php
namespace tracky\dataprovider;

class Helper
{
    const TYPE_MOVIE = "movie";
    const TYPE_SHOW = "show";

    const PROVIDER_TMDB = "tmdb";
    const PROVIDER_TVDB = "tvdb";

    public function __construct(
        private readonly TMDB   $tmdb,
        private readonly TVDB   $tvdb,
        private readonly string $movieProvider,
        private readonly string $showProvider
    )
    {
    }

    public function getProviderNameByType(string $type): ?string
    {
        return match ($type) {
            self::TYPE_MOVIE => $this->movieProvider,
            self::TYPE_SHOW => $this->showProvider,
            default => null,
        };
    }

    public function getProviderByName(string $name): ?Provider
    {
        return match ($name) {
            self::PROVIDER_TMDB => $this->tmdb,
            self::PROVIDER_TVDB => $this->tvdb,
            default => null,
        };
    }

    public function getProviderByType(string $type): ?Provider
    {
        return $this->getProviderByName($this->getProviderNameByType($type));
    }
}