#!/bin/bash   
# * Programa: get_instaldor - Obtem o instalador de modulos do Update 
# * Copyright (c) 2013 - Opens Tecnologia - Projeto SNEP
# * Licenciado sob Creative Commons. Veja arquivo ./doc/licenca.txt
# * Autor: Flavio Henrique Somensi <flavio@opens.com.br>
#  

# Define variaveis
DST="/var/www/instalador"
ARQ="http://www.sneplivre.com.br/downloads/instalador.tar.gz"
# Obtem o arquivo
wget -c $ARQ
if [ $? != 0 ];then
   echo "dow" 
else
  # Descompacta
  if [ -d $DST ]; then
     mv $DST $DST.bkp
  fi
  mkdir -p $DST 
  tar -xvzf instalador.tar.gz -C $DST
  # Ajusta permissoes
  chown www-data.www-data $DST -R
  rm instalador.tar.gz
fi
