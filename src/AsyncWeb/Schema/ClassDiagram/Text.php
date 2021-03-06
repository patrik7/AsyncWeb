<?php

namespace AsyncWeb\Schema\ClassDiagram;

class Text{
	public $Folder = "";
	public $SchemaDirectory = "";
	public $OutputDirectory = "";
	public $Namespace = "AW";
	public $datatypes = array();
	public $append = array();
	public $doc = array();
	public $optionality = array();
	public $schema = "";
	public $extendsClasses = array();
	public function __construct($SchemaDirectory,$OutputDirectory,$OutputFormsDirectory,$Namespace){
		$this->SchemaDirectory = $SchemaDirectory;
		$this->OutputDirectory = $OutputDirectory;
		$this->OutputFormsDirectory = $OutputFormsDirectory;
		$this->Namespace = $Namespace;
		
		$info = pathinfo($OutputDirectory);
		$this->Folder = $info["basename"];

	}
	public function ProcessExtension(){		
		foreach($this->datatypes as $class=>$types){
	
			$extendsClass = "";
			$extends = '\AsyncWeb\Api\REST\Service';
			if(isset($this->append[$class]["-:>"])){
				foreach($this->append[$class]["-:>"] as $k=>$v){
					
					$extends = "\\".$this->Namespace."\\".$this->Folder."\\".$k;
					$extendsClass = $k;
					if($this->datatypes[$extendsClass])
					foreach($this->datatypes[$extendsClass] as $type=>$datatype){
						if(!isset($this->datatypes[$class][$type])) $this->datatypes[$class][$type] = $datatype;
					}
					if(!isset($this->doc[true][false][false][$currentClass][$class]["doc"])){
						$this->doc[true][false][false][$currentClass][$class]["doc"] = $this->doc[true][false][false][$extendsClass][$extendsClass]["doc"];
					}
					break;
				}
			}
			
			$this->extendsClasses[$class] = $extends;
		}
	}
	public function ParseDirectory($dir = false){
		
		if(!$dir){
			$dir = $this->SchemaDirectory;
			$this->append = $this->optionality = $this->datatypes = $this->doc = $this->extendsClasses =  array();
		}
		if(!is_dir($dir)){
			echo "\n$dir does not exists!\n";exit;
		}
		$files = scandir($dir);
		sort($files);
		$dir = rtrim($dir,"/");
		foreach($files as $file){
			
			
			if(substr($file,0,1) == ".") continue;
			if(is_dir($dir."/".$file)){
				$this->ParseDirectory($dir."/".$file);
			}
			//echo "-------------------------------$file:\n";
			
			$out = "";
			foreach(explode("\n",file_get_contents($dir."/".$file)) as $line){
				$data = trim("".$line);
			
				$posComment = $end = strpos($line,"#");
				$posRule = strpos($line,"!");
				if($posRule !== false){
					$end = $end === false?$end = $posRule:$end = min($posRule,$posComment);
				}
				if($end !== false){
					$data = trim(substr($line,0,$end));
				}
				if(substr($data,0,8)=="[<state>") continue;
				
				if(substr($data,0,1) == "["){
					//if($last = strpos($data,"|") != false || $last = strpos($data,"]") != false){
						$last = strpos($data,"|");
						if(!$last)$last = strpos($data,"]");
						if(!$last)$last = strpos($data,"#");
						if($last){
							$currentClass = trim(substr($data,1,$last-1));
						}else{
							$currentClass = trim(substr($data,1));
						}
						$currentClassA = explode(":",$currentClass);
						if(count($currentClassA) > 1){
							// inherited
							$currentClass = trim($currentClassA[0]);
							$parentClass = trim($currentClassA[1]);
							$this->append[$currentClass]["-:>"][$parentClass] = true;
							$data = "[$currentClass|";
						}
						
						$this->docforclass = true;$this->docforparam = false;$this->docforfunction = false;$this->docobject=$currentClass;
						if(strtolower(substr($currentClass,-4)) != "enum"){
							if(!isset($this->datatypes[$currentClass])) $this->datatypes[$currentClass] = array();
						}
					//}
				}
				
				if(trim($data)){ // if not empty row include it into the schema
					$this->schema .= $data."\n";
				}
				
				$datatype = "";
				$posDataType = strpos("".$data,":");
				
				 
				if($posDataType !== false){
					$datatype = trim(substr($data,$posDataType+1));
					$name = $nameWithOptionality = trim(substr($data,0,$posDataType ));
					$start = substr($name,0,1);
					if($start == "+" || $start == "-"){
						$name = trim(substr($name,1));
						$this->optionality[$currentClass][$name] = $start;
					}else{
						$this->optionality[$currentClass][$name] = "+";
					}
					$this->datatypes[$currentClass][$name] = $this->datatypes[$currentClass][$name] = $datatype;

					$this->docforclass = false;$this->docforparam = true;$this->docforfunction = false;$this->docobject=$name;
					
				} 
				$posFunction = strpos("".$data,"()");
				if($posFunction !== false){
					$fnctName = substr($data,0,$posFunction);
					$this->docforclass = false;$this->docforparam = false;$this->docforfunction = true;$this->docobject=$fnctName;
				}		
				if($posRule !== false){
					if($posComment === false){
						$rule = trim(substr($line,$posRule+1));
					}else{
						$rule = trim(substr($line,$posRule+1,$posComment-$posRule- 1));
					}
					@$this->doc[$this->docforclass][$this->docforparam][$this->docforfunction][$currentClass][$this->docobject]["rule"] .= $rule;
					
					if(substr($rule,0,1) == "`"){
						$this->doc[$this->docforclass][$this->docforparam][$this->docforfunction][$currentClass][$this->docobject]["code"][] = trim(substr($rule,1),"`");
					}
					if(substr($rule,0,2) == ".`"){
						$this->doc[$this->docforclass][$this->docforparam][$this->docforfunction][$currentClass][$this->docobject]["codeiter"][] = trim(substr($rule,2),"`");
					}
					
				}
				if(substr($datatype,-2) == "ID"){
					$other = $datatype;
					if($datatype != "ID"){
						$other = substr($datatype,0,-2);
					}
					
					if($this->optionality[$currentClass][$this->docobject] == "+"){
						$this->append[$currentClass]["* - 1"][$other] = true;
					}else{
						$this->append[$currentClass]["* - 0..1"][$other] = true;
					}
					$this->doc[$this->docforclass][$this->docforparam][$this->docforfunction][$currentClass][$this->docobject]["instanceof"] = $other;
				}
				if(substr($datatype,-4) == "Enum"){
					$this->append[$currentClass]["+-"][$datatype] = true;
				}
				
				
				if(substr($currentClass,-4) == "Enum") continue;
				if($posComment!= false){
					@$this->doc[$this->docforclass][$this->docforparam][$this->docforfunction][$currentClass][$this->docobject]["doc"] .= trim(substr($line,$posComment+1))."\n";
				}
			}
		}
	}
	public function GeneratePHPTop($class){
		
		$fileout ='<?php
 
namespace '.$this->MyNamespace($class).';
use AsyncWeb\System\Language;
use AsyncWeb\DB\DB;

/**
'.$this->doc[true][false][false][$class][$class]["doc"].'
*/

class '.$this->ClassName($class).' extends '.$this->ConvertClassToNamespaceName($this->extendsClasses[$class]).'{';
		return $fileout;
	}
	public function GeneratePHPVariables($class){
		$table = $class;
		if($this->extendsClasses[$class] != '\AsyncWeb\Api\REST\Service'){
			$parts = explode("\\",$this->extendsClasses[$class]);
			$table = $parts[count($parts)-1];
		}
$fileout.='
	/** @internal */
	public static $TABLE = "'.$this->ConvertToDBName($table).'";
	/** @internal */
	public static $COL_ID = "id2";
	/** @internal */
	public static $COL_UID = "id";
	/** @internal */
	public static $COL_MODIFIED_BY = "modified_by";
';
	foreach($this->datatypes[$class] as $type=>$datatype){
		if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
		if(substr($datatype,-8) == "Instance") continue;
		$col = "COL_".strtoupper($coldbname = $this->ConvertToDBName($type));
		
		$fileout.='	/** @internal */
	public static $'.$col.' = "'.$coldbname.'";
';
		
	}
	if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
		$type = "SuggestedBy";
		$col = "COL_".strtoupper($coldbname = $this->ConvertToDBName($type));
		$fileout.='	/** @internal */
	public static $'.$col.' = "'.$coldbname.'";
';
		$type = "State";
		$col = "COL_".strtoupper($coldbname = $this->ConvertToDBName($type));
		$fileout.='	/** @internal */
	public static $'.$col.' = "'.$coldbname.'";
';
	}
	if($this->extendsClasses[$class] != '\AsyncWeb\Api\REST\Service' && !isset($this->datatypes[$class]["InstanceDataType"])){
		$fileout.='	/** @internal */
	public static $COL_INSTANCE_DATA_TYPE = "instance_data_type";
';
	}
	$fileout.='	/** @internal */
	public static $CACHE = array();
	/** @internal */
	public static $DB_DICT_COLS = array(
';
	if($this->extendsClasses[$class] != '\AsyncWeb\Api\REST\Service' && !isset($this->datatypes[$class]["InstanceDataType"])){
		$fileout.='		InstanceDataType=>instance_data_type,'."\n";		
	}
	foreach($this->datatypes[$class] as $type=>$datatype){
		if($datatype == "LocalisedString"){
			
			$fileout.='		"'.$type.'"=>"'.$this->ConvertToDBName($type).'",'."\n";
		}
	}
	$fileout.='	);
	/** @internal */
	public static $CONVERT = array(
		"ID"=>"id2",
		"UID"=>"id",
		"ModifiedBy"=>"modified_by",';
	foreach($this->datatypes[$class] as $type=>$datatype){
		if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
		if(substr($datatype,-8) == "Instance") continue;
		$coldbname = $this->ConvertToDBName($type);
		$fileout.="\n".'		"'.$type.'"=>"'.$coldbname.'",';
	}
	if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
		$type = "SuggestedBy";
		$coldbname = $this->ConvertToDBName($type);
		$fileout.="\n".'		"'.$type.'"=>"'.$coldbname.'",';
		$type = "State";
		$coldbname = $this->ConvertToDBName($type);
		$fileout.="\n".'		"'.$type.'"=>"'.$coldbname.'",';
	}
	$fileout.='	
		);';
		
		
$fileout.='		
	/** @internal */
	public $ModifiedBy;	
	/** Internal identifier */
	public $ID;
	/** Internal unique identifier */
	public $UID;'."\n";
	foreach($this->datatypes[$class] as $type=>$datatype){
		if(isset($this->doc[false][true][false][$class][$type]["doc"])) $fileout.='	/** '.trim($this->doc[false][true][false][$class][$type]["doc"]). ' */'."\n";
		$add = "";
		if($datatype == "String" || $datatype == "LocalisedString"){
			$add = " = ''";
		}else if($datatype == "Array"){
			$add = " = array()";
		}
		$fileout.='	public $'.$type.$add.';'."\n";
	}
	if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
$fileout.='	/** Identifier of user who has suggested the object */
	public $SuggestedBy;
	/** State of the object.*/
	public $State;'."\n";
	}
	
	
	
