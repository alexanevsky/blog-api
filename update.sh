#!/bin/bash

env="prod"

while getopts e: flag
do
    case "${flag}" in
        e) env=${OPTARG};;
    esac
done

if [ $env == 'dev' ] || [ $env == 'test' ]; then
    echo "Update for $env environment"

    git pull
    composer install --optimize-autoloader
    php bin/console cache:clear
    php bin/console doctrine:migrations:migrate --no-interaction
    php bin/console cache:clear

elif [ $env == 'prod' ]; then
    echo "Update for prod environment"

    git pull
    composer install --no-dev --optimize-autoloader
    php bin/console cache:clear
    php bin/console doctrine:migrations:migrate --no-interaction
    php bin/console cache:clear

else
    echo "Given undefined environment name \"$env\""
    exit
fi

echo -e "App was updated!"
