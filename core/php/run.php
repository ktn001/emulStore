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
 * Calcul de l'heure de fin d'ouverture ou fermeture
 */
$cmdAction = $cmd->getLogicalId();
$positionCmd=cmd::byEqLogicIdAndLogicalId($emulateur->getId(),'position');
$position=$positionCmd->execCmd();
switch ($cmdAction){
case 'ouvre':
	$cheminRestant = 100 - $position;
	$tempsCourseComplete = (int)$emulateur->getConfiguration('TempsOuverture');
	$timeToOpen = $cheminRestant / 100 * $tempsCourseComplete;
	$heureFin = time() + $timeToOpen;
	break;
case 'ferme':
	$cheminRestant = $position;
	$tempsCourseComplete = (int)$emulateur->getConfiguration('TempsFermeture');
	$timeToClose = $cheminRestant / 100 * $tempsCourseComplete;
	$heureFin = time() + $timeToClose;
	break;
default:
	_log("error",sprintf(__("Action %s inconnue", __FILE__), $cmdAction));
	exit(1);
}

_log("debug",__("Position de départ: ",__FILE__) . $position);
_log("debug",sprintf(__("Heure de fin: %d (dans %d secondes)",__FILE__), $heureFin, $heureFin - time()));

$dernierePosition=position;
while ($cheminRestant > 0){
	if ($emulateur->getActionEnCours() != $cmd->getLogicalId()){
		exit(0);
	}
	$tempsRestant = $heureFin - time();
	switch ($cmdAction){
	case 'ouvre':
		$newPosition = round(100 - ($tempsRestant / $tempsCourseComplete * 100));
		if ($newPosition > 100){
			$newPosition = 100;
		}
		$cheminRestant = 100 - $newPosition;
		break;
	case 'ferme':
		$newPosition = round($tempsRestant / $tempsCourseComplete * 100);
		if ($newPosition < 0){
			$newPosition = 0;
		}
		$cheminRestant = $newPosition;
		break;
	}
	_log("debug",__("Nouvelle position: ",__FILE__) . $newPosition);
	if ($newPosition != $dernierePosition) {
		$emulateur->checkAndUpdateCmd($positionCmd,$newPosition);
		$dernierePosition = $newPosition;
	}
	usleep(200000);
}	
if ($emulateur->getActionEnCours() != $cmd->getLogicalId()){
	$emulateur->setActionEnCours("");
}
exit (0);
