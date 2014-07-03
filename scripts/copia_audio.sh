#!/bin/bash
cp -f /tmp/gravatmp.gsm /var/lib/asterisk/sounds/pt_BR/000_GRAVADO_`date '+%d%m%Y_%H%M%S'`.gsm
cp -f /tmp/gravatmp.gsm /var/lib/asterisk/sounds/000_GRAVADO_`date '+%d%m%Y_%H%M%S'`.gsm
chown -R www-data.www-data /var/lib/asterisk/sounds/

