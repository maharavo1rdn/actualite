USE info_actualite;

-- Catégories de base
INSERT INTO categories (nom, description) VALUES
('Géopolitique', 'Nouvelles et analyses géopolitiques'),
('Économie', 'Informations économiques et financières'),
('Climat', 'Évènements climatiques et environnementaux');

-- Roles de base
INSERT INTO roles (code, niveau) VALUES
('ADMIN', 10),
('REDACTEUR', 5),
('EMPLOYE', 1);

-- Utilisateurs de test
INSERT INTO utilisateurs (pseudo, email, mot_de_passe, id_role) VALUES
('admin', 'admin@gmail.com', '$2y$10$PUjODAoDM53ay9Es4Br4I.lC86ny19b452rmUe5Nw3nias.8Mgify', 1),
('sophie_roux', 'sophie.roux@gmail.com', '$2y$10$0Kf/ARBU8U.Li1nvlukjLuLJoigOF5/in6kdtJnDhaGJWKu.xEMr6', 2),
('luc_martin', 'luc.martin@gmail.com', '$2y$10$zYrrVgWxK6oAakdTzyx8D.lPEtnE7bhrlZyh/F6zMJCnLgkVQ4Uz2', 3);

-- Types de sources
INSERT INTO type_sources (libelle, description) VALUES
('Officiel', 'Source gouvernementale ou institutionnelle'),
('Média', 'Source de presse ou agence de presse'),
('Document', 'Rapport ou document officiel');

-- Sources d’exemple
INSERT INTO sources (nom_source, url_source, id_type_source) VALUES
('Agence France-Presse', 'https://www.afp.com', 2),
('Al Jazeera', 'https://www.aljazeera.com', 2),
('ONU', 'https://www.un.org', 1);
