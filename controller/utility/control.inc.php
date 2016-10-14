<?php
	class Controller_utility{

		function __construct(){
			$this->_utility = getModel('utility');
			$this->_bibdata = getModel('bibdata');
			$this->_summon  = getModel('summon');
		}


		function getSubmitFields($type) {
			return $this->_utility->call($command, $params);
		}


		function getJSON($command, $params) {
			$res = $this->call($command, $params);
			return $res;
		}


		function getArray($command, $params) {
			$res = $this->call($command, $params);
			$dec = json_decode($res, TRUE);
			return $dec['data'];
		}


	}
