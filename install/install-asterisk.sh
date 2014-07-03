#!/bin/bash

    clear
    echo " > Instalar ToFalando IPBX"
    echo "====================================="
    echo "  1)  Instalar PLacas TDM / E1"
    echo "  2)  Instalar SIP / VOIP apenas"
    echo "  3)  Instalar PABX ToFalando"
    echo "  4)  Instalar Portabilidade"
    echo "  5)  Instalar G729 FREE"
    echo "  6)  Instalar Mesa Operadora"	
    echo "  0)  Sair"
    echo -n "(0-6 : "
    read OPTION < /dev/tty

ExitFinish=0

while [ $ExitFinish -eq 0 ]; do


	 case $OPTION in

		1)
			clear
			cd /usr/src/
			wget -c http://downloads.asterisk.org/pub/telephony/dahdi-linux-complete/dahdi-linux-complete-2.9.0+2.9.0.1.tar.gz
			wget -c http://downloads.asterisk.org/pub/telephony/libpri/libpri-1.4-current.tar.gz
			wget -c http://downloads.asterisk.org/pub/telephony/asterisk/asterisk-1.8.26.1.tar.gz

			# Instalando DAHDI
			tar xvfz dahdi-linux-complete-2.9.0+2.9.0.1.tar.gz
			ln -s dahdi-linux-complete-2.9.0+2.9.0.1/ dahdi
			cd dahdi
			make all
			make install
			make config
			
			clear
			cd ..
			# DAHDI Instalado

			#Instaldo LIBPRI
			cd /usr/src
			tar xvfz libpri-1.4-current.tar.gz
			cd libpri-1.4.14
			make
			make install
			ln -s libpri-1.4.14 libpri
			cd ..
			
			clear
			#Instalando ASTERISK
			cd /usr/src/ 
			tar zxvf asterisk-1.8.26.1.tar.gz
			ln -s asterisk-1.8.26.1 asterisk
			cd asterisk
			make distclean
			./configure
			contrib/scripts/get_mp3_source.sh
			make menuselect.makeopts
			menuselect/menuselect --disable CORE-SOUNDS-EN-GSM --enable app_mysql --enable cdr_mysql --enable res_config_mysql --enable cdr_odbc --enable res_odbc --enable res_config_odbc --enable  format_mp3 --enable cdr_csv menuselect.makeopts
			make
			make install
			make config
			make samples
			ldconfig
			cd ..
			echo done
			ExitFinish=1
			bash install-asterisk.sh
		;;

		2)

		
		      #Instalando ASTERISK
			clear
                        cd /usr/src/ 
			tar zxvf asterisk-1.8.26.1.tar.gz
			ln -s asterisk-1.8.26.1 asterisk
			cd asterisk
			make distclean
			./configure
			contrib/scripts/get_mp3_source.sh
			make menuselect.makeopts
			menuselect/menuselect --disable CORE-SOUNDS-EN-GSM --enable app_mysql --enable cdr_mysql --enable res_config_mysql --enable cdr_odbc --enable res_odbc --enable res_config_odbc --enable  format_mp3 --enable cdr_csv menuselect.makeopts
			make
			make install
			make config
			make samples
			ldconfig
			cd ..
                        echo done
                        ExitFinish=1
                        bash install-asterisk.sh
		;;



                3)

                        #Instalar o ToFalando IPBX
                        clear
			cd /usr/src/
                        wget --no-check-certificate https://raw.github.com/guilhermeguto/VOXIPBX/master/install/install-tofalando.sh
                        ExitFinish=1
			bash install-tofalando.sh
                ;;


		4)

  			#Instalar o Portabilidade IPBX
        		clear
			cd /usr/src/
        		wget --no-check-certificate https://raw.github.com/guilhermeguto/VOXIPBX/master/install/install-portabilidade.sh
        		bash install-portabilidade.sh
			ExitFinish=1
			bash install-asterisk.sh
		;;

                5)

                        #Instalar o G729 FREE
                        clear
			cd /usr/src/
                        wget --no-check-certificate https://raw.github.com/guilhermeguto/VOXIPBX/master/install/install-g729.sh
                        bash install-g729.sh
                        ExitFinish=1
                        bash install-asterisk.sh
                ;;

                6)

                        #Instalar a Mesa Operadora
                        clear
                        cd /usr/src/
                        wget --no-check-certificate https://raw.github.com/guilhermeguto/VOXIPBX/master/install/install-mesa.sh
                        bash install-mesa.sh
                        ExitFinish=1
                        bash install-asterisk.sh
                ;;


		0)
        		clear
			cd /usr/src/
			rm -rf asterisk* dahdi* lib* install* fop*  
			ExitFinish=1
		;;
		*)
	esac
done

