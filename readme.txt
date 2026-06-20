http://localhost:8210/admin/login
Email: admin@medikal.com
Password: password123






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



========================
docker logs -f multi-vendor-ecommerc-admin-1

۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰
god@god:~/Videos/multi-vendor-ecommerc$ curl -X POST http://localhost:8210/admin/login \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:3001" \
  -d '{"email": "admin@example.com", "password": "password"}' \
  -v



----------------------- front
docker exec -it multi-vendor-ecommerc-front-1 sh
docker exec -it multi-vendor-ecommerc-admin-1 sh

EOF
console.log('Token:', localStorage.getItem('admin_token'));
console.log(localStorage.getItem('token'));


35|9no0j0ALN1avenUZWYJQB68jrS1ELMsRIjCDovhA7d92e3
docker exec -it socket-mysql mysql -u root -p cms
-----------------------------
docker exec -it socket-laravel sh
docker exec -it socket-laravel php artisan config:clear
docker exec -it socket-laravel php artisan route:clear
docker exec -it socket-laravel php artisan view:clear
docker restart socket-laravel
docker restart socket_front_1

chown -R www-data:www-data /var/www/storage/logs
chmod -R 775 /var/www/storage/logs
chmod 664 /var/www/storage/logs/laravel.log
------------------------------------------

cd /home/discord/Videos/socket

# 1. اگر کانتینر در حال اجراست، متوقف کنید
docker compose down -v

# 2. Build مجددイメージ‌ها
docker compose build --no-cache

# 3. بالا آوردن سرویس MySQL و Redis ابتدا
docker compose up -d socket-mysql socket-redis

# 4. منتظر بمانید تا MySQL آماده شود (حدود 30 ثانیه)
sleep 30

# 5. ایجاد و تنظیم فایل env در src
cd src
cp .env.example .env 2>/dev/null || echo "فایل env از قبل وجود دارد"

# 6. نصب وابستگی‌های Laravel از طریق کانتینر
docker compose run --rm socket-laravel composer install --ignore-platform-reqs

# 7. تولید کلید اپلیکیشن
docker compose run --rm socket-laravel php artisan key:generate

# 8. نصب Broadcasting و Reverb
docker compose run --rm socket-laravel php artisan install:broadcasting
docker compose run --rm socket-laravel php artisan reverb:install

# 9. اجرای Migration
docker compose run --rm socket-laravel php artisan migrate --force

# 10. انتشار فایل‌های Reverb
docker compose run --rm socket-laravel php artisan vendor:publish --tag=reverb-config

# 11. تنظیم مجدد Cache
docker compose run --rm socket-laravel php artisan optimize:clear
docker compose run --rm socket-laravel php artisan config:cache
docker compose run --rm socket-laravel php artisan route:cache
docker compose run --rm socket-laravel php artisan view:cache

# 12. ایجاد استوریج لینک
docker compose run --rm socket-laravel php artisan storage:link

# 13. راه‌اندازی همه سرویس‌ها
cd /home/discord/Videos/socket
docker compose up -d

# 14. بررسی وضعیت Reverb
docker compose logs socket-laravel | grep -i reverb
docker compose exec socket-laravel supervisorctl status
















----------------------------------------------------
-----------------------------------------------------
sudo nano /etc/docker/daemon.json
sudo systemctl restart docker
docker exec -it socket-laravel bash
docker exec -it socket-laravel php artisan config:clear
docker exec -it socket-laravel php artisan cache:clear
docker exec -it socket-laravel php artisan view:clear
docker exec -it socket-laravel php artisan queue:restart
docker exec -it socket-laravel supervisorctl restart all
--------------------------- nginx restart
docker restart socket-webserver



discord@discord-Predator-PH315-51:~/Videos/socket$ docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' socket-laravel
172.18.0.7

