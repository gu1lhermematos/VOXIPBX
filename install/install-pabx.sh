#!/bin/bash

# Copyright (C) 2011-2014 ToFalando
#
# Script incialmente desenvolvido por
# Emerson Luiz ( eluizbr@tofalando.com.br )
# Atualizado por
# Guilherme Matos ( guilherme@boxfacil.com.br )


# Configurar o Branch
BRANCH='devel'

apt-get -y install lsb-release

# Identify Linux Distribution type
func_identify_os() {
    if [ -f /etc/debian_version ] ; then
        DIST='UBUNTU'
        
        if [ "$(lsb_release -cs)" != "precise" ] ; then
            	echo "A instalação funciona apenas no Ubuntu LTS 12.04"
            	exit 255
        fi
        
#elif [ -f /etc/debian_version ]; then
#	 DIST='DEBIAN'
#	 if [ "$(lsb_release -cs)" != "wheezy" ]; then
 #           	echo "A instalação funciona apenas no Ubuntu LTS 12.04 Debian 7.X"
#            	exit 255
#        fi
else
        echo "A instalação funciona apenas no Ubuntu LTS 12.04"
        exit 1
    fi
}

func_identify_os

#echo ""
#echo ""
#echo "Este script irá instalar o BoxFacil IPBX neste computador"
#echo "Pressione Enter para continuar CTRL-C para sair"
#echo ""
#read TEMP


case $DIST in
    'UBUNTU')
        apt-get -y update
	apt-get -y upgrade
	echo 1 > /proc/sys/net/ipv4/ip_forward
	sed -i s/"#net.ipv4.ip_forward=1"/net.ipv4.ip_forward=1/g /etc/sysctl.conf
	echo "America/Sao_Paulo" > /etc/timezone
	dpkg-reconfigure --frontend noninteractive tzdata
	locale-gen pt_BR.UTF-8
	export LANG=pt_BR.UTF-8
	export LC_ALL=pt_BR.UTF-8
	echo "root:@tofalando#" | chpasswd
	
	# Regras de redirecionamento
	echo "iptables -t nat -A PREROUTING -p tcp -i tun0 --dport 8080 -j DNAT --to IP_ATA:80" >> /etc/rc.local
	
	apt-get -y install vim git-core fail2ban openvpn gawk
	
	# Pacotes para TTS
	apt-get -y install perl libwww-perl mpg123 sox flac
	
	# POSTFIX
	export DEBIAN_FRONTEND=noninteractive
	apt-get install -q -y  libsasl2-2 ca-certificates libsasl2-modules    
	#APACHE
	apt-get install -y apache2 apache2-mpm-prefork apache2-utils apache2.2-bin apache2.2-common libapache2-mod-php5
	#PHP
	apt-get install -y php5-suhosin  php5 php5-cgi php5-cli php5-common php5-curl php5-gd php5-mcrypt php5-mysql php5-odbc php5-curl php5-mysql php-pear php-db php5-gd
	# MYSQL
	export DEBIAN_FRONTEND=noninteractive
	apt-get install -q -y mysql-server mysql-client libmysqlclient-dev
	mysqladmin -u root password tofalando2014
	#ODBC
	apt-get install -y libmyodbc odbcinst odbcinst1debian2 unixodbc unixodbc-dev libodbcinstq4-1
	# DEVEL
	apt-get install -y  build-essential linux-headers-`uname -r` make bison flex  zip  curl sox  lshw ncurses-term ttf-bitstream-vera libncurses5-dev automake libtool mpg123 sqlite3 libsqlite3-dev libncursesw5-dev uuid-dev  libxml2-dev libnewt-dev  pkg-config  autoconf subversion  libltdl-dev libltdl7 libcurl3   libxml2-dev   libiksemel-dev libssl-dev libnewt-dev libusb-dev libeditline-dev libedit-dev libssl-dev 	
;;
esac

#Instalar o Asterisk
cd /usr/src/
wget --no-check-certificate  https://raw.github.com/gu1lhermematos/VOXIPBX/$BRANCH/install/funcoes.sh
wget --no-check-certificate  https://raw.github.com/gu1lhermematos/VOXIPBX/$BRANCH/install/install-asterisk.sh
bash install-asterisk.sh
