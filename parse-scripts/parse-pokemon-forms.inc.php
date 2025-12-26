<?php
function collectForms($forms, $formSettings) {
	if (
		isset($formSettings->pokemon) &&
		isset($formSettings->forms)
	) {
		$monForms = [];

		foreach ($formSettings->forms as $form) {
			if (isset($form->form)) {
				$monForms[$form->form] = 
					isset($form->isCostume) && 
					$form->isCostume == true;
			}
		}

		if (!empty($monForms)) {
			$forms[$formSettings->pokemon] = $monForms;
		}
	}

	return $forms;
} 

function parsePokemonForms($forms, $pokemonData, $langLines) {
	// here we'll try to wrangle various forms, including inaccessible
	// ones, costumes, and some unique variations (particularly Pikachus)

	// these are costume forms with unique move sets that should
	// be separated out
	$special = [
		'PIKACHU' => [
			'VS_2019' => 'LIBRE',
			'ROCK_STAR' => 'ROCK STAR',
			'POP_STAR' => 'POP STAR',
			'HORIZONS' => 'HORIZONS',
			'GOFEST_2022' => 'SHAYMIN SCARF',
			'COSTUME_2020' => 'FLYING'
		]
	];

	foreach ($forms as $pokemon => $formData) {
		// $forms holds all of the possible forms,
		// keyed by the pokemon name, then by form name
		// eg. 'PIKACHU' => [ 'PIKACHU_NORMAL' => isCostume ].

		$formNames = array_keys($formData);

		// get all instances of the mon whose forms we're looking at
		$instances = array_filter(
			$pokemonData, 
			function($each) use ($pokemon) {
				return $each['pokemonId'] === $pokemon;
			}
		);

		// gets all indexes for that mon
		$indices = array_keys($instances);

		// speculation, but appears that the first form name
		// is the default
		$defaultIndex = array_keys(
			array_filter(
				$instances, 
				function($each) use ($formNames) {
					return (
						isset($each['form']) && 
						$each['form'] === $formNames[0]
					);
				}
			)
		);

		if (count($defaultIndex) != 1) {
			// this appears to occur when there's form data
			// in the game master, for a mon that's not yet
			// available in the game
			// such as Basculegion, at time of writing
			continue;
		}

		$defaultIndex = $defaultIndex[0];

		// check each index; an accessible mon has a 'form' key
		// (even if form is 'normal')
		foreach ($indices as $i) {
			if (
				!isset($pokemonData[$i]['form']) ||
				!in_array($pokemonData[$i]['form'], $formNames)
			) {
				// this prunes out forms that are not actually accessible,
				// like Shellos w/o an east/west designation				
				unset($pokemonData[$i]);
			} else if (
				substr($pokemonData[$i]['shortForm'], 0, 5) == 'COPY_'
			) {
				// this handles "clone" pokemon that are classed as
				// unique forms rather than costumes
				if (!isset($pokemonData[$defaultIndex]['costumes'])) {	
					$pokemonData[$defaultIndex]['costumes'] = [];
				}
				$pokemonData[$defaultIndex]['costumes'][] = $pokemonData[$i]['shortForm'];
				unset($pokemonData[$i]);
			} else if (
				$pokemon == 'PIKACHU' &&
				$pokemonData[$i]['shortForm'] != 'NORMAL'
			) {
				if (
					!in_array(
					   $pokemonData[$i]['shortForm'],
					   array_keys($special['PIKACHU'])
				   )
			   ) {
				   unset($pokemonData[$i]);
			   } else {
				   $pokemonData[$i]['label'] = str_replace(
					   $pokemonData[$i]['shortForm'],
					   $special['PIKACHU'][$pokemonData[$i]['shortForm']],
					   $pokemonData[$i]['label']
				   );
			   }
			} else if (
				$defaultIndex !== $i && 
				$formData[$pokemonData[$i]['form']]
			) {
				// this sets costume mons into a sub-array of the
				// default mon
				if (!isset($pokemonData[$defaultIndex]['costumes'])) {	
					$pokemonData[$defaultIndex]['costumes'] = [];
				}
				$pokemonData[$defaultIndex]['costumes'][] = $pokemonData[$i]['shortForm'];
				unset($pokemonData[$i]);
			}
		}
	}

	// the form filtering causes explicit indices; get rid of them
	return array_values($pokemonData);
}
