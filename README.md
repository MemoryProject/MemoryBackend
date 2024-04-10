# MemoryBackend

Cette API fournit des données pour le jeu [MemoryGame](https://github.com/MemoryProject/MemoryGame). Elle est construite avec PHP et Symfony.

## Installation

1. Clonez ce repository.
2. Installez les dépendances avec Composer : `composer install`.
3. Configurez votre base de données dans le fichier `.env.local`.
4. Créez la base de données avec la commande : `php bin/console doctrine:database:create`.
5. Exécutez les migrations avec la commande : `php bin/console doctrine:migrations:migrate`.
6. Chargez les fixtures avec la commande : `php bin/console doctrine:fixtures:load`.