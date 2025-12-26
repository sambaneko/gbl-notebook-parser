<?php

function parseMoveData($jsonObj, $langLines, $appends) {
	$move = $jsonObj->data->combatMove;

	// moves w/o energyDelta appear to be max moves,
	// and are not wanted
	if (!isset($move->energyDelta)) return null;
	
	preg_match('/\d+/', $jsonObj->templateId, $matches);
	$zeroIndex = $matches[0];

	$value = $move->uniqueId;
	if (is_numeric($move->uniqueId)) {
		// uniqueId is normally a string, but in the case of Aura Wheel,
		// the game master suddenly started using ints (having used strings
		// there too, previously); so, convert them to strings following
		// uniqueId's usual pattern...

		$value = substr(
			$jsonObj->data->templateId,
			strpos($jsonObj->data->templateId, 'MOVE_') + 5
		);
	}

	$label = "move_name_{$zeroIndex}";

	if (isset($langLines[$label])) {
		$label = $langLines[$label];
	} else {
		// the move name is not specified in the language file;
		// so let's intuit the english name
		$label = ucwords(
			strtolower(
				str_replace('_', ' ', $value)
			)
		);
	}	

	$data = [
		'value' => $value,
		'type' => $move->type,
		'label' => $label,
		'energyDelta' => $move->energyDelta,
		'index' => (int)$zeroIndex
	];

	// durationTurns in the game master seems to be a 0-index?
	// add 1 for our values

	if (isset($move->durationTurns)) {
		$data['durationTurns'] = $move->durationTurns + 1;
	} else if ($move->energyDelta > 0) {
		// or if durationTurns is not set on a fast move, 
		// it's a 1 turn move
		$data['durationTurns'] = 1;
	}

	if (isset($appends[$value])) {
		$data = array_merge(
			$data, $appends[$value]
		);
	}	

	return $data;
}

// game master may now mix string move names with numeric values...
function fixNumericMoves($pokemonData, $moveData) {
	$fix = function ($moves) use ($moveData) {
		foreach ($moves as &$move) {
			if (is_numeric($move)) {
				$move = $moveData[$move]['value'];
			}
		}
		return $moves;
	};

	foreach ($pokemonData as &$pData) {
		$pData['fastMoves'] = $fix($pData['fastMoves']);
		$pData['chargeMoves'] = $fix($pData['chargeMoves']);		
	}

	return $pokemonData;
}
