<?
function arginput($opts) {
        // expects $opts = getopt('hd');
        // Defaults
        $output['level'] = 0;
        $output['help'] = 0;
        foreach (array_keys($opts) as $opt) {
                switch ($opt) {
                        case 'd':
				$output['level'] = $opts['d'];
                                break;
                        case 'h':
                                $output['help'] = 1;
                                break;
                        default:
                                $output['level'] = 0;
                                $output['help'] = 0;
                }
        }

        return $output;
}

function climsg($input,$level=0,$log=0) {
        switch($level) {
                case 1:
                echo $input."\n";
        }
	if($log != "0") {
		file_put_contents($log, $input."\n", FILE_APPEND | LOCK_EX);
	}
}


function msg($input,$runlevel=0,$msglevel=1,$log=0) {
	if($runlevel >= $msglevel) {
		echo $input."\n";
	}
        if($log != "0") {
                file_put_contents($log, $input."\n", FILE_APPEND | LOCK_EX);
        }
}

?>
