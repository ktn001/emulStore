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

require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/../class/emulStore.class.php';

function _log ($level, $msg){
	log::add('emulStore', $level, 'run [' . getmypid() . '] ' . $msg);
}

_log("debug", __("Lancement de ",__FILE__) . __FILE__ );

/*
 *  Vérification des options de la ligne de commande
 */
$options = getopt ("i:");
if (! $options ){
	_log("error", __("option de la ligne de commande erronée",__FILE__));
	exit(1);
}
if (! array_key_exists("i", $options)){
	_log("error", __("option -i manquante",__FILE__));
	exit(1);
}
_log('debug',__('Commande ID: ',__FILE__) . $options['i']);

/*
 * Récupération de la commande
 */
$cmd = emulStoreCmd::byId($options['i']);
if (! is_object($cmd)){
	_log("error",sprintf(__("La commande %s est introuvable", __FILE__), $options['i']));
	exit(1);
}

/*
 * Récupération de l'émulateur
 */
$emulateur = emulStore::byId($cmd->getEqLogic_Id());
if (! is_object($emulateur)){
	_log("error",sprintf(__("L'équipement eqLogic %s est introuvable", __FILE__), $cmd->getEqLogic_Id()));
	exit(1);
}

_log("info",__("execution de la commande ", __FILE__) . $cmd->getHumanName());


/*
 * Initialisations
 */
$action = $cmd->getLogicalId();
$positionCmd=cmd::byEqLogicIdAndLogicalId($emulateur->getId(),'position');
$position=$positionCmd->execCmd();

/*
 * Temps de décollage en début d'ouverture
 */
if ($position == 0 && $action == 'ouvre') {
	_log('debug',__('Attente du décollage...',__FILE__));
	sleep((int) $emulateur->getConfiguration('TempsDecollage'));
	_log('debug',__('Décollé',__FILE__));
}

switch ($action){
case 'ouvre':
	$tempsCourseComplete = (int)$emulateur->getConfiguration('TempsOuverture');
	$timeToOpen = $cheminRestant / 100 * $tempsCourseComplete;
	$heureFin = microtime(true) + $timeToOpen;
	$timeForOnePerCent = $tempsCourseComplete / 10000;
	break;
case 'ferme':
	$tempsCourseComplete = (int)$emulateur->getConfiguration('TempsFermeture');
	$timeToClose = $cheminRestant / 100 * $tempsCourseComplete;
	$heureFin = microtime(true) + $timeToClose;
	$timeForOnePerCent = $tempsCourseComplete / 100000000;
	break;
default:
	_log("error",sprintf(__("Action %s inconnue", __FILE__), $cmdAction));
	exit(1);
}

_log("debug",__("Position de départ: ",__FILE__) . $position);
_log("debug",sprintf(__("Heure de fin: %d (dans %d secondes)",__FILE__), $heureFin, $heureFin - time()));

$dernierePosition=$position;
$finDeCourse = false;
while (! $finDeCourse) {
	if ($emulateur->getActionEnCours() != $action()){
		exit(0);
	}
	$tempsRestant = $heureFin - microtime(true);
	switch ($action){
	case 'ouvre':
		$newPosition = round(100 - ($tempsRestant / $tempsCourseComplete * 100));
		if ($newPosition > 100) {
			$newPosition = 100;
		}
		if ($newPosition == 100) {
			$findeCourse = true;
		}
		break;
	case 'ferme':
		$newPosition = round($tempsRestant / $tempsCourseComplete * 100);
		if ($newPosition < 0) {
			$newPosition = 0;
		};
		if ($newPosition == 0) {
			$findeCourse = true;
		}break;
	}
	_log("debug",__("Nouvelle position: ",__FILE__) . $newPosition);
	if ($newPosition != $dernierePosition) {
		$emulateur->checkAndUpdateCmd($positionCmd,$newPosition);
		$dernierePosition = $newPosition;
	}
	usleep($timeForOnePerCent);
}	
$emulateur->setActionEnCours("");
exit (0);
