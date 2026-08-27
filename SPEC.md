# SPEC — USFAH Mémoire & Soutenance Manager

> Spécification technique dérivée du sujet officiel *"Projet Final — Programmation Web PHP Procédurale"* (Université Saint-François d'Assise d'Haïti). Ce document sert de référence de développement (schéma BD, structure de fichiers, règles métier) en complément du PDF original.

## 1. Contexte et objectif

Application web pour centraliser et automatiser la gestion des mémoires et soutenances universitaires : étudiants, facultés, programmes, mémoires, encadreurs, jurys, dates de soutenance, salles, résultats.

**Contrainte technique majeure : PHP procédural pur, sans framework (pas de MVC, pas de Laravel/Symfony/CodeIgniter).**

## 2. Stack obligatoire

- PHP 8.x procédural
- PDO (connexions + requêtes préparées uniquement)
- MySQL / MariaDB
- HTML5, CSS3
- Bootstrap 5 ou Tailwind CSS
- Sessions PHP natives
- JavaScript facultatif

## 3. Structure de dossiers

```
usfah-soutenance/
├── config/
│   ├── database.php
│   ├── auth.php
│   └── csrf.php
├── includes/
│   ├── header.php
│   ├── navbar.php
│   ├── sidebar.php
│   └── footer.php
├── auth/
│   ├── login.php
│   └── logout.php
├── dashboard/
│   └── index.php
├── students/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   ├── show.php
│   └── delete.php
├── faculties/        (même schéma CRUD que students/)
├── programs/          (même schéma CRUD)
├── supervisors/        (même schéma CRUD)
├── theses/            (même schéma CRUD)
├── jury-members/       (même schéma CRUD)
├── defenses/          (même schéma CRUD)
├── rooms/             (même schéma CRUD)
├── results/           (même schéma CRUD)
├── corrections/        (même schéma CRUD)
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── index.php
```

Chaque module CRUD (`students/`, `faculties/`, etc.) suit le même patron de 5 fichiers : `index.php` (liste + recherche/filtres), `create.php`, `edit.php`, `show.php`, `delete.php` (avec confirmation).

## 4. Modèle de données

Toutes les FK utilisent `ON DELETE RESTRICT` par défaut (empêcher la suppression accidentelle de données référencées) sauf indication contraire.

### `users`
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| last_name | VARCHAR(100) | NOT NULL |
| first_name | VARCHAR(100) | NOT NULL |
| email | VARCHAR(150) | NOT NULL, UNIQUE |
| password_hash | VARCHAR(255) | NOT NULL — `password_hash()` |
| role | ENUM('admin','responsable_academique') | NOT NULL |
| statut | ENUM('actif','inactif') | NOT NULL DEFAULT 'actif' |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP |

### `faculties`
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| nom | VARCHAR(150) | NOT NULL |
| code | VARCHAR(20) | NOT NULL, UNIQUE |
| description | TEXT | NULL |
| responsable | VARCHAR(150) | NULL |
| statut | ENUM('actif','inactif') | DEFAULT 'actif' |

### `programs`
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| nom | VARCHAR(150) | NOT NULL |
| faculty_id | INT UNSIGNED | FK → faculties.id, NOT NULL |
| niveau | VARCHAR(50) | NULL |
| duree | VARCHAR(50) | NULL |
| type | ENUM('licence','diplome','maitrise') | NOT NULL |

### `students`
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| matricule | VARCHAR(30) | NOT NULL, UNIQUE |
| last_name | VARCHAR(100) | NOT NULL |
| first_name | VARCHAR(100) | NOT NULL |
| sexe | ENUM('M','F') | NULL |
| date_naissance | DATE | NULL |
| email | VARCHAR(150) | NOT NULL, UNIQUE |
| telephone | VARCHAR(30) | NULL |
| faculty_id | INT UNSIGNED | FK → faculties.id |
| program_id | INT UNSIGNED | FK → programs.id |
| niveau | VARCHAR(50) | NULL |
| annee_academique | VARCHAR(20) | NOT NULL |
| statut | ENUM('actif','inactif') | DEFAULT 'actif' |

### `supervisors` (encadreurs)
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| last_name | VARCHAR(100) | NOT NULL |
| first_name | VARCHAR(100) | NOT NULL |
| email | VARCHAR(150) | NOT NULL, UNIQUE |
| telephone | VARCHAR(30) | NULL |
| specialite | VARCHAR(150) | NULL |
| institution | VARCHAR(150) | NULL |
| grade | VARCHAR(100) | NULL |

