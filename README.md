web-admin-backend
=================


Процесс установки:

su dev -l

cd /home/dev/www/admin/
git pull

composer install
bin/console cache:clear --env prod --no-warmup

cd /home/dev/www/admin/python_generator/text-generator/
git pull 

------------------------------------------------------------------------------------------------------------------------

обновить зависимости nodejs:

yarn upgrade

проинсталить зависимости зависимости nodejs:

yarn upgrade

Сборка ассетов веба:

yarn run assets:dev

Также доступны команды

yarn run assets:watch

yarn run assets:build

------------------------------------------------------------------------------------------------------------------------


Запуск локального сервера:

php bin/console server:run

------------------------------------------------------------------------------------------------------------------------
