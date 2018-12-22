echo "This script will install Discours along with Apache, PHP, MySQL, etc."
echo "Please use it on a NEWLY CREATED SERVER because it can DAMAGE your existing dependencies."
echo "E.g. it will install PHP7.2 while you already have PHP5."
echo "USE THIS SCRIPT AT YOUR OWN RISK AND MAKE A BACKUP FOR GOD'S SAKE."
echo ""

read -p "Continue (y/n)? " CONT
if [ ! $CONT == "y" ];
then
  exit 0;
fi

read -p "New (or existing) MySQL root password: " MYSQL_ROOT_PASSWORD

if [ ! -f app/config/passwords.txt ];
then
  cp app/config/example-passwords.txt app/config/passwords.txt
  echo "Please edit app/config/passwords.txt before continuing."
  echo "Use your MySQL root password as 'mysql_password'"
  echo ""
  read -p "Press enter to continue... "
  echo ""
fi

sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update
sudo apt-get install -y apache2
sudo apt-get install -y mysql-server
sudo apt-get install -y php libapache2-mod-php php-mcrypt php-mysql php-cli
sudo apt-get install -y php7.2-phalcon
sudo apt-get install -y php7.2-mbstring libapache2-mod-php7.2
sudo apt-get install -y nodejs nodejs-legacy
sudo apt-get install -y npm
sudo apt-get install -y curl
sudo apt-get install -y memcached php7.2-memcached
sudo apt-get install -y php7.2-gd
sudo apt-get install -y imagemagick

npm install jquery
npm install jquery-browserify
npm install jquery-browserify -g
npm install browserify -g
npm install uglify-js -g
npm install uglifycss -g
npm install less -g

npm install noty

if [ ! -f /usr/local/bin/composer ];
then
  echo "Installing Composer..."
  curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
else
  echo "Composer installed"
fi

echo "Please change '/var/www/html' to $PWD in Virtual Host"
echo "Virtual Host location: /etc/apache2/sites-available/000-default.conf"
echo ""
read -p "Press enter to continue... "
echo ""

echo "Please Add this paragraph to Virtual Host file:"
echo "<Directory \"/var/www\">"
echo "  AllowOverride All"
echo "</Directory>"
echo ""
read -p "Press enter to continue... "
echo ""

echo "Please edit app/config/config.php (MAIN_HOST, FILE_HOST, FILE_PROTOCOL)"
echo ""
read -p "Press enter to continue... "
echo ""

sudo a2enmod rewrite # enable mod_rewrite
service apache2 restart # restart Apache

mysql -u root -p$MYSQL_ROOT_PASSWORD -e "create database discours;"
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "use discours; source database.sql;"
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "use discours; insert into forums values (1, 'Discours', 'b');"

mkdir public/assets && chmod 777 public/assets
mkdir public/files && chmod 777 public/files
mkdir sessions && chmod 777 sessions

composer install
./build.sh default

echo ""
echo "Enable OPcache in php.ini to increase performance"
echo "Enjoy your Discours!"
echo ""