<?

function rrmdir($dir) {
        if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                        if ($object != "." && $object != "..") {
                                if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
                                }
                        }
                reset($objects);
                rmdir($dir);
        }
}

function formatBytes($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, 2).$units[$i];
}

function diskInfo ($drive,$readable=1) {
        $diskUsed = disk_total_space($drive) - disk_free_space($drive);
        $diskFree = disk_free_space($drive);
        $diskTotal = disk_total_space($drive);
        $freePercent = $diskFree ? round($diskFree / $diskTotal, 2) * 100 : 0;

        $diskInfo['used'] = $diskUsed;
        $diskInfo['free'] = $diskFree;
        $diskInfo['total'] = $diskTotal;
        $diskInfo['percentfree'] = $freePercent;
        $diskInfo['percentused'] = 100-$freePercent;

        if($readable==1) {
                $diskInfo['used'] = formatBytes($diskUsed);
                $diskInfo['free'] = formatBytes($diskFree);
                $diskInfo['total'] = formatBytes($diskTotal);
                $diskInfo['percentfree'] = $freePercent."%";
                $diskInfo['percentused'] = 100-$freePercent."%";
        }

        return($diskInfo);
}

function joinAvi($dir,$outFile) {
        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;
        $aviFiles = findFilesRegex($dir,'.*cd[1-9]\.avi');
        if(!empty($aviFiles[0])) {
                $arg = "/usr/bin/mencoder -oac copy -ovc copy ";
                foreach ($aviFiles as $aviFile) {
                        $arg .= $aviFile." ";
                }
                $arg .= "-o ".$outFile." -quiet";
                shell_exec($arg);
                $mtime = microtime();
                $mtime = explode(" ",$mtime);
                $mtime = $mtime[1] + $mtime[0];
                $endtime = $mtime;
                $totaltime = ($endtime - $starttime);
                $output = "[JOINED(".substr($totaltime,0,5)."s)]";
        } else {
                $output = "[NOJOIN]";
        }
        return $output;
}

function getRarFile($files) {
        $file = getFileByPattern('part01', $files);
        if(empty($file)) {
                $file = getFileByPattern('.rar', $files);
                return $file;
        } else {
                return $file;
        }
}

function getFileByPattern($pattern, $files) {
        foreach ($files as $fileNum => $fileName) {
                if(strpos($fileName, $pattern)!==false){
                        return $fileName;
                }
        }
        return false;
}

function findFilesRegex($dir, $pattern){
        $dir = escapeshellarg($dir);
        $files = shell_exec("find $dir -regex '$pattern' -print | sort");
        $files = explode("\n", trim($files));
        return $files;
}

function findFiles($dir, $pattern, $subs=0){
        $dir = escapeshellarg($dir);
        if($subs == 1) {
                $files = shell_exec("find $dir -name '$pattern' -print");
        } else {
                $files = shell_exec("find $dir -iname '$pattern' ! -iname '*subs*' -print");
        }
        $files = explode("\n", trim($files));
        return $files;
}

function formatSeconds($time) {
        $out['seconds'] = $time%60;
        $out['mins'] = floor($time/60)%60;
        $out['hours'] = floor($time/60/60)%24;
        $out['days'] = floor($time/60/60/24);
        return $out;
}


?>
