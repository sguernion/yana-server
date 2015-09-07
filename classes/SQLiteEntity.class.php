<?php

/*
	@nom: SQLiteEntity
	@auteur: Idleman (idleman@idleman.fr)
	@description: Classe parent de tous les mod�les (classe entit�es) li�es a la base de donn�e,
	 cette classe est configur� pour agir avec une base SQLite, mais il est possible de redefinir ses codes SQL pour l'adapter � un autre SGBD sans affecter 
	 le reste du code du projet.

*/


class SQLiteEntity extends SQLite3
{
	
	private $debug = false;
	
	function __construct(){
		$this->open(__ROOT__.'/'.DB_NAME);
	}

	function __destruct(){
		 $this->close();
	}

	function sgbdType($type){
		$return = false;
		switch($type){
			case 'string':
			case 'timestamp':
			case 'date':
				$return = 'VARCHAR(255)';
			break;
			case 'longstring':
				$return = 'longtext';
			break;
			case 'key':
				$return = 'INTEGER NOT NULL PRIMARY KEY';
			break;
			case 'object':
			case 'integer':
				$return = 'bigint(20)';
			break;
			case 'boolean':
				$return = 'INT(1)';
			break;
			default;
				$return = 'TEXT';
			break;
		}
		return $return ;
	}
	

	public function closeDatabase(){
		$this->close();
	}


	// GESTION SQL

	/**
	* Verifie l'existence de la table en base de donn�e
	* @author Valentin CARRUESCO
	* @category manipulation SQL
	* @param <String> cr�� la table si elle n'existe pas
	* @return true si la table existe, false dans le cas contraire
	*/
	public function checkTable($autocreate = false){
		$query = 'SELECT count(*) as numRows FROM sqlite_master WHERE type="table" AND name="'.MYSQL_PREFIX.$this->TABLE_NAME.'"';  
		$statement = $this->query($query);

		if($statement!=false){
			$statement = $statement->fetchArray();
			if($statement['numRows']==1){
				$return = true;
			}
		}
		if($autocreate && !$return) $this->create();
		return $return;
	}

	/**
	* Methode de creation de l'entit�
	* @author Valentin CARRUESCO
	* @category manipulation SQL
	* @param <String> $debug='false' active le debug mode (0 ou 1)
	* @return Aucun retour
	*/
	public function create($debug='false'){
		$query = 'CREATE TABLE IF NOT EXISTS `'.MYSQL_PREFIX.$this->TABLE_NAME.'` (';

		$i=false;
		foreach($this->object_fields as $field=>$type){
			if($i){$query .=',';}else{$i=true;}
			$query .='`'.$field.'`  '. $this->sgbdType($type).'  NOT NULL';
		}

		$query .= ');';
		if($this->debug)echo '<hr>'.$this->CLASS_NAME.' ('.__METHOD__ .') : Requete --> '.$query;
		if(!$this->exec($query)) echo $this->lastErrorMsg();
	}

	public function drop($debug='false'){
		$query = 'DROP TABLE `'.MYSQL_PREFIX.$this->TABLE_NAME.'`;';
		if($this->debug)echo '<hr>'.$this->CLASS_NAME.' ('.__METHOD__ .') : Requete --> '.$query;
		if(!$this->exec($query)) echo $this->lastErrorMsg();
	}


	public function massiveInsert($events,$forceId = false){
		$query = 'INSERT INTO `'.MYSQL_PREFIX.$this->TABLE_NAME.'`(';
			$i=false;
			foreach($this->object_fields as $field=>$type){
				if($type=='key' && !$forceId) continue;
					if($i){$query .=',';}else{$i=true;}
					$query .='`'.$field.'`';
				
			}
			$query .=') select';
			$u = false;
			foreach($events as $event){
				if($u){$query .=' union select ';}else{$u=true;}
				
				$i=false;
				foreach($event->object_fields as $field=>$type){
					if($type=='key' && !$forceId) continue;
						if($i){$query .=',';}else{$i=true;}
						$query .='"'.eval('return htmlentities($event->'.$field.');').'"';
				}
				
			}

			$query .=';';
		//echo '<i>'.$this->CLASS_NAME.' ('.__METHOD__ .') : Requete --> '.$query.'<br>';
		if(!$this->exec($query)) echo $this->lastErrorMsg().'</i>';

	}

