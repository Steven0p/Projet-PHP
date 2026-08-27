# USFAH Mémoire & Soutenance Manager

Application web de gestion des mémoires, soutenances et jurys universitaires, développée en **PHP 8 procédural** (sans framework) pour l'Université Saint-François d'Assise d'Haïti (USFAH), dans le cadre du projet final de programmation web.

Voir [`../SPEC.md`](../SPEC.md) pour la spécification technique complète du projet.

## Stack technique

- PHP 8.x procédural + PDO (requêtes préparées)
- MySQL / MariaDB
- Bootstrap 5 + Bootstrap Icons (via CDN)
- Sessions PHP natives
- JavaScript vanilla (confirmations, filtres dynamiques)

## Installation

### Prérequis

- PHP 8.1 ou supérieur avec l'extension `pdo_mysql`
- MySQL ou MariaDB
- Un serveur web (Apache, ou le serveur intégré de PHP)

### Étapes

1. **Cloner / copier le projet** dans le répertoire de votre serveur web (ex: `htdocs/` pour XAMPP/WAMP).

2. **Créer la base de données** en important le script SQL :

   ```bash
   mysql -u root -p < database.sql
   ```

   Ce script crée la base `usfah_soutenance`, toutes les tables, et insère des données de démonstration (facultés, programmes, étudiants, encadreurs, membres de jury, salles, un mémoire et une soutenance d'exemple).

3. **Configurer la connexion à la base de données** dans [`config/database.php`](config/database.php) : ajustez `$db_host`, `$db_name`, `$db_user`, `$db_pass` selon votre environnement.

   Par défaut, le fichier est configuré pour `root` sans mot de passe (valeurs par défaut classiques de XAMPP/WAMP/MAMP). En production ou sur un serveur partagé, préférez un utilisateur dédié avec des droits limités à cette base :

   ```sql
   CREATE USER 'usfah_app'@'localhost' IDENTIFIED BY 'un_mot_de_passe_fort';
   GRANT ALL PRIVILEGES ON usfah_soutenance.* TO 'usfah_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

   ⚠️ Ne committez jamais de vrais identifiants de base de données dans `config/database.php` avant de pousser sur un dépôt public — utilisez toujours des valeurs génériques dans le dépôt.

   Si le projet n'est pas servi à la racine du domaine (ex: `http://localhost/usfah-soutenance/`), adaptez aussi la constante `BASE_URL` en haut du même fichier :

   ```php
   define('BASE_URL', '/usfah-soutenance');
   ```

4. **Démarrer le serveur** :

   - Avec Apache/XAMPP : placez le dossier dans `htdocs/` et visitez `http://localhost/usfah-soutenance/`.
   - Avec le serveur intégré de PHP (pratique en développement) :

     ```bash
     php -S localhost:8000
     ```

     puis ouvrez `http://localhost:8000/`.

5. **Se connecter** avec le compte administrateur de démonstration :

   - Email : `admin@usfah.edu`
   - Mot de passe : `Admin123!`

   Un second compte "responsable académique" est aussi fourni : `responsable@usfah.edu` / `Admin123!`.

   > Changez ces mots de passe avant tout déploiement réel.

## Structure du projet

```
usfah-soutenance/
├── config/        Connexion PDO, authentification, protection CSRF
├── includes/      Gabarits partagés (header, navbar, sidebar, footer) et fonctions utilitaires
├── auth/          Connexion / déconnexion
├── dashboard/     Tableau de bord (statistiques, prochaines soutenances)
├── users/         Gestion des comptes utilisateurs (admin uniquement)
├── faculties/     CRUD Facultés
├── programs/      CRUD Programmes
├── students/      CRUD Étudiants
├── supervisors/   CRUD Encadreurs
├── theses/        CRUD Mémoires
├── jury-members/  CRUD Membres de jury
├── rooms/         CRUD Salles
├── defenses/      CRUD Soutenances (programmation + composition du jury)
├── results/       CRUD Résultats de soutenance
├── corrections/   CRUD Corrections de mémoires
├── assets/        CSS et JS
├── database.sql   Script de création + données de démonstration
└── index.php      Point d'entrée (redirige vers le tableau de bord ou la connexion)
```

Chaque module CRUD suit le même patron : `index.php` (liste, recherche, filtres, pagination), `create.php`, `edit.php`, `show.php`, `delete.php` (avec confirmation).

## Fonctionnalités principales

- Authentification par session avec rôles (Administrateur / Responsable académique)
- Gestion complète : facultés, programmes, étudiants, encadreurs, mémoires, membres de jury, salles, soutenances, résultats, corrections
- Tableau de bord avec statistiques en temps réel
- Recherche et filtres sur tous les modules principaux
- Règles métier appliquées côté serveur :
  - un étudiant ne peut pas avoir deux mémoires actifs simultanément ;
  - une salle ne peut pas accueillir deux soutenances au même moment ;
  - un résultat ne peut être enregistré que pour une soutenance marquée « réalisée » ;
  - matricule et email uniques ;
  - confirmation obligatoire avant toute suppression.

## Sécurité

- Connexion à la base via PDO avec requêtes préparées exclusivement (aucune concaténation de `$_POST`/`$_GET` dans du SQL)
- Mots de passe hashés avec `password_hash()` / vérifiés avec `password_verify()`
- Échappement systématique des sorties avec `htmlspecialchars()` (fonction `e()`)
- Jeton CSRF sur tous les formulaires de création/modification/suppression
- Pages protégées par vérification de session (`require_login()`) et de rôle (`require_role()`)

## Notifications par email (bonus — Resend)

Lorsqu'une soutenance est programmée, l'application peut envoyer automatiquement un email à l'étudiant (titre du mémoire, date, heure, salle, composition du jury) via l'API [Resend](https://resend.com).

1. Copiez le fichier modèle : `cp config/resend.example.php config/resend.php`
2. Renseignez votre clé API Resend et votre adresse d'expédition dans `config/resend.php`.
3. `config/resend.php` est ignoré par git (`.gitignore`) : ne committez jamais de vraie clé API.

Sans domaine vérifié sur Resend, seule l'adresse `onboarding@resend.dev` peut être utilisée comme expéditeur, et uniquement vers l'adresse email associée à votre propre compte Resend (mode bac à sable). Avec un domaine vérifié, l'envoi fonctionne vers n'importe quel destinataire.

Si `config/resend.php` est absent ou que `enabled` vaut `false`, la fonctionnalité est simplement désactivée : la programmation de la soutenance continue de fonctionner normalement, sans email (fonctionnalité bonus, non bloquante).

## Export CSV/Excel (bonus)

Chaque module de liste (facultés, programmes, étudiants, encadreurs, mémoires, membres de jury, salles, soutenances, résultats, corrections) propose un bouton **« Export CSV »** qui télécharge l'ensemble des enregistrements correspondant aux filtres actuellement appliqués. Le fichier CSV (délimiteur `;`, encodage UTF-8 avec BOM) s'ouvre directement dans Excel/LibreOffice sans configuration supplémentaire.

## Fiche de soutenance en PDF (bonus)

Depuis la page de détail d'une soutenance, le bouton **« Fiche PDF »** génère et télécharge une fiche récapitulative (étudiant, mémoire, date, heure, salle, composition du jury) au format PDF.

Le PDF est généré par un petit moteur PHP pur (`includes/pdf.php`, sans bibliothèque externe type FPDF/TCPDF) : suffisant pour un document texte structuré sur une page.

## Compte de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | admin@usfah.edu | Admin123! |
| Responsable académique | responsable@usfah.edu | Admin123! |