### `theses` (mémoires)
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| student_id | INT UNSIGNED | FK → students.id, NOT NULL |
| titre | VARCHAR(255) | NOT NULL |
| resume | TEXT | NULL |
| domaine_recherche | VARCHAR(150) | NULL |
| supervisor_id | INT UNSIGNED | FK → supervisors.id |
| date_soumission | DATE | NULL |
| annee_academique | VARCHAR(20) | NOT NULL |
| statut | ENUM('en_preparation','soumis','valide','a_corriger','autorise_a_soutenir','soutenu') | DEFAULT 'en_preparation' |

**Règle métier :** un étudiant ne peut avoir qu'un seul mémoire *actif* (statut ≠ soutenu) à la fois — vérifier en appli avant tout INSERT.

### `jury_members`
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| last_name | VARCHAR(100) | NOT NULL |
| first_name | VARCHAR(100) | NOT NULL |
| email | VARCHAR(150) | NOT NULL |
| telephone | VARCHAR(30) | NULL |
| specialite | VARCHAR(150) | NULL |
| institution | VARCHAR(150) | NULL |
| fonction | VARCHAR(100) | NULL — fonction habituelle, informative |

### `rooms` (salles)
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| nom_numero | VARCHAR(50) | NOT NULL |
| campus | VARCHAR(100) | NULL |
| capacite | INT | NULL |
| disponibilite | ENUM('disponible','indisponible') | DEFAULT 'disponible' |
| description | TEXT | NULL |

### `defenses` (soutenances)
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| thesis_id | INT UNSIGNED | FK → theses.id, NOT NULL, UNIQUE |
| date | DATE | NOT NULL |
| heure | TIME | NOT NULL |
| room_id | INT UNSIGNED | FK → rooms.id, NOT NULL |
| statut | ENUM('programmee','reportee','realisee','annulee') | DEFAULT 'programmee' |

**Contrainte d'intégrité :** `UNIQUE(room_id, date, heure)` — empêche deux soutenances dans la même salle au même créneau (à renforcer aussi côté application avec message clair).

### `defense_jury` (table de jonction)
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| defense_id | INT UNSIGNED | FK → defenses.id, NOT NULL |
| jury_member_id | INT UNSIGNED | FK → jury_members.id, NOT NULL |
| role | ENUM('president','examinateur','rapporteur') | NOT NULL |

Contraintes : `UNIQUE(defense_id, role)` (un seul président/examinateur/rapporteur par soutenance) et `UNIQUE(defense_id, jury_member_id)` (une personne ne peut pas cumuler deux rôles sur la même soutenance).

### `results`
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| defense_id | INT UNSIGNED | FK → defenses.id, NOT NULL, UNIQUE |
| note_finale | DECIMAL(5,2) | NULL |
| mention | ENUM('passable','assez_bien','bien','tres_bien','excellent') | NULL |
| decision | ENUM('admis','admis_avec_corrections','ajourne') | NOT NULL |
| commentaires_jury | TEXT | NULL |
| date_validation | DATE | NULL |

**Règle métier :** impossible d'enregistrer un résultat si `defenses.statut != 'realisee'` — à vérifier côté serveur avant l'INSERT.

### `corrections`
| Colonne | Type | Contraintes |
|---|---|---|
| id | INT UNSIGNED | PK |
| thesis_id | INT UNSIGNED | FK → theses.id, NOT NULL |
| description | TEXT | NOT NULL |
| date_limite | DATE | NULL |
| statut | ENUM('a_faire','en_cours','soumise','validee') | DEFAULT 'a_faire' |
| date_validation | DATE | NULL |

## 5. Modules fonctionnels

### 5.1 Authentification (`auth/`)
- Connexion / déconnexion / gestion de session
- CRUD utilisateurs (création, modification, activation/désactivation) — réservé au rôle admin
- Rôles minimum : `admin`, `responsable_academique`
- `password_hash()` à l'écriture, `password_verify()` à la connexion
- Toute page hors `auth/` doit vérifier la session et rediriger si non connecté

### 5.2 Facultés, Programmes, Étudiants, Encadreurs, Jurys, Salles, Corrections
CRUD complet standard pour chacun (voir §3 pour le patron de fichiers). Voir §4 pour les champs exacts de chaque table.

