# Ina Zaoui -

## Description

Ce site web appartient à une photographe spécialisée dans les photos de paysages du monde entier. Il a été mis à jour et de nouvelles fonctionnalités ont été ajoutées.

## Dépendances

- PHP 8.2 ou supérieur
- Composer
- Symfony CLI
- MySQL 8.0 ou un autre système de gestion de base de données compatible avec Doctrine

## Installation

1. Cloner le projet sur votre machine
2. Installer les dépendances avec Composer : `composer install`
3. Créer un fichier `.env.local` et ajouter l'URL de la base de données :

```
   DATABASE_URL="mysql://root:password@127.0.0.1:3306/critipixel_dev"
```

## Initialisation de la base de données

1. Créer la base de données : `php bin/console doctrine:database:create`
2. Exécuter les migrations : `php bin/console doctrine:migrations:migrate`
3. Charger les fixtures : `php bin/console doctrine:fixtures:load`

**Note :** Les fixtures chargeront automatiquement les images présentes dans le dossier `public/uploads/`. Assurez-vous d'avoir des images dans ce dossier avant de charger les fixtures.

## Utilisation

Vous pouvez lancer un serveur en local avec la commande `symfony serve`.

L'application sera accessible sur : `http://localhost:8000`

## Tests

Le projet est testé avec PHPUnit et PHPStan. Voici les différentes commandes disponibles :

- `php bin/phpunit` : Permet de lancer tous les tests (49 tests)
- `php bin/phpunit --coverage-html coverage/` : Permet de lancer les tests et de générer un rapport de couverture de code
- `vendor/bin/phpstan analyse src tests --level=6` : Permet de lancer PHPStan pour vérifier la qualité du code

**Couverture de code actuelle :** 83.72%

## Informations supplémentaires

- Un workflow GitHub pour l'intégration continue est également présent. Il permet de lancer les tests unitaires et de vérifier la qualité du code à chaque push sur la branche main.

- Les comptes de test sont créés lors du chargement des fixtures avec les identifiants suivants :
  - **Administrateur :**
    - Email : `admin@test.com`
    - Mot de passe : `admin123`
  - **Utilisateurs :**
    - Email : `user1@test.com` à `user30@test.com`
    - Mot de passe : `test123`

- **Validation des fichiers :** L'application valide les fichiers uploadés (MIME type, taille max 20 Méga, formats autorisés : JPG, PNG, GIF, WEBP, PDF, MP4, MOV)

- **Performance :** Le projet a été optimisé pour gérer de grandes quantités de médias avec pagination (25 éléments/page)
