# Дискурс

В репозитории находятся исходники анонимного форума Дискурс (https://discou.rs/).

## Начало работы

Вам понадобится установить:
1) Apache
2) PHP версии 7.0 или выше (на работает на более ранних версиях)
3) MySQL (phpMyAdmin — по желанию)
4) Phalcon
5) Memcached
6) Node.JS
7) ImageMagick

Инструкции по установке несложно найти в интернете. Рекомендуемый дистрибутив — Ubuntu последней стабильной версии.

**ВНИМАНИЕ!** Установка  ПО может занять несколько часов. Это нормально. Также для установки понадобятся прямые руки и понимание принципов работы Linux.

### Сжатие ресурсов
Для ускорения загрузки на сервере скрипты и CSS сжимаются.
Это делается при помощи выполнения скрипта build.sh: ./build.sh

Чтобы он мог работать, нужно установить Node.js и несколько модулей.

npm install jquery

npm install jquery-browserify

npm install autosize

npm install uglify-js -g

npm install uglifycss -g

### Установка

1) Скопируйте содержание этого репозитория в корень вашего веб-сервера (git clone).
2) Установите Twig через [composer](https://twig.symfony.com/doc/2.x/installation.html).
3) Создайте базу данных и таблицы при помощи файла database.sql
4) Отредактируйте app/config-example.php и переименуйте в config.php
5) Создайте первую запись в таблице forums при помощи phpMyAdmin или консоли
6) Создайте в директории public директории assets и files (и установите chmod 777 для каждой директории)
7) Создайте в корневой директории директорию sessions (и установите chmod 777)
8) Запустите команду ./build.sh для сжатия статичных ресурсов
9) Откройте корневую директорию сайта и создайте первую тему

## Авторы

В случае возникновения вопросов/проблем с установкой пишите @zefirov в Telegram.

## Лицензия

Исходный код предоставляется в свободном доступе.