<?php

function parsePokemonData($jsonObj, $langLines, $appends) {
	$dexNumber = substr($jsonObj->templateId, 1, 4); // keep string

	$stg = $jsonObj->data->pokemonSettings;

	$fastMoves = array_merge(
		isset($stg->quickMoves) ? $stg->quickMoves : [],
		isset($stg->eliteQuickMove) ? $stg->eliteQuickMove : []
	);

	$chargeMoves = array_merge(
		isset($stg->cinematicMoves) ? $stg->cinematicMoves : [],
		isset($stg->eliteCinematicMove) ? $stg->eliteCinematicMove : []
	);

	$types = [$stg->type];
	if (isset($stg->type2)) {
		$types[] = $stg->type2;
	}

	// derive the pokemon name from the templateId;
	// this will be relevant below...
	$pos = strpos($jsonObj->templateId, 'POKEMON_') + 8;
	$_pos = strpos($jsonObj->templateId, '_', $pos);
	$determinedPokemonId = $_pos !== false 
		? substr($jsonObj->templateId, $pos, $_pos - $pos)
		: substr($jsonObj->templateId, $pos);

	$label = 'pokemon_name_' . $dexNumber;
	$label = isset($langLines[$label])
		? $langLines[$label]
		: ucwords(strtolower($determinedPokemonId));

	// DKW: some pokemonId are now INTs; normalize them
	if (is_numeric($stg->pokemonId))
		$stg->pokemonId = $determinedPokemonId;

	if (isset($stg->form)) {
		// DKW: also some form names are INTs now; they're NORMAL
		if (is_numeric($stg->form)) {
			$form = "{$stg->pokemonId}_NORMAL";
			$shortForm = 'NORMAL';
		} else {
			$form = $stg->form;
			$shortForm = substr($form, strlen($stg->pokemonId) + 1);
		}
		
		if ($shortForm != 'NORMAL') {
			$formLang = 'form_' . strtolower($form);
			if (isset($langLines[$formLang])) {
				$label .= " ({$langLines[$formLang]})";
			} else {
				$label .= " ($shortForm)";
			}
		}
	}

	$dexNumber = (int)$dexNumber;

	$data = [
		'value' => $jsonObj->templateId,
		'templateId' => $jsonObj->templateId,
		'pokemonId' => $stg->pokemonId,
		'dexNumber' => $dexNumber,
		'shadowAvailable' => isset($stg->shadow)
	];

	if (isset($form)) {
		$data['form'] = $form;
		$data['shortForm'] = $shortForm;
	}

	$data = array_merge(
		$data, compact(
			'label', 'types', 'fastMoves', 'chargeMoves'
		)
	);

	if (isset($appends[$jsonObj->templateId])) {
		$data = array_merge_recursive(
			$data, $appends[$jsonObj->templateId]
		);
	}

	// clean up if our appends duplicated anything
	$data['fastMoves'] = array_unique($data['fastMoves']);
	$data['chargeMoves'] = array_unique($data['chargeMoves']);
	
	return $data;
}