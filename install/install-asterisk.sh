#!/bin/bash
# Copyright (C) 2011-2014 BoxFacil
#
# Script incialmente desenvolvido por
# Emerson Luiz ( gu1lhermematos@BoxFacil.com.br )


source funcoes.sh
# Configurar o Branch
#BRANCH='devel'

    clear
    echo " > Instalar BoxFacil IPBX"
    echo "====================================="
    echo "  1)  Instalar Central E1 / Placas"
    echo "  2)  Instalar Central SIP"
    echo "  3)  Instalar Portabilidade"
    echo "  4)  Instalar G729 FREE"
    echo "  5)  Instalar Mesa Operadora"
    echo "  6)  Instalar DONGLE USB"
    echo "  7)  Instalar Tarifador"
    echo "  0)  Sair"
    echo -n "(0-7) : "
    read OPTION < /dev/tty

ExitFinish=0

while [ $ExitFinish -eq 0 ]; do


	 case $OPTION in

		1)

                        #Instalar Placas
                        clear
                        cd /usr/src/
                        wget --no-check-certificate https://raw.github.com/gu1lhermematos/VOXIPBX/$BRANCH/install/install-cards.sh
                        ExitFinish=1
                        bash install-cards.sh
		;;

		2)

		
		      #Instalando ASTERISK
			clear
			cd /usr/src/
			wget --no-check-certificate https://raw.githubusercontent.com/gu1lhermematos/VOXIPBX/$BRANCH/install/install-tofalando.sh
			func_install_asterisk
			bash install-tofalando.sh
			cd /var/www/snep/install/
			mysql -uroot -ptofalando2014 snep25 < tofalando.sql
			cd /usr/src/
			bash install-asterisk.sh
		;;


		3)

  			#Instalar o Portabilidade IPBX
        		clear
			func_install_portabilidade
			ExitFinish=1
			bash install-asterisk.sh
		;;

                4)

                        #Instalar o G729 FREE
                        clear
			cd /usr/src/
                        # Checar asterisk
		if [ ! -d "/etc/asterisk" ]; then

                        clear
			cd /usr/src/		
			func_install_asterisk
			func_install_g729
			bash install-asterisk.sh
        	        ExitFinish=1

		
		else
                        clear
			cd /usr/src/
			func_install_g729
			bash install-asterisk.sh
	           ExitFinish=1

		fi
		
		
# Fim seta CPU

                        
                ;;

                5)

                        #Instalar a Mesa Operadora
                        clear
                        func_install_mesa
                        bash install-asterisk.sh

                ;;

		6)
		
			#Install DONGLE USB
			clear
			func_install_dongle
			bash install-asterisk.sh
		;;

		7)
		
			#Install A2B
			clear
			func_install_A2B
			bash install-asterisk.sh
		;;


		0)
        		clear
			cd /usr/src/
			rm -rf asterisk* dahdi* lib* install* fop*  funcoes* linux-3* openr2* chan_* a2b*
			# Apaga Instalacao
			cd /var/www/ipbx/
			rm -rf install

			ExitFinish=1
		;;
		*)
	esac
done
