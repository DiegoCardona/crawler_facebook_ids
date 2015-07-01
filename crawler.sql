CREATE DATABASE `facebook_ids` /*!40100 DEFAULT CHARACTER SET utf8 */$$

USE facebook_ids;

CREATE TABLE `collection` (
  `id` int(11) NOT NULL,
  `checked` binary(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8$$


CREATE TABLE `urls` (
  `id` varchar(255) NOT NULL,
  `checked` binary(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8$$

