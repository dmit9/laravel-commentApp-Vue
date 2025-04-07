-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema comment_app
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `comment_app` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `comment_app`;

-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `homepage` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `users_email_unique` (`email` ASC)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `comments`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `text` TEXT NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `comments_user_id_foreign` (`user_id` ASC),
  INDEX `comments_parent_id_foreign` (`parent_id` ASC),
  CONSTRAINT `comments_user_id_foreign`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `comments_parent_id_foreign`
    FOREIGN KEY (`parent_id`)
    REFERENCES `comments` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `captchas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `captchas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(255) NOT NULL,
  `text` VARCHAR(10) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `captchas_token_unique` (`token` ASC)
) ENGINE = InnoDB;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Comments
-- -----------------------------------------------------
/*
Таблица users:
- id: Уникальный идентификатор пользователя
- name: Имя пользователя (только латинские буквы и цифры)
- email: Email пользователя (уникальный)
- homepage: Домашняя страница (опционально)
- created_at: Дата создания записи
- updated_at: Дата обновления записи

Таблица comments:
- id: Уникальный идентификатор комментария
- user_id: Внешний ключ на пользователя
- parent_id: Внешний ключ на родительский комментарий (NULL для корневых комментариев)
- text: Текст комментария с поддержкой тегов (a, code, i, strong)
- image_path: Путь к изображению (опционально)
- created_at: Дата создания комментария
- updated_at: Дата обновления комментария

Таблица captchas:
- id: Уникальный идентификатор капчи
- token: Уникальный токен для идентификации капчи
- text: Текст капчи
- created_at: Дата создания капчи
- updated_at: Дата обновления капчи

Особенности:
1. Все таблицы используют InnoDB для поддержки внешних ключей
2. Установлены каскадные удаления для обеспечения целостности данных
3. Используется utf8mb4 для полной поддержки Unicode
4. Добавлены необходимые индексы для оптимизации запросов
*/ 