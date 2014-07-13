#!/bin/bash

# Copyright (C) 2011-2014 ToFalando
#
# Script incialmente desenvolvido por
# Emerson Luiz ( eluizbr@tofalando.com.br )
echo "`ip addr show eth0 | cut -c16-32 | egrep \"[0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}$\"`" | tr -d ' : ' >/tmp/mac.txt
MAC=$(cat /tmp/mac.txt)
ALEATORIO=$MAC
BOXFACIL="Boxfacil-$ALEATORIO"
BOXFACIL2="$ALEATORIO"
#echo " $BOXFACIL"
#echo "$BOXFACIL2"
export BOXFACIL=$BOXFACIL
export BOXFACIL2=$BOXFACIL2
clear


if [ -e /etc/openvpn/$BOXFACIL.crt ]; then

	clear
	echo "VPN Já instalada"

else

	cd /etc/openvpn/

	echo "`ip addr show eth0 | cut -c16-32 | egrep \"[0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}$\"`" | tr -d ' : ' >/tmp/mac.txt
	MAC=$(cat /tmp/mac.txt)
	ALEATORIO=$MAC
	BOXFACIL="BoxFacil-$ALEATORIO"
	BOXFACIL2="$ALEATORIO"
#	echo " $BOXFACIL"
#	echo "$BOXFACIL2"
	export BOXFACIL=$BOXFACIL
	export BOXFACIL2=$BOXFACIL2


	echo "$BOXFACIL" > /etc/hostname

	echo "127.0.0.1	localhost" > /etc/hosts
	IP_LOCAL=$(/sbin/ifconfig | sed -n '2 p' | awk '{print $3}')
	echo "${IP_LOCAL}	$BOXFACIL.boxfacil.com.br	$BOXFACIL" >> /etc/hosts

	echo "

# The following lines are desirable for IPv6 capable hosts
::1     ip6-localhost ip6-loopback
fe00::0 ip6-localnet
ff00::0 ip6-mcastprefix
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters" >> /etc/hosts



	ssh root@vpn.boxfacil.com.br '/usr/src/gera-key.sh '$BOXFACIL''
	scp root@vpn.boxfacil.com.br:/etc/openvpn/easy-rsa/keys/$BOXFACIL* .

	wget https://raw.githubusercontent.com/gu1lhermematos/VOXIPBX/master/install/etc/openvpn/client.conf
	wget https://raw.githubusercontent.com/gu1lhermematos/VOXIPBX/master/install/etc/openvpn/ca.crt

	sed -i s/"cert ipbx.crt"/"cert "$BOXFACIL".crt"/g /etc/openvpn/client.conf
	sed -i s/"key ipbx.key"/"key "$BOXFACIL".key"/g /etc/openvpn/client.conf

	sed -i s/SNEP_VERSION/$BOXFACIL2/g /var/www/ipbx/modules/default/views/scripts/systemstatus/index.phtml
	sed -i s/$BOXFACIL2/"'$BOXFACIL2'"/g /var/www/ipbx/modules/default/views/scripts/systemstatus/index.phtml

	mv BoxFacil* /etc/openvpn/
	/etc/init.d/openvpn restart


fi