---------------------------- kill reverb
ls -l /proc/*/exe 2>/dev/null | grep -E "php|reverb"
kill -9 7
-------------------- supervisor
docker exec -it socket-laravel supervisorctl status
docker exec -it socket-laravel tail -f /var/log/supervisor/reverb.log

----------------------- front
docker exec -it multi-vendor-ecommerc-front-1 sh
docker exec -it multi-vendor-ecommerc-admin-1 sh



docker exec -it socket_front_1 sh
docker exec -it socket_admin_1 sh
npm install -g wscat
npm install antd @ant-design/icons moment-jalaali dayjs axios
-------------------------
docker-compose build --no-cache socket-laravel
composer config -g repos.packagist composer https://package-mirror.liara.ir/repository/composer/
composer i
"token":"1|cFnj1JouTU5zkcUeFGXk5dLADu4qWZb5BHks0sn2160f3d28"
--------------------------------------------------- install package
composer config repositories.packagist false

composer config repositories.liara composer https://package-mirror.liara.ir/repository/composer/
composer require kavenegar/laravel
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
composer require morilog/jalali --ignore-platform-reqs --no-interaction
composer require kavenegar/laravel --ignore-platform-reqs --no-interaction
composer require maatwebsite/excel --ignore-platform-reqs --no-interaction
composer require barryvdh/laravel-dompdf --ignore-platform-reqs --no-interaction

composer require laravel/sanctum --ignore-platform-reqs --no-interaction
composer require spatie/laravel-permission --ignore-platform-reqs --no-interaction
composer require shetabit/payment --ignore-platform-reqs --no-interaction
composer require spatie/laravel-medialibrary --ignore-platform-reqs --no-interaction
composer require intervention/image --ignore-platform-reqs --no-interaction

docker exec -it socket-laravel composer require laravel/reverb --ignore-platform-reqs --no-interaction
docker exec -it socket-laravel composer require laravel/sanctum --ignore-platform-reqs --no-interaction
----------------------------------------------------
 curl -I https://package-mirror.liara.ir/repository/composer/packages.json
----------------------------------------------------- cors
curl -I -X OPTIONS http://localhost:8210/api/login -H "Origin: http://localhost:3000"
----------------------------------------------------
# نصب در front


docker exec -it socket_front_1 sh -c "cd /app && npm install laravel-echo pusher-js axios"

# نصب در admin
docker exec -it socket_admin_1 sh -c "cd /app && npm install laravel-echo pusher-js axios"or
----------------------------------------------------
# فرانت
docker exec -it socket_front_1 sh
docker restart socket_front_1
docker exec -it socket_admin_1 sh
npm install laravel-echo pusher-js axios
-----------------------------------------------------
# پاک کردن همه کش‌ها
docker exec -it socket-laravel php artisan optimize:clear
docker exec -it socket-laravel php artisan config:clear
docker exec -it socket-laravel php artisan route:clear
docker exec -it socket-laravel php artisan view:clear

# دوباره کش کن
docker exec -it socket-laravel php artisan route:cache
docker exec -it socket-laravel php artisan config:cache

# ریستارت Nginx
docker restart socket-webserver
--------------------------------------------------
php artisan reverb:start --host=0.0.0.0 --port=8080
docker exec socket-laravel php artisan reverb:list
docker exec socket-laravel curl -I http://localhost:8080
supervisorctl status

------------------------------------------------------------------ curl
console.log(localStorage.getItem('admin_token'));
npx antigravity-ide .
# تست با کاربر معمولی
curl -X POST http://localhost:8210/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# یا با ادمین
curl -X POST http://localhost:8210/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

TOKEN="توکنی_که_گرفتی"

# ارسال پیام به ادمین (از کاربر)
curl -X POST http://localhost:8210/api/send-message \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"receiver_id":1,"message":"سلام ادمین جان!"}'


docker exec -it socket_admin_1 sh
------------------------------------------------------------------
# داخل کانتینر
mkdir -p /root/.composer
chmod 755 /root/.composer



composer clear-cache
composer require laravel/sanctum --ignore-platform-reqs --no-interaction





-----------------------------------------------------------------
# داخل کانتینر لاراول
docker exec -it socket-laravel bash

# اجرای migration ها
php artisan migrate:fresh --seed

# کش کردن routes
php artisan route:cache

# چک کردن اینکه همه چی درسته
php artisan route:list
php artisan event:list

# تست ارسال پیام با curl
curl -X POST http://localhost:8210/api/send-message \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"receiver_id":1,"message":"سلام"}'
---------------------------------------------------
https://docs.botble.com/cms/commands.html
## publish
php artisan cms:publish:assets


 sudo systemctl restart docker

sudo docker-compose build
sudo docker-compose exec socket-laravel sh
exit
php artisan cache:clear
php artisan storage:link

sudo docker-compose exec socket-laravel composer i --ignore-platform-req=ext-gd
sudo chmod -R 775 src/storage
sudo chown -R www-data:www-data src/storage

php artisan octane:install
php artisan octane:start


sudo docker stop $(sudo docker ps -aq)
sudo docker rm $(sudo docker ps -aq)

http://localhost:8210/   server
http://localhost:8080/   echo
http://localhost:8310/   phpmyadmin

------------------------------------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------------------------------------
sudo usermod -aG docker $USER
sudo systemctl restart docker
sudo docker stop socket_laravel
sudo -i
docker rm -f $(docker ps -aq)
sudo docker kill rabbitmq
------------------------------------------------------------------------------------------------------------------------
sudo rm -rf /etc/docker
sudo snap refresh
------------------------------------------------------------------------------------------------------------------------
sudo reboot
------------------------------------------------------------------------------------------------------------------------
sudo curl -L "https://github.com/docker/compose/releases/download/v2.21.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
docker-compose --version
------------------------------------------------------------------------------------------------------------------------
php artisan make:migration add_show_in_detail_to_ec_product_specification_attribute --table=ec_product_specification_attribute

------------------------------------------------------------------------------------------------------------------------
  @if (EcommerceHelper::isProductSpecificationEnabled() && $product->specificationAttributes->where('pivot.hidden', false)->isNotEmpty())
   @endif
------------------------------------------------------------------------------------------------------------------------
  @foreach($product->specificationAttributes->where('pivot.show_in_detail', true)->sortBy('pivot.order') as $attribute)
  @endforeach
------------------------------------------------------------------------------------------------------------------------
php artisan cms:publish:assets
------------------------------------------------------------------------------------------------------------------------
locate product-specification.blade.php
------------------------------------------------------------------------------------------------------------------------
platform/plugins/ecommerce/resources/views/themes/includes/product-specification.blade.php
--- front
platform/themes/shofy/views/ecommerce/includes/product-detail.blade.php
------------------------------------------------------------------------------------------------------------------------
shift
locate product-gallery.blade
------------------------------------------------------------------------------------------------------------------------
 docker-compose restart socket-webserver
docker exec -it socket-laravel php artisan config:clear
docker exec -it socket-laravel php artisan cache:clear



discord@discord-Predator-PH315-51:~/Videos/docker-danasocket$ sudo lsof -i :9000
COMMAND     PID USER   FD   TYPE DEVICE SIZE/OFF NODE NAME
docker-pr 15673 root    4u  IPv4 154621      0t0  TCP *:9000 (LISTEN)
docker-pr 15684 root    4u  IPv6 155340      0t0  TCP *:9000 (LISTEN)

+++++++++++++++++++++++++++++
1. کاربر A در سایت ثبت‌نام میکنه و عضو برنامه وابسته میشه
         ↓
2. یه لینک اختصاصی دریافت میکنه: https://site.com/?ref=USER_A_CODE
         ↓
3. لینک رو در کانال‌هاش (اینستاگرام، تلگرام، وبلاگ) به اشتراک میذاره
         ↓
4. کاربر B روی لینک کلیک میکنه و خرید انجام میده
         ↓
5. سیستم تشخیص میده که کاربر B توسط کاربر A معرفی شده
         ↓
6. به کاربر A به ازای هر فروش، 10% کمیسیون تعلق میگیره
         ↓
7. کاربر A میتونه موجودی خودش رو برداشت کنه
++++++++++++++++++++++++++++++
++++++++++++++++++++++++++++++
++++++++++++++++++++++++++++++
++++++++++++++++++++++++++++++
++++++++++++++++++++++++++++++
++++++++++++++++++++++++++++++




# فقط این سه خط را اجرا کنید
npm install @neshan-maps-platform/mapbox-gl-react
npm install @neshan-maps-platform/mapbox-gl
npm install @types/mapbox-gl --save-dev

 docker exec -it socket-laravel bash
docker exec -it socket-laravel php dump.php
docker exec -it socket_front_1 node /app/dump.js
docker exec -it socket_admin_1 node /app/dump.js
node dump.js
docker exec -it socket_admin_1 sh







