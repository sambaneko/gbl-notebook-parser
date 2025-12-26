<?php

function parseLeagueData($jsonObj, $langLines) {

	$moveData = [
		'templateId' => $jsonObj->templateId,
		'value' => $jsonObj->templateId
	];
	$conditions = $jsonObj->data->combatLeague->pokemonCondition;

	foreach ($conditions as $cond) {
		if (isset($cond->pokemonCaughtTimestamp)) {
			return false; // no catch cups
		}

		if (isset($cond->withPokemonCpLimit)) {
			$moveData['maxCp'] = $cond->withPokemonCpLimit->maxCp;
		}
	
		if (isset($cond->withPokemonType)) {
			$moveData['allowedTypes'] = $cond->withPokemonType->pokemonType;
		}	
	
		if (isset($cond->pokemonWhiteList)) {
			$moveData['whiteList'] = _parseList($cond->pokemonWhiteList->pokemon);
		}		
	
		if (isset($cond->pokemonBanList)) {
			$moveData['banList'] = _parseList($cond->pokemonBanList->pokemon);
		}
	}

	if (isset($jsonObj->data->combatLeague->bannedPokemon)) {
		if (!isset($moveData['banList'])) {
			$moveData['banList'] = [];
		}

		$moveData['banList'] = array_merge(
			$moveData['banList'],
			$jsonObj->data->combatLeague->bannedPokemon
		);
	}

	$moveData['label'] = $jsonObj->data->combatLeague->title;
	$moveData['label'] = isset($langLines[$moveData['label']])
		? $langLines[$moveData['label']]
		: $moveData['label'];

	$moveData['slug'] = str_replace([' ', ':'], ['-', ''], strtolower($moveData['label']));

	return $moveData;
}

function _parseList($inList) {
	$outList = [];
	foreach ($inList as $listItem) {
		$listItemString = $listItem->id;

		if (isset($listItem->forms)) {
			foreach ($listItem->forms as $form) {
				$formSplit = explode('_', $form);

				if ($formSplit[1] != 'UNSET') {
					$outList[] = $listItemString . ", {$formSplit[1]}";
				}
			}
		} else {
			$outList[] = $listItemString;
		}		
	}
	return $outList;
}