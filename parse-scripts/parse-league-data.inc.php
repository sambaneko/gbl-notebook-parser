<?php

function parseLeagueData($jsonObj, $langLines, $appends) {
	$data = [
		'templateId' => $jsonObj->templateId,
		'value' => $jsonObj->templateId,
		'unique' => false
	];
	$conditions = $jsonObj->data->combatLeague->pokemonCondition;

	foreach ($conditions as $cond) {
		if (isset($cond->pokemonCaughtTimestamp)) {
			return false; // no catch cups
		}

		if (isset($cond->withPokemonCpLimit)) {
			$data['maxCp'] = $cond->withPokemonCpLimit->maxCp;
		}
	
		if (isset($cond->withPokemonType)) {
			$data['allowedTypes'] = $cond->withPokemonType->pokemonType;
		}	
	
		if (isset($cond->pokemonWhiteList)) {
			$data['whiteList'] = _parseList($cond->pokemonWhiteList->pokemon);
		}		
	
		if (isset($cond->pokemonBanList)) {
			$data['banList'] = _parseList($cond->pokemonBanList->pokemon);
		}

		if (
			$cond->type == "WITH_UNIQUE_POKEMON" ||
			// default GL, UL and ML are unique, but not marked as such
			str_contains($jsonObj->templateId, 'DEFAULT')
		) {
			$data['unique'] = true;
		}
	}

	if (isset($jsonObj->data->combatLeague->bannedPokemon)) {
		if (!isset($data['banList'])) {
			$data['banList'] = [];
		}

		$data['banList'] = array_merge(
			$data['banList'],
			$jsonObj->data->combatLeague->bannedPokemon
		);
	}

	$data['label'] = $jsonObj->data->combatLeague->title;
	$data['label'] = isset($langLines[$data['label']])
		? $langLines[$data['label']]
		: $data['label'];

	$data['slug'] = str_replace(
		[' ', ':'], ['-', ''], strtolower($data['label'])
	);

	if (isset($appends[$jsonObj->templateId])) {
		$data = array_merge_recursive(
			$data, $appends[$jsonObj->templateId]
		);
	}	

	return $data;
}

function _parseList($inList) {
	$outList = [];
	foreach ($inList as $listItem) {
		$listItemString = $listItem->id;

		if (isset($listItem->forms)) {
			foreach ($listItem->forms as $form) {
				// DKW: there are now ints in this list, which refer
				// to NORMAL forms
				if (is_numeric($form)) {
					$formName = 'NORMAL';
				} else {
					$formSplit = explode('_', $form);
					$formName = $formSplit[1];
				}

				if ($formName != 'UNSET') {
					$outList[] = "$listItemString, $formName";
				}
			}
		} else {
			// if it's an INT, refers to a pokemon
			$outList[] = $listItemString;
		}		
	}
	return $outList;
}