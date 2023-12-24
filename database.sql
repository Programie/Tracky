CREATE TABLE `users`
(
    `id`       int(11)      NOT NULL AUTO_INCREMENT,
    `username` varchar(100) NOT NULL,
    `password` varchar(200) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX (`username`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `shows`
(
    `id`             int(11)      NOT NULL AUTO_INCREMENT,
    `title`          varchar(300) NOT NULL,
    `dataProvider`   enum ('tmdb', 'tvdb')                    DEFAULT NULL,
    `tmdbId`         int(11)                                  DEFAULT NULL,
    `tvdbId`         int(11)                                  DEFAULT NULL,
    `posterImageUrl` varchar(500)                             DEFAULT NULL,
    `language`       varchar(10)                              DEFAULT NULL,
    `status`         enum ('upcoming', 'continuing', 'ended') DEFAULT NULL,
    `lastUpdate`     datetime                                 DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `seasons`
(
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `show`           int(11) NOT NULL,
    `number`         int(11) NOT NULL,
    `posterImageUrl` varchar(500) DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`show`) REFERENCES `shows` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `episodes`
(
    `id`             int(11)      NOT NULL AUTO_INCREMENT,
    `season`         int(11)      NOT NULL,
    `number`         int(11)      NOT NULL,
    `title`          varchar(300) NOT NULL,
    `plot`           text         DEFAULT NULL,
    `firstAired`     date         DEFAULT NULL,
    `runtime`        int(11)      DEFAULT NULL,
    `posterImageUrl` varchar(500) DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`season`) REFERENCES `seasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `movies`
(
    `id`             int(11)      NOT NULL AUTO_INCREMENT,
    `title`          varchar(300) NOT NULL,
    `plot`           text                  DEFAULT NULL,
    `year`           int(11)               DEFAULT NULL,
    `runtime`        int(11)               DEFAULT NULL,
    `dataProvider`   enum ('tmdb', 'tvdb') DEFAULT NULL,
    `tmdbId`         int(11)               DEFAULT NULL,
    `tvdbId`         int(11)               DEFAULT NULL,
    `posterImageUrl` varchar(500)          DEFAULT NULL,
    `language`       varchar(10)           DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `views`
(
    `id`       int(11)  NOT NULL AUTO_INCREMENT,
    `user`     int(11)  NOT NULL,
    `type`     enum ('episode', 'movie'),
    `item`     int(11)  NOT NULL,
    `datetime` datetime NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `scrobblequeue`
(
    `id`       int(11)  NOT NULL AUTO_INCREMENT,
    `user`     int(11)  NOT NULL,
    `json`     json     NOT NULL,
    `datetime` datetime NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;