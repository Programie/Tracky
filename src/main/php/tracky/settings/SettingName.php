<?php
namespace tracky\settings;

enum SettingName: string
{
    case LANGUAGE = "language";
    case OVERVIEW_MAX_EPISODES = "overviewMaxEpisodes";
    case OVERVIEW_MAX_MOVIES = "overviewMaxMovies";
    case OVERVIEW_MAX_NEXT_EPISODE_SHOWS = "overviewMaxNextEpisodeShows";
    case SHOWS_MAX_EPISODES = "showsMaxEpisodes";
    case HIDE_SHOWS = "hideShows";
    case PROFILE_HISTORY_ITEMS_PER_PAGE = "profileHistoryItemsPerPage";
    case PROFILE_HISTORY_MAX_PREVIOUS_NEXT_PAGES = "profileHistoryMaxPreviousNextPages";
}
