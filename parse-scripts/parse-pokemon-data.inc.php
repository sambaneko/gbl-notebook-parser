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

	$label = 'pokemon_name_' . $dexNumber;
	$label = isset($langLines[$label]) ? $langLines[$label] : $label;

	if (isset($stg->form)) {
		$form = $stg->form;
		$shortForm = substr($form, strlen($stg->pokemonId) + 1);
		
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
	
	return $data;
}