<?php
namespace AppBundle\Helpers;

use Doctrine\DBAL\Connection;
//use AppBundle\Helpers\HarImport;
//require_once 'vendor/autoload.php';

class DataConverter{
	private $connection;

	function __construct(Connection $connection){
		$this->connection = $connection;
	}
	//export to excel and download
	function excelDownload($db){
		//EXCEL DOWNLOAD FILE

		include("/mysql_excel/mysql_excel.inc.php");

		$month = date('n');
		$year = date('Y');
		$month_full = str_replace('','_', date('F Y'));

		$date = date("d_M_Y");

		$import=new \HarImport();
		//$import->openDatabase("localhost","root","","snlis");

		$import->ImportData($db,"snlis.xls",true); //To force to download

	}
	//convert an array of need ids to a string of comma separated ids
	function convertToCommaString($array, $removeDelimitingQuotes = false){
		$escaped = array_map(array($this->connection, 'quote'), $array); //escape each element of the array

		if($removeDelimitingQuotes){
			$quote = "'";
			$escaped = $this->arrayRemoveQuotes($escaped);
		}
		return implode(',', $escaped); //convert the array to a string of comma separated values
	}
	//convert a string of comma separated values to an array
	function convertToArray($commaString){
		return explode(',', $commaString);
	}
	//removing surrounding quotes from every element of an array
	function arrayRemoveQuotes($array){
		$quote = "'";
		$unquoted = array_map(
		/*remove surrounding quotes from every element of the array*/
				function($item) use ($quote) {
					return trim($item, $quote);
				},
				$array );
		return $unquoted;
	}
	function countArray($array, $key, $value){
		$count = 0;
		foreach($array as $element){
			if(is_array($element)){
				if($element[$key] == $value)
					$count++;
			}
		}
		return $count;
	}
	function countArrayMultipleBool($array, $conditions){
		$count = 0;
		foreach($array as $element){
			if(is_array($element)){
				$passed = true;
				foreach ($conditions as $key => $condition){
					$value = (is_numeric($element[$key]))? $element[$key] : '\''.$element[$key].'\'';
					if(!eval("return ".$value.$condition.";")){
						$passed = false;
					}
				}
				if($passed){
					$count++;
				}
			}
		}
		return $count;
	}
	function countArrayMultipleGtEt($array, $conditions){
		$count = 0;
		foreach($array as $element){
			if(is_array($element)){
				$passed = true;
				foreach ($conditions as $key => $condition){
					//echo "return \"".$element[$key]."\"".$condition.";<br>";
					if($element[$key] >= $condition){
						$passed = false;
					}
				}
				if($passed){
					$count++;
				}
			}
		}
		return $count;
	}
	function countArrayMultiple($array, $values){
		$count = 0;

		foreach($array as $element){

			if(is_array($element)){
				$passed = true;
				foreach ($values as $key => $value){
					if($element[$key] != $value){
						$passed = false;
					}
				}
				if($passed){
					$count++;
				}
			}
		}
		return $count;
	}
	function countArrayBool($array, $key, $condition){
		$count = 0;
		foreach($array as $element){
			if(is_array($element)){
				if(eval("return ".$element[$key].$condition.";"))
					$count++;
			}
		}
		return $count;
	}
	function selectFromArray($array, $key, $value){
		$resultArray = array();
		foreach($array as $element){
			if(is_array($element)){
				if($element[$key] == $value){
					$resultArray[] = $element;
				}
			}
		}
		return $resultArray;
	}
	function selectFromArrayBool($array, $key, $condition, $keyIsString = false){
		$quote = ($keyIsString)? "'" : '';
		$resultArray = array();
		foreach($array as $element){
			//echo 'Method found';
			if(is_array($element)){
				if(eval("return ".$quote.$element[$key].$quote.$condition.";")){
					$resultArray[] = $element;
				}
			}
		}
		return $resultArray;
	}
	function findArrayMax($array, $key){
		$max = 0;
		foreach($array as $element){
			$max = $element[$key];
			break;
		}
		foreach($array as $element){
			if(is_array($element)){
				if($element[$key] > $max)
					$max = $element[$key];
			}
		}
		return $max;
	}
	function findArrayMin($array, $key){
		$min = 0;
		foreach($array as $element){
			$min = $element[$key];
			break;
		}
		foreach($array as $element){
			if(is_array($element)){
				if($element[$key] < $min)
					$min = $element[$key];
			}
		}
		return $min;
	}
	function serializeColumn(&$array, $key){

	}

}

?>