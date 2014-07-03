#!/bin/bash

cpu=`getconf LONG_BIT`

if echo $cpu | grep -i "32" > /dev/null ; then
	echo "32"

	clear
        cd /var/www/ipbx/
	git pull origin master
	cd /usr/src
        wget -c http://download2.fop2.com/fop2-2.27-debian-i386.tgz
        tar zxvf fop2-2.27-debian-i386.tgz
        cd fop2
        make install
        cd /var/www/ipbx/install/mesa
        cp fop2.cfg /usr/local/fop2/
        cp buttons.cfg /usr/local/fop2/
        cp presence.js /var/www/fop2/js/
        cp config.php /var/www/fop2/
        cp lang_pt_BR.js /var/www/fop2/js/
	rm -rf /etc/asterisk/manager.conf
	cp manager.conf /etc/asterisk
	cd /var/www/ipbx/
	ln -s /var/www/fop2/ mesa
	cd /usr/src
        rm -rf fop*
        echo "create database fop2" | mysql -u root -ptofalando2014
	/etc/init.d/fop2 restart
        
	# Alterações em Arquivos
	sed -i s/";callevents=no"/callevents=yes/g /etc/asterisk/sip.conf
	/etc/init.d/asterisk restart
	clear


else
	echo "64"
	clear
	cd /var/www/ipbx/
	git pull origin master
	cd /usr/src
	wget -c http://download2.fop2.com/fop2-2.27-debian-x86_64.tgz
	tar zxvf fop2-2.27-debian-x86_64.tgz
	cd fop2
	make install
	cd /var/www/ipbx/install/mesa
	cp fop2.cfg /usr/local/fop2/
	cp buttons.cfg /usr/local/fop2/
	cp presence.js /var/www/fop2/js/
	cp config.php /var/www/fop2/
	cp lang_pt_BR.js /var/www/fop2/js/
	rm -rf /etc/asterisk/manager.conf
	cp manager.conf /etc/asterisk/
	cd /var/www/ipbx/
        ln -s /var/www/fop2/ mesa
	cd /usr/src
	rm -rf fop*
	echo "create database fop2" | mysql -u root -ptofalando2014
	/etc/init.d/fop2 restart

	# Alterações em Arquivos
        sed -i s/";callevents=no"/callevents=yes/g /etc/asterisk/sip.conf
        /etc/init.d/asterisk restart
	clear

fi

# Fim seta CPU


