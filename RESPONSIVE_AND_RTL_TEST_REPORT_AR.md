# تقرير اختبار الاستجابة و RTL/LTR — BY RASLAN ERP

## نطاق الاختبار
تم التحقق بالكود وباختبارات Feature الآلية، مع مراجعة البنية responsive في Layout/Sidebar/Dashboard.

## RTL
- `layouts/app.blade.php` و`layouts/guest.blade.php` يضبطان `dir` حسب locale (`ar` => RTL).
- Sidebar مثبت يمين الشاشة في desktop، ويتحول إلى drawer في الموبايل.
- Topbar وbreadcrumb ومحاذاة النصوص الافتراضية مناسبة لـ RTL.
- الحقول الرقمية/التواريخ/المبالغ المهمة تستخدم `dir="ltr"` لمنع انقلاب الأرقام.
- الجداول تستخدم `overflow-x-auto` لتجنب كسر التخطيط.

## Responsive
- Dashboard: شبكات `sm/md/xl/2xl` للـ KPI والسكاشن.
- Sidebar: `md:translate-x-0` + mobile overlay.
- الجداول: wrapped in responsive containers.
- النماذج: grid responsive في الصفحات المعاد بناؤها؛ الصفحات القديمة استفادت من DS classes.

## اختبارات منفذة
- `php artisan view:clear` — نجاح.
- Render فعلي لـ Dashboard عبر Tinker bootstrap — نجاح (HTML مُنتَج).
- `npm run build` — نجاح.
- `php artisan test --filter=RouteSmokeTest` — PASS.
- `php artisan test` — **66 passed, 0 failed**.

## قيود بيئة الاختبار
- لم يتم تشغيل متصفح حقيقي/لقطات شاشة ضمن هذه الجلسة لأن البيئة CLI بدون تشغيل UI browser interactively هنا.
- التحقق من RTL/LTR تم على مستوى القوالب، classes، وسلوك التخطيط المتوقع؛ يوصى بجولة بصرية سريعة على متصفح فعلي للشاشات 375/768/1280/1536.

## توصيات متابعة بصرية
1. مراجعة صفحات POS/Production على شاشة 375px للتأكد من كثافة الجداول.
2. مراجعة الجداول العريضة (Shifts/Sales/Returns) وإضافة card-view للموبايل عند الحاجة.
3. اختبار تبديل locale فعلي إلى `en` للتأكد من عكس الأيقونات الاتجاهية إن وُجدت.
