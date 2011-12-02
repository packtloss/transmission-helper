<?php
require_once(dirname(__FILE__).'/../includes/config.inc.php');
require_once(dirname(__FILE__).'/../includes/functions.general.inc.php');

if(!$_POST['editmode']) {
	$editMode = "view";
} else {
	$newShows = $_POST['newshows'];
	$newIgnores = $_POST['newignores'];
	$editMode = $_POST['editmode'];
}

if($editMode == "save") {
	$logData = "";
	$logData = "[".date('M n g:i:sa')."][WEBEDIT]";
	file_put_contents($allowedShows,$newShows, LOCK_EX);
	file_put_contents($ignoredStrings,$newIgnores, LOCK_EX);
	//file_put_contents($logFile, $logData, FILE_APPEND | LOCK_EX);
}

// Define allowed shows (case insensitive)
$fp = @fopen($allowedShows, 'r');
if ($fp) {
        $shows = explode("\n", file_get_contents($allowedShows));
	$shows = array_unique($shows);
}
// Define cockblocking strings
$fp = @fopen($ignoredStrings, 'r');
if ($fp) {
        $ignores = explode("\n", file_get_contents($ignoredStrings));
	$ignores = array_unique($ignores);
}
$showCount = sizeof($shows);
$ignoreCount = sizeof($ignores);

include(dirname(__FILE__).'/../includes/header-www.inc.php');
if($editMode == "save") { echo "Saved.<br />"; }

?>
Search Strings:<br />
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
<input type="hidden" name="editmode" value="save">
<textarea cols=100 rows=<?=$showCount?> name="newshows">
<?php
sort($shows);
sort($ignores);
$counter=0;
foreach($shows as $show) {
	$show = trim($show);
	$counter++;
	if($counter != $showCount) { echo $show."\n"; } else { echo $show; }
}
?>
</textarea>
<br />


ignore Strings:<br />
<textarea cols=100 rows=<?=$ignoreCount?> name="newignores">
<?php
$counter=0;
foreach($ignores as $ignore) {
	$ignore = trim($ignore);
        $counter++;
        if($counter != $ignoreCount) { echo $ignore."\n"; } else { echo $ignore; }
}
?>
</textarea>
<br />
Make sure there are no blank lines first.<br />
<input type="submit" value="     Save     ">
</form>
<?php
include(dirname(__FILE__).'/../includes/footer-www.inc.php');
?>

