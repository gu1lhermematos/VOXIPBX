#!/bin/bash

# Copyright (C) 2011-2014 BoxFacil
#
# Script incialmente desenvolvido por
# Emerson Luiz ( gu1lhermematos@BoxFacil.com.br )
echo "`ip addr show eth0 | cut -c16-32 | egrep \"[0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}$\"`" | tr -d ' : ' >/tmp/mac.txt
MAC=$(cat /tmp/mac.txt)
ALEATORIO=$MAC
BoxFacil="BoxFacil-$ALEATORIO"
BoxFacil2="$ALEATORIO"
#echo " $BoxFacil"
#echo "$BoxFacil2"
export BoxFacil=$BoxFacil
export BoxFacil2=$BoxFacil2
clear


if [ -e /etc/openvpn/$BoxFacil.crt ]; then

	clear
	echo "VPN Já instalada"

else

	cd /etc/openvpn/

	echo "`ip addr show eth0 | cut -c16-32 | egrep \"[0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}[:][0-9a-z]{2}$\"`" | tr -d ' : ' >/tmp/mac.txt
	MAC=$(cat /tmp/mac.txt)
	ALEATORIO=$MAC
	BoxFacil="BoxFacil-$ALEATORIO"
	BoxFacil2="$ALEATORIO"
#	echo " $BoxFacil"
#	echo "$BoxFacil2"
	export BoxFacil=$BoxFacil
	export BoxFacil2=$BoxFacil2


	echo "$BoxFacil" > /etc/hostname

	echo "127.0.0.1	localhost" > /etc/hosts
	IP_LOCAL=$(/sbin/ifconfig | sed -n '2 p' | awk '{print $3}')
	echo "${IP_LOCAL}	$BoxFacil.BoxFacil.com.br	$BoxFacil" >> /etc/hosts

	echo "

# The following lines are desirable for IPv6 capable hosts
::1     ip6-localhost ip6-loopback
fe00::0 ip6-localnet
ff00::0 ip6-mcastprefix
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters" >> /etc/hosts



	ssh root@vpn.BoxFacil.com.br '/usr/src/gera-key.sh '$BoxFacil''
	scp root@vpn.BoxFacil.com.br:/etc/openvpn/easy-rsa/keys/$BoxFacil* .

	wget https://raw.githubusercontent.com/gu1lhermematos/VOXIPBX/master/install/etc/openvpn/client.conf
	wget https://raw.githubusercontent.com/gu1lhermematos/VOXIPBX/master/install/etc/openvpn/ca.crt

	sed -i s/"cert ipbx.crt"/"cert "$BoxFacil".crt"/g /etc/openvpn/client.conf
	sed -i s/"key ipbx.key"/"key "$BoxFacil".key"/g /etc/openvpn/client.conf

	sed -i s/SNEP_VERSION/$BoxFacil2/g /var/www/ipbx/modules/default/views/scripts/systemstatus/index.phtml
	sed -i s/$BoxFacil2/"'$BoxFacil2'"/g /var/www/ipbx/modules/default/views/scripts/systemstatus/index.phtml

	mv BoxFacil* /etc/openvpn/
	/etc/init.d/openvpn restart


fi
