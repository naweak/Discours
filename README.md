<p align="center">
  <img src="/public/christmas.png" width="250" height="250" title="Merry Christmas">
</p>

## Goodnight, sweet prince

К 2019 году борды умерли. Активное население ушло, остались нытики и пустословы. На этом прощайте. Счастливого Нового года!

## Дискурс

В репозитории находятся исходники анонимного форума Дискурс (https://discou.rs/).

## Быстрая установка

```diff
+ Запустите команду ./deploy.sh и следуйте инструкциями.
```

Рекомендуется устанавливать Дискурс на чистый сервер. Купить виртуальные сервера дешево можно, например, на DigitalOcean.

## Ручная установка

Вам понадобится установить:
1) Apache
2) PHP версии 7.0 или выше (на работает на более ранних версиях)
3) MySQL (phpMyAdmin — по желанию)
4) Phalcon
5) Memcached
6) Node.JS
7) ImageMagick
8) PHP GD
9) cURL (опционально)

Для работы с SSL используйте [Certbot](https://certbot.eff.org/lets-encrypt/ubuntuxenial-apache).

Инструкции по установке несложно найти в интернете.

Рекомендуемый дистрибутив — Ubuntu последней стабильной версии.
Следите за совместимостью версий PHP! Например php5-memcached может быть не совместим с PHP 7.2. В этом случае просто подставьте нужную версию в название пакета.

**ВНИМАНИЕ!** Установка  ПО может занять несколько часов. Это нормально. Также для установки понадобятся прямые руки и понимание принципов работы Linux.

### Сжатие ресурсов
Для ускорения загрузки на сервере скрипты и CSS сжимаются.

```diff
+ Это делается при помощи выполнения специального скрипта: ./build.sh
```

Чтобы он мог работать, нужно установить Node.js и несколько модулей.

npm install jquery

npm install jquery-browserify

npm install browserify -g

npm install uglify-js -g

npm install uglifycss -g

npm install less -g

npm install noty

### Процесс ручной установки

1) Скопируйте содержание этого репозитория в корень вашего веб-сервера (git clone).
2) Включите поддержку .htaccess для Apache
3) Установите Twig через [composer](https://twig.symfony.com/doc/2.x/installation.html).
4) Установите дополнение [mbstring](http://php.net/manual/en/mbstring.installation.php) для PHP.
5) Установите дополнение PHP GD.
6) Установите ImageMagick.
7) Установите Node.js и все необходимые пакеты (см. выше).
8) Создайте базу данных, импортируйте в нее структуру таблиц из файла database.sql
9) Отредактируйте app/config/config.php
10) Создайте первую запись в таблице forums при помощи phpMyAdmin или консоли
11) Создайте директории public/assets и public/files (и установите chmod 777 для каждой)
12) Создайте в корневой директории директорию sessions (и установите chmod 777)
13) Запустите команду ./build.sh для сжатия статичных ресурсов
14) Откройте сайт и создайте первую тему

## Авторы

В случае возникновения вопросов/проблем с установкой пишите @zefirov в Telegram.

## Лицензия

The Unlicense ([http://unlicense.org](http://unlicense.org)): исходный код передан в общественное достояние.

## Полезные ссылки

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

https://stackoverflow.com/questions/6382539/call-to-undefined-function-curl-init