		return $fileout;
	}
	public function GeneratePHPConstructor($class){
		


	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// CONSTRUCTOR
$fileout.='
	/** 
	Constructor for '.$class.'.
	
	Obtain instance of '.$class.' trhough '.$class.'::Instance(Identifier).
	*/
	
	private function __construct($data){
		$id = $data;
		if(!is_array($data)){
			$data = DB::gr(self::$TABLE,array(self::$COL_ID=>$data));
		}else{$id = self::$CACHE[$data[self::$COL_ID]];}

		if(!$data){
			throw new \\'.$this->Namespace.'\Service\Exception\InvalidArgumentException(Language::get("'.$class.' %object% does not exists",array("%object%"=>$id)));
		}
		
		$this->ID = $data[self::$COL_ID];
		$this->UID = $data[self::$COL_UID];
		$this->ModifiedBy = $data[self::$COL_MODIFIED_BY];
';

	foreach($this->datatypes[$class] as $type=>$datatype){
		$col = "COL_".strtoupper($coldbname = $this->ConvertToDBName($type));
		if($datatype == "LocalisedString"){
			$fileout.='		$this->'.$type.' = Language::get($data[self::$'.$col.']);'."\n";
		}elseif(isset($this->doc[false][true][false][$class][$type]["code"])){
			foreach($this->doc[false][true][false][$class][$type]["code"] as $line){
				$fileout.='		'.$line."\n";
			}
		}elseif(substr($datatype,-8) == "Instance"){
			$otherType = substr($datatype,0,-8);
			if(!isset($this->datatypes[$class][$otherType]) || substr($this->datatypes[$class][$otherType],-2) != "ID"){
				echo "!ERROR in ".$class.": ".$type." is wrongly referenced!\n";
				continue;
			}
			$otherDataType = substr($this->datatypes[$class][$otherType],0,-2);
			if(isset($this->optionality[$class][$type]) && $this->optionality[$class][$type] == "+"){
				$fileout.='		$this->'.$type.' = '.$otherDataType.'::Instance($this->'.$otherType.');'."\n";
			}else{
				$fileout.='		if($this->'.$otherType.'){'."\n";
				$fileout.='			$this->'.$type.' = '.$otherDataType.'::Instance($this->'.$otherType.');'."\n";
				$fileout.='		}'."\n";
			}
			
		}else{
			$fileout.='		$this->'.$type.' = $data[self::$'.$col.'];'."\n";
		}
	}

	
	if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
		$type = "SuggestedBy";
		$col = "COL_".strtoupper($coldbname = $this->ConvertToDBName($type));
		$fileout.='		$this->'.$type.' = $data[self::$'.$col.'];'."\n";
		$type = "State";
		$col = "COL_".strtoupper($coldbname = $this->ConvertToDBName($type));
		$fileout.='		$this->'.$type.' = $data[self::$'.$col.'];'."\n";
		
	}
	
$fileout.='		self::$CACHE[$data[self::$COL_ID]] = $this;
	}
	';
		return $fileout;		
	}
	public function GeneratePHPInstance($class){

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// Instance
$fileout.='
	/**
		Returns instance from identifier.
	*/
	public static function Instance($id){
		if(!$id){
			throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("Invalid call for object %object%. Identifier must not be empty.",array("%object%"=>"'.$class.'")));
		}
		if(is_array($id)){
			throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("Invalid call for object %object%. Identifier must not be an array. %array%",array("%object%"=>"'.$class.'","%array%"=>print_r($id,true))));
		}
		if(isset(self::$CACHE[$id])){
			return self::$CACHE[$id];
		}
		return new '.$class.'($id);
	}'."\n";
		return $fileout;		
	}
	public function GeneratePHPCreate($class){

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// CREATE
	
	if(isset($this->doc[false][false][true][$class]["Create"])){
$fileout.='
	/**
	'.$this->doc[false][false][true][$class]["Create"]["doc"].'	
';
	foreach($this->datatypes[$class] as $type=>$datatype){
		if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
		if(substr($datatype,-8) == "Instance") continue;
		//if($type == "Owner") {continue;}
		if(isset($this->doc[false][true][false][$class][$type]["doc"]) && $this->doc[false][true][false][$class][$type]["doc"]){
			$fileout.='	@param string $'.$type.' '.trim($this->doc[false][true][false][$class][$type]["doc"])."\n";
		}else{
			$fileout.='	@param string $'.$type.' '.trim($type)."\n";
		}
	}
	$fileout.='	@param string $ApiKeySession Session identifier obtained from Service->Connect() function
	@param string $CRC <p><b>CRC & Authorisation verifier</b></p>
		<p>CRC = sha512(
			"';
	foreach($this->datatypes[$class] as $type=>$datatype){
		if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
		if(substr($datatype,-8) == "Instance") continue;
		//if($type == "Owner") continue;
		$fileout.=$type."=..&";
	}			
			$fileout.='ApiKeySession=..&ApiSecret=.."
			)</p>
		<p>If CRC does not match, function returns unauthorized exception.</p>
		<p>If any of the variable is null or is empty string, it must not be used for hash crc.</p>
		<p>The order of parameters matters. Parameters as well as data are case sensitive.</p>
		
	@return string "1" on success, throws exception on non success
	@throws \\'.$this->Namespace.'\Service\Exception\InvalidArgumentException Invalid argument
	@throws \\'.$this->Namespace.'\Service\Exception\UnauthorizedException Unauthorized accesss
	*/
	
	public static function Create($ID="",';
	foreach($this->datatypes[$class] as $type=>$datatype){
		if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
		if(substr($datatype,-8) == "Instance") continue;
		//if($type == "Owner") continue;
$fileout.='$'.$type."='',";
	}			
			$fileout.='$ApiKeySession = "",$CRC = ""){
		$vars=array("ID"=>$ID,';
	foreach($this->datatypes[$class] as $type=>$datatype){
	if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
	if(substr($datatype,-8) == "Instance") continue;
	//if($type == "Owner") continue;
$fileout.='"'.$type.'"=>$'.$type.',';
	}$fileout.='"ApiKeySession"=>$ApiKeySession,"CRC"=>$CRC);
		foreach($vars as $var=>$v){if(isset($_REQUEST[$var])){$$var = $_REQUEST[$var];$vars[$var] = $_REQUEST[$var];}else{if(isset($_REQUEST[strtolower($var)])){$$var = $_REQUEST[strtolower($var)];$vars[$var] = $_REQUEST[strtolower($var)];}}}
		$apiuser = \\'.$this->Namespace.'\Classes\Session::Validate($vars);
		$session = \\'.$this->Namespace.'\Classes\Session::Instance($ApiKeySession);'."\n";
		if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
			$fileout.='		// userIsAllowedToSuggest rule applies'."\n";
			$fileout.='
		if(isset($session->ApiKey->Groups["admin"])){
			$State = "Approved";
		}else{
			$State = "Suggested";
		}'."\n";
		}elseif(isset($this->doc[true][false][false][$class][$class]["rule"]) && substr($this->doc[true][false][false][$class][$class]["rule"],0,4) == "only"){
			$fileout.='		// '.$this->doc[true][false][false][$class][$class]["rule"].' rule applies '."\n";
			 $groups = explode("|",substr($this->doc[true][false][false][$class][$class]["rule"],4));
		$fileout.='
		if(';
		$i = 0;
		foreach($groups as $group){$i++;
			if($i > 1) $fileout.='&&';
			$fileout.='!isset($session->ApiKey->Groups["'.trim($group).'"])';
		}
		$fileout.='){
			throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You are not allowed to create instance of %object%.",array("%object%"=>"'.$class.'")));
		}'."\n";
		}
		
		$fileout.='		//if user do not wish to select custom identifier, generate one for him'."\n";
		$fileout.='		$OrigID = $ID;'."\n";
		$fileout.='		if($OrigID){';
		$fileout.='			// ID is defined'."\n";
		$fileout.='		}else{$ID = md5(uniqid());}'."\n";
		
		if(isset($this->doc[false][false][true][$class]["Create"]["code"])){
			$fileout.='		//source code copied from schema file'."\n";
			foreach($this->doc[false][false][true][$class]["Create"]["code"] as $line){
				$fileout.='		'.$line."\n";
			}
		}
		foreach($this->datatypes[$class] as $type=>$datatype){
			if(isset($this->doc[false][true][false][$class][$type]["code"])){
				$fileout.='		// '.$type.' is defined by copied code from schema file'."\n";
				continue;
			}
			if(substr($datatype,-8) == "Instance") continue;
			//if($type == "Owner") continue;
			if($type == "Owner"){
				$fileout.='		if(!$Owner) $Owner = $apiuser;'."\n";

				$fileout.='		if($Owner == $apiuser){
			// ok
		}else{
			if(!isset($SecurityChecked) || !$SecurityChecked){
				if(!isset($session->ApiKey->Groups["admin"])){
					throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You are not authorized to insert this instance for this owner."));
				}else{
					// ok
				}
			}
		}'."\n";
			}else if(isset($this->doc[false][true][false][$class][$type]["instanceof"])){
				
				if(isset($this->optionality[$class][$type]) && $this->optionality[$class][$type] == "-"){
					$fileout.='		if($'.$type.'){'."\n	";
				}
				
				$fileout.='		'.$this->doc[false][true][false][$class][$type]["instanceof"].'::Instance($'.$type.'); // checks if instance is correct'."\n";
				if(isset($this->optionality[$class][$type]) && $this->optionality[$class][$type] == "-"){
				$fileout.='		}'."\n";
				}
			}else if(isset($this->optionality[$class][$type]) && $this->optionality[$class][$type] == "+"){
				$dt = strtolower($datatype);
				if($dt == "bool" || $dt == "boolean"){
					$fileout.='		if($'.$type.' === null){';
					$fileout.='throw new \\'.$this->Namespace.'\Service\Exception\InvalidArgumentException(Language::get("Parameter %parameter% must not be empty!",array("%parameter%"=>"'.$type.'")));';
					$fileout.='}'."\n";
				}else{
					$fileout.='		if(!$'.$type.'){';
					$fileout.='throw new \\'.$this->Namespace.'\Service\Exception\InvalidArgumentException(Language::get("Parameter %parameter% must not be empty!",array("%parameter%"=>"'.$type.'")));';
					$fileout.='}'."\n";
				}
			}
		}
		if(isset($this->optionality[$class]["Name"])){
			$fileout.='		// We will try to make nice looking identifier'."\n";
			$fileout.='		$ID = substr(\AsyncWeb\Text\Texts::clear($Name),0,32);'."\n";
			$fileout.='		if(DB::gr(self::$TABLE,array("id2"=>$ID))){'."\n";
			$fileout.='			$ID = substr($ID,0,30).rand(10,99);'."\n";
			$fileout.='			if(DB::gr(self::$TABLE,array("id2"=>$ID))){'."\n";
			$fileout.='				$ID = md5(uniqid());'."\n";
			$fileout.='			}'."\n";
			$fileout.='		}'."\n";
		}

		$fileout.="\n".'		$config = array();'."\n";
		foreach($this->datatypes[$class] as $type=>$datatype){
			if(substr($datatype,-8) == "Instance") continue;
			$DataType =strtolower($datatype);
			$type = $this->ConvertToDBName($type);
			if($DataType == "string"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "varchar";'."\n";
				$fileout.='		$config["cols"]["'.$type.'"]["length"] = "250";'."\n";
			}else
			if($DataType == "int"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "int";'."\n";
			}else		
			if($DataType == "bool"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "int";'."\n";
			}else		
			if($DataType == "text"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "text";'."\n";
			}else		
			if($DataType == "decimal"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "decimal";'."\n";
			}else		
			if($DataType == "double"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "double";'."\n";
			}else		
			if($DataType == "blob"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "blob";'."\n";
			}else		
			if($datatype == "LocalisedString"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "char";'."\n";
				$fileout.='		$config["cols"]["'.$type.'"]["length"] = "32";'."\n";
				$fileout.='		$config["keys"][] = "'.$type.'";'."\n";			
			}else
			if(substr($datatype,-2) == "ID"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "char";'."\n";
				$fileout.='		$config["cols"]["'.$type.'"]["length"] = "32";'."\n";
				$fileout.='		$config["keys"][] = "'.$type.'";'."\n";			
			}else{
				//echo "WARNING: unknown data type $class.$type :: $datatype\n";
			}		
		}
		$fileout.='		$config["cols"]["modified_by"]["type"] = "char";'."\n";
		$fileout.='		$config["cols"]["modified_by"]["length"] = "32";'."\n";
		$fileout.='		$config["cols"]["created"]["type"] = "bigint";'."\n"; 
		if($this->extendsClasses[$class] != '\AsyncWeb\Api\REST\Service' && !isset($this->datatypes[$class]["InstanceDataType"])){
			$fileout.='		$config["cols"]["instance_data_type"]["type"] = "varchar";'."\n"; 
			$fileout.='		$config["cols"]["instance_data_type"]["length"] = "250";'."\n";
			$fileout.='		$config["keys"][] = "instance_data_type";'."\n";			
		}
	
		if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
			$fileout.='		$config["cols"]["suggested_by"]["type"] = "char";'."\n";
			$fileout.='		$config["cols"]["suggested_by"]["length"] = "32";'."\n";
			$fileout.='		$config["cols"]["state"]["type"] = "varchar";'."\n";
			$fileout.='		$config["cols"]["state"]["length"] = "32";'."\n";
		}

				
		$fileout.='		// generate insert array'."\n";
		$fileout.='
		$update = array('."\n";
		foreach($this->datatypes[$class] as $type=>$datatype){
			if(substr($datatype,-8) == "Instance") continue;
			//if($type == "Owner") continue;
			$col = "COL_".strtoupper($coldbname = $this->ConvertToDBName($type));
			if($datatype == "LocalisedString"){
				$fileout.='			self::$'.$col.'=>Language::set($'.$type.'),'."\n";
			}else if(isset($this->doc[false][true][false][$class][$type]["code"])){
				continue;
			}else{
				$fileout.='			self::$'.$col.'=>$'.$type.','."\n";
			}
		}
		
		if(isset($this->doc[true][false][false][$class][$class]["rule"])&& $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
			$type = "SuggestedBy";
			$col = "COL_".strtoupper($coldbname = $this->ConvertToDBName($type));
			$fileout.='			self::$'.$col.'=>$apiuser,'."\n";
			$type = "State";
			$col = "COL_".strtoupper($coldbname = $this->ConvertToDBName($type));
			$fileout.='			self::$'.$col.'=>$State,'."\n";
		}
		if($this->extendsClasses[$class] != '\AsyncWeb\Api\REST\Service'){
			$fileout.='			self::$COL_INSTANCE_DATA_TYPE=>"'.$class.'",'."\n";
		}
		
		$fileout.='			self::$COL_MODIFIED_BY=>$session->ApiKey->ID,'."\n";
		$fileout.='			"created"=>time(),
			);
		// perform insert into the database
		if(DB::u(self::$TABLE,$ID,$update,$config)){
			if(DB::error()) throw new \Exception(DB::error());
			return $ID;
		}else{
			if(DB::error()) throw new \Exception(DB::error());
			return false;
		}
	}'."\n\n";
	}
		return $fileout;
	}
	public function GeneratePHPUpdate($class){

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// UPDATE
	if(isset($this->doc[false][false][true][$class]["Update"])){
		
		$fileout.='	/**
	'.$this->doc[false][false][true][$class]["Update"]["doc"].'	
';
	foreach($this->datatypes[$class] as $type=>$datatype){
		if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
		if(substr($datatype,-8) == "Instance") continue;
		//if($type == "Owner") continue;
		if(isset($this->doc[false][true][false][$class][$type]["doc"])){
			$fileout.='	@param string $'.$type.' '.trim($this->doc[false][true][false][$class][$type]["doc"])."\n";
		}else{
			$fileout.='	@param string $'.$type.' '.trim($type)."\n";
		}
	}
	$fileout.='	@param string $ApiKeySession Session identifier obtained from Service->Connect() function
	@param string $CRC <p><b>CRC & Authorisation verifier</b></p>
		<p>CRC = sha512(
			"ID=..&';
	foreach($this->datatypes[$class] as $type=>$datatype){
	if(substr($datatype,-8) == "Instance") continue;
	//if($type == "Owner") continue;
	if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
$fileout.=$type."=..&";
	}			
			$fileout.='ApiKeySession=..&ApiSecret=.."
			)</p>
		<p>If CRC does not match, function returns unauthorized exception.</p>
		<p>If any of the variable is null or is empty string, it must not be used for hash crc.</p>
		<p>The order of parameters matters. Parameters as well as data are case sensitive.</p>
		
	@return int (1) on success update, Returns true on success with no modification. Throws exception on non success
	@throws \\'.$this->Namespace.'\Service\Exception\InvalidArgumentException Invalid argument
	@throws \\'.$this->Namespace.'\Service\Exception\UnauthorizedException Unauthorized accesss
	*/
	
	public static function Update($ID="",';
	foreach($this->datatypes[$class] as $type=>$datatype){
		if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
		if(substr($datatype,-8) == "Instance") continue;
		//if($type == "Owner") continue;
		$fileout.='$'.$type."='__AW__VALUE_NOT_CHANGED',";
	}			
			$fileout.='$ApiKeySession = "",$CRC = ""){
		$vars=array("ID"=>$ID,';
	foreach($this->datatypes[$class] as $type=>$datatype){
		if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
		if(substr($datatype,-8) == "Instance") continue;
		//if($type == "Owner") continue;
		$fileout.='"'.$type.'"=>$'.$type.',';
	}$fileout.='"ApiKeySession"=>$ApiKeySession,"CRC"=>$CRC);
		foreach($vars as $var=>$v){if(isset($_REQUEST[$var])){$$var = $_REQUEST[$var];$vars[$var] = $_REQUEST[$var];}else{if(isset($_REQUEST[strtolower($var)])){$$var = $_REQUEST[strtolower($var)];$vars[$var] = $_REQUEST[strtolower($var)];}}}
		$apiuser = \\'.$this->Namespace.'\Classes\Session::Validate($vars);
		$session = \\'.$this->Namespace.'\Classes\Session::Instance($ApiKeySession);
		
		$instance = self::Instance($ID);'."\n";
		if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
			$fileout.='		if($instance->State == "Suggested" && $instance->SuggestedBy == $apiuser){
			$State = "Suggested";
		}else{
			if(!isset($session->ApiKey->Groups["admin"])){
				throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You are not authorized to update information about this instance."));
			}else{
				$State = "Approved";
			}
		}'."\n";
		}elseif(isset($this->doc[true][false][false][$class][$class]["rule"]) && substr($this->doc[true][false][false][$class][$class]["rule"],0,4) == "only"){
			 $groups = explode("|",substr($this->doc[true][false][false][$class][$class]["rule"],4));
		$fileout.='
		if(';
		$i = 0;
		foreach($groups as $group){$i++;
			if($i > 1) $fileout.='&&';
			$fileout.='!isset($session->ApiKey->Groups["'.trim($group).'"])';
		}
		$fileout.='){
			throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You are not allowed to update instance of %object%.",array("%object%"=>"'.$class.'")));
		}'."\n";
		}elseif(isset($this->datatypes[$class]["Owner"])){
			$fileout.='		if($instance->Owner == $apiuser){
			// ok
		}else{
			if(!isset($SecurityChecked) || !$SecurityChecked){
				if($Owner != "__AW__VALUE_NOT_CHANGED" && !isset($session->ApiKey->Groups["admin"])){
					throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You are not authorized to update this instance."));
				}else{
					// ok
				}
			}
		}'."\n";
			}
		
	
		if(isset($this->doc[false][false][true][$class]["Update"]["code"])){
			foreach($this->doc[false][false][true][$class]["Update"]["code"] as $line){
				$fileout.='		'.$line."\n";
			}
		}
		foreach($this->datatypes[$class] as $type=>$datatype){
			if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
			if(substr($datatype,-8) == "Instance") continue;
			//if($type == "Owner") continue;
			if(isset($this->doc[false][true][false][$class][$type]["instanceof"])){
				
				if(isset($this->optionality[$class][$type]) && $this->optionality[$class][$type] == "-"){
					$fileout.='		if($'.$type.' && $'.$type.' != "__AW__VALUE_NOT_CHANGED"){'."\n	";
				}else{
					$fileout.='		if($'.$type.' != "__AW__VALUE_NOT_CHANGED"){'."\n	";
				}
				
				$fileout.='		'.$this->doc[false][true][false][$class][$type]["instanceof"].'::Instance($'.$type.'); // checks if instance is correct'."\n";
				$fileout.='		}'."\n";
				
			}else if(isset($this->optionality[$class][$type]) && $this->optionality[$class][$type] == "+"){
				$dt = strtolower($datatype);
				if($dt == "bool" || $dt == "boolean"){
					$fileout.='		if($'.$type.' === null){';
					$fileout.='throw new \\'.$this->Namespace.'\Service\Exception\InvalidArgumentException(Language::get("Parameter %parameter% must not be empty!",array("%parameter%"=>"'.$type.'")));';
					$fileout.='}'."\n";
				}else{
					$fileout.='		if(!$'.$type.'){';
					$fileout.='throw new \\'.$this->Namespace.'\Service\Exception\InvalidArgumentException(Language::get("Parameter %parameter% must not be empty!",array("%parameter%"=>"'.$type.'")));';
					$fileout.='}'."\n";
				}
			}		
		}
		
		$fileout.='		$update=array();'."\n";	
		foreach($this->datatypes[$class] as $type=>$datatype){
			if(substr($datatype,-8) == "Instance") continue;
			//if($type == "Owner") continue;
			$col = "COL_".strtoupper($coldbname = $this->ConvertToDBName($type));
			if($datatype == "LocalisedString"){
				$fileout .= '		if($'.$type.' != "__AW__VALUE_NOT_CHANGED") $update[self::$'.$col.'] = Language::set($'.$type.');'."\n";
			}else if(isset($this->doc[false][true][false][$class][$type]["code"])){
				continue;
			}else{
				$fileout .= '		if($'.$type.' != "__AW__VALUE_NOT_CHANGED") $update[self::$'.$col.'] = $'.$type.';'."\n";
			}
		}

		if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
			$fileout.='		if($instance->State != $State){$update["state"] = $State;};'."\n";
		}		
		if($this->extendsClasses[$class] != '\AsyncWeb\Api\REST\Service'){
			$fileout.='			$update[self::$COL_INSTANCE_DATA_TYPE]="'.$class.'";'."\n";
		}
		$fileout.='			if($instance->ModifiedBy != $apiuser) $update[self::$COL_MODIFIED_BY]=$apiuser;'."\n";
		
		$fileout.="\n".'		$config = array();'."\n";
		foreach($this->datatypes[$class] as $type=>$datatype){
			if(substr($datatype,-8) == "Instance") continue;
			$DataType =strtolower($datatype);
			$type = $this->ConvertToDBName($type);
			if($DataType == "string"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "varchar";'."\n";
				$fileout.='		$config["cols"]["'.$type.'"]["length"] = "250";'."\n";
			}else
			if($DataType == "int"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "int";'."\n";
			}else		
			if($DataType == "bool"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "int";'."\n";
			}else		
			if($DataType == "text"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "text";'."\n";
			}else		
			if($DataType == "decimal"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "decimal";'."\n";
			}else		
			if($DataType == "double"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "double";'."\n";
			}else		
			if($DataType == "blob"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "blob";'."\n";
			}else		
			if($datatype == "LocalisedString"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "char";'."\n";
				$fileout.='		$config["cols"]["'.$type.'"]["length"] = "32";'."\n";
				$fileout.='		$config["keys"][] = "'.$type.'";'."\n";			
			}else
			if(substr($datatype,-2) == "ID"){
				$fileout.='		$config["cols"]["'.$type.'"]["type"] = "char";'."\n";
				$fileout.='		$config["cols"]["'.$type.'"]["length"] = "32";'."\n";
				$fileout.='		$config["keys"][] = "'.$type.'";'."\n";			
			}else{
				echo "WARNING: unknown data type $class.$type :: $datatype\n";
			}
		}
		if($this->extendsClasses[$class] != '\AsyncWeb\Api\REST\Service' && !isset($this->datatypes[$class]["InstanceDataType"])){
			$fileout.='		$config["cols"]["instance_data_type"]["type"] = "varchar";'."\n"; 
			$fileout.='		$config["cols"]["instance_data_type"]["length"] = "250";'."\n";
			$fileout.='		$config["keys"][] = "instance_data_type";'."\n";			
		}
		$fileout.='		$config["cols"]["modified_by"]["type"] = "char";'."\n";
		$fileout.='		$config["cols"]["modified_by"]["length"] = "32";'."\n";
		$fileout.='		$config["cols"]["created"]["type"] = "bigint";'."\n";
		
		if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
			$fileout.='		$config["cols"]["suggested_by"]["type"] = "char";'."\n";
			$fileout.='		$config["cols"]["suggested_by"]["length"] = "32";'."\n";
			$fileout.='		$config["cols"]["state"]["type"] = "varchar";'."\n";
			$fileout.='		$config["cols"]["state"]["length"] = "32";'."\n";
		}

		
		
		$fileout.='		if(count($update) > 0){'."\n";
		$fileout.='			$ret= DB::u(self::$TABLE,$ID,$update,$config);'."\n";
		$fileout.='			if(DB::error()) throw new \Exception(DB::error());'."\n";
		$fileout.='			return $ret;'."\n";
		$fileout.='		}else{return true;}'."\n";
		
		$fileout.='	}'."\n"."\n";
	}
		return $fileout;
	}
	public function GeneratePHPDelete($class){
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// DELETE
	if(isset($this->doc[false][false][true][$class]["Delete"])){
		
		$fileout.='	/**
	'.$this->doc[false][false][true][$class]["Delete"]["doc"].'	
';
	$fileout.='	
	@param string $UID Unique identifier of the object to be deleted.
	@param string $ID Identifier of the object to be deleted.
	@param string $ApiKeySession Session identifier obtained from Service->Connect() function
	@param string $CRC <p><b>CRC & Authorisation verifier</b></p>
		<p>CRC = sha512(
			"UID=..&ID=..&ApiKeySession=..&ApiSecret=.."
			)</p>
		<p>If CRC does not match, function returns unauthorized exception.</p>
		<p>If any of the variable is null or is empty string, it must not be used for hash crc.</p>
		<p>The order of parameters matters. Parameters as well as data are case sensitive.</p>
		
	@return int (1) on success Deletetion. Throws exception on non success
	@throws \\'.$this->Namespace.'\Service\Exception\InvalidArgumentException Invalid argument
	@throws \\'.$this->Namespace.'\Service\Exception\UnauthorizedException Unauthorized accesss
	*/
	
	public static function Delete($UID = "",$ID = "",$ApiKeySession = "",$CRC = ""){
		$vars=array("UID"=>$UID,"ID"=>$ID,"ApiKeySession"=>$ApiKeySession,"CRC"=>$CRC);
		foreach($vars as $var=>$v){if(isset($_REQUEST[$var])){$$var = $_REQUEST[$var];$vars[$var] = $_REQUEST[$var];}else{if(isset($_REQUEST[strtolower($var)])){$$var = $_REQUEST[strtolower($var)];$vars[$var] = $_REQUEST[strtolower($var)];}}}
		$apiuser = \\'.$this->Namespace.'\Classes\Session::Validate($vars);
		$session = \\'.$this->Namespace.'\Classes\Session::Instance($ApiKeySession);

		$instance = self::Instance($ID);'."\n";

		
		if(isset($this->doc[false][false][true][$class]["Delete"]["code"])){
			foreach($this->doc[false][false][true][$class]["Delete"]["code"] as $line){
				$fileout.='			'.$line."\n";
			}
		}
		
		if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
			$fileout.='		if($instance->State == "Suggested" && $instance->SuggestedBy == $apiuser){
			// ok
		}else{
			if(!isset($session->ApiKey->Groups["admin"])){
				throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You are not authorized to delete this instance."));
			}else{
				// ok
			}
		}'."\n";
		}else if(isset($this->datatypes[$class]["Owner"])){
			$fileout.='		if($instance->Owner == $apiuser){
			// ok
		}else{
			if(!isset($SecurityChecked) || !$SecurityChecked){
				if(!isset($session->ApiKey->Groups["admin"])){
					throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You are not authorized to delete this instance."));
				}else{
					// ok
				}
			}
		}'."\n";
		}else{ // only admin is allowed to delete objects which are not suggestable and are not owned by anyone
				$fileout.='
		if(!isset($session->ApiKey->Groups["admin"])){
			throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You are not authorized to delete this instance."));
		}'."\n";
		}
		
		
