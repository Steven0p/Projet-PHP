-- ============================================================
-- USFAH Mémoire & Soutenance Manager
-- Script de création de la base de données + données de démo
-- ============================================================

CREATE DATABASE IF NOT EXISTS usfah_soutenance
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE usfah_soutenance;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS corrections;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS defense_jury;
DROP TABLE IF EXISTS defenses;
DROP TABLE IF EXISTS theses;
DROP TABLE IF EXISTS jury_members;
DROP TABLE IF EXISTS supervisors;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS programs;
DROP TABLE IF EXISTS faculties;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- users
-- ------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    last_name     VARCHAR(100) NOT NULL,
    first_name    VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','responsable_academique') NOT NULL DEFAULT 'responsable_academique',
    statut        ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- faculties
-- ------------------------------------------------------------
CREATE TABLE faculties (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(150) NOT NULL,
    code         VARCHAR(20)  NOT NULL UNIQUE,
    description  TEXT NULL,
    responsable  VARCHAR(150) NULL,
    statut       ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- programs
-- ------------------------------------------------------------
CREATE TABLE programs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(150) NOT NULL,
    faculty_id  INT UNSIGNED NOT NULL,
    niveau      VARCHAR(50)  NULL,
    duree       VARCHAR(50)  NULL,
    type        ENUM('licence','diplome','maitrise') NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_programs_faculty FOREIGN KEY (faculty_id) REFERENCES faculties(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- students
-- ------------------------------------------------------------
CREATE TABLE students (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    matricule         VARCHAR(30)  NOT NULL UNIQUE,
    last_name         VARCHAR(100) NOT NULL,
    first_name        VARCHAR(100) NOT NULL,
    sexe              ENUM('M','F') NULL,
    date_naissance    DATE NULL,
    email             VARCHAR(150) NOT NULL UNIQUE,
    telephone         VARCHAR(30)  NULL,
    faculty_id        INT UNSIGNED NULL,
    program_id        INT UNSIGNED NULL,
    niveau            VARCHAR(50)  NULL,
    annee_academique  VARCHAR(20)  NOT NULL,
    statut            ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_faculty FOREIGN KEY (faculty_id) REFERENCES faculties(id),
    CONSTRAINT fk_students_program FOREIGN KEY (program_id) REFERENCES programs(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- supervisors (encadreurs)
-- ------------------------------------------------------------
CREATE TABLE supervisors (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    last_name    VARCHAR(100) NOT NULL,
    first_name   VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    telephone    VARCHAR(30)  NULL,
    specialite   VARCHAR(150) NULL,
    institution  VARCHAR(150) NULL,
    grade        VARCHAR(100) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- theses (mémoires)
-- ------------------------------------------------------------
CREATE TABLE theses (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id         INT UNSIGNED NOT NULL,
    titre              VARCHAR(255) NOT NULL,
    resume             TEXT NULL,
    domaine_recherche  VARCHAR(150) NULL,
    supervisor_id      INT UNSIGNED NULL,
    date_soumission    DATE NULL,
    annee_academique   VARCHAR(20) NOT NULL,
    statut             ENUM('en_preparation','soumis','valide','a_corriger','autorise_a_soutenir','soutenu')
                       NOT NULL DEFAULT 'en_preparation',
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_theses_student    FOREIGN KEY (student_id)    REFERENCES students(id),
    CONSTRAINT fk_theses_supervisor FOREIGN KEY (supervisor_id) REFERENCES supervisors(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- jury_members
-- ------------------------------------------------------------
CREATE TABLE jury_members (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    last_name    VARCHAR(100) NOT NULL,
    first_name   VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL,
    telephone    VARCHAR(30)  NULL,
    specialite   VARCHAR(150) NULL,
    institution  VARCHAR(150) NULL,
    fonction     VARCHAR(100) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- rooms (salles)
-- ------------------------------------------------------------
CREATE TABLE rooms (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom_numero     VARCHAR(50) NOT NULL,
    campus         VARCHAR(100) NULL,
    capacite       INT NULL,
    disponibilite  ENUM('disponible','indisponible') NOT NULL DEFAULT 'disponible',
    description    TEXT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- defenses (soutenances)
-- ------------------------------------------------------------
CREATE TABLE defenses (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thesis_id   INT UNSIGNED NOT NULL UNIQUE,
    date        DATE NOT NULL,
    heure       TIME NOT NULL,
    room_id     INT UNSIGNED NOT NULL,
    statut      ENUM('programmee','reportee','realisee','annulee') NOT NULL DEFAULT 'programmee',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_defenses_thesis FOREIGN KEY (thesis_id) REFERENCES theses(id),
    CONSTRAINT fk_defenses_room   FOREIGN KEY (room_id)   REFERENCES rooms(id),
    CONSTRAINT uq_room_slot UNIQUE (room_id, date, heure)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- defense_jury (table de jonction)
-- ------------------------------------------------------------
CREATE TABLE defense_jury (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    defense_id      INT UNSIGNED NOT NULL,
    jury_member_id  INT UNSIGNED NOT NULL,
    role            ENUM('president','examinateur','rapporteur') NOT NULL,
    CONSTRAINT fk_dj_defense FOREIGN KEY (defense_id)     REFERENCES defenses(id) ON DELETE CASCADE,
    CONSTRAINT fk_dj_jury    FOREIGN KEY (jury_member_id) REFERENCES jury_members(id),
    CONSTRAINT uq_defense_role   UNIQUE (defense_id, role),
    CONSTRAINT uq_defense_member UNIQUE (defense_id, jury_member_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- results
-- ------------------------------------------------------------
CREATE TABLE results (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    defense_id         INT UNSIGNED NOT NULL UNIQUE,
    note_finale        DECIMAL(5,2) NULL,
    mention            ENUM('passable','assez_bien','bien','tres_bien','excellent') NULL,
    decision           ENUM('admis','admis_avec_corrections','ajourne') NOT NULL,
    commentaires_jury  TEXT NULL,
    date_validation    DATE NULL,
    CONSTRAINT fk_results_defense FOREIGN KEY (defense_id) REFERENCES defenses(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- corrections
-- ------------------------------------------------------------
CREATE TABLE corrections (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thesis_id        INT UNSIGNED NOT NULL,
    description      TEXT NOT NULL,
    date_limite      DATE NULL,
    statut           ENUM('a_faire','en_cours','soumise','validee') NOT NULL DEFAULT 'a_faire',
    date_validation  DATE NULL,
    CONSTRAINT fk_corrections_thesis FOREIGN KEY (thesis_id) REFERENCES theses(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- activity_log (journal des activités des administrateurs, bonus)
-- ------------------------------------------------------------
CREATE TABLE activity_log (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NULL,
    user_name    VARCHAR(200) NOT NULL,
    action       ENUM('create','update','delete','login','logout') NOT NULL,
    entity_type  VARCHAR(50) NOT NULL,
    entity_id    INT UNSIGNED NULL,
    description  VARCHAR(255) NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Données de démonstration
-- ============================================================

-- Utilisateur admin par défaut : admin@usfah.edu / Admin123!
INSERT INTO users (last_name, first_name, email, password_hash, role, statut) VALUES
('Administrateur', 'Système', 'admin@usfah.edu', '$2y$12$TJDyBt272uUMSg3v5n.QoORaJhtiRmNxa9OQjJDXSCwtqkW7u7aaS', 'admin', 'actif'),
('Joseph', 'Marie', 'responsable@usfah.edu', '$2y$12$TJDyBt272uUMSg3v5n.QoORaJhtiRmNxa9OQjJDXSCwtqkW7u7aaS', 'responsable_academique', 'actif');

INSERT INTO faculties (nom, code, description, responsable) VALUES
('Sciences Informatiques', 'INFO', 'Faculté des sciences informatiques', 'Dr. Pierre Louis'),
('Sciences Infirmières', 'INFIRM', 'Faculté des sciences infirmières', 'Dr. Marie Claude'),
('Médecine', 'MED', 'Faculté de médecine', 'Dr. Jean Baptiste'),
('Sciences Juridiques', 'DROIT', 'Faculté des sciences juridiques', 'Me. Antoine Charles'),
('Gestion des Affaires', 'GESTION', 'Faculté de gestion des affaires', 'Dr. Nadège Pierre'),
('Sciences Comptables', 'COMPTA', 'Faculté des sciences comptables', 'Dr. Wilson Saint-Fleur');

INSERT INTO programs (nom, faculty_id, niveau, duree, type) VALUES
('Génie Logiciel', 1, 'Licence', '4 ans', 'licence'),
('Réseaux et Sécurité', 1, 'Licence', '4 ans', 'licence'),
('Soins Infirmiers', 2, 'Licence', '4 ans', 'licence'),
('Médecine Générale', 3, 'Doctorat', '7 ans', 'maitrise'),
('Droit Privé', 4, 'Licence', '4 ans', 'licence'),
('Administration des Affaires', 5, 'Licence', '4 ans', 'licence'),
('Comptabilité et Finance', 6, 'Licence', '4 ans', 'licence');

INSERT INTO students (matricule, last_name, first_name, sexe, date_naissance, email, telephone, faculty_id, program_id, niveau, annee_academique) VALUES
('USFAH-2022-001', 'Etienne', 'Sophia', 'F', '2000-03-14', 'sophia.etienne@usfah.edu', '+509 3701 1122', 1, 1, 'Licence 4', '2025-2026'),
('USFAH-2022-002', 'Dorcéus', 'Kenson', 'M', '1999-11-02', 'kenson.dorceus@usfah.edu', '+509 3701 1123', 1, 2, 'Licence 4', '2025-2026'),
('USFAH-2021-015', 'Michel', 'Anaïs', 'F', '1998-06-21', 'anais.michel@usfah.edu', '+509 3701 1124', 2, 3, 'Licence 4', '2025-2026'),
('USFAH-2020-034', 'Bélizaire', 'Frantz', 'M', '1997-09-09', 'frantz.belizaire@usfah.edu', '+509 3701 1125', 5, 5, 'Licence 4', '2025-2026');

INSERT INTO supervisors (last_name, first_name, email, telephone, specialite, institution, grade) VALUES
('Louis', 'Pierre', 'pierre.louis@usfah.edu', '+509 3701 2001', 'Génie Logiciel', 'USFAH', 'Professeur'),
('Claude', 'Marie', 'marie.claude@usfah.edu', '+509 3701 2002', 'Soins Infirmiers', 'USFAH', 'Maître de conférences'),
('Charles', 'Antoine', 'antoine.charles@usfah.edu', '+509 3701 2003', 'Droit des Affaires', 'USFAH', 'Professeur');

INSERT INTO jury_members (last_name, first_name, email, telephone, specialite, institution, fonction) VALUES
('Saint-Fleur', 'Wilson', 'wilson.saintfleur@usfah.edu', '+509 3701 3001', 'Comptabilité', 'USFAH', 'Professeur'),
('Pierre', 'Nadège', 'nadege.pierre@usfah.edu', '+509 3701 3002', 'Gestion', 'USFAH', 'Maître de conférences'),
('Baptiste', 'Jean', 'jean.baptiste@usfah.edu', '+509 3701 3003', 'Médecine', 'USFAH', 'Professeur'),
('Fils-Aimé', 'Rosemarie', 'rosemarie.filsaime@ext.edu', '+509 3701 3004', 'Informatique', 'Externe', 'Consultante');

INSERT INTO rooms (nom_numero, campus, capacite, disponibilite, description) VALUES
('Salle A101', 'Campus Principal', 30, 'disponible', 'Salle de soutenance équipée d\'un projecteur'),
('Salle B204', 'Campus Principal', 20, 'disponible', 'Petite salle de réunion'),
('Amphithéâtre 1', 'Campus Principal', 100, 'disponible', 'Grand amphithéâtre pour soutenances publiques');

INSERT INTO theses (student_id, titre, resume, domaine_recherche, supervisor_id, date_soumission, annee_academique, statut) VALUES
(1, 'Conception d\'une plateforme de gestion académique pour l\'USFAH', 'Étude et développement d\'un système web de gestion académique.', 'Génie Logiciel', 1, '2026-05-10', '2025-2026', 'autorise_a_soutenir'),
(2, 'Mise en place d\'une infrastructure réseau sécurisée pour campus universitaire', 'Analyse et implémentation de solutions de sécurité réseau.', 'Réseaux et Sécurité', 1, '2026-04-20', '2025-2026', 'valide'),
(3, 'Impact des protocoles de soins infirmiers sur la réduction des infections nosocomiales', 'Étude clinique sur les protocoles de soins.', 'Santé Publique', 2, '2026-03-15', '2025-2026', 'en_preparation');

INSERT INTO defenses (thesis_id, date, heure, room_id, statut) VALUES
(1, '2026-09-15', '09:00:00', 1, 'programmee');

INSERT INTO defense_jury (defense_id, jury_member_id, role) VALUES
(1, 4, 'president'),
(1, 1, 'examinateur'),
(1, 3, 'rapporteur');
