-- Password is "test"
INSERT INTO `users` SET `username` = 'sample', `password` = '$2y$13$OXdDHjQe3ufZhUrjVu5Pb.jh1X1Z/6Uy5fd.wlp/nkzjQDqh/MNDK';

INSERT INTO `movies` SET `title` = 'Back to the Future', `year` = 1985, `tmdbId` = 105;
INSERT INTO `movieviews` SET `user` = 1, `movie` = 1, `datetime` = DATE_SUB(NOW(), INTERVAL 1 MONTH);

INSERT INTO `shows` SET `title` = 'South Park', `tmdbId` = 2190;