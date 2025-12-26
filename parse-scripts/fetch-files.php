<?php
$files = [
	[
		'name'		=> 'game master',
		'owner'		=> 'PokeMiners',
		'repo'		=> 'game_masters',
		'branch'	=> 'master',
		'repoPath'	=> 'latest/latest.json',
		'localFile'	=> 'pokeminers/latest.json'
	], [
		'name'		=> 'language',
		'owner'		=> 'PokeMiners',
		'repo'		=> 'pogo_assets',
		'branch'	=> 'master',
		'repoPath'	=> 'Texts/Latest%20APK/English.txt', // note encoded whitespace
		'localFile'	=> 'pokeminers/languages/English.txt'
	]
];

$apiBase   = 'https://api.github.com';

function github_api_get($url) {
    $headers = [
        'User-Agent: php-file-sync',
        'Accept: application/vnd.github+json',
    ];

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => implode("\r\n", $headers),
            'timeout' => 30,
        ]
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) return null;

    $data = json_decode($json, true);
    if (!is_array($data)) return null;

    return $data;
}

function download_remote_file($url, $localPath) {
    $dir = dirname($localPath);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            echo "Failed to create directory: $dir\n";
            return false;
        }
    }

    $tmp = @tempnam(sys_get_temp_dir(), 'ghdl');
    if ($tmp === false) {
        echo "Failed to create temp file in " . sys_get_temp_dir() . "\n";
        return false;
    }

    $handle = @fopen($tmp, 'w');
    if ($handle === false) {
        echo "Failed to open temp file for writing: $tmp\n";
        @unlink($tmp);
        return false;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout'         => 30,
            'follow_location' => true,
        ]
    ]);

    $in = @fopen($url, 'rb', false, $ctx);
    if ($in === false) {
        echo "Failed to open remote URL.\n";
        $error = error_get_last();
        if ($error) echo "Error: " . $error['message'] . "\n";
        fclose($handle);
        @unlink($tmp);
        return false;
    }

    while (!feof($in)) {
        $data = fread($in, 8192);
        if ($data === false) break;
        fwrite($handle, $data);
    }

    fclose($in);
    fclose($handle);

    if (@rename($tmp, $localPath)) return true;

    @unlink($tmp);
    return false;
}


foreach ($files as $file) {
	echo "Checking {$file['name']} file...\n";

	$commitsUrl = sprintf(
		'%s/repos/%s/%s/commits?path=%s&sha=%s&per_page=1',
		$apiBase,
		$file['owner'],
		$file['repo'],
		$file['repoPath'],
		$file['branch']
	);

	$remoteRawUrl = sprintf(
		'https://raw.githubusercontent.com/%s/%s/%s/%s',
		$file['owner'],
		$file['repo'],
		$file['branch'],
		$file['repoPath']
	);

	$localShaFile = $file['localFile'] . '.sha';

	$commits = github_api_get($commitsUrl);

	if (
		$commits === null || 
		count($commits) === 0
	) {
		if (!file_exists($file['localFile'])) {
			if (download_remote_file($remoteRawUrl, $file['localFile'])) {
				echo "Downloaded (no commit info): {$file['localFile']}\n";
			} else {
				echo "Failed to download (no commit info): $remoteRawUrl\n";
			}
		} else {
			echo "Local file exists; no commit info from API\n";
		}
		exit;
	}

	$latestCommit = $commits[0];
	$remoteSha = isset($latestCommit['sha']) ? $latestCommit['sha'] : null;

	if ($remoteSha === null) {
		echo "Could not determine remote commit SHA\n";
		exit;
	}

	$localSha = null;
	if (file_exists($localShaFile)) {
		$localSha = trim(@file_get_contents($localShaFile));
	}

	$localExists = file_exists($file['localFile']);
	$needsDownload = false;

	if (
		!$localExists ||
		$localSha === '' || 
		$localSha !== $remoteSha
	) {
		$needsDownload = true;
	}

	if ($needsDownload) {
		$success = download_remote_file($remoteRawUrl, $file['localFile']);
		if ($success) {
			@file_put_contents($localShaFile, $remoteSha . "\n");
			echo "Downloaded/updated: {$file['localFile']} (SHA: $remoteSha)\n";
		} else {
			echo "Failed to download: $remoteRawUrl\n";
		}
	} else {
		echo "Local file is up to date (SHA: $localSha)\n";
	}	
}
