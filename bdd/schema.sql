SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1. Catégories (Géopolitique, Humanitaire, etc.)
CREATE TABLE categories (
    id  INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 2. Rôles (ADMIN, REDACTEUR, etc.)
CREATE TABLE roles (
    id     INT PRIMARY KEY AUTO_INCREMENT,
    code   VARCHAR(50) UNIQUE NOT NULL,
    niveau INT UNIQUE NOT NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE utilisateurs (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    pseudo       VARCHAR(50)  NOT NULL,
    email        VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    id_role      INT,
    FOREIGN KEY (id_role) REFERENCES roles(id) ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE articles (
    id               INT PRIMARY KEY AUTO_INCREMENT,
    titre            VARCHAR(255) NOT NULL,
    slug             VARCHAR(255) UNIQUE NOT NULL, -- URLs propres (ex: /guerre-iran-impact)
    contenu          LONGTEXT NOT NULL,
    date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_categorie     INT,
    FOREIGN KEY (id_categorie) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE articles_images (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    id_article INT,
    url_image  VARCHAR(255) NOT NULL,
    legende    VARCHAR(255),
    FOREIGN KEY (id_article) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 3. Sources (Officiel, Agence, Social Media)
CREATE TABLE type_sources (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    libelle     VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sources (
    id_type_source INT,
    id             INT PRIMARY KEY AUTO_INCREMENT,
    nom_source     VARCHAR(100) NOT NULL,
    url_source     VARCHAR(255),
    FOREIGN KEY (id_type_source) REFERENCES type_sources(id) ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE article_sources (
    id_article INT,
    id_source  INT,
    PRIMARY KEY (id_article, id_source),
    FOREIGN KEY (id_article) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (id_source)  REFERENCES sources(id)  ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evenements_chronologie (
    id                 INT PRIMARY KEY AUTO_INCREMENT,
    titre_evenement    VARCHAR(200),
    date_evenement     DATETIME NOT NULL,
    description_courte TEXT,
    id_article         INT,
    FOREIGN KEY (id_article) REFERENCES articles(id) ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- Accélérer l'affichage de la page d'accueil (tri par date)
CREATE INDEX idx_articles_date_pub ON articles(date_publication);

-- Accélérer l'accès à un article via son URL (SEO)
CREATE INDEX idx_articles_slug     ON articles(slug);

-- Accélérer les filtres par thématique (ex: menu "Guerre")
CREATE INDEX idx_articles_cat      ON articles(id_categorie);

-- Fil d'actualité en temps réel (Timeline)
CREATE INDEX idx_chrono_date       ON evenements_chronologie(date_evenement);

-- Sécuriser et accélérer la connexion des rédacteurs
CREATE INDEX idx_utilisateurs_email ON utilisateurs(email);