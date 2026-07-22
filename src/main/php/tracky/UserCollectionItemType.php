<?php
namespace tracky;

enum UserCollectionItemType: string
{
    case SHOW = "show";
    case SEASON = "season";
    case EPISODE = "episode";
    case MOVIE = "movie";
    case MOVIE_SET = "movieset";
}
