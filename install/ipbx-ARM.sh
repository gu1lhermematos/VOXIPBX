#!/bin/bash

cd /usr/src/
apt-get update
apt-get upgrade -y

apt-get install git apache2 apache2-mpm-prefork apache2-utils apache2.2-bin apache2.2-common libapache2-mod-php5 php5 php5-cgi php5-cli php5-common php5-curl php5-gd php5-mcrypt php5-mysql php5-odbc mysql-server mysql-client libmyodbc odbcinst odbcinst1debian2 unixodbc unixodbc-dev libodbcinstq4-1 libltdl-dev libltdl7 libcurl3 libncurses5-dev build-essential  libxml2-dev lshw sudo sox vim zip libsqlite3-dev libxml2-dev libncurses5-dev libncursesw5-dev libmysqlclient-dev libiksemel-dev libssl-dev libnewt-dev libusb-dev libeditline-dev libedit-dev curl libcurl4-gnutls-dev build-essential  uuid uuid-dev openssh-server mysql-server mysql-client bison flex php5 php5-curl php5-cli php5-mysql php-pear php-db php5-gd curl sox libncurses5-dev libssl-dev libmysqlclient-dev mpg123 libxml2-dev libnewt-dev sqlite3 libsqlite3-dev pkg-config automake libtool autoconf git subversion ncurses-term ttf-bitstream-vera -y

a2enmod rewrite
/etc/init.d/apache2 restart


cd /usr/src/

wget -c http://downloads.asterisk.org/pub/telephony/certified-asterisk/certified-asterisk-1.8.15-current.tar.gz

tar zxvf certified-*
cd certified-*
make clean
./configure
contrib/scripts/get_mp3_source.sh
make menuselect
make
make install
make config
make samples

cd /usr/src/


git clone https://github.com/eluizbr/VOXIPBX.git
mv VOXIPBX ipbx
mv ipbx  /var/www/
cd /var/www/
chown -R www-data.www-data *
chmod 775 ipbx
ln -s ipbx snep
ln -s ipbx snep2
cd /etc/apache2/sites-enabled/
cp /var/www/ipbx/install/tofalando.apache2 001-tofalando
cd /etc/apache2/sites-available/
cp /var/www/ipbx/install/tofalando.apache1 default

cd /var/log
mkdir snep
touch snep/ui.log
touch snep/agi.log
chown -R www-data.www-data snep/
cd /var/lib/asterisk/agi-bin/
chmod 776 /var/www/snep/agi -R
 ln -s /var/www/snep/agi/ snep
cd /etc
cp -avr /var/www/snep/install/etc/* .
mv /var/spool/asterisk/monitor /var/spool/asterisk/monitor.snep
ln -sf /var/www/snep/arquivos /var/spool/asterisk/monitor
cd /var/lib/asterisk
mkdir moh/tmp moh/backup
mkdir -p moh/snep_1/tmp moh/snep_1/backup
mkdir -p moh/snep_2/tmp moh/snep_2/backup
mkdir -p moh/snep_3/tmp moh/snep_3/backup
chown www-data.www-data /var/lib/asterisk/moh/ -R
cd /usr/src
wget -c http://www.sneplivre.com.br/downloads/asterisk-sounds.tgz
tar -xvzf asterisk-sounds.tgz -C /var/lib/asterisk/
mkdir -p /var/lib/asterisk/sounds/pt_BR/tmp
mkdir -p /var/lib/asterisk/sounds/tmp
mkdir -p /var/lib/asterisk/sounds/pt_BR/backup
mkdir -p /var/lib/asterisk/sounds/backup
chown www-data:www-data /var/lib/asterisk/sounds -R
cd /var/www/snep/sounds/
ln -sf /var/lib/asterisk/moh/ moh
ln -sf /var/lib/asterisk/sounds/pt_BR/ pt_BR

cd /var/www/snep/install/
mysql -uroot -p < database.sql
cd /var/www/snep/modules/default/installer
mysql -uroot -p snep25 < schema.sql
mysql -uroot -p snep25 < system_data.sql
mysql -uroot -p snep25 < cnl_data.sql

cd /usr/lib/odbc/
ln -s /usr/lib/arm-linux-gnueabihf/odbc/libmyodbc.so
sed -i s/"register_argc_argv = Off"/register_argc_argv=On/g /etc/php5/cli/php.ini
sed -i s/"register_argc_argv = Off"/register_argc_argv=On/g /etc/php5/cgi/php.ini
sed -i s/"register_argc_argv = Off"/register_argc_argv=On/g /etc/php5/apache2/php.ini

rm -rf /var/www/index.html
cd /var/www/ipbx/install
cp index.php /var/www/
echo "tofalando" > /etc/hostname

/etc/init.d/mysql restart
/etc/init.d/apache2 restart
/etc/init.d/asterisk start
# asterisk -rx “module reload”
