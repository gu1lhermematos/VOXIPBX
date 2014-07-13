<?php
/**
 * Ação que envia email quando executada.
 *
 * @see PBX_Rule
 * @see PBX_Rule_Action
 *
 * @category  Snep
 * @package   PBX_Rule_Action
 * @copyright Copyright (c) 2009 OpenS Tecnologia
 * @author    Henrique Grolli Bassotto
 */
class EmailAction extends PBX_Rule_Action {
    /**
     * Retorna o nome da Ação. Geralmente o nome da classe.
     * @return Nome da Ação
     */
    public function getName() {
        return "Email";
    }

    /**
     * Retorna o numero da versão da classe.
     * @return Versão da classe
     */
    public function getVersion() {
        return Zend_Registry::get('snep_version');
    }

    /**
     * Retorna uma breve descrição de funcionamento da ação.
     * @return Descrição de funcionamento ou objetivo
     */
    public function getDesc() {
        return "Envia um email quando executada";
    }

    /**
     * Devolve um XML com as configurações requeridas pela ação
     * @return String XML
     */
    public function getConfig() {
        $to = (isset($this->config['to']))?"<value>{$this->config['to']}</value>":"";
        $message = (isset($this->config['message']))?"<value>{$this->config['message']}</value>":"";
        $subject = (isset($this->config['subject']))?"<value>{$this->config['subject']}</value>":"";

        return <<<XML
<params>
    <string>
        <id>subject</id>
        <label>Assunto</label>
        <description></description>
        $subject
    </string>
    <email>
        <id>to</id>
        <label>Destinatário</label>
        <description>Endereço de email do destinatário</description>
        $to
    </email>
    <text>
        <id>message</id>
        <label>Mensagem</label>
        $message
    </text>
</params>
XML;
    }

    /**
     * Executa a ação.
     * @param Asterisk_AGI $asterisk
     * @param PBX_Asterisk_AGI_Request $request
     */
    public function execute($asterisk, $request) {
        $log = Zend_Registry::get('log');
        
        $mail = new Zend_Mail("utf8");
        $mail->setFrom("Snep PBX");
        $mail->setSubject($this->config['subject']);
        $mail->addTo($this->config['to']);
        $mail->setBodyText($this->config['message']);

        $log->info("Enviando email para " . $this->config['to']);
        $mail->send();
    }
}
