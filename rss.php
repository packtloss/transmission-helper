#!/usr/bin/php -q
<?php
// Config Files
require_once(dirname(__FILE__).'/includes/config.inc.php');
require_once(dirname(__FILE__).'/includes/functions.general.inc.php');
require_once(dirname(__FILE__).'/includes/functions.cli.inc.php');
require_once(dirname(__FILE__).'/includes/class/TransmissionRPC.class.php');
// --- 
$debugstatus = arginput(getopt('hd:x:'));
if(empty($debugstatus['xmlpath'])) {
	$debugstatus['help'] = 1;
	msg("\nMissing XML Path",1);
} else {
	$xmlPath = $debugstatus['xmlpath'];
}
if($debugstatus['help']==1) {
        msg("\nUsage: ".$argv[0]." -x http://some.url/feed.rss for normal (quiet) operation.",1);
        msg("Usage: ".$argv[0]." -h for this message",1);
        msg("Usage: ".$argv[0]." -d 1 -x http://some.url/feed.rss for debug 1",1);
        msg("Usage: ".$argv[0]." -d 1 -x http://some.url/feed.rss for debug 2 (verbose)",1);
	echo "\n";
        exit();
}
if($debugstatus['level']>0) {
	msg("Debug Messages Enabled (Level: ".$debugstatus['level'].")",$debugstatus['level'],1);
}
$disk = diskInfo($downloadDisk);
if($minimumDiskSpace > $disk['percentfree']) {
	msg("\nNot enough free disk space.\n",$debugstatus['level'],1);
	msg("There is only ".$disk['percentfree']." available, where you have ".$minimumDiskSpace."% configured as a minimum\n",$debugstatus['level'],1);
	msg("Disk Info: ".$disk['used']." (".$disk['percentused'].") used of ".$disk['total']." total - (".$disk['free']." (".$disk['percentfree'].") available)\n\n",$debugstatus['level'],1);
	msg("[".date('M n g:i:sa')."][HUNTING][ABORTED: Disk Space Low]",$debugstatus['level'],1,$logFile);
	exit();
}

// Setup transmission class
$rpc = new TransmissionRPC();
$rpc->username = $transmissionUser;
$rpc->password = $transmissionPass;
$rpc->url = $transmissionUrl;

//$rpc->url = "http://localhost:9091/transmission/rpc";
try 
{
        $torrentList = $rpc->get();
} catch (Exception $e) {
	msg("[".date('M n g:i:sa')."][HUNTING][ABORTED: RPC UNAVAILABLE(".$e->getMessage().")]",$debugstatus['level'],1,$logFile);
	exit();
}
// For some reason these sometimes come back empty...will sleep and try again...
if(!isset($torrentList->arguments->torrents)) {
	msg("[".date('M n g:i:sa')."][HUNTING][RPC LIST EMPTY 1]",$debugstatus['level'],1,$logFile);
	sleep(2);
	try
	{
        	$torrentList = $rpc->get();
	} catch (Exception $e) {
        	msg("[".date('M n g:i:sa')."][HUNTING][ABORTED: RPC UNAVAILABLE(".$e->getMessage().")]",$debugstatus['level'],1,$logFile);
		exit();
	}
	// If it's empty a second time, third time might be a charm....
	if(!isset($torrentList->arguments->torrents)) {
        	msg("[".date('M n g:i:sa')."][HUNTING][RPC LIST EMPTY 2]",$debugstatus['level'],1,$logFile);
        	sleep(2);
        	try
        	{
                	$torrentList = $rpc->get();
        	} catch (Exception $e) {
                	msg("[".date('M n g:i:sa')."][HUNTING][ABORTED: RPC UNAVAILABLE(".$e->getMessage().")]",$debugstatus['level'],1,$logFile);
			exit();
        	}
		// Three failues....eek.
		if(!isset($torrentList->arguments->torrents)) {
                	msg("[".date('M n g:i:sa')."][HUNTING][RPC LIST EMPTY 3]",$debugstatus['level'],1,$logFile);
			if($abortRssParser==1) {
				msg("[".date('M n g:i:sa')."][HUNTING][RPC LIST EMPTY (3 Tries)]",$debugstatus['level'],1,$logFile);
				exit();
			}

		}

	}

}

