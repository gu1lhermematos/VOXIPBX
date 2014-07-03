#!/bin/bash

ALEATORIO=`echo $RANDOM`
TOFALANDO="tofalando-$ALEATORIO"
echo " $TOFALANDO"
export TOFALANDO=$TOFALANDO

ssh root@vpn.tofalando.com.br '/usr/src/gera-key.sh '$TOFALANDO''
scp root@vpn.tofalando.com.br:/etc/openvpn/easy-rsa/keys/$TOFALANDO* .