$fileout.='
		if(!$UID && !$ID){
			throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You must provide identifier in order to delete the object."));
		}
		if($ID){
			return DB::delete(self::$TABLE,array(self::$COL_ID=>$ID));
		}elseif($UID){
			return DB::delete(self::$TABLE,array(self::$COL_UID=>$UID));
		}
		return false;
	}'."\n"."\n";
	}
		return $fileout;
	}
	public function GeneratePHPRequest($class){
		

	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// Request
	if(isset($this->doc[false][false][true][$class]["Request"])){
		
		$fileout.='	/**
	'.$this->doc[false][false][true][$class]["Request"]["doc"].'	
';
	$fileout.='	
	@param string $QueryBuilder Query Builder Array of ("Where"=>$where, "Offset"=>$offset, "Limit"=>$count, "Time"=>$time, "Sort"=>$order, "Cols"=>$cols, "GroupBy"=>$groupby, "Having"=>$having, "Distinct"=>$distinct)
	@param string $ApiKeySession Session identifier obtained from Service->Connect() function
	@param string $CRC <p><b>CRC & Authorisation verifier</b></p>
		<p>CRC = sha512(
			"QueryBuilder=..&Limit=..&ApiKeySession=..&ApiSecret=.."
			)</p>
		<p>If CRC does not match, function returns unauthorized exception.</p>
		<p>If any of the variable is null or is empty string, it should not be used for hash crc</p>
		<p>The order of parameters matters. Parameters as well as data are case sensitive.</p>
		
	@return Array<'.$class.'> ArrayOf'.$class.' Returns array of '.$class.'.
	
	@throws \\'.$this->Namespace.'\Service\Exception\InvalidArgumentException Invalid argument
	@throws \\'.$this->Namespace.'\Service\Exception\UnauthorizedException Unauthorized accesss
	*/
	
	public static function Request($QueryBuilder = array(),$ApiKeySession = "",$CRC = ""){
		
		$vars=array("QueryBuilder"=>$QueryBuilder,"ApiKeySession"=>$ApiKeySession,"CRC"=>$CRC);
		foreach($vars as $var=>$v){if(isset($_REQUEST[$var])){$$var = $_REQUEST[$var];$vars[$var] = $_REQUEST[$var];}else{if(isset($_REQUEST[strtolower($var)])){$$var = $_REQUEST[strtolower($var)];$vars[$var] = $_REQUEST[strtolower($var)];}}}
		$apiuser = \\'.$this->Namespace.'\Classes\Session::Validate($vars);
		$session = \\'.$this->Namespace.'\Classes\Session::Instance($ApiKeySession);'."\n";
		if(isset($this->doc[false][false][true][$class]["Request"]["code"])){
			foreach($this->doc[false][false][true][$class]["Request"]["code"] as $line){
				$fileout.='		'.$line."\n";
			}
		}
		$fileout.='
		if(!isset($QueryBuilder["Sort"])){
			$QueryBuilder["Sort"]=array("created"=>"desc");
		}
		$qb = self::ConvertQuery($QueryBuilder,self::$CONVERT,self::$DB_DICT_COLS);
		$ret = array();
		$qb["cols"] = array(self::$COL_ID);';
		
		if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
			$fileout .= '
		if(!isset($session->ApiKey->Groups["admin"])){
			$qb["cols"][] = self::$COL_STATE;
			$qb["cols"][] = self::$COL_SUGGESTED_BY;
		}'."\n";	
		}elseif(isset($this->datatypes[$class]["Owner"])){
			$fileout .= '
		if(!isset($session->ApiKey->Groups["admin"])){
			$qb["cols"][] = self::$COL_OWNER;';
			if(!isset($this->doc[false][false][true][$class]["Request"]["codeiter"])){
				$fileout.='
			$qb["where"][self::$COL_OWNER] = $apiuser;';
			}
			$fileout.='
		}'."\n";	
		}
		if($this->extendsClasses[$class] != '\AsyncWeb\Api\REST\Service'){
			$fileout.='		$qb["where"][] = array("col"=>"instance_data_type","op"=>"eq","value"=>"'.$class.'");'; 
		}
		$fileout .= '
		$res = DB::qb(self::$TABLE,$qb);

		$num = DB::num_rows($res);
		while($row=DB::f($res)){'."\n";
		if(isset($this->doc[false][false][true][$class]["Request"]["codeiter"])){
			foreach($this->doc[false][false][true][$class]["Request"]["codeiter"] as $line){
				$fileout.='			'.$line."\n";
			}
		}
		
		if(isset($this->doc[true][false][false][$class][$class]["rule"]) && $this->doc[true][false][false][$class][$class]["rule"] == "userIsAllowedToSuggest"){
			$fileout .= '
		if(!isset($session->ApiKey->Groups["admin"])){
			if($row[self::$COL_STATE] != "Approved" && $row[self::$COL_SUGGESTED_BY] != $apiuser){
				if($num == 1){
					throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You are not authorized to display this object!"));
				}else{
					continue;// skip loading this object
				}
			}
		}';
		}elseif(isset($this->datatypes[$class]["Owner"])){
			$fileout .= '
			if(!isset($session->ApiKey->Groups["admin"])){
				if($row[self::$COL_OWNER] != $apiuser){
					if(!isset($SecurityChecked) || !$SecurityChecked){
						if(isset($QueryBuilder["Where"]["ID"])){
							throw new \\'.$this->Namespace.'\Service\Exception\UnauthorizedException(Language::get("You are not authorized to display this object!"));
						}else{
							continue;// skip loading this object
						}
					}
				}
			}';
		}

		
		$fileout .= '
			$ret[] = self::Instance($row[self::$COL_ID]);
		}
		return $ret;
	}'."\n";
	}
		return $fileout;
	}
	public function GeneratePHPFooter($class){
		return $fileout.='}';	
	}
	public function GenerateForm($class){

	
	$form  = '<?php

namespace '.$this->Namespace.'\Block\Form\\'.$this->Folder.';

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;

class '.$class.' extends \AsyncWeb\DefaultBlocks\Form{
	protected $requiresAuthenticatedUser = true;
	protected $requiresAllGroups = array("admin");
	protected $type = "ApiForm";
	public function initTemplate(){
		$this->formSettings = array(
			"ApiServer"=>AdminApiServer,
			"ApiKey"=>AdminApiKey, 
			"ApiPass"=>AdminApiSecret,


			"table" => "'.$class.'",
			"col" => array( ';
			foreach($this->datatypes[$class] as $type=>$datatype){
				
				if(isset($this->doc[false][true][false][$class][$type]["code"])) continue;
				if(substr($datatype,-8) == "Instance") continue;
				if($datatype == "int"){
					$form.='	array("name"=>Language::get("'.$type.'"),"texts"=>array("default"=>""),"data"=>array("col"=>"'.$type.'"),"usage"=>array("MFi","MFu","DBVs","DBVe")),';
				}elseif(strtolower($datatype) == "string"){
					$form.='	array("name"=>Language::get("'.$type.'"),"texts"=>array("default"=>""),"data"=>array("col"=>"'.$type.'"),"usage"=>array("MFi","MFu","DBVs","DBVe")),';
				}elseif(substr($datatype,-4) == "Enum"){
					$form.='	array("name"=>Language::get("'.$type.'"),"texts"=>array("default"=>""),"data"=>array("col"=>"'.$type.'"),"usage"=>array("MFi","MFu","DBVs","DBVe")),';
				}elseif(substr($datatype,-2) == "ID"){
					$form.='	array(
						"name"=>Language::get("'.$type.'"),
						"form"=>array("type"=>"selectDB"),
						"data"=>array(
						    "col"=>"'.$type.'",
							"allowNull"=>'.((isset($this->optionality[$class][$type]) && $this->optionality[$class][$type] == "+")?"false":"true").',
							"fromTable"=>"'.substr($datatype,0,-2).'",
							"fromColumn"=>"Name",
						),
						"texts"=>array(
							"nullValue"=>Language::get("Choose a value"),
							"no_data"=>Language::get("Not selected"),
						),
						"usage"=>array("MFi","MFu")),
					';
				}else{
					$form.='	array("name"=>Language::get("'.$type.'"),"texts"=>array("default"=>""),"data"=>array("col"=>"'.$type.'"),"usage"=>array("MFi","MFu","DBVs","DBVe")),';
				}
				$form.="\n";

			}
			$form.='
			),
			"bootstrap"=>"1",
			"uid"=>"'.$class.'",
			"show_export"=>true,
			"iter"=>array("per_page"=>"30"),
			"show_filter"=>true,
			"allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
			"rights"=>array("insert"=>"admin","update"=>"admin","delete"=>"admin",),
		);

		$this->initTemplateForm();
	}
}';
		return $form;
	}
	public function SaveFormToOutput(){
		$this->ParseDirectory();
		$this->ProcessExtension();
		foreach($this->datatypes as $class=>$this->datatypes[$class]){
			$fileout = $this->GenerateForm($class);
			

			if($this->OutputFormsDirectory){
				$outform = $this->OutputFormsDirectory."/".$this->Folder."/".$class.".php";
				if(!file_exists($outform) || md5_file($outform) != md5($form)) {
					$res = file_put_contents($outform,$form);
					echo $outform." ".$res."\n";
				}
			}			
		}
	}
	public function SaveToOutput(){
		$this->ParseDirectory();
		$this->ProcessExtension();
		foreach($this->datatypes as $class=>$this->datatypes[$class]){
			$fileout = $this->GeneratePHPTop($class);
			$fileout.= $this->GeneratePHPVariables($class);
			$fileout.= $this->GeneratePHPConstructor($class);
			$fileout.= $this->GeneratePHPInstance($class);
			$fileout.= $this->GeneratePHPCreate($class);
			$fileout.= $this->GeneratePHPUpdate($class);
			$fileout.= $this->GeneratePHPDelete($class);
			$fileout.= $this->GeneratePHPRequest($class);
			$fileout.= $this->GeneratePHPFooter($class);
			
			
			$outfile = $this->OutputDirectory."/".$this->ConvertClassToDirectory($class).".php";
			$dir = dirname($outfile);
			if(!is_dir($dir)) mkdir($dir,0770,true);
			if(!file_exists($outfile) || md5_file($outfile) != md5($fileout)){
				$res = file_put_contents($outfile,$fileout);
				echo $outfile." ".$res."\n";
			}
			
		}
		
			
	}
	public function ConvertClassToDirectory($class){
		return str_replace("_","/",$class);
	}
	public function ConvertClassToNamespaceName($class){
		return str_replace("_","\\",$class);
	}
	public function MyNamespace($class){
		$arr = explode("_",$class);
		array_pop($arr);
		if($arr){
			return $this->Namespace.'\\'.$this->Folder.'\\'.implode("\\",$arr);
		}
		return $this->Namespace.'\\'.$this->Folder;
	}
	public function ClassName($class){
		$arr = explode("_",$class);
		return array_pop($arr);
	}
	public function Schema(){
		$this->ParseDirectory();
		foreach($this->append as $class=>$arr1){
			foreach($arr1 as $operand => $arr2){
				foreach($arr2 as $otherclass=>$true){
					$this->schema .= "[$class]${operand}[$otherclass]\n";
				}
			}
		}
		$link = "http://www.nomnoml.com/#view/".urlencode($this->schema);
		
		
		return $link."\n".$this->schema;
	}
	public function ListClasses(){
		$ret = array();
		$this->ParseDirectory();
		foreach($this->datatypes as $class=>$types){
			$ret[] = $class;
		}
		return $ret;
	}
	public function ConvertToDBName($text){
		//$text = "ILovePHPAndXMLSoMuch";
		$lastUpper = false;
		$newStr = "";
		foreach(str_split($text) as $index => $char) {
			$lower = strtolower($char);
			if($lower == $char){
				if($lastUpper){
					$newStr = substr($newStr,0,-1)."_".substr($newStr,-1);
				}
				$newStr.=$char;
				$lastUpper = false;
				
				continue;
			}
			if($lastUpper){
				$newStr .= $lower;
			}else{
				$newStr .= "_".$lower;
				$lastUpper = true;
			}
		}
		$newStr = trim($newStr,"_");
		$newStr = str_replace("__","_",$newStr);
		$newStr = str_replace("__","_",$newStr);
		$newStr = str_replace("__","_",$newStr);
		return $newStr;
	}
}
