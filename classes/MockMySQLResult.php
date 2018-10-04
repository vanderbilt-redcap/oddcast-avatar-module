<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

class MockMySQLResult
{
	private $index = 0;

	function __construct($data){
		$this->data = $data;
	}

	function fetch_assoc(){
		return $this->data[$this->index++];
	}
}