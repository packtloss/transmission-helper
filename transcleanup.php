#!/usr/bin/php -q
<?php
// Config Files
require_once('/home/debian-transmission/.code/includes/config.inc.php');
require_once('/home/debian-transmission/.code/includes/functions.general.inc.php');
require_once('/home/debian-transmission/.code/includes/functions.cli.inc.php');
require_once('/home/debian-transmission/.code/includes/class/TransmissionRPC.class.php');
$rpc = new TransmissionRPC();
$rpc->username = $transmissionUser;
$rpc->password = $transmissionPass;
$rpc->url = $transmissionUrl;
//$rpc->debug = true;
$result = $rpc->get();
$logData = "";
$torrentTotal=0;
$removed=0;
foreach($result->arguments->torrents as $torrentData) {
	//"id", "name", "status", "doneDate", "haveValid", "totalSize", "percentDone", "hashString", "rateUpload","rateDownload", "uploadRatio", "peersConnected"
	$timeNow = date('U');
	$timeDone = $torrentData->doneDate;
	$torrentId = $torrentData->id;
	$torrentAge = formatSeconds($timeNow - $timeDone);
	$torrentName = $torrentData->name;
	$torrentBytes = $torrentData->totalSize;
	$torrentSize = formatBytes($torrentBytes);
	$torrentTotal = $torrentTotal+$torrentBytes;
	if($torrentAge['days'] >$maxTorrentAge || $torrentData->status == 16) {
		$logData .= "[".date('M n g:i:sa')."][CLEANUP][".$torrentName."]";
		if($torrentAge['days'] >$maxTorrentAge) { $logData .= "[AGE".$torrentAge['days'].">".$maxTorrentAge."]"; }
		if($torrentData->status == 16) { $logData .= "[STOPPED]"; }
		$removeResult = $rpc->remove( $torrentId, true );
		$removed+1;
		$logData .= "[Torrent:".strtoupper($removeResult->result)."]";
		$logData .= "[T:".$torrentSize."]";
		$extractedDir = $unrarDir."/".$torrentName;
		if(!empty($torrentName)) {
			// See if there's an extract dir...
			if(!is_link($extractedDir) && !is_dir($extractedDir)) { $logData .= "[EX:NODATA]"; }
			// Verify its not a symlink and is a dir...
			if(!is_link($extractedDir) && is_dir($extractedDir)) {
				$extractSize = formatBytes(disk_total_space($extractedDir));
				rrmdir($extractedDir);
				if(!is_dir($extractedDir)) { $logData .= "[EX:SUCCESS]"; } else { $logData .= "[EX:ERROR]"; }
			}
			if(is_link($extractedDir)) {
				unlink($extractedDir);
				if(!is_link($extractedDir)) { $logData .= "[SYM:SUCCESS]"; } else { $logData .= "[SYM:ERROR]"; }
			}
		}
		if(!empty($extractSize)) { $logData .= "[EX:".$extractSize."][".$extractedDir."]";  }
		$logData .= "\n";
	}
}
$logData .= "[".date('M n g:i:sa')."][CLEANUP][Queue Total:".formatBytes($torrentTotal)."][Removed: ".$removed."]\n";
echo $logData;
file_put_contents($logFile, $logData, FILE_APPEND | LOCK_EX);
?>
