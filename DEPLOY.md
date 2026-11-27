# نشر BY RASLAN ERP — دليل النشر المجاني

## ⚠️ حقيقة مهمة عن Cloudflare
**Cloudflare Workers / Pages لوحدها لا تشغّل PHP، يعني لا تشغّل Laravel.**
السيستم لازم يشتغل على سيرفر فيه PHP، و Cloudflare تبقى **الواجهة** قدامه
(DNS + حماية + HTTPS) أو **النفق** (Tunnel) اللي يوصّل جهازك بالإنترنت.
الملفات دي بتجهّز السيستم لأي سيرفر Docker، و Cloudflare تكمل الصورة.

## الطريق 1 — Cloudflare Tunnel من جهازك (أسرع، مجاني، 5 دقائق)
السيستم يفضل شغال على جهازك، و Cloudflare تعطيه لينك عام.
ينفع للتجربة والعرض، بس لو الجهاز اتقفل السيستم بيقف.

```bash
# 1) شغّل السيستم زي ما هو شغال عندك
php artisan serve   # http://127.0.0.1:8000

# 2) نزّل cloudflared من https://developers.cloudflare.com/cloudflare-one/connections/connect/downloads/
# 3) في ترمينال جديد:
cloudflared tunnel --url http://localhost:8000
# هيطلع لينك زي: https://xxxx.trycloudflare.com — افتحه، السيستم شغال
```

لو عندك دومين على Cloudflare، ممكن تعمل Tunnel دائم باسم ثابت من لوحة
Cloudflare One → Networks → Tunnels (مجاني).

## الطريق 2 — Railway + Cloudflare DNS (شغال 24 ساعة)
1. من [railway.app](https://railway.app) اعمل مشروع جديد → Deploy from GitHub →
   اختار `Emam-Medhat/By-Raslan`.
2. Railway هيقرأ الـ `Dockerfile` و `railway.json` اللي في الريبو ويبني
   السيستم لوحده (PHP 8.3 + Apache + Vite build + migrations تلقائياً).
3. ضيف خدمة MySQL من Railway (Add → Database → MySQL)، والمتغيرات دي
   هتتظبط تلقائياً من Railway:
   `MYSQLHOST, MYSQLPORT, MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD`
4. في إعدادات الخدمة ضيف المتغيرات دي:
   ```
   APP_NAME=BY RASLAN ERP
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://<الدومين-اللي-Railway-هيديهولك>
   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   ```
   ملاحظة: `APP_KEY` بيتولد تلقائياً عند أول تشغيل لو مش موجود.
5. افتح الدومين اللي Railway اداهولك، سجّل دخول، وجرّب.
6. **Cloudflare في الصورة:** من لوحة Cloudflare اعمل CNAME من دومينك
   (مثلاً `erp.yourdomain.com`) إلى دومين Railway، وفعّل الـ Proxy
   (السحابة البرتقالية) — كده الدومين بتاعك بيشتغل عليه السيستم
   بحماية و HTTPS من Cloudflare.

## الطريق 3 — أي VPS مجاني (Oracle Always Free) + Cloudflare Tunnel
على السيرفر: `git clone` الريبو، `docker compose up` أو
`docker build -t byraslan . && docker run`، وبعدين ثبّت `cloudflared`
واعمل Tunnel دائم للدومين. ده الحل المجاني الدائم الوحيد.

## ملاحظات تشغيل مهمة
- **الحجوزات:** التحويل التلقائي للحجوزات الساعة 00:00 محتاج
  `php artisan schedule:run` كل دقيقة (cron). على Railway ضيف Cron Job
  منفصل بنفس الصورة يشغّل الأمر ده، أو شغّله من السيرفر.
- **البيانات:** على Railway، قاعدة MySQL بتاعتهم هي اللي بتحفظ البيانات —
  مش SQLite (ملف SQLite هيتمسح مع كل Deploy).
- **أول دخول:** بعد الـ Deploy اعمل حساب الأدمن من الـ Seeder أو من
  صفحة التسجيل حسب إعدادات السيستم عندك.
- النسخة اللي هتتنشر من GitHub هي اللي فيها مميزات المدير
  (باركود الميزان، الحجوزات، العهدة). إعادة تصميم الواجهة لسه
  محلية عند Muse ومترفعتش — لو عايزها في النشر قولي أرفعها الأول.