	/**
	* Methode d'insertion ou de modifications d'elements de l'entit�
	* @author Valentin CARRUESCO
	* @category manipulation SQL
	* @param  Aucun
	* @return Aucun retour
	*/
	public function save(){
	
		if(isset($this->id)){
			$query = 'UPDATE `'.MYSQL_PREFIX.$this->TABLE_NAME.'`';
			$query .= ' SET ';

			$i = false;
			foreach($this->object_fields as $field=>$type){
				if($i){$query .=',';}else{$i=true;}
				$id = eval('return htmlentities($this->'.$field.');');
				$query .= '`'.$field.'`="'.$id.'"';
			}

			$query .= ' WHERE `id`="'.$this->id.'";';
		}else{
			$query = 'INSERT INTO `'.MYSQL_PREFIX.$this->TABLE_NAME.'`(';
			$i=false;
			foreach($this->object_fields as $field=>$type){
				if($type!='key'){
					if($i){$query .=',';}else{$i=true;}
					$query .='`'.$field.'`';
				}
			}
			$query .=')VALUES(';
			$i=false;
			foreach($this->object_fields as $field=>$type){
				if($type!='key'){
					if($i){$query .=',';}else{$i=true;}
					$query .='"'.eval('return htmlentities($this->'.$field.');').'"';
				}
			}

			$query .=');';
		}
		if($this->debug)echo '<i>'.$this->CLASS_NAME.' ('.__METHOD__ .') : Requete --> '.$query.'<br>';
		//var_dump ($query);
		if(!$this->exec($query)) echo $this->lastErrorMsg().'</i>';
		$this->id =  (!isset($this->id)?$this->lastInsertRowID():$this->id);
	}

	/**
	* M�thode de modification d'�l�ments de l'entit�
	* @author Valentin CARRUESCO
	* @category manipulation SQL
	* @param <Array> $colonnes=>$valeurs
	* @param <Array> $colonnes (WHERE) =>$valeurs (WHERE)
	* @param <String> $operation="=" definis le type d'operateur pour la requete select
	* @param <String> $debug='false' active le debug mode (0 ou 1)
	* @return Aucun retour
	*/
	public function change($columns,$columns2=null,$operation='=',$debug='false'){
		$query = 'UPDATE `'.MYSQL_PREFIX.$this->TABLE_NAME.'` SET ';
		$i=false;
		foreach ($columns as $column=>$value){
			if($i){$query .=',';}else{$i=true;}
			$query .= '`'.$column.'`="'.$value.'" ';
		}

		if($columns2!=null){
			$query .=' WHERE '; 
			$i=false;
			foreach ($columns2 as $column=>$value){
				if($i){$query .='AND ';}else{$i=true;}
				$query .= '`'.$column.'`'.$operation.'"'.$value.'" ';
			}
		}

		//echo '<hr>'.$this->CLASS_NAME.' ('.__METHOD__ .') : Requete --> '.$query.'<br>';
		if(!$this->exec($query)) echo $this->lastErrorMsg();
	}

	/**
	* M�thode de selection de tous les elements de l'entit�
	* @author Valentin CARRUESCO
	* @category manipulation SQL
	* @param <String> $ordre=null
	* @param <String> $limite=null
	* @param <String> $debug='false' active le debug mode (0 ou 1)
	* @return <Array<Entity>> $Entity
	*/
	public function populate($order='null',$limit='null',$debug='false'){
		eval('$results = '.$this->CLASS_NAME.'::loadAll(array(),\''.$order.'\','.$limit.',\'=\','.$debug.');');
		return $results;
	}


	/**
	* M�thode de selection multiple d'elements de l'entit�
	* @author Valentin CARRUESCO
	* @category manipulation SQL
	* @param <Array> $colonnes (WHERE)
	* @param <Array> $valeurs (WHERE)
	* @param <String> $ordre=null
	* @param <String> $limite=null
	* @param <String> $operation="=" definis le type d'operateur pour la requete select
	* @param <String> $debug='false' active le debug mode (0 ou 1)
	* @return <Array<Entity>> $Entity
	*/
	public function loadAll($columns,$order=null,$limit=null,$operation="=",$debug='false',$selColumn='*'){
		$objects = array();
		$whereClause = '';
	
			if($columns!=null && sizeof($columns)!=0){
			$whereClause .= ' WHERE ';
				$i = false;
				foreach($columns as $column=>$value){

					if($i){$whereClause .=' AND ';}else{$i=true;}
					$whereClause .= '`'.$column.'`'.$operation.'"'.$value.'"';
				}
			}
			$query = 'SELECT '.$selColumn.' FROM `'.MYSQL_PREFIX.$this->TABLE_NAME.'` '.$whereClause.' ';
			if($order!=null) $query .='ORDER BY '.$order.' ';
			if($limit!=null) $query .='LIMIT '.$limit.' ';
			$query .=';';
			  
			//echo '<hr>'.__METHOD__.' : Requete --> '.$query.'<br>';
			$execQuery = $this->query($query);

			if(!$execQuery) 
				echo $this->lastErrorMsg();
			while($queryReturn = $execQuery->fetchArray() ){
				$object = eval(' return new '.$this->CLASS_NAME.'();');
				foreach($this->object_fields as $field=>$type){
					if(isset($queryReturn[$field])) eval('$object->'.$field .'= html_entity_decode(\''. addslashes($queryReturn[$field]).'\');');
				}
				$objects[] = $object;
				unset($object);
			}
			return $objects;
	}

