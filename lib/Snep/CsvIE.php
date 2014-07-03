<?php
/**
 *  This file is part of SNEP.
 *  Para território Brasileiro leia LICENCA_BR.txt
 *  All other countries read the following disclaimer
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
 * Gerencia exportação e importação de CSV
 *
 * @see Snep_CsvIE
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Iago Uilian Berndt
 *
 */
class Snep_CsvIE {


	/**
	 * Contem a tabela a ser exportada
	 */
	public $table;

	/**
	 * Contem o tipo de estrutura a ser exportada
	 */
	public $type;

	/**
         * columns
	 * @return <array> colunas da tabela
	 */
	public function columns(){

		$db = Zend_Registry::get('db');
		if($this->type == "cartesiano"){
			return array(array_keys($db->describeTable($this->table[0])), array_keys($db->describeTable($this->table[1])));
		}elseif($this->type == "ignore"){
			return array_keys($db->describeTable($this->table[0]));
		}else{
			return array_keys($db->describeTable($this->table));
		}
	}


	/**
	 * Define a tabela e o tipo de estrutura
	 * @param <string> $table tabela.
	 * @param <string> $type tipo de estrutura.
	 */
	public function __construct($table, $type = null) {
		 
		$this->table = $table;
		$this->type = $type;
		 
	}

	/**
         * getForm
	 * @return <string> html do formulario de exportação
	 */
	public function getForm(){
		if(isset($_FILES['csv']['tmp_name']))
			if(is_file($_FILES['csv']['tmp_name'])){

			if($this->type == "cartesiano"){
				$result = $this->importCartesiano(fopen($_FILES['csv']['tmp_name'], 'r'));
			}else if($this->type == "ignore"){
				$result = $this->import(fopen($_FILES['csv']['tmp_name'], 'r'), $this->columns(), $this->table[0]);
			}else{
				$result = $this->import(fopen($_FILES['csv']['tmp_name'], 'r'), $this->columns(), $this->table);
			}


			return '<div class="zend_form" id="form_ie">'.
					'<span>'.$result[0].'</span>'.$result[1].
					'<div class="menus">'.
					'<input type="submit" name="submit" onclick="history.go(-1)" class="voltar" id="submit" title="Voltar" value="Voltar">'.
					'</div>'.
					'</div>';
		}
		return '<form enctype="multipart/form-data" action="" method="post">'.
				'<div class="zend_form" id="form_ie">'.
				'<span>Arquivo CSV</span>'.
				'<input type="file" name="csv" id="csv"/>'.
				'<div class="menus">'.
				'<input type="submit" name="submit" id="submit" title="Enviar" value="Enviar">'.
				'</div>'.
				'</div>'.
				'</div>'.
				'</form>';
	}

	/**
	 * export - Gera e imprime a csv
	 */
	public function export(){
		$db = Zend_Registry::get('db');
		 
		$columns = $this->columns();
		 
		if($this->type == "cartesiano"){
			$values = $db->fetchAll("SELECT * FROM {$this->table[0]}, {$this->table[1]} WHERE {$this->table[2]}");
			$columns = array_merge($columns[0], $columns[1]);
		}elseif($this->type == "ignore"){
			$values = $db->fetchAll("SELECT * FROM {$this->table[0]} WHERE {$this->table[1]}");
		}else{
			$values = $db->fetchAll("SELECT * FROM $this->table");
		}
		 
		$dateNow = new Zend_Date();
		 
		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.Zend_Controller_Front::getInstance()->getRequest()->getControllerName().'_'. $dateNow->toString(" dd-MM-yyyy_hh'h'mm'm' ") . '.csv"');
		 
