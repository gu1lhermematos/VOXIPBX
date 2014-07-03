#!/bin/bash


#ALEATORIO=`echo $RANDOM`
#TOFALANDO="tofalando-$ALEATORIO"
echo " $TOFALANDO"
cd /etc/openvpn/easy-rsa/
source vars
./build-key $1
