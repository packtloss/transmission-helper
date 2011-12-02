<?

// Maximum age in days, before a torrent is deleted.
$maxTorrentAge = 30;

// Mount point data is downloaded to
$downloadDisk = "/";

// Directory that scripts/includes/configs can be found in
$baseDirectory = "/var/transmission/helper";

// Place to make symlinks, extract files etc
$unrarDir = "/var/transmission/extracted";

// If these are not in /usr/bin/ - symlink them
$unrarBinary = "/usr/bin/unrar";
$mencoderBinary = "/usr/bin/mencoder";

//Attempt to join avi files named cd1,cd2? 0 = no, 1 = yes
$JOIN_AVI = 1;

// Transmission Auth info...
$transmissionUser = "";
$transmissionPass = "";
$transmissionUrl = "http://localhost:9091/transmission/rpc";

// Log File
$logFile = "/var/transmission/helper/log/log.txt";


// -- RSS Hunter stuff -- 
// Config Files - Need to be web read/writable, and readable by whoever execs the rss hunter.
$allowedShows = "/var/transmission/helper/includes/file.types.inc.php";
$allowedCategories = "/var/transmission/helper/includes/file.categories.inc.php";
$ignoredStrings = "/var/transmission/helper/includes/file.ignore.inc.php";

// Percentage of disk to keep free. Will abort RSS hunter if free space is lower than this value
$minimumDiskSpace = 30;

// Sometimes the rpc gives an empty list, even if there are running torrents - this aborts the rss parser if the list is unavailable.
// Script will attempt up to 3 retries before giving up.
// With this disabled, the dupe checker doesnt run.
$abortRssParser = 1;

// If a torrent with the matching name is already running, this prevents an attempt to re-insert it.
// You might want this disabled, if you want to add 2 torrents from 2 trackers with the same data, for example.
$preventRssDupes = 1;


?>