		$tmp = tmpfile();
		fputcsv($tmp, $columns);
		foreach($values as $row){
			$data = array();
			foreach($columns as $value)  $data[] = str_replace(array('"', "'"), array('\\"', "\\'"), $row[$value]);
			fputcsv($tmp, $data);
		}
		rewind($tmp);
		while(FALSE != ($line = fgets($tmp)))echo $line;
		fclose($tmp);
		 
	}

	/**
         * exportResult
	 * @return <string> html com o resultado da exportação
	 */
	public function exportResult(){
		$db = Zend_Registry::get('db');
		 
		if($this->type == "cartesiano"){
			$total = $db->fetchOne("SELECT COUNT(*) AS count FROM {$this->table[0]}" );
		}elseif($this->type == "ignore"){
			$total = $db->fetchOne("SELECT COUNT(*) AS count FROM {$this->table[0]} WHERE {$this->table[1]}");
			$total .= "<br/>".$this->table[2]. ($db->fetchOne("SELECT COUNT(*) AS count FROM {$this->table[0]}") - $total);
		}else{
			$total = $db->fetchOne("SELECT COUNT(*) AS count FROM $this->table" );
		}
		 
		return '<form enctype="" action="'. Zend_Controller_Front::getInstance()->getBaseUrl() .'/'. Zend_Controller_Front::getInstance()->getRequest()->getModuleName().'/'. Zend_Controller_Front::getInstance()->getRequest()->getControllerName().'/'. Zend_Controller_Front::getInstance()->getRequest()->getActionName().'/download/true" method="post">'.
				'<div class="zend_form" id="form_ie">'.
				'<span>Exportar CSV</span>'.
				'Total de cadastros a serem exportados: '.$total.
				'<div class="menus">'.
				'<input type="submit" style="width:100px;" name="submit" id="submit" title="Download" value="Download">'.
				'</div>'.
				'</div>'.
				'</div>'.
				'</form>';
	}

	/**
	 * importCartesiano - importa csv com estrutura de produto cartesiano
	 * @param <string> $file arquivo a ser importado
	 */
	public function importCartesiano($f){
		$tmp1 = tmpfile();
		$tmp2 = tmpfile();
		$columns = $this->columns();
		 
		$line = fgetcsv($f);
		foreach($line as $key=>$value){
			if($key < count($columns[0])){
				if($value != $columns[0][$key]) return array("Erro", "Estrutura do CSV não pode ser importada0");
			}else{
				if($value != $columns[1][$key-count($columns[0])]) return array("Erro", "Estrutura do CSV não pode ser importada1");
			}
		}
		 
		fputcsv($tmp1, $columns[0]);
		fputcsv($tmp2, $columns[1]);
		 
		$ant = array();
		 
		while($line = fgetcsv($f)){
			$data = array();
			foreach($line AS $key => $value){
				if($key < count($columns[0])){
					$data[0][] = $value;
				}else{
					$data[1][] = $value;
				}
			}
			if($ant !== $data[0])fputcsv($tmp1, $data[0]);
			$ant = $data[0];
			fputcsv($tmp2, $data[1]);
		}
		rewind($tmp1);
		rewind($tmp2);
		 
		$result = $this->import($tmp2, $columns[1], $this->table[1]);
		if($result[0] != "Erro") $result = $this->import($tmp1, $columns[0], $this->table[0]);
		return $result;
		 
	}

        /**
         * import
         * @param <string> $f
         * @param <array> $columns
         * @param <string> $table
         * @return type
         */
	public function import($f, $columns, $table) {
		$db = Zend_Registry::get('db');
		$line = fgetcsv($f);
		if($columns !== $line) return array("Erro", "Estrutura do CSV não pode ser importada");
		$i = 0;
		$buffer = array();
		$pedido = 0;
		$inserido = 0;
		do{
			$line = fgetcsv($f);
			if($buffer && (!$line || !($i%10))){
				$query = "INSERT IGNORE INTO $table(`".implode("`, `", $columns)."`) VALUES ".implode(",", $buffer);
				$inserido += $db->Query($query)->rowCount();
				$i = 0;
				$buffer = array();
			}
			if($line){
				$data = array();
				foreach($columns as $key => $column){
					$data[$column] = $line[$key];
				}
				foreach($data as $key=>$value)  $data[$key] = str_replace('\\"', '"', $value);
				$buffer[] = "('".implode("','", $data)."')";
				$pedido++;
			}
			$i++;
		}while($line);

		return array("Importação realizada com sucesso", "$inserido cadastros inseridos <br/> ". ($pedido - $inserido) ." ignorados por repetição de identificação");

	}


}