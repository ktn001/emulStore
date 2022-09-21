<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class emulStore extends eqLogic {

    /*     * *********************Méthodes d'instance************************* */


	// Fonction exécutée automatiquement après la création de l'équipement
	public function postInsert() {

		// Création de l'info de position
		$cmd = new emulStoreCmd();
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId("position");
		$cmd->setName("position");
		$cmd->setType("info");
		$cmd->setSubType("numeric");
		$cmd->setOrder(0);
		$cmd->setConfiguration("minValue",0);
		$cmd->setConfiguration("maxValue",100);
		$cmd->setUnite("%");
		$cmd->save();

		// Création de la commande de fermeture
		$cmd = new cmd();
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId("ferme");
		$cmd->setName("fermer");
		$cmd->setType("action");
		$cmd->setSubType("other");
		$cmd->setOrder(1);
		$cmd->save();

		// Création de la commande d'arret
		$cmd = new cmd();
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId("stop");
		$cmd->setName("arrêt");
		$cmd->setType("action");
		$cmd->setSubType("other");
		$cmd->setOrder(2);
		$cmd->save();

		// Création de la commande d'ouverture
		$cmd = new cmd();
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId("ouvre");
		$cmd->setName("ouvrir");
		$cmd->setType("action");
		$cmd->setSubType("other");
		$cmd->setOrder(3);
		$cmd->save();

		// Création de l'info de puissance
		$cmd = new emulStoreCmd();
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId("puissance");
		$cmd->setName("puissance");
		$cmd->setType("info");
		$cmd->setSubType("numeric");
		$cmd->setOrder(4);
		$cmd->setConfiguration("minValue",0);
		$cmd->setConfiguration("maxValue",10);
		$cmd->setUnite("Kwh");
		$cmd->save();
    }

    public function setActionEnCours($action){
	    $this->setCache("actionEnCours", $action);
	    $cmd =  $this->getCmd('info','puissance');
	    if ($action == "ouvre") {
		    $this->checkAndUpdateCmd($cmd,$this->getConfiguration('PuissanceOuverture'));
	    } 
	    if ($action == "ferme") {
		    $this->checkAndUpdateCmd($cmd,$this->getConfiguration('PuissanceFermeture'));
	    }
	    if ($action == "") {
		    $this->checkAndUpdateCmd($cmd,0);
	    }
    }

    public function getActionEnCours(){
	    return $this->getCache("actionEnCours");
    }

}

class emulStoreCmd extends cmd {

	// Exécution d'une commande
	public function execute($_options = array()) {
		if ($this->getType() == 'action'){
			$emulator = $this->getEqLogic();
			$positionCmd = $emulator->getCmd('info','position');
			$position = $positionCmd->execCmd();
			if ($position == "") {
				$emulator->checkAndUpdateCmd($positionCmd,0);
				$position = 0;
			}
			log::add("emulStore","info",__("Traitement de la commande ",__FILE__) . $this->getLogicalId());
			if ($this->getLogicalId() == "ouvre"){
				if ($emulator->getActionEnCours() == $this->getLogicalId()){
					$emulator->setActionEnCours("");
				} elseif ($position < 100){
					$emulator->setActionEnCours($this->getLogicalId());
					$run = __DIR__ . "/../php/run.php -i " . $this->getId();
					system::php($run . ' >> ' . log::getPathToLog('emulStore') . ' 2>&1 &');
				}
			}
			if ($this->getLogicalId() == "ferme"){
				if ($emulator->getActionEnCours() == $this->getLogicalId()){
					$emulator->setActionEnCours("");
				} elseif ($position > 0){
					$emulator->setActionEnCours($this->getLogicalId());
					$run = __DIR__ . "/../php/run.php -i " . $this->getId();
					system::php($run . ' >> ' . log::getPathToLog('emulStore') . ' 2>&1 &');
				}
			}
			if ($this->getLogicalId() == "stop") {
				$emulator->setActionEnCours("");
			}
		}
		return 1;
	}

}


