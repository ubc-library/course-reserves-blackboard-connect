<?php

	class Model_utility {

		function resetMemcache(){
			$p = $this->getPickslip();

			MC::flush();

			$this->setPickslip($p);
		}

		function getMenuBrokenLinks(){
			$brokenlinks = md5('brokenlinks');
			if (!($count = MC::get($brokenlinks))) {
				if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
					$json   = file_get_contents('http://joss.library.ubc.ca/areslinkchecker/numbroken.php');
					$obj    = json_decode($json);
					$count  = $obj->count;
					MC::set($brokenlinks, $count, MC::getDuration('long'));
				}
				else {}
			}
			return $count;
		}

		function getMenuNewItems() {
			if (!($count = MC::get(md5('newitemcount')))) {
				if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
					$count = -99999;
				}
				else {}
			}
			return $count;
		}

		function getDiskSpace() {
			$key = md5('diskspace');
			if (!($used = MC::get($key))) {
				if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
					$df = disk_free_space("/");
					$ds = disk_total_space("/");
					$used    = (100 - round(($df/$ds)*100));
					MC::set($key,$used, MC::getDuration('medium'));
				}
				else {}
			}
			return $used;
		}

		function getList($key,$command){
			$licr       = getModel('licr');
			$k = md5($key);
			if (!($list = MC::get($k))) {
				if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
					$list = $licr->getArray($command);
					MC::set($k, $list,MC::getDuration('long'));
				}
				else {}
			}
			return $list;
		}

		function getParsedStatuses(){

			$itemtypekeys = md5('parsedstatuses');

			if (!($parsedTypes = MC::get($itemtypekeys))) {
				if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
					$licr   = getModel('licr');
					$types  = $licr->getArray('ListStatuses');
					if(isset($types)){
						$parsedTypes = array();
						foreach($types as $k => &$type){
							if(strpos($type['status_name'],'ARES')){}
							else {
								$parsedTypes[$type['status_id']] = array(
									'status_id'     => $type['status_id']
								,'status_name'  => $type['status_name']
								);
							}
						}
						MC::set($itemtypekeys,$parsedTypes, MC::getDuration('long'));
						unset($types);
					}
				}
				else {}
			}

			return $parsedTypes;
		}

		function getParsedItemTypes(){

			$itemtypekeys = 'parseditemtypes';

//            MC::flush();

			if (!($parsedTypes = MC::get($itemtypekeys))) {
				if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
					$licr   = getModel('licr');
					$types  = $licr->getArray('ListTypes');
					if(isset($types)){
						$parsedTypes = array();
						foreach($types as $k => &$type){
							$parsedTypes[$k] = array(
								'type_id'       => $k
               ,'name'         => strtolower(preg_replace('/[^\\w]+/', '_', $type['name']))
               ,'physical'     => $type['physical']
               ,'displayname'  => $type['name']
							);
						}
						unset($types);
            MC::set($itemtypekeys, $parsedTypes, MC::getDuration('long'));
					} else {
            error_log("Failed to get list of types from LICR");
          }
				}
			}

			return $parsedTypes;
		}

		function getBibdata($bibdata){

			$isAres = false;
			$bibdataraw = unserialize($bibdata);

			if(isset($bibdataraw['AresItem'])){
				$bibdataraw = $bibdataraw['AresItem'];
				$isAres     = true;
			}

			return array ('bibdata' => $bibdataraw, 'isAres' => $isAres);
		}

		function setPickslip($arr){
			$k = md5('most-recent-pickslip');
			MC::set($k,$arr,MC::getDuration('short'));
		}

		function getPickslip(){
			$k = md5('most-recent-pickslip');
			if (!($arr = MC::get($k))) {
				if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
					$arr = array('time'=>'-2208988800','url'=>'/pickslips.archive');
				}
				else {}
			}
			return $arr;
		}

		function checkURIExists($url){
			$exists = false;
			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,15);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_exec($ch);
			if(curl_errno($ch)){
			}
			if(curl_errno($ch)== 3) {
				$exists = -3;
			}
			if(curl_errno($ch)== 2 || curl_errno($ch)== 5 || curl_errno($ch)== 6) {
				$exists = -1;
			}
			if(curl_errno($ch)== 7 || curl_errno($ch)== 28 || curl_errno($ch)== 22) {
				$exists = 28;
			}
			else {
				$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($retcode >= 400){
					$exists = false;
				}
				else if ($retcode == 200 || $retcode == 301 || $retcode == 302  || $retcode == 304 || $retcode == 307) {
					$exists = true;
				}
			}
			curl_close($ch);

			return $exists;
		}

		public function getAllIdBoxRoles(){
			require_once (Config::get ( 'approot' ) . '/core/idboxapi.inc.php');
			$groups = idboxCall ('ListAllGroups', array());
			$parsedGroups = array();
			foreach ($groups as $group){
				if(!(strpos($group,'CR-') === false)){
					$parsedGroups[] = $group;
				}
			}
			unset($groups);
			return $parsedGroups;
		}

		public function getUserIdBoxRoles($puid){
			require_once (Config::get ( 'approot' ) . '/core/idboxapi.inc.php');
			$groups = idboxCall ('ListGroups', array('puid' => $puid));
			$parsedGroups = array();
			foreach ($groups as $group){
				if(!(strpos($group,'CR-') === false)){
					$parsedGroups[] = $group;
				}
			}
			unset($groups);
			return $parsedGroups;
		}
	}
