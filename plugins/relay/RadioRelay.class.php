<?php

/*
 @nom: RadioRelay
 @auteur: Idleman (idleman@idleman.fr)
 @description:  Classe de gestion des peices
 */

class RadioRelay extends SQLiteEntity{

	protected $id,$name,$description,$radioCode,$room,$pulse;
	protected $TABLE_NAME = 'plugin_radioRelay';
	protected $CLASS_NAME = 'RadioRelay';
	protected $object_fields = 
	array(
		'id'=>'key',
		'name'=>'string',
		'description'=>'string',
		'radioCode'=>'int',
		'room'=>'int',
		'pulse'=>'int'
	);

	function __construct(){
		parent::__construct();
	}

	function setId($id){
		$this->id = $id;
	}
	
	function getId(){
		return $this->id;
	}

	function getName(){
		return $this->name;
	}

	function setName($name){
		$this->name = $name;
	}

	function getDescription(){
		return $this->description;
	}

	function setDescription($description){
		$this->description = $description;
	}

	function getRadioCode(){
		return $this->radioCode;
	}

	function setRadioCode($radioCode){
		$this->radioCode = $radioCode;
	}

	function getRoom(){
		return $this->room;
	}

	function setRoom($room){
		$this->room = $room;
	}

	function getPulse(){
		return $this->pulse;
	}

	function setPulse($pulse){
		$this->pulse = $pulse;
	}
}

?>
