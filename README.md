# Tracky

Track your watched movies and TV shows.

Open Source, your data, do whatever you want with it.

To scrobble watched movies and episodes from Kodi, you might want to check out [HTTP Scrobbler for Kodi](https://github.com/Programie/KodiAddon-HttpScrobbler) which is a compatible scrobble client for Tracky.

## Requirements

* Webserver with PHP 8.2 or newer
* MySQL/MariaDB server (tested with MariaDB 11)

## Installation

### Manual installation

Download the latest release and extract it on your webserver. Point the document root to the "public" folder.

### Docker setup

In case you want to deploy Tracky as a Docker container, you can use the Docker image `programie/tracky` from Docker Hub or `ghcr.io/programie/tracky` from GitHub Container Registry.

## Configuration

Create a `.env.local` file in the application root (i.e. the one containing this readme file). You may take a look at the [.env.sample](.env.sample) file for an example and some documentation about the available variables.

In case of the Docker setup, you may also specify the variables as environment variables for the container.

## Database

Create a database on your MySQL server and import the [database.sql](database.sql) file into it.

## Cronjobs

This application requires an external tool to execute scripts in a regular interval. On Linux, you might want to use crontab or systemd timers for that job.

The following commands should be executed:

* `bin/console update-data`: Fetches new seasons and episodes (also required to execute after adding a new TV show)
* `bin/console process-scrobble-queue`: Adds all items from the scrobble queue to the database (only required if scrobble queue is used)

## Getting started

After setting everything up, open the address of your webserver in your web browser and create a new account in the top right corner.

Now, you are ready to go and start tracking your watched TV show episodes and movies.

Happy tracking!