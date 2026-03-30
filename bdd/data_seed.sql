USE info_actualite;

-- Catégories de base
INSERT INTO categories (nom, description) VALUES
('Géopolitique', 'Nouvelles et analyses géopolitiques'),
('Humanitaire', 'Suivi des populations civiles et de l aide internationale'),
('Économie', 'Informations économiques et financières'),
('Climat', 'Évènements climatiques et environnementaux');

-- Roles de base
INSERT INTO roles (code, niveau) VALUES
('ADMIN', 10),
('REDACTEUR', 5),
('EMPLOYE', 1);

-- Utilisateurs de test
-- Mots de passe en clair pour référence :
-- admin  -> adminpass
-- sophie -> redacteurpass
-- luc   -> lecteurpass
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
('ONU', 'https://www.un.org', 1),
('Banque mondiale', 'https://www.worldbank.org', 1),
('Organisation maritime internationale', 'https://www.imo.org', 1);

-- Articles de demonstration
INSERT INTO articles (titre, slug, contenu, date_publication, id_categorie) VALUES
('<h1>Etat d alerte dans le Golfe persan</h1>', 'etat-d-alerte-dans-le-golfe-persan', '<p>Les autorites regionales ont releve le niveau d alerte maritime apres plusieurs incidents signales dans les couloirs energetiques.</p><p>Les assurances de fret ont augmente en quelques heures, ce qui fragilise les routes commerciales vers l Europe et l Asie.</p><p>Les analystes insistent sur la necessite d un canal diplomatique permanent pour eviter un blocage durable.</p>', '2026-03-28 14:00:00', 1),
('<h1>Couloirs humanitaires sous pression dans le sud</h1>', 'couloirs-humanitaires-sous-pression-sud', '<p>Les equipes medicales signalent une saturation des postes de soins de premiere urgence.</p><p>Plusieurs ONG demandent la securisation des acces pour evacuer les blesses les plus graves.</p><p>Des convois supplementaires sont annonces pour la nuit.</p>', '2026-03-28 11:30:00', 2),
('<h1>Le detroit d Ormuz sous surveillance renforcee</h1>', 'detroit-ormuz-surveillance-renforcee', '<p>Le detroit d Ormuz reste un point de passage vital pour les hydrocarbures.</p><p>Les armateurs adaptent leurs itineraires et renegocient les fenetres d escorte.</p><p>Les operateurs craignent un impact direct sur les couts d approvisionnement.</p>', '2026-03-28 09:15:00', 1),
('<h1>Le prix du baril franchit un nouveau seuil</h1>', 'prix-baril-franchit-nouveau-seuil', '<p>La volatilite des marches energétiques se maintient a un niveau eleve.</p><p>Les importateurs asiatiques constituent des stocks tampons pour limiter les ruptures.</p><p>Les banques centrales surveillent les effets inflationnistes secondaires.</p>', '2026-03-27 18:20:00', 3),
('<h1>Declaration conjointe de plusieurs chancelleries</h1>', 'declaration-conjointe-chancelleries', '<p>Un appel commun a la desescalade a ete publie apres une reunion d urgence.</p><p>Le texte souligne le respect du droit maritime et la protection des civils.</p><p>Une nouvelle sequence de discussions est annoncee pour les prochains jours.</p>', '2026-03-27 15:10:00', 1),
('<h1>Ports regionaux: trafic ralenti mais maintenu</h1>', 'ports-regionaux-trafic-ralenti-maintenu', '<p>Les autorites portuaires confirment une baisse du rythme de chargement.</p><p>Les navires prioritaires sont diriges vers des fenetres securisees.</p><p>Le secteur logistique anticipe une normalisation progressive si la tension baisse.</p>', '2026-03-27 12:00:00', 3),
('<h1>Bilan provisoire des aides distribuees</h1>', 'bilan-provisoire-aides-distribuees', '<p>Les agences sur le terrain annoncent une hausse des distributions alimentaires.</p><p>Les besoins en eau potable et en materiel sanitaire restent critiques.</p><p>La coordination inter-organisations devient la priorite des prochaines 48 heures.</p>', '2026-03-27 08:45:00', 2),
('<h1>Risques climatiques et conditions de navigation</h1>', 'risques-climatiques-conditions-navigation', '<p>Des vents saisonniers irreguliers compliquent les manoeuvres dans certaines zones.</p><p>Les capitaineries renforcent les consignes de securite en approche de chenal.</p><p>Les equipages recoivent des bulletins meteo a frequence rapprochee.</p>', '2026-03-26 17:25:00', 4);

