<?php
/**
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as
 *  published by the Free Software Foundation, either version 3 of
 *  the License, or (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/lgpl.txt>.
 */

/**
 * Dial trunk
 *
 * @category  Snep
 * @package   PBX_Rule_Action
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Henrique Grolli Bassotto
 */
class DiscarTronco extends PBX_Rule_Action {

    /**
     * @var Zend_Translate
     */
    private $i18n;

    public function __construct() {
        $this->i18n = Zend_Registry::get("Zend_Translate");
    }

    /**
     * Retorna o nome da Ação. Geralmente o nome da classe.
     *
     * @return Name da Ação
     */
    public function getName() {
        return $this->i18n->translate("Dial Trunk");
    }

    /**
     * @return string
     */
    public function getVersion() {
        return SNEP_VERSION;
    }

    /**
     * Envia email de alerta sobre uso desse tronco
     *
     * @param array string $adresses
     * @param array $informations, informações a serem anexadas ao email.
     */
    private function sendMailAlert($addresses, $informations) {
        $log = Zend_Registry::get('log');
        $config = Zend_Registry::get('config');
        $mail = new Zend_Mail('UTF-8');

        $mail->setFrom($config->system->mail, 'SNEP PBX');

        if(is_array($addresses)) {
            foreach ($addresses as $address) {
                $mail->addTo(trim($address));
            }
        }
        else {
            $mail->addTo($addresses);
        }

        $mail->setSubject($this->i18n->translate('[snep] Warning of trunk usage'));

        $tronco = PBX_Trunks::get($this->config['tronco']);
        $msg = $this->i18n->translate("\tThis warning is being delivered to you because Snep detected the usage of a trunk marked with your email.\n");
        $msg .= $this->i18n->translate("Warning to trunk $tronco, the following information where gathered by the system:\n");

        foreach ($informations as $info => $message) {
            $msg .= "$info: $message\n";
        }

        $mail->setBodyText($msg);
        $log->info("Sending email to '{$this->config['alertEmail']}'");
        $mail->send();
    }

    /**
     * Seta as configurações da ação.
     *
     * @param array $config configurações da ação
     */
    public function setConfig($config) {

        if( !isset($config['tronco']) ) {
            throw new PBX_Exception_BadArg("Trunk is required");
        }

        $config['dial_timeout'] = (isset($config['dial_timeout'])) ? $config['dial_timeout'] : '60';
        $config['dial_flags']   = (isset($config['dial_flags'])) ? $config['dial_flags'] : "TWK";
        $config['alertEmail']   = (isset($config['alertEmail'])) ? $config['alertEmail'] : "";
        $this->config = $config;
    }

    /**
     * Retorna uma breve descrição de funcionamento da ação.
     * @return Descrição de funcionamento ou objetivo
     */
    public function getDesc() {
        return $this->i18n->translate("Dial to a Trunk");
    }

    /**
     * Devolve um XML com as configurações requeridas pela ação
     * @return String XML
     */
    public function getConfig() {
        $i18n = $this->i18n;

        $tronco          = (isset($this->config['tronco']))?"<value>{$this->config['tronco']}</value>":"";
        $dial_timeout    = (isset($this->config['dial_timeout']))?"<value>{$this->config['dial_timeout']}</value>":"";
        $dial_flags      = (isset($this->config['dial_flags']))?"<value>{$this->config['dial_flags']}</value>":"";
        $dial_limit      = (isset($this->config['dial_limit']))?"<value>{$this->config['dial_limit']}</value>":"";
        $omit_kgsm       = (isset($this->config['omit_kgsm']))?"<value>{$this->config['omit_kgsm']}</value>":"";
        $alertEmail      = (isset($this->config['alertEmail']))?"<value>{$this->config['alertEmail']}</value>":"";

        
        $Tdialtime = $i18n->translate("Dial Timeout");
        $Tcallduration = $i18n->translate("Call Duration Limit");
        $Tmiliseconds = $i18n->translate("in milliseconds");
        $Tdial = $i18n->translate("Dial Flags");
        $Talert = $i18n->translate("Ommit origin (only for Khomp KGSM)");
        $Talertemail = $i18n->translate("Alert email");
        return <<<XML
<params>
    <tronco>
        <id>tronco</id>
        $tronco
    </tronco>

    <int>
        <id>dial_timeout</id>
        <label>$Tdialtime</label>
        <unit>{$i18n->translate("segundos")}</unit>
        <size>2</size>
        <default>60</default>
        $dial_timeout
    </int>

    <int>
        <id>dial_limit</id>
        <default>0</default>
        <label>$Tcallduration</label>
        <size>4</size>
        <unit>$Tmiliseconds</unit>
        $dial_limit
    </int>

    <string>
        <id>dial_flags</id>
        <label>$Tdial</label>
        <size>10</size>
        <default>TWK</default>
        $dial_flags
    </string>

    <boolean>
        <id>omit_kgsm</id>
        <default>false</default>
        <label>$Talert</label>
        $omit_kgsm
    </boolean>

    <string>
        <id>alertEmail</id>
        <label>$Talertemail</label>
        <size>50</size>
        $alertEmail
    </string>
</params>
XML;
    }

