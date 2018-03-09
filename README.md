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

Инструкции по установке несложно найти в интернете:

https://www.digitalocean.com/community/tutorials/linux-apache-mysql-php-lamp-ubuntu-16-04-ru

https://docs.phalconphp.com/ru/3.2/installation

https://stackoverflow.com/a/37141822/8392705

https://www.digitalocean.com/community/tutorials/how-to-install-and-secure-memcached-on-ubuntu-16-04

https://nodejs.org/en/download/package-manager/

https://www.digitalocean.com/community/questions/problem-installing-imagemagick-ubuntu-14-04

https://www.digitalocean.com/community/tutorials/how-to-set-up-mod_rewrite-for-apache-on-ubuntu-14-04

https://stackoverflow.com/questions/17666249/how-to-import-an-sql-file-using-the-command-line-in-mysql

https://twig.symfony.com/doc/2.x/installation.html

http://php.net/manual/en/mbstring.installation.php

Рекомендуемый дистрибутив — Ubuntu последней стабильной версии.
Следите за совместимостью версий PHP! Например php5-memcached может быть не совместим с PHP 7.2. В этом случае просто подставьте нужную версию в названии пакета.

**ВНИМАНИЕ!** Установка  ПО может занять несколько часов. Это нормально. Также для установки понадобятся прямые руки и понимание принципов работы Linux.

### Сжатие ресурсов
Для ускорения загрузки на сервере скрипты и CSS сжимаются.
Это делается при помощи выполнения скрипта build.sh: ./build.sh

Чтобы он мог работать, нужно установить Node.js и несколько модулей.

npm install jquery

npm install jquery-browserify

npm install autosize

npm install browserify -g

npm install uglify-js -g

npm install uglifycss -g

### Установка

1) Скопируйте содержание этого репозитория в корень вашего веб-сервера (git clone).
2) Включите поддержку .htaccess для Apache
3) Установите Twig через [composer](https://twig.symfony.com/doc/2.x/installation.html).
4) Установите дополнение [mbstring](http://php.net/manual/en/mbstring.installation.php) для PHP.
4) Создайте базу данных, импортируйте в нее структуру таблиц из файла database.sql
5) Отредактируйте app/config-example.php и переименуйте в config.php
6) Создайте первую запись в таблице forums при помощи phpMyAdmin или консоли
7) Создайте в директории public директории assets и files (и установите chmod 777 для каждой директории)
8) Создайте в корневой директории директорию sessions (и установите chmod 777)
9) Запустите команду ./build.sh для сжатия статичных ресурсов
10) Откройте корневую директорию сайта и создайте первую тему

## Авторы

В случае возникновения вопросов/проблем с установкой пишите @zefirov в Telegram.

## Лицензия

Исходный код предоставляется в свободном доступе.

![Свободное ПО](https://vzuks.files.wordpress.com/2015/05/gnu_and_stallman_2012.jpg)

## Заключение

Выбирай жизнь. Выбирай работу. Выбирай карьеру. Выбирай семью. Выбирай большие телевизоры, стиральные машины, автомобили, компакт-диск плееры, электрические консервные ножи. Выбирай хорошее здоровье, низкий уровень холестерина и стоматологическую страховку. Выбирай недвижимость и аккуратно выплачивай взносы. Выбери свой первый дом. Выбирай своих друзей. Выбери себе курорт и шикарные чемоданы. Выбери костюм-тройку лучшей фирмы из самого дорогого материала. Выбери набор «Сделай сам», чтобы было чем заняться воскресным утром. Выбирай диван, чтобы развалиться на нем и смотреть отупляющее шоу, набивая своё брюхо помойной едой. Выбери загнивание в конце всего, проёбывая последнее в жалком доме, когда не остаётся ничего, кроме удивления, что это за эгоистичные ублюдки, которых ты породил, чтобы заменить ими самого себя. Выбирай будущее. Выбирай жизнь… Но зачем мне все это? Я не стал выбирать жизнь… Я выбрал кое-что другое… Причины? Какие могут быть причины, когда есть **Gentoo**?