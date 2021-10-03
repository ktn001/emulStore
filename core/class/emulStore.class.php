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
    /*     * *************************Attributs****************************** */

  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Méthodes d'instance************************* */

 // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {

    }

 // Fonction exécutée automatiquement après la création de l'équipement
	public function postInsert() {
		// Création de la commande d'ouverture
		$cmd = new cmd();
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId("ouvre");
		$cmd->setName("ouvrir");
		$cmd->setType("action");
		$cmd->setSubType("other");
		$cmd->setOrder(0);
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

		// Création de l'info de position
		$cmd = new emulStoreCmd();
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId("position");
		$cmd->setName("position");
		$cmd->setType("info");
		$cmd->setSubType("numeric");
		$cmd->setOrder(2);
		$cmd->setConfiguration("minValue",0);
		$cmd->setConfiguration("maxValue",100);
		$cmd->setUnite("%");
		$cmd->save();

		// Création de l'info de position
		$cmd = new emulStoreCmd();
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId("puissance");
		$cmd->setName("puissance");
		$cmd->setType("info");
		$cmd->setSubType("numeric");
		$cmd->setOrder(3);
		$cmd->setConfiguration("minValue",0);
		$cmd->setConfiguration("maxValue",10);
		$cmd->setUnite("Kwh");
		$cmd->save();
    }

 // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {

    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {

    }

 // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave() {

    }

 // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave() {

    }

 // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove() {

    }

 // Fonction exécutée automatiquement après la suppression de l'équipement
    public function postRemove() {

    }

    /*
     * Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire : permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire : permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
    public function setActionEnCours($action){
	    $this->setCache("actionEnCours", $action);
    }

    public function getActionEnCours(){
	    return $this->getCache("actionEnCours");
    }

}

class emulStoreCmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*
      public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

  // Exécution d'une commande
	public function execute($_options = array()) {
		if ($this->getType() == 'action'){
			$emulatorId = $this->getEqLogic_id();
			$emulator = EmulStore::byId($emulatorId);
			$positionCmd = emulStoreCmd::byEqLogicIdAndLogicalId($emulatorId,'position');
			$position = $positionCmd->execCmd();
			if ($position == "") {
				$position = 0;
			}
			log::add("emulStore","debug","Position: " . $position);
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
		}
		return 1;
	}

    /*     * **********************Getteur Setteur*************************** */
}


