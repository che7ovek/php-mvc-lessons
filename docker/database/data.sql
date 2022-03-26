DROP TABLE IF EXISTS `dockerSample`;

CREATE TABLE `dockerSample` (
  `name` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `dockerSample` WRITE;
INSERT INTO `dockerSample` VALUES ('George'),('Sam'),('Kathy');
UNLOCK TABLES;
