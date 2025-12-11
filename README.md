# EcoRide

Une plateforme de covoiturage moderne développée avec Symfony 7, permettant aux utilisateurs de partager des trajets et de réduire leur empreinte carbone.

## Description

EcoRide est une application web de covoiturage qui facilite la mise en relation entre conducteurs et passagers. Le système intègre un système de crédits virtuels, un système d'avis modérés, et une gestion complète des trajets avec validation post-trajet.

## Fonctionnalités principales

### Pour les utilisateurs

- **Gestion de profil** : Création et modification de profil avec photo, informations personnelles et gestion des crédits
- **Système de véhicules** : Ajout et gestion de véhicules personnels (marque, modèle, places disponibles, véhicule électrique)
- **Proposition de trajets** : Création de covoiturages avec détails (départ, arrivée, date/heure, prix par place)
- **Recherche de trajets** : Recherche et réservation de covoiturages disponibles
- **Gestion des réservations** : Suivi des réservations en tant que conducteur ou passager
- **Système de crédits** : Monnaie virtuelle pour réserver des places (attribution après validation des trajets)
- **Validation de trajets** : Système de feedback après chaque trajet terminé
- **Système d'avis** : Publication d'avis sur les conducteurs (soumis à modération)
- **Gestion de litiges** : Déclaration de problèmes lors d'un trajet

### Pour les employés

- **Tableau de bord employé** : Vue d'ensemble des litiges et avis en attente
- **Modération des avis** : Validation ou refus des avis publiés par les utilisateurs
- **Gestion des litiges** : Résolution des conflits entre conducteurs et passagers
- **Attribution des crédits** : Gestion des crédits en cas de litige

### Pour les administrateurs

- **Gestion des utilisateurs** : CRUD complet sur les comptes utilisateurs
- **Création d'employés** : Invitation d'employés avec envoi d'email de configuration
- **Gestion des rôles** : Attribution des rôles ADMIN, EMPLOYE, USER
- **Vue d'ensemble** : Statistiques et monitoring de la plateforme

## Technologies utilisées

### Backend
- **Framework** : Symfony 7.2
- **PHP** : 8.2+
- **Base de données** : MySQL 8.0
- **ORM** : Doctrine
- **Authentification** : Symfony Security Component
- **Emails** : Symfony Mailer

### Frontend
- **Template Engine** : Twig
- **CSS Framework** : Bootstrap 5
- **Icons** : Bootstrap Icons
- **JavaScript** : Vanilla JS + Bootstrap Bundle

### Outils de développement
- **Gestionnaire de dépendances** : Composer
- **Serveur de développement** : Symfony CLI
- **Mail catcher** : Mailpit
- **Migrations** : Doctrine Migrations

## Installation

### Prérequis

- PHP 8.2 ou supérieur
- MySQL 8.0 ou supérieur
- Composer
- Symfony CLI (optionnel mais recommandé)
- Mailpit (pour le développement local)

### Étapes d'installation

1. **Cloner le repository**
```bash
git clone https://github.com/votre-username/ecoride_ecf.git
cd ecoride_ecf
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configurer l'environnement**
```bash
# Copier le fichier .env et ajuster les paramètres
cp .env .env.local
```

Modifier `.env.local` avec vos paramètres :
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/ecoride_ecf?serverVersion=8.0.43&charset=utf8mb4"
MAILER_DSN=smtp://localhost:1025
```

4. **Créer la base de données**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. **Charger les données de test (optionnel)**
```bash
php bin/console doctrine:fixtures:load
```

6. **Lancer le serveur de développement**
```bash
symfony server:start
```

7. **Lancer Mailpit (dans un terminal séparé)**
```bash
mailpit
```

L'application sera accessible sur `http://127.0.0.1:8000`
L'interface Mailpit sera accessible sur `http://localhost:8025`

## Structure du projet

```
ecoride_ecf/
├── config/              # Configuration Symfony
├── migrations/          # Migrations de base de données
├── public/              # Point d'entrée et assets publics
│   ├── img/            # Images
│   ├── styles/         # Fichiers CSS
│   └── uploads/        # Fichiers uploadés (gitignored)
├── src/
│   ├── Controller/     # Contrôleurs de l'application
│   │   ├── Admin/     # Contrôleurs admin
│   │   ├── Employee/  # Contrôleurs employés
│   │   └── Passenger/ # Contrôleurs passagers
│   ├── Entity/        # Entités Doctrine
│   ├── Form/          # Types de formulaires
│   ├── Repository/    # Repositories Doctrine
│   ├── Service/       # Services métier
│   ├── Security/      # Voters et logique de sécurité
│   ├── Enum/          # Énumérations (statuts, rôles)
│   └── DTO/           # Data Transfer Objects
├── templates/         # Templates Twig
│   ├── admin/        # Vues administration
│   ├── employee/     # Vues employés
│   ├── passenger/    # Vues passagers
│   ├── emails/       # Templates d'emails
│   └── partials/     # Composants réutilisables
└── var/              # Cache, logs, sessions

```

## Modèle de données

### Entités principales

- **User** : Utilisateurs de la plateforme (conducteurs, passagers, employés, admins)
- **Car** : Véhicules des utilisateurs
- **Ride** : Trajets proposés par les conducteurs
- **RideBooking** : Réservations de places sur un trajet
- **Review** : Avis sur les conducteurs
- **ResetPasswordRequest** : Tokens de réinitialisation de mot de passe

### Statuts et énumérations

- **BookingStatus** : `PENDING`, `CONFIRMED`, `CANCELLED`, `COMPLETED`, `DISPUTED`
- **RideStatus** : `AVAILABLE`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`
- **ReviewStatus** : `PENDING`, `VALIDATED`, `REFUSED`
- **Roles** : `ROLE_USER`, `ROLE_EMPLOYE`, `ROLE_ADMIN`

## Workflow de l'application

### Cycle de vie d'un trajet

1. **Création** : Un conducteur crée un trajet avec son véhicule
2. **Réservation** : Des passagers réservent des places (paiement en crédits)
3. **Démarrage** : Le conducteur démarre le trajet
4. **Fin du trajet** : Le conducteur termine le trajet
5. **Notification** : Les passagers reçoivent un email pour donner leur feedback
6. **Validation** : Chaque passager valide le trajet (succès ou litige)
7. **Attribution des crédits** : Une fois tous les feedbacks reçus, le conducteur reçoit les crédits des trajets validés (les litiges n'attribuent pas de crédits)
8. **Avis (optionnel)** : Si le passager a laissé une note et un commentaire, un avis est créé et soumis à modération

### Système de crédits

- Les utilisateurs reçoivent des crédits initiaux à l'inscription
- Les passagers paient en crédits pour réserver
- Les conducteurs reçoivent les crédits après validation des passagers
- En cas de litige, les crédits de ce passager ne sont pas attribués au conducteur
- Les employés peuvent gérer manuellement les crédits en cas de litige

## Sécurité

- **Authentification** : Formulaire de connexion avec session
- **Hashage des mots de passe** : Algorithme bcrypt
- **Vérification des emails** : Système de confirmation par email
- **Reset de mot de passe** : Token sécurisé avec expiration
- **Voters Symfony** : Contrôle d'accès granulaire sur les ressources
- **Protection CSRF** : Tokens CSRF sur tous les formulaires
- **Validation des données** : Contraintes de validation Symfony

## Contact

Pour toute question ou suggestion, utilisez le formulaire de contact de l'application.

## Licence

Ce projet a été développé dans le cadre d'une formation académique.
