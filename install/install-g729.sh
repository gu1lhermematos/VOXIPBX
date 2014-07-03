#!/bin/bash

cpu=`getconf LONG_BIT`

if echo $cpu | grep -i "32" > /dev/null ; then
	echo "32"
	cd /var/www/ipbx/
	git pull origin master
	cd /usr/lib/asterisk/modules/
	wget -c http://asterisk.hosting.lv/bin/codec_g729-ast18-gcc4-glibc-pentium.so
	mv codec_g729-ast18-gcc4-glibc-pentium.so codec_g729.so
	chmod 755 /usr/lib/asterisk/modules/codec_g729.so
	asterisk -x "core restart now"
else
	echo "64"
	cd /var/www/ipbx/
	git pull origin master

	cd /usr/lib/asterisk/modules/
	wget -c http://asterisk.hosting.lv/bin/codec_g729-ast18-icc-glibc-x86_64-pentium4.so
	mv codec_g729-ast18-icc-glibc-x86_64-pentium4.so codec_g729.so
	chmod 755 /usr/lib/asterisk/modules/codec_g729.so
	asterisk -x "core restart now"

fi

# Fim seta CPU


