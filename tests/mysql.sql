DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
    `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '主キー',
    `username` VARCHAR(255) NOT NULL COMMENT 'ユーザ名',
    `password` VARCHAR(255) NOT NULL COMMENT 'パスワード'
);

DROP TABLE IF EXISTS `post`;
CREATE TABLE `post` (
    `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `created_at` INT(11) NOT NULL,
    `updated_at` INT(11) NOT NULL
);
