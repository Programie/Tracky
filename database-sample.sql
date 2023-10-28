INSERT INTO `users` SET `username` = 'sample';

INSERT INTO `movies` SET `title` = 'Back to the Future', `year` = 1985;
INSERT INTO `movieviews` SET `user` = 1, `movie` = 1, `datetime` = DATE_SUB(NOW(), INTERVAL 1 MONTH);

INSERT INTO `shows` SET `title` = 'South Park';
INSERT INTO `episodes` SET `show` = 1, `season` = 20, `episode` = 1, `title` = 'Member Berries';
INSERT INTO `episodeviews` SET `user` = 1, `episode` = 1, `datetime` = DATE_SUB(NOW(), INTERVAL 1 DAY);