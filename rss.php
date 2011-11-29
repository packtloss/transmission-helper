#!/usr/bin/php -q
<?php
// Config Files
require_once(dirname(__FILE__).'/includes/config.inc.php');
require_once(dirname(__FILE__).'/includes/functions.general.inc.php');
require_once(dirname(__FILE__).'/includes/functions.cli.inc.php');
require_once(dirname(__FILE__).'/includes/class/TransmissionRPC.class.php');


// --- 
$debugstatus = arginput(getopt('hd:'));
if($debugstatus['help']==1) {
        echo "\n\n\nUsage: ".$argv[0]." for normal (quiet) operation.\n";
        echo "Usage: ".$argv[0]." -h for this message\n";
        echo "Usage: ".$argv[0]." -d 1 for debug 1,2 etc\n";
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

$sxml = @simplexml_load_file($xmlPath);
if($sxml ===  FALSE) {
	$logData = "[".date('M n g:i:sa')."][HUNTING]";
	$logData .= "[ABORTED: RSS Unavailable]\n";
	climsg("RSS Feed Error",$debugstatus['level']);
	file_put_contents($logFile, $logData, FILE_APPEND | LOCK_EX);
	exit();
}

// Define allowed shows (case insensitive)
$fp = @fopen($allowedShows, 'r');
if ($fp) {
        $shows = explode("\n", file_get_contents($allowedShows));
}
// Define allowed categories
$fp = @fopen($allowedCategories, 'r');
if ($fp) {
        $categories = explode("\n", file_get_contents($allowedCategories));
}
// Define cockblocking strings
$fp = @fopen($ignoredStrings, 'r');
if ($fp) {
        $ignores = explode("\n", file_get_contents($ignoredStrings));
}

// output..
// Loop through RSS items and filter based on $formats and $shows
$downloads = array();
$urlCount=0;
foreach ( $sxml->channel->item as $item ) {
	msg("Checking ".$item->title." (".$item->category.") for matches",$debugstatus['level'],2);
	foreach ( $shows as $show ) {
		$show = trim($show);
		if(!empty($show)) {
    			if ( stristr( $item->title, $show ) ) {
				msg("\tFound ". $item->title." matches ".$show,$debugstatus['level'],2);
				$catmatch=0;
				foreach ( $categories as $category ) {
					$category = trim($category);
					if(!empty($category)) {
						if ( stristr( $item->category, $category ) ) {
							msg("\t\tCategory ".$item->category." matches ".$category,$debugstatus['level'],2);
							$catmatch++;
						}
					}
				}
                        	$cockblocks=0;
                        	foreach ( $ignores as $cockblock ) {
					$cockblock = trim($cockblock);
					if(!empty($cockblock)) {
                                		if ( stristr( $item->title, $cockblock ) ) {
							msg("\t\t\tTitle ".$item->title." cockblocked by ".$cockblock,$debugstatus['level'],2);
                                        		$cockblocks++;
                                		}
					}
                        	}
				// Make sure there is one acceptable category...
				if ($catmatch > 0 && $cockblocks ==0) {
					msg("\t\t\tPushing ".$item->title." to download array",$debugstatus['level'],2);
                        		if ( !isset( $downloads[$show] ) ) $downloads[$show] = array();
                   			array_push( $downloads[$show], $item );
					$urlCount++;
				}
			} 
    		}

  	}
}

if ( count( $downloads ) > 0 ) {
	// Setup transmission class
	$rpc = new TransmissionRPC();
	$rpc->username = $transmissionUser;
	$rpc->password = $transmissionPass;
	foreach ( $downloads as $show => $episodes ) {
		foreach ( $episodes as $episode ) {
			$logData = "[".date('M n g:i:sa')."][HUNTED!]";
			$logData .= "[".$episode->title." - ".$episode->category."]";
			// Add to transmission
      			try {
        			$result = $rpc->add( (string) $episode->link );
        			$logData .= "[RPC:".$result->result."]";
      			} catch (Exception $e) {
				$logData .= "[RPC Caught exception: ".$e->getMessage()."]";
			}
			msg($logData,$debugstatus['level'],1);
		}
	}

}
?>
