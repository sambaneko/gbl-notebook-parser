<?php
require('parse-scripts/parse-language.inc.php');
require('parse-scripts/parse-pokemon-data.inc.php');
require('parse-scripts/parse-pokemon-forms.inc.php');
require('parse-scripts/parse-league-data.inc.php');
require('parse-scripts/parse-move-data.inc.php');

if (!in_array('parse-only', $argv)) {
	include('parse-scripts/fetch-files.php');
}

$latestJsonFile = 'pokeminers/latest.json';
$languageFile = 'pokeminers/languages/English.txt';
$outputPath = 'game-data';

if (!file_exists($latestJsonFile))
	exit("Game master file is missing\n");

if (!file_exists($languageFile)) 
	exit("Language file is missing\n");

if (!is_dir($outputPath)) {
	if (!@mkdir($outputPath, 0775, true) && !is_dir($outputPath))
		exit("Failed to create output directory: $outputPath\n");
}

$langLines = parseLanguage($languageFile);
$latestJson = json_decode(
	file_get_contents($latestJsonFile)
);

$appends = [
	'pokemon'	=> [],
	'moves'		=> [],
	'leagues'	=> []
];

foreach (array_keys($appends) as $appendName) {
	$appendFile = __DIR__ . "/append/{$appendName}.json";
	if (file_exists($appendFile)) {
		$appends[$appendName] = json_decode(
			file_get_contents($appendFile), true
		);
	}
}

$output = [
	'leagues'	=> [],
	'pokemon'	=> [],
	'moves'		=> []
];

$forms = [];

echo "Parsing files...\n";

foreach ($latestJson as $jsonObj) {
	if (isset($jsonObj->data->pokemonSettings)) {
		$pokemon = parsePokemonData(
			$jsonObj, $langLines, $appends['pokemon']
		);
		if ($pokemon !== false) {
			$output['pokemon'][] = $pokemon;
		}
	}

	if (isset($jsonObj->data->formSettings)) {
		$forms = collectForms($forms, $jsonObj->data->formSettings);
	}	

	if (isset($jsonObj->data->combatLeague)) {
		$move = parseLeagueData(
			$jsonObj, $langLines, $appends['leagues']
		);
		if ($move !== false) {
			$output['leagues'][] = $move;
		}
	}

	if (isset($jsonObj->data->combatMove)) {
		$move = parseMoveData(
			$jsonObj, $langLines, $appends['moves']
		);
		if (!is_null($move)) 
			$output['moves'][$move['index']] = $move;
	}	
}

$output['pokemon'] = parsePokemonForms($forms, $output['pokemon'], []);
$output['pokemon'] = fixNumericMoves($output['pokemon'], $output['moves']);

// move indexing was temporary; remove from final
$output['moves'] = array_values($output['moves']);

foreach ($output as $name => $data) {
	file_put_contents("{$outputPath}/{$name}.json", json_encode($data, JSON_PRETTY_PRINT));
}

echo "Done\n";