# Changelog

## [1.5.2] - 2026-01-24

* Fixed requiring TVDB api key if only using TMDB (and vice versa) - fixes #9
* Fixed throwing error when searching for shows using TMDB due to using wrong field in response

## [1.5.1] - 2025-11-13

* Fixed duplicate escaping of page title (e.g. & in show/movie title)

## [1.5.0] - 2025-11-13

* Fixed showing error on user history page if there are no items to show
* Show number of unwatched episodes on TV show pages
* Show percentage in TV show progress bars on profile page

## [1.4.0] - 2025-06-08

* Allow to filter history by date range

## [1.3.0] - 2025-05-18

* Show currently watching movie or episode on home page (if logged in) and on profile page (as reported in scrobble endpoint)

## [1.2.1] - 2025-04-21

* Fixed only adding first 500 episodes while fetching TV show data from TVDB

## [1.2.0] - 2025-02-12

* Add dropdown on movies view to sort them by title, release year, runtime, play count and date of last view

## [1.1.0] - 2024-08-31

* Show one episode per show to watch next on home page (if user is logged in)

## [1.0.1] - 2024-06-06

* Fixed throwing error on episode view in case previous or next season does not contain any episodes

## [1.0.0] - 2024-01-15

Initial release