$sxml = @simplexml_load_file($xmlPath);
if($sxml ===  FALSE) {
	$logData = "[".date('M n g:i:sa')."][HUNTING]";
	$logData .= "[ABORTED: RSS Unavailable]\n";
	climsg($logData,$debugstatus['level'],1,$logFile);
	exit();
}
// Define allowed shows (case insensitive)
$fp = @fopen($allowedShows, 'r');
if ($fp) {
        $shows = explode("\n", file_get_contents($allowedShows));
}
// Define cockblocking strings
$fp = @fopen($ignoredStrings, 'r');
if ($fp) {
        $ignores = explode("\n", file_get_contents($ignoredStrings));
}
// output..
// Loop through RSS items and filter based on $formats and $shows
$removeChars = array('.','_');
$downloads = array();
$urlCount=0;
foreach ( $sxml->channel->item as $item ) {
	$torrentTitle = str_replace($removeChars, " ", $item->title);
	msg("Checking ".$torrentTitle." for matches",$debugstatus['level'],2);
	foreach ( $shows as $show ) {
		$show = trim($show);
		if(!empty($show)) {
    			if ( stristr( $torrentTitle, $show ) ) {
				msg("\tFound ". $torrentTitle." matches ".$show,$debugstatus['level'],2);
                        	$cockblocks=0;

                        	foreach ( $ignores as $cockblock ) {
					$cockblock = trim($cockblock);
					if(!empty($cockblock)) {
                                		if ( stristr( $torrentTitle, $cockblock ) ) {
							msg("\t\t\tTitle ".$torrentTitle." cockblocked by ".$cockblock,$debugstatus['level'],2);
                                        		$cockblocks++;
                                		}
					}
                        	}
				// Check against running torrents
				if(!empty($torrentList->arguments->torrents)) {
					foreach($torrentList->arguments->torrents as $runningTorrent) {
						msg("\tChecking ".$torrentTitle." and ".$item->title." for matches on ".$runningTorrent->name." ",$debugstatus['level'],3);
						if ( stristr( $item->title, $runningTorrent->name ) ) {
							if($preventRssDupes==1) {
								msg("\t\t\tTitle ".$item->title." cockblocked by running torrent ".$runningTorrent->name,$debugstatus['level'],2);
								$cockblocks++;
							} else {
								msg("\t\t\tTitle ".$item->title." NOT cockblocked by running torrent ".$runningTorrent->name." (Prevent RSS Dupes Disabled) ",$debugstatus['level'],2);
							}
						}
                                                if ( stristr( $torrentTitle, $runningTorrent->name ) && $cockblocks ==0) {
                                                        if($preventRssDupes==1) {
                                                                msg("\t\t\tTitle ".$item->title." cockblocked by running torrent ".$runningTorrent->name,$debugstatus['level'],2);
                                                                $cockblocks++;
                                                        } else {
                                                                msg("\t\t\tTitle ".$item->title." NOT cockblocked by running torrent ".$runningTorrent->name." (Prevent RSS Dupes Disabled) ",$debugstatus['level'],2);
                                                        }
                                                }
                                                if ( stristr( $item->link, $runningTorrent->name ) && $cockblocks ==0) {
                                                        if($preventRssDupes==1) {
                                                                msg("\t\t\tLink ".basename(parse_url($item->link,PHP_URL_PATH),".torrent")." link cockblocked by running torrent ".$runningTorrent->name,$debugstatus['level'],2);
                                                                $cockblocks++;
                                                        } else {
                                                                msg("\t\t\tLink ".basename(parse_url($item->link,PHP_URL_PATH),".torrent")." NOT cockblocked by running torrent ".$runningTorrent->name." (Prevent RSS Dupes Disabled) ",$debugstatus['level'],2);
                                                        }
                                                }
					}
				}
				// Make are no cockblocks
				if ($cockblocks ==0) {
					msg("\t\t\tPushing ".$torrentTitle." to download array",$debugstatus['level'],2);
                        		if ( !isset( $downloads[$show] ) ) $downloads[$show] = array();
                   			array_push( $downloads[$show], $item );
					$urlCount++;
				}
			}
    		}
  	}
}
if ( count( $downloads ) > 0 ) {
	foreach ( $downloads as $show => $episodes ) {
		foreach ( $episodes as $episode ) {
			$logData = "[".date('M n g:i:sa')."][HUNTED!]";
			$logData .= "[".$episode->title."]";
      			try {
        			$result = $rpc->add( (string) $episode->link );
        			$logData .= "[RPC:".$result->result."]";
      			} catch (Exception $e) {
				$logData .= "[RPC Caught exception: ".$e->getMessage()."]";
			}
			msg($logData,$debugstatus['level'],1,$logFile);
		}
	}
}
?>
