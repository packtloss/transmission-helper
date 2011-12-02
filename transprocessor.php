#!/usr/bin/php -q
<?php
require_once(dirname( __FILE__ ).'/includes/config.inc.php');
require_once(dirname( __FILE__ ).'/includes/functions.general.inc.php');
// Environment Variables set by transmission - We dont use them all (yet?)
$torrentName = $_SERVER['TR_TORRENT_NAME'];
$torrentHash = $_SERVER['TR_TORRENT_HASH'];
$torrentTime = $_SERVER['TR_TIME_LOCALTIME'];
$torrentAppVersion = $_SERVER['TR_APP_VERSION'];
$torrentTorrentId = $_SERVER['TR_TORRENT_ID'];
$torrentDirectory = $_SERVER['TR_TORRENT_DIR'];
// ---
$torrentDownloadDir = $torrentDirectory."/".$torrentName;
$torrentDestination = $unrarDir."/".$torrentName;
$logData = "[".date('M n g:i:sa')."][EXTRACTOR][".$torrentName."]";
$mtime = microtime(); 
$mtime = explode(" ",$mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$starttime = $mtime;
//
$parts = findFiles($torrentDownloadDir,'*part01*',0);
if(!empty($parts[0])) {
	// There are part01 files - subs later
	foreach ($parts as $partFile) {
		$logData .= "[PART01]";
		shell_exec("/usr/bin/unrar x -y -inul ".$partFile." ".$torrentDestination."/");
	}
	// Handle subs for part01 files...
	$subs =  findFiles($torrentDownloadDir,'*subs*.rar',1);
	if(!empty($subs[0])) {
		foreach ($subs as $subRar) {
			$logData .= "[SUB]";
			shell_exec("/usr/bin/unrar x -y -inul ".$subRar." ".$torrentDestination."/");
		}
	}
} else {
	// No part01 files, look for any rar files...
	$rars = findFiles($torrentDownloadDir,'*.rar',1);
	if(!empty($rars[0])) {
		foreach ($rars as $rarFile) {
			$logData .= "[RAR]";
			shell_exec("/usr/bin/unrar x -y -inul ".$rarFile." ".$torrentDestination."/");
		}
	}
}
// Check the download directory for leftover rars - extract and delete.
if(is_dir($torrentDestination) && !is_link($torrentDestination)) {
	//echo "\nDestination is Real\n";
	$cleanupRars = findFiles($torrentDestination,'*.rar',1);
	if(!empty($cleanupRars[0])) {
		foreach ($cleanupRars as $rarFile) {
			$logData .= "[RAR-CU]";
			shell_exec("/usr/bin/unrar x -y -inul ".$rarFile." ".$torrentDestination."/");
			unlink($rarFile);
		}
	}
}
// If There's cd1.avi cd2.avi files, log them for join attempts...
$joinedName = $torrentDestination."/".$torrentName.".avi";
$logData .= joinAvi($torrentDestination,$joinedName);
// If we didnt make a directory, symlink the download directory...
if(!is_dir($torrentDestination) && !is_link($torrentDestination)) {
        $logData .= "[LNK]";
        symlink($torrentDownloadDir,$torrentDestination);
}
// End Logging
$mtime = microtime(); 
$mtime = explode(" ",$mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$endtime = $mtime; 
$totaltime = ($endtime - $starttime); 
$logData .= "[".substr($totaltime,0,5)."s]\n";
file_put_contents($logFile, $logData, FILE_APPEND | LOCK_EX);
?>

