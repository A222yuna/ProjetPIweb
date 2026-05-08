 composer install      
php bin/console make:migration             
php bin/console doctrine:migrations:migrate
symfony server:start --allow-all-ip
http://localhost:8000/chat/1
composer require symfony/http-client
symfony server:start --allow-all-ip