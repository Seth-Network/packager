CREATE TABLE `$prefix_packager_users` (
  `id`          INT(10)     NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(50) NULL     DEFAULT NULL,
  `active`      TINYINT     NULL     DEFAULT '0',
  `description` TEXT        NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT unique_name UNIQUE (`name`)
)
  COLLATE = 'utf8_general_ci'
  ENGINE = InnoDB;

CREATE TABLE `$prefix_packager_capacities` (
  `id`             INT(10)      NOT NULL AUTO_INCREMENT,
  `user_id`        INT(10)      NOT NULL,
  `package`        VARCHAR(150) NULL     DEFAULT NULL,
  `version`        VARCHAR(50)  NULL     DEFAULT NULL,
  `open_downloads` INT(10)      NOT NULL DEFAULT '0',
  `active`         TINYINT      NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `$prefix_package` (`package`, `version`, `user_id`),
  CONSTRAINT `$prefix_user` FOREIGN KEY (`user_id`) REFERENCES `$prefix_packager_users` (`id`)
    ON DELETE CASCADE
)
  COLLATE = 'utf8_general_ci'
  ENGINE = InnoDB;

CREATE TABLE `$prefix_packager_downloads` (
  `id`          INT(10)   NOT NULL AUTO_INCREMENT,
  `capacity_id` INT(10)   NULL     DEFAULT '0',
  `date`        DATETIME  NULL     DEFAULT CURRENT_TIMESTAMP,
  `package`     TEXT(150) NULL,
  `version`     TEXT(20)  NULL,
  `ip`          TEXT(20)  NULL,
  `host`        TEXT(50)  NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `$prefix_capacity` FOREIGN KEY (`capacity_id`) REFERENCES `$prefix_packager_capacities` (`id`)
    ON DELETE CASCADE
)
  COLLATE = 'utf8_general_ci'
  ENGINE = InnoDB;
