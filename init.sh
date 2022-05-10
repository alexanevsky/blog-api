mkdir -p var
mkdir -p var/cache
mkdir -p var/log
sudo chmod 777 -R var

sudo chmod 777 translations

mkdir -p public/uploads
sudo chmod 777 -R public/uploads

cp .env .env.local

composer install --optimize-autoloader

php bin/console app:security:generate-jwt-keypair
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear
