#!/bin/bash

# Copyright (C) 2011-2014 ToFalando
#
# Script incialmente desenvolvido por
# Emerson Luiz ( eluizbr@tofalando.com.br )

# Configurar o Branch
BRANCH='devel'


  IFCONFIG=`which ifconfig 2>/dev/null||echo /sbin/ifconfig`
    IPADDR=`$IFCONFIG tun0|gawk '/inet addr/{print $2}'|gawk -F: '{print $2}'`
    	export MEUIP=$IPADDR
	    echo $IPADDR
	
	if [ -z "$IPADDR" ]; then
        clear
        echo "We have not detected your IP address automatically, please enter it manually"
        read IPADDR
    fi
