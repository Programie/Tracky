<?php
namespace tracky;

use tracky\model\BaseEntity;
use tracky\model\Episode;
use tracky\model\Movie;
use tracky\model\MovieSet;
use tracky\model\Season;
use tracky\model\Show;

enum UserCollectionItemType: string
{
    case SHOW = "show";
    case SEASON = "season";
    case EPISODE = "episode";
    case MOVIE = "movie";
    case MOVIE_SET = "movieset";

    public static function fromEntity(BaseEntity $entity): ?self
    {
        $typeMap = [
            Show::class => self::SHOW,
            Season::class => self::SEASON,
            Episode::class => self::EPISODE,
            Movie::class => self::MOVIE,
            MovieSet::class => self::MOVIE_SET
        ];

        return $typeMap[$entity::class] ?? null;
    }
}
