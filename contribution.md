# Guide de contribution

Merci de votre intérêt pour contribuer au projet CritiPixel !

## Comment contribuer ?

### 1. Fork et clone

1. Forkez le projet sur GitHub
2. Clonez votre fork : `git clone https://github.com/votre-username/critipixel.git`
3. Créez une branche : `git checkout -b feature/ma-fonctionnalite`

### 2. Développer

- Respectez les standards PSR-12
- Ajoutez des tests pour votre code
- Commentez votre code si nécessaire

### 3. Tester

Avant de soumettre, vérifiez que :

# Les tests passent

php bin/phpunit

# PHPStan ne remonte pas d'erreur

vendor/bin/phpstan analyse src tests --level=6

### 4. Commit et Push

```bash
git add .
git commit -m "feat: description de votre modification"
git push origin feature/ma-fonctionnalite
```

**Convention de commit :**

- `feat:` : Nouvelle fonctionnalité
- `fix:` : Correction de bug
- `docs:` : Documentation
- `test:` : Tests
- `refactor:` : Refactorisation

### 5. Pull Request

Créez une Pull Request sur GitHub avec :

- Un titre clair
- Une description des modifications
- Les issues liées

## Standards de code

- Indentation : 4 espaces
- Type hints obligatoires
- PHPDoc pour les méthodes publiques
- Couverture de tests : minimum 70%
