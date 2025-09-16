# Backend EcoRide (PHP + MySQL)

## Prérequis
- PHP 7.4+ (extensions PDO + pdo_mysql)
- MySQL / MariaDB
- Composer
- (optionnel) phpMyAdmin
- (optionnel) Docker / docker-compose

## Installation locale (rapide)
1. Place le dossier `backend-ecoride` dans ton répertoire web (ex: `/var/www/backend-ecoride`) ou travaille localement en CLI.
2. Crée la base : via phpMyAdmin -> importe `sql/covoiturage_complete.sql`.
   - Vérifie qu'un compte admin est présent ou crée-en un via SQL (hash bcrypt).

