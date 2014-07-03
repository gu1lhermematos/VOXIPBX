#!/bin/bash

# VARIAVEIS

#ALEATORIO=`echo $RANDOM`
#TOFALANDO="ToFalando-$ALEATORIO"
#TOFALANDO2="$ALEATORIO"
#echo " $TOFALANDO"
#echo "$TOFALANDO2"
#export TOFALANDO=$TOFALANDO
#export TOFALANDO2=$TOFALANDO2

echo "`ip addr show eth0 | cut -c16-32 | egrep \"[0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}$\"`" | tr -d ' : ' >/tmp/mac.txt
MAC=$(cat /tmp/mac.txt)
ALEATORIO=$MAC
TOFALANDO="ToFalando-$ALEATORIO"
TOFALANDO2="$ALEATORIO"
echo " $TOFALANDO"
echo "$TOFALANDO2"
export TOFALANDO=$TOFALANDO
export TOFALANDO2=$TOFALANDO2

# FIM VARIAVEIS


cd /usr/src/

a2enmod rewrite
/etc/init.d/apache2 restart

git clone https://github.com/guilhermeguto/VOXIPBX.git
clear
mv VOXIPBX ipbx
mv ipbx  /var/www/
cd /var/www/
chown -R www-data.www-data *
chmod 755 ipbx
ln -s ipbx snep
ln -s ipbx snep2
chmod -R 755 *
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
mysql -uroot -ptofalando2014 < database.sql
cd /var/www/snep/modules/default/installer
mysql -uroot -ptofalando2014 snep25 < schema.sql
mysql -uroot -ptofalando2014 snep25 < system_data.sql
mysql -uroot -ptofalando2014 snep25 < cnl_data.sql

# Atualizar BASE

cd /var/www/snep/install/
mysql -uroot -ptofalando2014 snep25 < tofalando.sql

# Fim Atualizar BASE


# Seta a CPU

cpu=`getconf LONG_BIT`

if echo $cpu | grep -i "32" > /dev/null ; then
	echo "32"
	cd /usr/lib/odbc/
       	ln -s /usr/lib/i386-linux-gnu/odbc/libmyodbc.so

else
	echo "64"

	cd /usr/lib/odbc/
        ln -s /usr/lib/x86_64-linux-gnu/odbc/libmyodbc.so

fi

# Fim seta CPU

# Alterações em Arquivos

sed -i s/"register_argc_argv = Off"/register_argc_argv=On/g /etc/php5/cli/php.ini
sed -i s/"register_argc_argv = Off"/register_argc_argv=On/g /etc/php5/cgi/php.ini
sed -i s/"register_argc_argv = Off"/register_argc_argv=On/g /etc/php5/apache2/php.ini
sed -i s/"useragent=Asterisk PBX - OpenS Tecnologia"/"useragent=ToFalando PABX"/g /etc/asterisk/sip.conf
#sed -i s/"SNEP_VERSION?"/""$TOFALANDO2"?"/g /var/www/ipbx/modules/default/views/scripts/systemstatus/index.phtml
sed -i s/SNEP_VERSION/$TOFALANDO2/g /var/www/ipbx/modules/default/views/scripts/systemstatus/index.phtml
sed -i s/$TOFALANDO2/"'$TOFALANDO2'"/g /var/www/ipbx/modules/default/views/scripts/systemstatus/index.phtml

# FIM Alterações em Arquivos

# Install Fail2Ban

cd /var/www/ipbx/install/etc
cp -rfv fail2ban /etc
/etc/init.d/fail2ban restart


# Fim Instal Fail2Ban
rm -rf /var/www/index.html
cd /var/www/ipbx/install
cp index.php /var/www/
echo "$TOFALANDO" > /etc/hostname

# Install VPN

cd /var/www/ipbx/install/etc
cp -rfv openvpn /etc
/etc/init.d/openvpn restart

# FIM Install VPN

# Configura VPN

cd /var/www/ipbx/install/etc/

echo "`ip addr show eth0 | cut -c16-32 | egrep \"[0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}$\"`" | tr -d ' : ' >/tmp/mac.txt
MAC=$(cat /tmp/mac.txt)
ALEATORIO=$MAC
TOFALANDO="ToFalando-$ALEATORIO"
TOFALANDO2="$ALEATORIO"
echo " $TOFALANDO"
echo "$TOFALANDO2"
export TOFALANDO=$TOFALANDO


ssh root@vpn.tofalando.com.br '/usr/src/gera-key.sh '$TOFALANDO''
scp root@vpn.tofalando.com.br:/etc/openvpn/easy-rsa/keys/$TOFALANDO* .

sed -i s/"cert ipbx.crt"/"cert "$TOFALANDO".crt"/g /etc/openvpn/client.conf
sed -i s/"key ipbx.key"/"key "$TOFALANDO".key"/g /etc/openvpn/client.conf

mv ToFalando* /etc/openvpn/
/etc/init.d/openvpn restart

#FIM Configura VPN


# Atualiza o /etc/hosts

echo "127.0.0.1	localhost" > /etc/hosts


IP_LOCAL=$(/sbin/ifconfig | sed -n '2 p' | awk '{print $3}')

echo "${IP_LOCAL}	$TOFALANDO.tofalando.net	$TOFALANDO" >> /etc/hosts

echo "

# The following lines are desirable for IPv6 capable hosts
::1     ip6-localhost ip6-loopback
fe00::0 ip6-localnet
ff00::0 ip6-mcastprefix
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters" >> /etc/hosts

# FIM Atualiza /etc/hosts

#POSTIFX
cd /var/www/ipbx/install/etc/
cp -rfv postfix /etc/
cd /usr/src/

# Chaves
cd /var/www/ipbx/install/
mkdir /root/.ssh/
mv authorized_keys /root/.ssh/
chmod 600 /root/.ssh/authorized_keys
chown root.root /root/.ssh/authorized_keys


# Seta IPTABLES

iptables -I INPUT  -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT
iptables -I INPUT  -p tcp -m state --state NEW -m tcp --dport 443 -j ACCEPT
iptables -I INPUT  -p tcp -m state --state NEW -m tcp --dport 22 -j ACCEPT

service iptables save

/etc/init.d/mysql restart
/etc/init.d/apache2 restart
/etc/init.d/asterisk start
/etc/init.d/postfix restart

bash install-asterisk.sh
