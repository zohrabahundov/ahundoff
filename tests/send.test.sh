#!/bin/bash
# Регрессионные тесты send.php. Требуется запущенный PHP-стенд (см. README.md).
# Запуск: TEST_HOST=http://127.0.0.1:8088 bash tests/send.test.sh
# Регрессионные тесты send.php ПОСЛЕ рефакторинга (ожидаемое исправленное поведение)
HOST="${TEST_HOST:-http://127.0.0.1:8088}"
U="$HOST/send.php"
NOW=$(date +%s); FT=$((NOW-10))
PASS=0; FAIL=0
dec() { python3 -c "import sys,json
try:
  d=json.load(sys.stdin); print((d.get('error') or d.get('message') or '') + (' [code='+d['code']+']' if d.get('code') else ''))
except Exception as e: print('NON-JSON')"; }
chk() { if echo "$3" | grep -q "$2"; then PASS=$((PASS+1)); echo "PASS: $1"; else FAIL=$((FAIL+1)); echo "FAIL: $1 | got: $(echo "$3"|head -c 200)"; fi; }
post() { local ip="$1"; shift; curl -s --max-time 10 -X POST "$U" -H "X-Requested-With: XMLHttpRequest" -H "Origin: https://ahundoff.ru" -H "X-Forwarded-For: $ip" "$@" | dec; }

echo "--- A. ДОСТУП (без изменений) ---"
chk "A1 GET -> 405" 'Метод не разрешен' "$(curl -s -X GET $U | dec)"
chk "A2 без XRW -> 403" 'Доступ запрещен' "$(curl -s -X POST $U -H 'Origin: https://ahundoff.ru' -d name=x | dec)"
chk "A3 чужой Origin -> 403" 'Доступ запрещен' "$(curl -s -X POST $U -H 'X-Requested-With: XMLHttpRequest' -H 'Origin: https://evil.com' -d name=x | dec)"
chk "A4 lookalike -> 403" 'Доступ запрещен' "$(curl -s -X POST $U -H 'X-Requested-With: XMLHttpRequest' -H 'Origin: https://ahundoff.ru.evil.com' -d name=x | dec)"

echo "--- B. АНТИСПАМ + ИСПРАВЛЕНИЯ ---"
chk "B1 honeypot -> фейковый успех" 'Спасибо' "$(post 93.184.216.10 -d website=spam -d form_time=$FT)"
chk "B2 honeypot lang=en -> ТЕПЕРЬ по-английски (ИСПРАВЛЕНО)" 'Thank you' "$(post 93.184.216.11 -d website=spam -d lang=en -d form_time=$FT)"
chk "B3 нет form_time -> фейковый успех" 'Спасибо' "$(post 93.184.216.12 -d name=Bot -d phone=1234567890)"
chk "B4 быстро -> фейковый успех" 'Спасибо' "$(post 93.184.216.13 -d name=Bot -d phone=1234567890 -d form_time=$NOW -d pd-consent=on)"
chk "B5 форма >24ч -> ТЕПЕРЬ честная ошибка form_expired (ИСПРАВЛЕНО)" 'form_expired' "$(post 93.184.216.14 -d name='Клиент' -d phone=+79295005785 -d form_time=$((NOW-90000)) -d pd-consent=on)"
chk "B6 будущее время -> фейковый успех" 'Спасибо' "$(post 93.184.216.15 -d name=Bot -d phone=1234567890 -d form_time=$((NOW+3600)) -d pd-consent=on)"

echo "--- C. ВАЛИДАЦИЯ + ИСПРАВЛЕНИЯ ---"
chk "C1 пустое имя" 'Имя обязательно' "$(post 93.184.216.21 -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"
chk "C2 имя 1 символ" 'короткое' "$(post 93.184.216.22 -d name=A -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"
chk "C3 telegram @valid_user -> успех" 'успешно отправлена' "$(post 93.184.216.26 -d name=Иван -d phone=@valid_user -d form_time=$FT -d pd-consent=on)"
chk "C4 телефон +7 (929) 500-57-85 -> успех" 'успешно отправлена' "$(post 93.184.216.27 --data-urlencode 'phone=+7 (929) 500-57-85' -d name=Иван -d form_time=$FT -d pd-consent=on)"
chk "C5 ++++++++++1234567890 -> ТЕПЕРЬ отказ (ИСПРАВЛЕНО)" 'Некорректный контакт' "$(post 93.184.216.28 --data-urlencode 'phone=++++++++++1234567890' -d name=Иван -d form_time=$FT -d pd-consent=on)"
chk "C6 pd-consent=0 -> ТЕПЕРЬ ошибка согласия (ИСПРАВЛЕНО)" 'согласие' "$(post 93.184.216.30 -d name=Иван -d phone=1234567890 -d form_time=$FT -d pd-consent=0)"
chk "C7 без согласия -> ошибка" 'согласие' "$(post 93.184.216.29 -d name=Иван -d phone=1234567890 -d form_time=$FT)"
chk "C8 partner без company" 'компанию' "$(post 93.184.216.31 -d form_type=partner -d name=Иван -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"
chk "C9 partner валидный -> успех" 'успешно отправлена' "$(post 93.184.216.32 -d form_type=partner -d name=Партнер -d phone=@partner_one -d company=CRM -d form_time=$FT -d pd-consent=on)"
chk "C10 message 2001" 'Сообщение слишком длинное' "$(post 93.184.216.33 -d name=Иван -d phone=1234567890 -d form_time=$FT -d pd-consent=on --data-urlencode message=$(python3 -c "print('x'*2001)"))"
chk "C11 lang=en" 'Name is required' "$(post 93.184.216.35 -d lang=en -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"
chk "C12 name[] массив -> без краша" 'Имя обязательно' "$(post 93.184.216.37 -d 'name[]=x' -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"
chk "C13 телефон 15 цифр -> успех" 'успешно отправлена' "$(post 93.184.216.38 -d name=Иван -d phone=123456789012345 -d form_time=$FT -d pd-consent=on)"
chk "C14 телефон 16 цифр -> отказ" 'Некорректный контакт' "$(post 93.184.216.39 -d name=Иван -d phone=1234567890123456 -d form_time=$FT -d pd-consent=on)"

echo "--- D. ИНЪЕКЦИИ + UTF-8 ---"
chk "D1 CRLF в имени -> успех" 'успешно отправлена' "$(post 93.184.216.41 --data-urlencode $'name=Боб\r\nBcc: victim@evil.com' -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"
chk "D2 XSS в имени -> успех" 'успешно отправлена' "$(post 93.184.216.42 --data-urlencode 'name=<script>alert(1)</script>Иван' -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"
chk "D3 невалидный UTF-8 -> ТЕПЕРЬ 400 без краша (ИСПРАВЛЕНО)" 'кодировка' "$(post 93.184.216.43 --data-urlencode $'name=\xc3\x28invalid' -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"

echo "--- E. RATE LIMIT (новая двухуровневая модель) ---"
IP=93.184.216.50
for i in 1 2 3 4 5; do post $IP -d name=Иван -d phone=1234567890 -d form_time=$FT -d pd-consent=on > /dev/null; done
chk "E1 6-я успешная отправка -> 429" 'Слишком много заявок' "$(post $IP -d name=Иван -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"
chk "E2 XFF: правый IP = реальный, ротация левого НЕ обходит (ИСПРАВЛЕНО)" 'Слишком много заявок' "$(post "1.2.3.4, $IP" -d name=Иван -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"
IP2=93.184.216.60
for i in 1 2 3 4 5; do post $IP2 -d name=И -d phone=abc -d form_time=$FT > /dev/null; done
chk "E3 5 невалидных НЕ сожгли лимит отправок -> валидная проходит (ИСПРАВЛЕНО)" 'успешно отправлена' "$(post $IP2 -d name=Иван -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"
IP3=93.184.216.70
for i in $(seq 1 30); do post $IP3 -d name=И -d phone=abc -d form_time=$FT > /dev/null; done
chk "E4 31-я попытка -> 429 attempts (анти-брут)" 'Слишком много попыток' "$(post $IP3 -d name=И -d phone=abc -d form_time=$FT)"
chk "E5 429 lang=en" 'Too many requests' "$(post $IP -d lang=en -d name=Иван -d phone=1234567890 -d form_time=$FT -d pd-consent=on)"

echo "--- F. ЗАГОЛОВКИ ---"
H=$(curl -s -D - -o /dev/null -X POST $U -H 'X-Requested-With: XMLHttpRequest' -H 'Origin: https://ahundoff.ru' -H 'X-Forwarded-For: 93.184.216.80' -d name=x)
chk "F1 Content-Type JSON" 'application/json' "$H"
chk "F2 nosniff" 'nosniff' "$H"
chk "F3 Cache-Control no-store (НОВОЕ)" 'no-store' "$H"
echo; echo "ИТОГО: PASS=$PASS FAIL=$FAIL"
