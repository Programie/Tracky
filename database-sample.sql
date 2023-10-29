INSERT INTO `users` SET `username` = 'sample';

INSERT INTO `movies` SET `title` = 'Back to the Future', `year` = 1985, `tmdbId` = 105;
INSERT INTO `movieviews` SET `user` = 1, `movie` = 1, `datetime` = DATE_SUB(NOW(), INTERVAL 1 MONTH);

INSERT INTO `shows` SET `title` = 'South Park', `tmdbId` = 2190;