<?php
// Classes and libraries for module system
//
// webtrees: Web based Family History software
// Copyright (C) 2015 Łukasz Wileński.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
namespace Wooc\WebtreesAddon\WoocMapaNazwiskModule;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleTabInterface;

class WoocMapaNazwiskModule extends AbstractModule implements ModuleTabInterface {

	public function __construct() {
		parent::__construct('wooc_mapa_nazwisk');
	}

	// Extend Module
	public function getTitle() {
		return I18N::translate('Wooc Surname Map');
	}

	public function getTabTitle() {
		return /* I18N: Title used in the tab panel */ I18N::translate('Surname map');
	}

	// Extend Module
	public function getDescription() {
		return I18N::translate('Displays information about current individual name\'s locations in Poland.');
	}

	// Extend class Module
	public function defaultAccessLevel() {
		return Auth::PRIV_USER;
	}

	// Implement Module_Tab
	public function defaultTabOrder() {
		return 75;
	}

	// Implement Module_Tab
	public function getTabContent() {
		global $controller, $WT_TREE;

		$person = Individual::getInstance($controller->record->getXref(), $WT_TREE);
		if (!$person) return '';
		$mapfile = WT_MODULES_DIR.$this->getName().'/Polska.htm';
		if (file_exists($mapfile)) {
			include ($mapfile);
		}
		$html='<table><tr><td>';
		$person_all_names = $person->getAllNames();
		if (count($person_all_names)==0) {
			$html='<table class="facts_table"><tr><td id="no_tab8" colspan="2" class="facts_value">'.
					I18N::translate('There are no information about name\'s locations in Poland for this individual.').
					'</td></tr></table>';
		} else {
			$surname = array();
			foreach ($person_all_names as $name) {
				$surnameexpl = explode('-', $name['surname']);
				if (isset($surnameexpl[1])) {
					$name['surname'] = $surnameexpl[1];
				} else {
					$name['surname'] = $surnameexpl[0];
				}
				if (!empty($name['surname']) && !in_array($name['surname'], $surname) && ($name['surname']!='@N.N.')) {
					$urlsurname = str_replace('A','B',str_replace('E','C',urlencode(urlencode(strtolower(str_replace('Ł','ł',$name['surname']))))));
					$urlfile = 'http://s3.amazonaws.com/12XN8SEM7ZEYVXRQQ702-maps-pl/'.$urlsurname.'_kompletny.png';
					if ($this->checkmap($urlfile)) {
						$html.='<table class="facts_table">
								<tr><td class="facts_label" style="text-align: center;"><b>'.
								$name['surname'].'</b></td></tr></table>
								<table class="facts_table">
								<tr><td class="facts_value" style="text-align: center; width: 49%;"><b>'.
								I18N::translate('Complete').'</b></td>
								<td class="facts_value" style="text-align: center; width: 49%;"><b>'.
								I18N::translate('Relative').'</b></td></tr>
								<tr><td class="facts_value" style="background: #F0F9FB; text-align: center;">
								<img src="http://s3.amazonaws.com/12XN8SEM7ZEYVXRQQ702-maps-pl/'.
								$urlsurname.'_kompletny.png" usemap="#Polska" id="map" width="480" height="510" alt="'.
								I18N::translate('Complete').': '.$name['surname'].'">
								</td><td class="facts_value" style="background: #F0F9FB; text-align: center;">
								<img src="http://s3.amazonaws.com/12XN8SEM7ZEYVXRQQ702-maps-pl/'.
								$urlsurname.'_wzgledny.png" usemap="#Polska" id="map" width="480" height="510" alt="'.
								I18N::translate('Relative').': '.$name['surname'].'">
								</td></tr></table>';
						$strona = @file_get_contents('http://www.moikrewni.pl/mapa/kompletny/'.$urlsurname.'.html');
						preg_match("/<div class=\"statistics\">([^`]*?)<\/div>/", $strona, $wynik);
						if (isset($wynik[1])) {
							$html.= '<div class="facts_value" style="margin: 2px; width: 98%;">'.$wynik[1].'</div>';
						}
					}
					$surname[] = $name['surname'];
				}
			}
		}
		$html.='<table class="facts_table"><tr><td class="facts_label" style="text-align: center;"><b>2002</b></td></tr></table>
			</td></tr></table>';
		return '<div id="'.$this->getName().'_content" style="overflow:auto;">'.$html.'</div>';
	}

	static function checkmap($url='') {
		$fexist = false;
		$cfg = array (
			$url => array (
						$exist               = true,
						$default_prefix      = '',
					),
				);
		if (isset($url) and !empty($url)) {
			$url = htmlspecialchars(stripslashes($url));
			if (true == $cfg[$url][$exist])
				(!@FOPEN($url, 'r')) ? $fexist = false : $fexist = true;
			else $fexist = true;
		}
		return $fexist;
	}

	// Implement Module_Tab
	public function hasTabContent() {
		return true;
	}

	// Implement Module_Tab
	public function isGrayedOut() {
		return false;
	}

	// Implement Module_Tab
	public function canLoadAjax() {
		return true;
	}

	// Implement Module_Tab
	public function getPreLoadContent() {
		global $controller;

		$controller->addInlineJavascript('jQuery("a[href$=' . $this->getName() . ']").text("' . $this->getTabTitle() . '");');
	}
	
	// Implement Module_Tab
	public function getJSCallback() {
		return '';
	}
}

return new WoocMapaNazwiskModule;