	public function loadAllOnlyColumn($selColumn,$columns,$order=null,$limit=null,$operation="=",$debug='false'){
		eval('$objects = $this->loadAll($columns,\''.$order.'\',\''.$limit.'\',\''.$operation.'\',\''.$debug.'\',\''.$selColumn.'\');');
		if(count($objects)==0)$objects = array();
		return $objects;
	}

	/**
	* M�thode de selection unique d'�lements de l'entit�
	* @author Valentin CARRUESCO
	* @category manipulation SQL
	* @param <Array> $colonnes (WHERE)
	* @param <Array> $valeurs (WHERE)
	* @param <String> $operation="=" definis le type d'operateur pour la requete select
	* @param <String> $debug='false' active le debug mode (0 ou 1)
	* @return <Entity> $Entity ou false si aucun objet n'est trouv� en base
	*/
	public function load($columns,$operation='=',$debug='false'){
		eval('$objects = $this->loadAll($columns,null,\'1\',\''.$operation.'\',\''.$debug.'\');');
		if(!isset($objects[0]))$objects[0] = false;
		return $objects[0];
	}

	/**
	* M�thode de selection unique d'�lements de l'entit�
	* @author Valentin CARRUESCO
	* @category manipulation SQL
	* @param <Array> $colonnes (WHERE)
	* @param <Array> $valeurs (WHERE)
	* @param <String> $operation="=" definis le type d'operateur pour la requete select
	* @param <String> $debug='false' active le debug mode (0 ou 1)
	* @return <Entity> $Entity ou false si aucun objet n'est trouv� en base
	*/
	public function getById($id,$operation='=',$debug='false'){
		return $this->load(array('id'=>$id),$operation,$debug);
	}

	/**
	* Methode de comptage des �l�ments de l'entit�
	* @author Valentin CARRUESCO
	* @category manipulation SQL
	* @param <String> $debug='false' active le debug mode (0 ou 1)
	* @return<Integer> nombre de ligne dans l'entit�'
	*/
	public function rowCount($columns=null)
	{
		$whereClause ='';
		if($columns!=null){
			$whereClause = ' WHERE ';
			$i=false;
			foreach($columns as $column=>$value){
					if($i){$whereClause .=' AND ';}else{$i=true;}
					$whereClause .= '`'.$column.'`="'.$value.'"';
			}
		}
		$query = 'SELECT COUNT(id) FROM '.MYSQL_PREFIX.$this->TABLE_NAME.$whereClause;
		//echo '<hr>'.$this->CLASS_NAME.' ('.__METHOD__ .') : Requete --> '.$query.'<br>';
		$execQuery = $this->querySingle($query);
		//echo $this->lastErrorMsg();
		return (!$execQuery?0:$execQuery);
	}	
	
	/**
	* M�thode de supression d'elements de l'entit�
	* @author Valentin CARRUESCO
	* @category manipulation SQL
	* @param <Array> $colonnes (WHERE)
	* @param <Array> $valeurs (WHERE)
	* @param <String> $operation="=" definis le type d'operateur pour la requete select
	* @param <String> $debug='false' active le debug mode (0 ou 1)
	* @return Aucun retour
	*/
	public function delete($columns,$operation='=',$debug='false',$limit=null){
		$whereClause = '';

			$i=false;
			foreach($columns as $column=>$value){
				if($i){$whereClause .=' AND ';}else{$i=true;}
				$whereClause .= '`'.$column.'`'.$operation.'"'.$value.'"';
			}
			$query = 'DELETE FROM `'.MYSQL_PREFIX.$this->TABLE_NAME.'` WHERE '.$whereClause.' '.(isset($limit)?'LIMIT '.$limit:'').';';
			//echo '<hr>'.$this->CLASS_NAME.' ('.__METHOD__ .') : Requete --> '.$query.'<br>';
			if(!$this->exec($query)) echo $this->lastErrorMsg();
	}
	
	public function customExecute($request){
		$this->exec($request);
	}
	public function customQuery($request){
		return $this->query($request);
	}

	// ACCESSEURS
		/**
	* M�thode de r�cuperation de l'attribut debug de l'entit�
	* @author Valentin CARRUESCO
	* @category Accesseur
	* @param Aucun
	* @return <Attribute> debug
	*/
	
	public function getDebug(){
		return $this->debug;
	}
	
	/**
	* M�thode de d�finition de l'attribut debug de l'entit�
	* @author Valentin CARRUESCO
	* @category Accesseur
	* @param <boolean> $debug 
	*/

	public function setDebug($debug){
		$this->debug = $debug;
	}

	public function getObject_fields(){
		return $this->object_fields;
	}

}
?>
