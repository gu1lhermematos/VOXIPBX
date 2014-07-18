#!/bin/bash

# Copyright (C) 2011-2014 ToFalando
#
# Script incialmente desenvolvido por
# Emerson Luiz ( eluizbr@tofalando.com.br )

source funcoes.sh
    clear
    echo " > Instalar PLACAS ToFalando"
    echo "====================================="
    echo "  1)  Instalar PLaca E1 - R2"
    echo "  2)  Instalar Placa E1 - ISDN "
    echo "  3)  Instalar Placa TRONCO FXO/FXS "
    echo "  4)  Instalar Placa E1 R2 + FXO/FXS "
    echo "  5)  Instalar Placa E1 ISDN + FXO/FXS "
    echo "  0)  Sair"
    echo -n "(0-5) : "
    read OPTION < /dev/tty

ExitFinish=0

while [ $ExitFinish -eq 0 ]; do


	 case $OPTION in

		1)
			clear
			cd /usr/src/


				clear
				func_install_dahdi
				func_install_openr2
				func_install_asterisk
				wget --no-check-certificate https://raw.githubusercontent.com/gu1lhermematos/VOXIPBX/$BRANCH/install/install-tofalando.sh
				bash install-tofalando.sh
				cd /var/www/ipbx/install/
				mysql -uroot -ptofalando2014 snep25 < tofalando.sql
				cd /usr/src/
				bash install-cards.sh
				ExitFinish=1


                ;;


		2)
        			clear
        			cd /usr/src/
				clear
				func_install_dahdi
				func_install_libpri
				func_install_asterisk
				wget --no-check-certificate https://raw.githubusercontent.com/gu1lhermematos/VOXIPBX/$BRANCH/install/install-tofalando.sh
				bash install-tofalando.sh
				cd /var/www/ipbx/install/
				mysql -uroot -ptofalando2014 snep25 < tofalando.sql
				cd /usr/src/
				bash install-cards.sh
				ExitFinish=1
		;;

		3)
        			clear
        			cd /usr/src/
				clear
				func_install_dahdi_placas
				func_install_asterisk
				func_install_g729
				wget --no-check-certificate https://raw.githubusercontent.com/gu1lhermematos/VOXIPBX/$BRANCH/install/install-tofalando.sh
				bash install-tofalando.sh
				cd /var/www/ipbx/install/
<<<<<<< HEAD
				mysql -uroot -ptofalando2014 snep25 < tofalando.sql	
=======
				mysql -uroot -ptofalando2014 snep25 < tofalando.sql
>>>>>>> master
				cd /usr/src/
				bash install-cards.sh
				ExitFinish=1
		;;


		4)
				clear
				cd /usr/src/


				clear
				func_install_dahdi
				func_install_openr2
				func_install_asterisk
				func_install_g729
				wget --no-check-certificate https://raw.githubusercontent.com/gu1lhermematos/VOXIPBX/$BRANCH/install/install-tofalando.sh
				bash install-tofalando.sh
				cd /var/www/ipbx/install/
				mysql -uroot -ptofalando2014 snep25 < tofalando.sql
				cd /var/www/ipbx/install/placas
				cat genconf_parameters > /etc/dahdi/genconf_parameters
				cp system_r2.conf > /etc/dahdi/system.conf
				dahdi_genconf -v
				cd /usr/src
				bash install-cards.sh
				ExitFinish=1
		;;

		5)
        			clear
        			cd /usr/src/
				clear
				func_install_dahdi_placas
				func_install_asterisk
				func_install_g729
				wget --no-check-certificate https://raw.githubusercontent.com/gu1lhermematos/VOXIPBX/$BRANCH/install/install-tofalando.sh
				bash install-tofalando.sh
				cd /var/www/ipbx/install/
				mysql -uroot -ptofalando2014 snep25 < tofalando.sql
				cd /var/www/ipbx/install/placas/
				cp genconf_parameters /etc/dahdi/
				dahdi_genconf -v
				cd /usr/src.sh
				ExitFinish=1
		;;



		0)
        			clear
        			cd /usr/src/
				bash install-asterisk.sh
				ExitFinish=1
		;;
		*)
	esac
done

