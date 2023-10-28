CREATE TABLE `users`
(
    `id`       int(11)      NOT NULL AUTO_INCREMENT,
    `username` varchar(100) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `shows`
(
    `id`             int(11)      NOT NULL AUTO_INCREMENT,
    `title`          varchar(300) NOT NULL,
    `tmdbId`         int(11)      DEFAULT NULL,
    `posterImageUrl` varchar(500) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `episodes`
(
    `id`         int(11)      NOT NULL AUTO_INCREMENT,
    `show`       int(11)      NOT NULL,
    `season`     int(11)      NOT NULL,
    `episode`    int(11)      NOT NULL,
    `title`      varchar(300) NOT NULL,
    `firstAired` date DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`show`) REFERENCES `shows` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `episodeviews`
(
    `id`       int(11)  NOT NULL AUTO_INCREMENT,
    `user`     int(11)  NOT NULL,
    `episode`  int(11)  NOT NULL,
    `datetime` datetime NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`episode`) REFERENCES `episodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `movies`
(
    `id`             int(11)      NOT NULL AUTO_INCREMENT,
    `title`          varchar(300) NOT NULL,
    `year`           int(11)      DEFAULT NULL,
    `tmdbId`         int(11)      DEFAULT NULL,
    `posterImageUrl` varchar(500) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `movieviews`
(
    `id`       int(11)  NOT NULL AUTO_INCREMENT,
    `user`     int(11)  NOT NULL,
    `movie`    int(11)  NOT NULL,
    `datetime` datetime NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`movie`) REFERENCES `movies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;