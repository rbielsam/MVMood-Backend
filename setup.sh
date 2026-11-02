#!/bin/bash
echo "Iniciando instalacion"
echo "Para instalar dependencias se usa sudo apt, por favor introducir contraseña usuario actual"
sudo apt install php php-xml php-cli php-mysql php-curl

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'c8b085408188070d5f52bcfe4ecfbee5f727afa458b2573b8eaaf77b3419b0bf2768dc67c86944da1544f06fa544fd47') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
php composer-setup.php
php -r "unlink('composer-setup.php');"

sudo mv composer.phar /usr/local/bin/composer
echo "Composer instalado"

composer install
cp .env.example .env
php artisan key:generate

echo "⚠️  Edita MVMoodLaravel/.env con tus credenciales (DB_*, PUSHER_*, MAIL_*, FRONTEND_URL) antes de continuar"

php artisan install:broadcasting --no-interaction

php artisan install:api --no-interaction
composer require laravel/ui
php artisan ui react
php artisan migrate
php artisan storage:link
php artisan serve