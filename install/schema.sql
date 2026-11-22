-- EPICK Tours database schema (MySQL 5.7+ / MariaDB 10.3+)

CREATE TABLE IF NOT EXISTS roles (
    id    TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug  VARCHAR(50)  NOT NULL UNIQUE,
    name  VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    role_id         TINYINT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(190) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    failed_logins   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL,
    last_login_at   DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    skey    VARCHAR(100) NOT NULL PRIMARY KEY,
    svalue  TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_blocks (
    page        VARCHAR(50)  NOT NULL,
    bkey        VARCHAR(100) NOT NULL,
    value       MEDIUMTEXT NULL,
    updated_by  INT UNSIGNED NULL,
    updated_at  DATETIME NULL,
    PRIMARY KEY (page, bkey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tours (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug          VARCHAR(150) NOT NULL UNIQUE,
    title         VARCHAR(150) NOT NULL,
    category      VARCHAR(50)  NOT NULL,
    days          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    nights        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    price         INT UNSIGNED NOT NULL DEFAULT 0,
    summary       TEXT NULL,
    image         VARCHAR(255) NOT NULL DEFAULT '',
    highlights    TEXT NULL,
    itinerary     MEDIUMTEXT NULL,
    inclusions    TEXT NULL,
    exclusions    TEXT NULL,
    hotels        TEXT NULL,
    destinations  TEXT NULL,
    is_featured   TINYINT(1) NOT NULL DEFAULT 0,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tour_images (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tour_id     INT UNSIGNED NOT NULL,
    path        VARCHAR(255) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_tour_images_tour FOREIGN KEY (tour_id) REFERENCES tours (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destinations (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(150) NOT NULL UNIQUE,
    name         VARCHAR(150) NOT NULL,
    description  TEXT NULL,
    image        VARCHAR(255) NOT NULL DEFAULT '',
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS experiences (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(150) NOT NULL,
    icon         VARCHAR(60)  NOT NULL DEFAULT 'fa-star',
    description  TEXT NULL,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS themes (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    icon           VARCHAR(60)  NOT NULL DEFAULT 'fa-star',
    link_category  VARCHAR(50)  NOT NULL DEFAULT '',
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    sort_order     INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiries (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    country       VARCHAR(100) NOT NULL DEFAULT '',
    arrival       DATE NULL,
    departure     DATE NULL,
    travellers    VARCHAR(10)  NOT NULL DEFAULT '',
    holiday_type  VARCHAR(50)  NOT NULL DEFAULT '',
    message       TEXT NULL,
    status        VARCHAR(20)  NOT NULL DEFAULT 'new',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_enquiries_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