-- Images des articles
INSERT INTO articles_images (id_article, url_image, legende) VALUES
(1, '/assets/images/photo-1541872703-74c5e44368f9.jpeg', 'Navires militaires dans le Golfe a la tombee du jour'),
(2, '/assets/images/photo-1495020689067-958852a7765e.jpeg', 'Equipe medicale mobile sur un point de passage frontalier'),
(3, '/assets/images/photo-1504711434969-e33886168f5c.jpeg', 'Convoi commercial escorté dans un couloir maritime'),
(4, '/assets/images/photo-1569025743873-ea3a9ade89f9.jpeg', 'Ecrans de suivi des prix de l energie'),
(5, '/assets/images/photo-1454165804606-c3d57bc86b40.jpeg', 'Brief diplomatique avec representants internationaux'),
(6, '/assets/images/photo-1586528116311-ad8dd3c8310d.jpeg', 'Vue aerienne d un terminal portuaire actif'),
(7, '/assets/images/photo-1469571486292-b53601020f90.jpeg', 'Distribution de kits de premiere necessite'),
(8, '/assets/images/photo-1500375592092-40eb2168fd21.jpeg', 'Mer agitee et conditions de navigation degradees');

-- Liens articles-sources
INSERT INTO article_sources (id_article, id_source) VALUES
(1, 1), (1, 3), (1, 5),
(2, 3), (2, 2),
(3, 1), (3, 5),
(4, 4), (4, 1),
(5, 3), (5, 2),
(6, 5), (6, 1),
(7, 3), (7, 2),
(8, 5), (8, 2);

-- Evenements de chronologie (Live tracker)
INSERT INTO evenements_chronologie (titre_evenement, date_evenement, description_courte, id_article) VALUES
('<h4>Alerte navale renforcee</h4>', '2026-03-28 14:20:00', '<p>Des patrouilles supplementaires sont signalees a l entree du detroit.</p>', 1),
('<h4>Point de situation humanitaire</h4>', '2026-03-28 12:05:00', '<p>Des couloirs de ravitaillement restent ouverts sous escorte.</p>', 2),
('<h4>Briefing des armateurs</h4>', '2026-03-28 10:50:00', '<p>Les compagnies ajustent leurs plans de transit pour les prochaines 24 heures.</p>', 3),
('<h4>Tension sur les primes d assurance</h4>', '2026-03-28 09:30:00', '<p>Les couts de couverture maritime progressent sur la matinee.</p>', 4),
('<h4>Declaration diplomatique conjointe</h4>', '2026-03-27 15:25:00', '<p>Un appel formel a la desescalade a ete publie par plusieurs capitales.</p>', 5),
('<h4>Rythme portuaire ralenti</h4>', '2026-03-27 12:15:00', '<p>Les chargements prioritaires sont maintenus malgre des delais supplementaires.</p>', 6),
('<h4>Renfort medical en transit</h4>', '2026-03-27 09:10:00', '<p>Un nouveau convoi sanitaire a pris la route au lever du jour.</p>', 7),
('<h4>Avis meteo maritime special</h4>', '2026-03-26 17:40:00', '<p>Des rafales soutenues sont prevues sur plusieurs secteurs de navigation.</p>', 8),
('<h4>Reunion de crise logistique</h4>', '2026-03-26 14:00:00', '<p>Les operateurs harmonisent les protocoles de surete entre terminaux.</p>', 6),
('<h4>Mise a jour des routes alternatives</h4>', '2026-03-26 11:25:00', '<p>Deux corridors secondaires sont proposes pour limiter les congestions.</p>', 3),
('<h4>Suivi des stocks energetiques</h4>', '2026-03-26 08:30:00', '<p>Les reserves stratégiques sont mobilisees dans plusieurs pays importateurs.</p>', 4),
('<h4>Evaluation terrain des ONG</h4>', '2026-03-25 18:00:00', '<p>Les besoins prioritaires concernent l eau, les soins et l hebergement d urgence.</p>', 2);
