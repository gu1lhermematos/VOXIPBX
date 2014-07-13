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
 * Classe que faz verificaç� se o módulo cc existe no SNEP, caso 
 * exista  verifica se o script ler_queues está rodando
 *
 * @see Snep_Inspector_Test
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2013 OpenS Tecnologia
 * @author    Tiago Zimmermann <tiago.zimmermann@opens.com.br> 
 *
 */
class Ler_queues extends Snep_Inspector_Test {

    /**
     * Lista de arquivos a serem verificados
     * @var Array
     */
    public $paths = array();

    /**
     * Executa teste na criação do objeto.
     */
    public function __contruct() {

        self::getTests();
    }

    /**
     * Executa testes de existencia, dono de arquivo e permissão de escrita e leitura.
     * @return Array
     */
    public function getTests() {

        // Seta erro como falso.
        $result['logs']['error'] = 0;

        // Registra indice de mensagem no array.
        $result['logs']['message'] = '';

        // Pega array do setup.conf do Zend_Registry.
        $config = Zend_Registry::get('config');

        // Pega registro path.instalador do setup.conf
        $path = $config->system->path->base . "/modules/cc/";

        $archive = "info.xml";

        // Verifica existência do diretorioo.
        if (file_exists($path . $archive)) {

            $output = shell_exec('ps aux | grep ler_que');

            $findme = 'ler_queues.php';
            $pos = strpos($output, $findme);

            if ($pos === false) {
                // Não existindo concatena mensagem de erro.
                $result['logs']['message'] .= Zend_Registry::get("Zend_Translate")->translate("Start Script ler_queues.") . "\n";

                // Seta erro como verdadeiro.
                $result['logs']['error'] = 1;
            }
            // Transforma newline em br
            $result['logs']['message'] = $result['logs']['message'];
        }
        return $result['logs'];
    }

    public function getTestName() {
        return Zend_Registry::get("Zend_Translate")->translate("Script ler_queues");
    }

}

