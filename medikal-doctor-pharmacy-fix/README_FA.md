# نصب اصلاحیه مدیکال

از پوشه‌ای که فایل را استخراج کرده‌اید اجرا کنید:

```bash
python3 install.py /home/god/Videos/medikal
cd /home/god/Videos/medikal
docker compose up -d --force-recreate front
docker compose exec front sh -lc 'NODE_ENV=production npm run build'
docker compose exec front sh -lc 'npm run lint'
```

اگر Build یا API خطا داد:

```bash
docker compose logs --tail=200 front medikall-webserver medikall-laravel
curl -i http://localhost:8210/api/ping
curl -i 'http://localhost:3000/backend-api/api/doctors?per_page=4'
```
