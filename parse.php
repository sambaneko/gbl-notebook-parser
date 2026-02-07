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

$output = [
	'leagues'	=> [],
	'pokemon'	=> [],
	'moves'		=> []
];

$updates = [];
$inserts = [];

function readAppendFile($path, $name) {
	$file = __DIR__ . "/append/{$path}/{$name}.json";
	return file_exists($file)
		? json_decode(file_get_contents($file), true)
		: [];
}

foreach (array_keys($output) as $dataName) {
	$updates[$dataName] = readAppendFile('updates', $dataName);
	$inserts[$dataName] = readAppendFile('inserts', $dataName);
}

$forms = [];

echo "Parsing files...\n";

foreach ($latestJson as $jsonObj) {
	if (isset($jsonObj->data->pokemonSettings)) {
		$pokemon = parsePokemonData(
			$jsonObj, $langLines, $updates['pokemon']
		);
		if ($pokemon !== false) {
			$output['pokemon'][] = $pokemon;
		}
	}

	if (isset($jsonObj->data->formSettings)) {
		$forms = collectForms(
			$forms, $jsonObj->data->formSettings, $jsonObj->templateId
		);
	}	

	if (isset($jsonObj->data->combatLeague)) {
		$move = parseLeagueData(
			$jsonObj, $langLines, $updates['leagues']
		);
		if ($move !== false) {
			$output['leagues'][] = $move;
		}
	}

	if (isset($jsonObj->data->combatMove)) {
		list($index, $move) = parseMoveData(
			$jsonObj, $langLines, $updates['moves']
		);
		// store moves by their index number to accomodate
		// fixNumericMoves below
		if (!is_null($move)) 
			$output['moves'][$index] = $move;
	}	
}

$output['pokemon'] = parsePokemonForms($forms, $output['pokemon'], []);
$output['pokemon'] = fixNumericMoves($output['pokemon'], $output['moves']);

// don't want move indexing in final output
$output['moves'] = array_values($output['moves']);

foreach ($inserts as $name => $data) {
	if (!empty($data))
		$output[$name] = array_merge($output[$name], $data);
}

foreach ($output as $name => $data) {
	file_put_contents("{$outputPath}/{$name}.json", json_encode($data, JSON_PRETTY_PRINT));
}

echo "Done\n";