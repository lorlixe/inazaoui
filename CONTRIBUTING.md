# Guide de contribution

Merci de prendre le temps de contribuer au site de Ina ZAOUI ! ❤️

Tous les types de contributions sont encouragés et valorisés. Veuillez lire les sections pertinentes avant de contribuer. Cela facilitera le travail des mainteneurs et rendra l'expérience plus fluide pour tous. La communauté attend vos contributions avec impatience. 🎉

---

## Table des matières

- [Code de conduite](#code-de-conduite)
- [J'ai une question](#jai-une-question)
- [Je veux contribuer](#je-veux-contribuer)
  - [Signaler des bugs](#signaler-des-bugs)
  - [Suggérer des améliorations](#suggérer-des-améliorations)
  - [Votre première contribution](#votre-première-contribution)
- [Procédure de contribution](#procédure-de-contribution)
- [Conventions de code](#conventions-de-code)
- [Messages de commit](#messages-de-commit)
- [Tests](#tests)

---

## Code de conduite

Ce projet et tous ses participants sont régis par un code de conduite. En participant, vous acceptez de respecter ce code. Signalez tout comportement inacceptable à l'équipe du projet.

---

## J'ai une question

Avant de poser une question :

1. **Consultez la documentation** : Lisez le [README.md](README.md)
2. **Recherchez dans les issues** : Vérifiez si quelqu'un a déjà posé la même question
3. **Cherchez sur internet** : Stack Overflow, forums Symfony, etc.

Si vous avez toujours besoin d'aide :

- Ouvrez une [Issue](../../issues/new)
- Fournissez un maximum de contexte
- Indiquez vos versions (PHP, Symfony, MySQL, etc.)

---

## Je veux contribuer

### Notice légale

En contribuant à ce projet, vous acceptez que :

- Vous êtes l'auteur à 100% du contenu que vous soumettez
- Vous disposez des droits nécessaires sur ce contenu
- Votre contribution peut être distribuée sous la licence du projet (MIT)

---

## Signaler des bugs

### Avant de signaler un bug

1. **Utilisez la dernière version** du projet
2. **Vérifiez que c'est bien un bug** et non une erreur de configuration
3. **Recherchez dans les issues existantes** pour éviter les doublons
4. **Collectez des informations** :
   - Message d'erreur complet
   - Stack trace
   - Versions (PHP, Symfony, MySQL, etc.)
   - Système d'exploitation
   - Étapes pour reproduire le bug

### Comment signaler un bug

⚠️ **Ne jamais signaler de failles de sécurité publiquement**. Contactez l'équipe en privé.

Pour les bugs normaux :

1. Ouvrez une [Issue](../../issues/new)
2. Utilisez un **titre clair et descriptif**
3. Décrivez le **comportement attendu** vs **comportement actuel**
4. Fournissez les **étapes de reproduction**
5. Ajoutez des **captures d'écran** si pertinent
6. Incluez les **informations collectées** précédemment

---

## Suggérer des améliorations

### Avant de suggérer

1. **Vérifiez la dernière version** - la fonctionnalité existe peut-être déjà
2. **Lisez la documentation** attentivement
3. **Recherchez dans les issues** pour éviter les doublons
4. **Évaluez la pertinence** - est-ce utile pour la majorité des utilisateurs ?

### Comment suggérer une amélioration

1. Ouvrez une [Issue](../../issues/new)
2. **Titre clair** décrivant la suggestion
3. **Description détaillée** de la fonctionnalité proposée
4. **Justification** : pourquoi est-ce utile ?
5. **Alternatives** : avez-vous envisagé d'autres approches ?
6. **Captures d'écran ou mockups** si applicable

---

## Votre première contribution

Vous ne savez pas par où commencer ? Cherchez des issues avec les labels :

- `good first issue` - Bon pour débuter
- `help wanted` - Aide recherchée
- `documentation` - Amélioration de la documentation

---

## Procédure de contribution

### 1. Fork le projet

Cliquez sur le bouton "Fork" en haut de la page GitHub.

### 2. Cloner votre fork

```bash
git clone https://github.com/VOTRE-USERNAME/inazaoui.git
cd inazaoui
```

### 3. Créer une branche

```bash
git checkout -b type/description-courte
```

**Types de branches :**

- `feature/` - Nouvelle fonctionnalité
- `fix/` - Correction de bug
- `refactor/` - Refactorisation de code
- `docs/` - Documentation
- `test/` - Ajout/modification de tests
- `perf/` - Amélioration de performance
- `chore/` - Tâches diverses (maintenance, config)

**Exemples :**

```bash
git checkout -b feature/add-media-export
git checkout -b fix/pagination-error
git checkout -b docs/update-readme
```

### 4. Installer les dépendances

```bash
composer install
npm install
```

### 5. Configurer l'environnement

```bash
cp .env .env.local
# Éditez .env.local avec vos paramètres

# Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### 6. Développer votre fonctionnalité

- Suivez les [conventions de code](#conventions-de-code)
- Écrivez des tests pour votre code
- Testez localement

### 7. Tester votre code

```bash
# Tests unitaires et fonctionnels
php bin/phpunit

# Analyse statique
vendor/bin/phpstan analyse src tests --level=6

# Vérifier le style de code
vendor/bin/php-cs-fixer fix --dry-run --diff
```

### 8. Commit vos modifications

```bash
git add .
git commit -m "type: description courte en anglais"
```

Voir [Messages de commit](#messages-de-commit) pour les conventions.

### 9. Synchroniser avec le projet principal

```bash
git remote add upstream https://github.com/PROJET-ORIGINAL/inazaoui.git
git fetch upstream
git rebase upstream/main
```

### 10. Pousser vers votre fork

```bash
git push origin type/description-courte
```

### 11. Créer une Pull Request

1. Allez sur votre fork GitHub
2. Cliquez sur "Compare & pull request"
3. Remplissez le template de PR :
   - Description claire des changements
   - Référence aux issues concernées (#123)
   - Captures d'écran si pertinent
   - Liste des tests effectués

---

## Conventions de code

### Standards PSR-12

Ce projet suit les standards [PSR-12](https://www.php-fig.org/psr/psr-12/).

**Points clés :**

- Indentation : **4 espaces** (pas de tabulations)
- Encodage : **UTF-8 sans BOM**
- Longueur de ligne : **120 caractères maximum**
- Classes : **PascalCase** (`UserController`, `MediaRepository`)
- Méthodes : **camelCase** (`findAllUsers()`, `getUserById()`)
- Constantes : **UPPER_CASE** (`MAX_UPLOAD_SIZE`)

### Type hints obligatoires

```php
// ✅ BON
public function createUser(string $email, bool $isAdmin): User
{
    // ...
}

// ❌ MAUVAIS
public function createUser($email, $isAdmin)
{
    // ...
}
```

### Nommage

- **Variables** : `$userName`, `$isActive` (camelCase, en anglais)
- **Classes** : `UserController`, `MediaEntity` (PascalCase)
- **Méthodes** : `findAllUsers()`, `deleteMedia()` (verbes en anglais)
- **Routes** : `app_user_list`, `admin_media_edit` (snake_case)

### Sécurité

- **Toujours** valider les données utilisateur avec les contraintes Symfony
- **Toujours** utiliser `setParameter()` pour les requêtes SQL
- **Activer** la protection CSRF sur tous les formulaires
- **Ne jamais** stocker de mots de passe en clair

```php
// ✅ BON
$qb->where('u.email = :email')
   ->setParameter('email', $email);

// ❌ MAUVAIS - Injection SQL possible
$qb->where("u.email = '$email'");
```

### Performance

- **Utiliser la pagination** pour les listes > 25 éléments
- **Éviter les requêtes N+1** (utiliser les jointures)
- **Indexer** les colonnes fréquemment recherchées
- **Utiliser le cache** Symfony quand approprié

---

## Messages de commit

### Format

```
type: description courte en anglais (< 72 caractères)

Corps du message optionnel avec plus de détails.
Peut inclure des références à des issues.

Refs: #123
```

### Types de commit

- `feat:` - Nouvelle fonctionnalité
- `fix:` - Correction de bug
- `refactor:` - Refactorisation (pas de changement fonctionnel)
- `docs:` - Documentation uniquement
- `test:` - Ajout ou modification de tests
- `style:` - Formatage, point-virgules manquants, etc.
- `perf:` - Amélioration de performance
- `chore:` - Maintenance, configuration, dépendances

### Exemples

```bash
feat: add thumbnail generation for images

fix: prevent unauthorized access to admin panel

refactor: simplify media repository query methods

docs: update installation instructions in README

test: add unit tests for UserController

perf: add pagination to guest list page
```

### Règles

- ✅ Verbe à l'impératif ("add", pas "added" ou "adds")
- ✅ Pas de point final
- ✅ En anglais
- ✅ Description claire et concise
- ❌ Éviter "fix typo", "WIP", "misc changes"

---

## Tests

### Tests requis

Toute contribution doit inclure des tests appropriés.

**Couverture minimale :**

- **80%** globalement
- **90%** pour les nouvelles fonctionnalités
- **100%** pour le code critique (authentification, sécurité)

### Lancer les tests

```bash
# Tous les tests
php bin/phpunit

# Tests avec couverture
php bin/phpunit --coverage-html coverage

# Tests d'un fichier spécifique
php bin/phpunit tests/Functional/UserControllerTest.php
```

### Types de tests

**Tests fonctionnels** (`tests/Functional/`) :

```php
public function testGuestsPageIsAccessible(): void
{
    $this->client->request('GET', '/guests');

    self::assertResponseIsSuccessful();
    self::assertSelectorTextContains('h1', 'Invités');
}
```

**Tests unitaires** (`tests/Unit/`) :

```php
public function testUserCanBeCreated(): void
{
    $user = new User();
    $user->setEmail('test@example.com');

    self::assertEquals('test@example.com', $user->getEmail());
}
```

### Qualité du code

```bash
# PHPStan niveau 6 minimum
vendor/bin/phpstan analyse src tests --level=6

# Style de code PSR-12
vendor/bin/php-cs-fixer fix --dry-run
```

---

## Processus de validation

Une Pull Request sera acceptée si :

✅ Tous les tests passent (49+ tests)  
✅ PHPStan niveau 6 sans erreur  
✅ Couverture de code ≥ 80%  
✅ Respect de PSR-12  
✅ Documentation à jour  
✅ Messages de commit conformes  
✅ Pas de conflits avec `main`

**Timeline :**

- Première revue : **48-72 heures**
- Temps de réponse aux modifications : **1 semaine**
- Fusion après validation : **quelques jours**

---

## Structure du projet

```
inazaoui/
├── config/              # Configuration Symfony
├── public/              # Point d'entrée web
│   └── uploads/         # Fichiers uploadés
├── src/
│   ├── Controller/      # Contrôleurs
│   ├── Entity/          # Entités Doctrine
│   ├── Form/            # Formulaires Symfony
│   ├── Repository/      # Repositories Doctrine
│   └── DataFixtures/    # Fixtures pour les tests
├── templates/           # Templates Twig
│   ├── admin/           # Back office
│   └── front/           # Front office
├── tests/
│   ├── Functional/      # Tests fonctionnels
│   └── Unit/            # Tests unitaires
├── docs/                # Documentation
└── var/                 # Cache, logs
```

---

## Ressources utiles

- [Documentation Symfony 7.2](https://symfony.com/doc/7.2/index.html)
- [Documentation Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html)
- [PHPUnit](https://phpunit.de/documentation.html)
- [PSR-12 Coding Style](https://www.php-fig.org/psr/psr-12/)
- [PHPStan](https://phpstan.org/user-guide/getting-started)
- [Conventional Commits](https://www.conventionalcommits.org/)

---

## Questions ou problèmes ?

- 📧 Ouvrez une [Issue](../../issues/new)
- 💬 Consultez les [Discussions](../../discussions)
- 📚 Lisez le [README.md](README.md)

---

## Remerciements

Merci à tous les contributeurs qui rendent ce projet meilleur ! 🎉

---

**Bon code ! 🚀**
