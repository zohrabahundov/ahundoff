#!/bin/sh
# Заглушка sendmail для тестов: складывает письма в tests/mail.out
cat >> "$(dirname "$0")/mail.out"
printf "\n===MAIL-END===\n" >> "$(dirname "$0")/mail.out"
exit 0
