PKG12 — Platforma SMS matnlari, kartaga qo'lda to'lov (chek), fon jarayonlari monitoringi
1) update.bat (yoki serverda: php artisan migrate --force && php artisan optimize:clear)
2) Super Admin -> Sozlamalar: "Kartaga to'lov" blokini to'ldiring (karta raqami, egasi, bank).
3) Super Admin -> SMS matnlari: platforma xabarlari matnlarini tahrirlang (Eskizda tasdiqlatish uchun "Eskizga yuborish").
4) Super Admin -> To'lov cheklari (karta): kelgan cheklarni tasdiqlang yoki sabab bilan bekor qiling.
5) Tizim holati -> "Fon jarayonlari": cron ishlayotganini ko'ring.
   ahost.uz cPanel -> Cron Jobs -> "Каждую минуту" -> buyruq:
   /usr/local/bin/ea-php83 /home/atkoiduz/clinik_crm/artisan schedule:run >> /dev/null 2>&1
   (PHP versiyasi hosting sozlamasidagi bilan bir xil bo'lsin)