    /**
     * @return string XML com as configurações default para as classes
     */
    public function getDefaultConfigXML() {
        $i18n = $this->i18n;

        $play_warning_value = isset($this->defaultConfig['play_warning']) ? "<value>{$this->defaultConfig['play_warning']}</value>" : "";
        $warning_freq_value = isset($this->defaultConfig['warning_freq']) ? "<value>{$this->defaultConfig['warning_freq']}</value>" : "";
        $warning_sound_value = isset($this->defaultConfig['warning_sound']) ? "<value>{$this->defaultConfig['warning_sound']}</value>" : "";

        
        $Tsecondsleft = $i18n->translate("Seconds left to alert");
        $Tinmili = $i18n->translate("in milliseconds");
        $Trepetition = $i18n->translate("Alert repetition rate");
        $Talertsound = $i18n->translate("Alert sound");
        return <<<XML
<params>
    <int>
        <id>play_warning</id>
        <label>$Tsecondsleft</label>
        <unit>$Tinmili</unit>
        <size>5</size>
        $play_warning_value
    </int>
    <int>
        <id>warning_freq</id>
        <label>$Trepetition</label>
        <unit>{$i18n->translate("in milliseconds")}</unit>
        <size>5</size>
        $warning_freq_value
    </int>
    <string>
        <id>warning_sound</id>
        <default>beep</default>
        <label>$Talertsound</label>
        $warning_sound_value
    </string>
</params>
XML;
    }

    /**
     * Executa a ação. É chamado dentro de uma instancia usando AGI.
     *
     * @param Asterisk_AGI $asterisk
     * @param Asterisk_AGI_Request $request
     */
    public function execute($asterisk, $request) {
        $log = Zend_Registry::get('log');
        $trs = $this->i18n;

        $tronco = PBX_Trunks::get($this->config['tronco']);

        // Montando as Flags para limite na ligação
        $flags = $this->config['dial_flags'];
        if(isset($this->config['dial_limit']) && $this->config['dial_limit'] > 0) {
            $flags .= "L(" . $this->config['dial_limit'];
            // play_warning_value
            if( isset($this->defaultConfig['play_warning']) && $this->defaultConfig['play_warning'] > 0) {
                $flags .= ":" . $this->defaultConfig['play_warning'];
                // warning_freq
                if( isset($this->defaultConfig['warning_freq']) && $this->defaultConfig['warning_freq'] > 0) {
                    $flags .= ":" . $this->defaultConfig['warning_freq'];
                }
            }
            $flags .= ")";

            if( isset($this->defaultConfig['warning_sound']) ) {
                $warning_sound = $this->defaultConfig['warning_sound'] != "" ? $this->defaultConfig['warning_sound'] : "beep";
                $asterisk->set_variable("LIMIT_WARNING_FILE", $warning_sound);
            }
        }

        if($tronco->getDtmfDialMode()) {
            $dst_number = $tronco->getDtmfDialNumber();
            $flags .= "D($request->destino)";
        }
        else {
            $dst_number = $request->destino;
        }

        if($tronco->getInterface() instanceof PBX_Asterisk_Interface_SIP_NoAuth || $tronco->getInterface() instanceof PBX_Asterisk_Interface_IAX2_NoAuth) {
            $destiny = $tronco->getInterface()->getTech() . "/" . $dst_number . "@" . $tronco->getInterface()->getHost();
        }
        else {
            $postfix = ( isset($this->config['omit_kgsm']) && $this->config['omit_kgsm'] == "true" ) ? "/orig=restricted" : "";
            $destiny = $tronco->getInterface()->getCanal() . "/" . $dst_number . $postfix;
        }

        $log->info("Dialing to $request->destino through trunk {$tronco->getName()}($destiny)");

        $dialstatus = $asterisk->get_variable("DIALSTATUS");
        $lastdialstatus = $dialstatus['data'];

        if( Zend_Registry::get('outgoingNumber') !== "" ) {
            $asterisk->set_variable("CALLERID(num)", Zend_Registry::get('outgoingNumber') );
        }

        $log->debug("Dial($destiny, {$this->config['dial_timeout']}, $flags)");
        // ==== DIAL ====
        $asterisk->exec_dial($destiny, $this->config['dial_timeout'], $flags);

        $dialstatus = $asterisk->get_variable("DIALSTATUS");
        $log->debug("DIALSTATUS: " . $dialstatus['data']);

        // Enviar email de alerta.
        if(isset($this->config['alertEmail']) && $this->config['alertEmail'] != "") {
            $informations = array(
                $trs->translate('Rule')             => $this->getRule(),
                $trs->translate('Call Time')        => date('H:i'),
                $trs->translate('Call Date')        => date('d/m/Y'),
                $trs->translate('Original Source')  => $request->getOriginalCallerid(),
                $trs->translate('Original Destination') => $request->getOriginalExtension(),
                $trs->translate('Source')           => $request->origem,
                $trs->translate('Destination')      => $request->destino,
                $trs->translate('Call status')      => $dialstatus['data']
            );

            if($lastdialstatus != "") {
                $lastdialaction = null;
                foreach ($this->getRule()->getAcoes() as $action) {
                    if($action == $this) {
                        break;
                    }
                    $cfg = $action->getConfigArray();
                    if($action instanceof DiscarTronco) {
                        $lastdialaction = PBX_Trunks::get($cfg['tronco']);
                    }
                    else if($action instanceof DiscarRamal) {
                        $lastdialaction = $cfg['ramal'];
                    }
                }
                $informations[$trs->translate("\nThere were a previous call attempt")] = "";
                $informations[$trs->translate("Last call to")] = $lastdialaction;
                $informations[$trs->translate("Status of last call")] = $lastdialstatus;
            }

            $this->sendMailAlert(explode(",",$this->config['alertEmail']), $informations);
        }

        switch($dialstatus['data']) {
            case 'ANSWER':
            case 'CANCEL':
            case 'NOANSWER':
            case 'BUSY':
                throw new PBX_Rule_Action_Exception_StopExecution("Call end");
                break;
            default:
                $log->err($dialstatus['data'] . " dialing to $request->destino through trunk $tronco");
        }
    }
}
