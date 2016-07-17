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

define('FILE_RELEASE', 'release.nfo');

function getVersion() {
    if (file_exists(FILE_RELEASE)) {
        $version = explode(':', file_get_contents(FILE_RELEASE));
        return trim($version[1]);
    }
    exit;
}


static $search = ['/(franken)-(.*)$/i', '/-generic-/i', '/openwrt/i'];
$replacement = ['fff-${2}', '-g-', 'fff-'.getVersion()];

define('FILE_REGEX', '/(openwrt|franken-[\w.]+)-[\w.]+-[\w.]+-[\w.]+-[\w.]+-[\w.]+-[\w.]+(-[\w.]+)?\.bin(.md5|.sha256)?/');


/*
 * Extend Filename by replacing the given filename with the given replacements
 */

function extendFileName($origin, $search, $replacement) {
    return preg_replace($search, $replacement, $origin);
}

/*
 * Returns true if the filename ends on .md5 or .sha256
 */

function checkIfCheckFile($filename) {
    return (substr($filename, -4) === '.md5' || substr($filename, -7) === '.sha256');
}

/*
 * returns a 404error page
 */

function return404($file) {
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    echo <<<EOL
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested FILE {$file} was not found on this server.</p>
</body></html>
EOL;
    exit;
}




/*
 * first check if filename is valid and check if renamed file exist
 */
$oldfildname = filter_input(INPUT_GET, 'file', FILTER_VALIDATE_REGEXP, array(
    'options' => array('regexp' => FILE_REGEX)
        ));
if (empty($oldfildname)) {
    return404($oldfildname);
}
$newfilename = extendFileName($oldfildname, $search, $replacement);
if (!file_exists($newfilename)) {
    return404($oldfildname);
}

/*
 * read file content
 */
$filecontent = file_get_contents($newfilename);
/*
 * if it is a checksum file replace the filename in it
 */
if(checkIfCheckFile($oldfildname)) {
    $hash = explode('  ',$filecontent);
    $filecontent = $hash[0].'  '.pathinfo($oldfildname,PATHINFO_FILENAME);
}
/*
 * return file content to the user
 */
header('Content-disposition: attachment; filename=' . $oldfildname);
header('Content-type: application/octet-stream');
header('Content-Length: ' . strlen($filecontent));
header("Pragma: no-cache");
header("Expires: 0");
echo $filecontent;