- **Étudiants** : recherche par matricule, recherche par nom, filtre par faculté, filtre par programme.
- **Programmes** : chaque programme doit être rattaché à une faculté (select obligatoire au formulaire).

### 5.3 Mémoires (`theses/`)
CRUD complet. Formulaire lie un étudiant, un encadreur, gère le statut (`en_preparation` → `soumis` → `valide`/`a_corriger` → `autorise_a_soutenir` → `soutenu`). Bloquer la création si l'étudiant a déjà un mémoire actif.

### 5.4 Soutenances (`defenses/`)
CRUD complet, liée obligatoirement à un mémoire. Le formulaire assigne date, heure, salle, et les 3 rôles de jury (président, examinateur, rapporteur) via `defense_jury`. Vérifier la disponibilité de la salle avant l'enregistrement.

### 5.5 Résultats (`results/`)
CRUD complet, liée à une soutenance réalisée uniquement.

### 5.6 Tableau de bord (`dashboard/index.php`)
Après connexion, afficher au minimum :
- Nombre total d'étudiants
- Nombre de mémoires enregistrés / en préparation / autorisés à soutenir
- Nombre de soutenances programmées
- Soutenances du jour / du mois
- Nombre de soutenances réalisées
- Nombre d'étudiants admis

### 5.7 Recherche et filtres
Recherche sur : étudiant, mémoire, soutenance, encadreur, membre de jury.
Filtres transversaux minimum : faculté, programme, année académique, statut, date.

### 5.8 Notifications (bonus)
À la programmation d'une soutenance, envoi d'un email à l'étudiant avec titre du mémoire, date, heure, salle, composition du jury. Intégration Resend autorisée en bonus.

## 6. Contraintes d'intégrité à faire respecter par l'application

- Un même matricule ne peut pas être utilisé par deux étudiants (`UNIQUE`).
- Un même email ne peut pas être utilisé par plusieurs utilisateurs (`UNIQUE`).
- Deux soutenances ne peuvent pas être programmées dans la même salle au même moment.
- Impossible d'enregistrer un résultat pour une soutenance non réalisée.
- Toute suppression de données académiques (étudiant, mémoire, soutenance...) doit demander confirmation explicite.

## 7. Sécurité (obligatoire)

- Connexion PDO exclusivement, requêtes préparées partout — **aucune requête SQL ne doit concaténer directement `$_POST`/`$_GET`**
- Validation des données côté serveur (jamais confiance au client seul)
- `htmlspecialchars()` systématique lors de l'affichage de données utilisateur
- `password_hash()` / `password_verify()` pour les mots de passe
- Sessions PHP pour l'état de connexion
- Protection de toutes les pages privées (redirection si non authentifié)
- Vérification des rôles avant chaque action sensible (ex : seul un admin gère les utilisateurs)
- Protection CSRF (token) sur tous les formulaires de modification/suppression — voir `config/csrf.php`

## 8. Livrables obligatoires

1. Code source complet
2. Script SQL de création de la base de données
3. Données de démonstration (seed)
4. `README.md`
5. Rapport de projet
6. Schéma de la base de données (diagramme ER)
7. Présentation orale avec démonstration fonctionnelle

## 9. Présentation finale (20 min)

- 5 min : présentation du problème et de la solution
- 10 min : démonstration
- 5 min : questions de l'enseignant
- Chaque membre du groupe doit pouvoir expliquer une partie du code.

## 10. Barème — /100

| Critère | Points |
|---|---|
| Fonctionnalités et CRUD | 25 |
| Base de données et relations | 15 |
| PHP procédural et PDO | 15 |
| Authentification et sessions | 10 |
| Sécurité | 15 |
| Interface utilisateur | 10 |
| Qualité et organisation du code | 5 |
| Rapport et présentation | 5 |

## 11. Bonus (max +10 points)

- Intégration email Resend
- Génération de fiche de soutenance en PDF
- Export Excel/CSV
- Statistiques graphiques
- Impression du procès-verbal de soutenance
- Pagination
- Journal des activités des administrateurs (audit log)

## 12. Résultat attendu

L'application doit permettre de suivre le parcours d'un mémoire depuis son enregistrement jusqu'à la soutenance et à la publication du résultat final, avec une organisation claire, sécurisée et exploitable par les responsables académiques.
