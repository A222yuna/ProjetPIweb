 composer install      
php bin/console make:migration             
php bin/console doctrine:migrations:migrate
symfony server:start --allow-all-ip
http://localhost:8000/chat/1
composer require symfony/http-client
symfony server:start --allow-all-ip

start test:
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:update --force --env=test
vendor/bin/phpunit tests/MessageSystemTest.php