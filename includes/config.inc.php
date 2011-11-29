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

// Log File
$logFile = "/var/transmission/helper/log/log.txt";


// -- RSS Hunter stuff -- 
// Config Files - Need to be web read/writable, and readable by whoever execs the rss hunter.
$allowedShows = "/var/transmission/helper/includes/file.types.inc.php";
$allowedCategories = "/var/transmission/helper/includes/file.categories.inc.php";
$ignoredStrings = "/var/transmission/helper/includes/file.ignore.inc.php";

// XML url/path - for rss hunter.
$xmlPath = "http://some/feed.rss";

// Percentage of disk to keep free. Will abort RSS hunter if free space is lower than this value
$minimumDiskSpace = 30;


?>
