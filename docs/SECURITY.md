# Xavfsizlik va ishga tushirish ro'yxati

## Tizimda mavjud himoyalar
- **Ko'p mijozli izolyatsiya**: har bir klinika ma'lumoti `clinic_id` global scope bilan ajratilgan (testlar bilan).
- **Parollar**: bcrypt; kamida 8 belgi, harf+raqam, ommabop parollar rad etiladi.
- **Kirish**: login+IP bo'yicha 5/daq va IP bo'yicha 30/daq cheklov; barcha kirish/chiqish/xato urinishlari audit jurnalida.
- **2FA (TOTP)**: Profil → Ikki bosqichli tasdiqlash. Zaxira kodlar bilan. Super Admin uchun qat'iy tavsiya.
- **Oferta**: har bir xodim birinchi kirishda va har yangi versiyada ofertani qabul qilmaguncha ishlay olmaydi; dalil (vaqt, IP, brauzer, versiya) saqlanadi.
- **Onlayn qabul**: oferta + shaxsiy ma'lumotlar roziligi majburiy (server tomonida ham tekshiriladi).
- **Sarlavhalar**: CSP, X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, HSTS (HTTPS'da); kirgan foydalanuvchi sahifalari keshlanmaydi.
- **OTP SMS** jurnal va logda yashiriladi. SMS yuboruvchi marshrutlar cheklangan.
- **Zaxira**: `php artisan db:backup` (har kuni 03:00, 14 kun). Nusxani BOSHQA serverga ham ko'chiring.
- **Monitoring**: Super Admin → Tizim holati ichida xavfsizlik tekshiruvlari (debug, HTTPS, 2FA, standart parollar, zaxira).

## Sotuvdan oldin (majburiy)
1. `.env.production.example` asosida `.env` yarating; `APP_DEBUG=false`, `APP_ENV=production`, HTTPS.
2. Serverni O'zbekiston hududida joylashtiring (shaxsiy ma'lumotlarni lokalizatsiya qilish talabi) — yurist bilan tasdiqlang.
3. Demo ma'lumot buyrug'ini (`seed-demo`) productionda ishlatmang; demo parollarni almashtiring.
4. Super Admin uchun 2FA yoqing, parolni almashtiring.
5. MySQL: alohida foydalanuvchi (root emas), tashqaridan 3306 yopiq.
6. `php artisan schedule:run` ni har daqiqada ishga tushiring (cron / Task Scheduler) — zaxira, eslatmalar, billing shunga bog'liq.
7. Oferta va maxfiylik siyosatini yurist ko'rib chiqsin; Sozlamalarda kompaniya rekvizitlarini kiriting.
8. Zaxiradan tiklashni bir marta sinab ko'ring.
9. Brauzer konsolida CSP buzilishlari yo'qligini tekshirib, `SECURITY_CSP=enforce` qoldiring.

## Cron (ahost.uz / cPanel)
cPanel → Cron Jobs → «Каждую минуту» (* * * * *):
`/usr/local/bin/ea-php83 /home/<user>/clinik_crm/artisan schedule:run >> /dev/null 2>&1`
Tekshirish: Super Admin → Tizim holati → «Fon jarayonlari» (yashil bo'lishi kerak).
