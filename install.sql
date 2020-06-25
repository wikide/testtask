CREATE TABLE `users` (
 `id` INT AUTO_INCREMENT,
 `login` VARCHAR(255) NOT NULL DEFAULT '',
 `fname` VARCHAR(255) NOT NULL DEFAULT '',
 `mname` VARCHAR(255) NOT NULL DEFAULT '',
 `lname` VARCHAR(255) NOT NULL DEFAULT '',
 `phone` VARCHAR(100) NOT NULL DEFAULT '',
 `instagram` VARCHAR(100) NOT NULL DEFAULT '',
 `email` VARCHAR(100) NOT NULL DEFAULT '',
 `address` VARCHAR(255) NOT NULL DEFAULT '',
 `password` VARCHAR(255) NOT NULL DEFAULT '',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `users` (`login`,`fname`,`mname`,`lname`,`phone`,`instagram`,`email`,`address`,`password`)
VALUES
('kirill','Кирилл','Владимирович','Гетманский','8-926-620-10-77', 'elonmusk', 'wikide@gmail.com', 'Moscow', MD5('123123'));


 CREATE TABLE `logs` (
 `id` INT AUTO_INCREMENT,
 `ip` VARCHAR(255) NOT NULL DEFAULT '',
 `ban` INT(1) NOT NULL DEFAULT 0,
 `failedto` INT(2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;