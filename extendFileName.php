<?php

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// regex for filename verification
define('FILE_REGEX', '/(fff-[\w.]+|openwrt|franken-[\w.]+)-.*\.bin(\.md5|\.sha256)?/');

function extractBoard($filename) {
	// remove everything other than boardname
	$search = ['/(.*)-generic-/', '/(.*)-tiny-/', '/(.*)-g-/', '/(.*)-t-/'];
	$replace = '';
	$filename = preg_replace($search, $replace, $filename);

	$search = ['/-squashfs(.*)/', '/-sysupgrade(.*)/'];
	$replace = '';
	$filename = preg_replace($search, $replace, $filename);


	// rewrite changed boardnames
	$search = ['/-wr841n-/i', '/-cpe210-220-510-520-/i'];
	$replace = ['-wr841-', '-cpe210-220-'];
	$filename = preg_replace($search, $replace, $filename);

	return $filename;
}

function extractVariant($filename) {
	if (strpos($filename, 'layer3') !== false) {
		return 'layer3';
	} else {
		return 'node';
	}
}

/*
 * Extract version from release.nfo file in "current" directory for requested variant
 */
function getServerVersion($variant) {
	$releaseFile = "$variant/current/release.nfo";

	if (file_exists($releaseFile)) {
		$version = explode(':', file_get_contents($releaseFile));
		return trim($version[1]);
	} else {
		trigger_error($releaseFile . " not found. Firmware version on server unknown.", E_USER_ERROR);
	}
}

/*
 * Outputs a 404 error page
 */
function return404($filename, $reason="") {
	http_response_code(404);
	if ($reason != "") {
		$reason = "<p>Reason: $reason.</p>";
	}
         
	echo <<<EOL
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested file {$filename} was not found on this server.</p>
{$reason}
</body></html>
EOL;
	exit;
}

/*
 * Outputs the file given in arguemnt
 */
function returnFile($filename, $oldfilename) {
	$filecontent = file_get_contents($filename);

	// replace filename in sha256 and md5 sum
	if (strpos($filename, '.sha256') !== false || strpos($filename, '.md5') !== false) {
		$shasum = strtok($filecontent, ' ');
		if ($shasum === false) {
			return;
		}

		// remove .sha256 or .md5
		$filename = preg_replace(array('/\.sha256/', '/\.md5/'), array(''), $oldfilename);
		if ($filename === false) {
			return;
		}

		$filecontent = "$shasum  $filename" . "\n";
	}

	header('Content-disposition: attachment; filename=' . $oldfilename);
	header('Content-type: application/octet-stream');
	header('Content-Length: ' . strlen($filecontent));
	header("Pragma: no-cache");
	header("Expires: 0");

	echo $filecontent;
}



if ($_GET["file"] === 'release.nfo') {
	returnFile('node/current/release.nfo', 'release.nfo');
	exit;
}

$oldfilename = filter_input(INPUT_GET, 'file', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => FILE_REGEX)));
//$oldfilename = $argv[1];
if (empty($oldfilename)) {
	return404($oldfilename, "Filename doesn't match verification pattern");
}

$variant = extractVariant($oldfilename);
$board = extractBoard($oldfilename);
$version = getServerVersion($variant);

// rewrite filename
$newfilename = "$variant/current/fff-$version-$board-sysupgrade.bin";
if (!file_exists($newfilename)) {
	return404($oldfilename, "Can't find file with rewritten name: $newfilename");
}

// append checksum to filename
if (substr($oldfilename, -4) === '.md5') {
	$newfilename = $newfilename . '.md5';
} else if (substr($oldfilename, -7) === '.sha256') {
	$newfilename = $newfilename . '.sha256';
}

returnFile($newfilename, $oldfilename);
exit;
