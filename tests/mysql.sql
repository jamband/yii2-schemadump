-- for some primary keys and unique keys
DROP TABLE IF EXISTS `0010_pk_ai`;
CREATE TABLE `0010_pk_ai` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
);
DROP TABLE IF EXISTS `0020_pk_not_ai`;
CREATE TABLE `0020_pk_not_ai` (
    `id` INT(11) NOT NULL,
    PRIMARY KEY (`id`)
);
DROP TABLE IF EXISTS `0030_pk_bigint_ai`;
CREATE TABLE `0030_pk_bigint_ai` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
);
DROP TABLE IF EXISTS `0040_pk_unsigned_ai`;
CREATE TABLE `0040_pk_unsigned_ai` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
);
DROP TABLE IF EXISTS `0050_pk_bigint_unsigned_ai`;
CREATE TABLE `0050_pk_bigint_unsigned_ai` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
);
DROP TABLE IF EXISTS `0060_composite_pks`;
CREATE TABLE `0060_composite_pks` (
    `foo_id` INT(11) NOT NULL,
    `bar_id` INT(11) NOT NULL,
    PRIMARY KEY (`foo_id`, `bar_id`)
);
DROP TABLE IF EXISTS `0070_uks`;
CREATE TABLE `0070_uks` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_0070_uks_username` (`username`),
    UNIQUE KEY `uk_0070_uks_email` (`email`)
);

-- for some types
DROP TABLE IF EXISTS `0100_types`;
CREATE TABLE `0100_types` (
    `char` CHAR(20) NOT NULL,
    `varchar` VARCHAR(20) NOT NULL,
    `text` TEXT NOT NULL,
    `smallint` SMALLINT(6) NOT NULL,
    `integer` INT(11) NOT NULL,
    `bigint` BIGINT(20) NOT NULL,
    `float` FLOAT NOT NULL,
    `float_decimal` FLOAT(20,10) NOT NULL,
    `double` DOUBLE(20,10) NOT NULL,
    `decimal` DECIMAL(20,10) NOT NULL,
    `money` DECIMAL(19,4) NOT NULL,
    `datetime` DATETIME NOT NULL,
    `timestamp` TIMESTAMP NOT NULL,
    `time` TIME NOT NULL,
    `date` DATE NOT NULL,
    `binary` BLOB NOT NULL,
    `boolean` BOOLEAN NOT NULL DEFAULT 0,
    `tinyint_1` TINYINT(1) NOT NULL DEFAULT 0,
    `enum` ENUM('foo', 'bar', 'baz') NOT NULL
);

-- for some default values
DROP TABLE IF EXISTS `0200_default_values`;
CREATE TABLE `0200_default_values` (
    `integer` SMALLINT(6) NOT NULL DEFAULT 1,
    `string` VARCHAR(255) NOT NULL DEFAULT 'UNKNOWN',
    `enum` ENUM ('foo', 'bar', 'baz') DEFAULT NULL,
    `enum_foo` ENUM ('foo', 'bar', 'baz') NOT NULL DEFAULT 'foo'
);

-- for some comments
DROP TABLE IF EXISTS `0300_comment`;
CREATE TABLE `0300_comment` (
    `username` VARCHAR(20) NOT NULL COMMENT 'ユーザ名',
    `enum` ENUM('foo', 'bar', 'baz') NOT NULL COMMENT 'foo'
);

-- for foreign keys
DROP TABLE IF EXISTS `0400_fk_parent`;
CREATE TABLE `0400_fk_parent` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
);
DROP TABLE IF EXISTS `0410_fk_child`;
CREATE TABLE `0410_fk_child` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `parent_id` INT(11) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`parent_id`) REFERENCES `0400_fk_parent` (`id`)
);
