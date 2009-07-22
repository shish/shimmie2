<?php
/**
 * Functions which are only in some versions of PHP,
 * or only implemented on some platforms
 *
 * \privatesection
 */

# (PHP 5 >= 5.2.1)
# Based on http://www.phpit.net/
# article/creating-zip-tar-archives-dynamically-php/2/
if(!function_exists('sys_get_temp_dir')) {
function sys_get_temp_dir() {
	// Try to get from environment variable
	if(!empty($_ENV['TMP'])) {
		return realpath($_ENV['TMP']);
	}
	else if(!empty($_ENV['TMPDIR'])) {
		return realpath($_ENV['TMPDIR']);
	}
	else if(!empty($_ENV['TEMP'])) {
		return realpath($_ENV['TEMP']);
	}

	// Detect by creating a temporary file
	else {
		// Try to use system's temporary directory
		// as random name shouldn't exist
		$temp_file = tempnam(md5(uniqid(rand(), TRUE)), '');
		if($temp_file) {
			$temp_dir = realpath(dirname($temp_file));
			unlink($temp_file);
			return $temp_dir;
		}
		else {
			return FALSE;
		}
	}
}
}

# (PHP >= 5.1)
# from http://www.php.net/inet_pton
if(!function_exists('inet_pton')) {
function inet_pton($ip) {
    # ipv4
    if(strpos($ip, '.') !== FALSE) {
        $ip = pack('N',ip2long($ip));
    }
    # ipv6
    else if(strpos($ip, ':') !== FALSE) {
        $ip = explode(':', $ip);
        $res = str_pad('', (4*(8-count($ip))), '0000', STR_PAD_LEFT);
        foreach($ip as $seg) {
            $res .= str_pad($seg, 4, '0', STR_PAD_LEFT);
        }
        $ip = pack('H'.strlen($res), $res);
    }
    return $ip;
}
}

# (PHP >= 5.1)
# from http://www.php.net/inet_ntop
if(!function_exists('inet_ntop')) {
function inet_ntop($ip) {
    if (strlen($ip)==4) {
        // ipv4
        list(,$ip)=unpack('N',$ip);
        $ip=long2ip($ip);
    } elseif(strlen($ip)==16) {
        // ipv6
        $ip=bin2hex($ip);
        $ip=substr(chunk_split($ip,4,':'),0,-1);
        $ip=explode(':',$ip);
        $res='';
        foreach($ip as $seg) {
            while($seg{0}=='0') $seg=substr($seg,1);
            if ($seg!='') {
                $res.=($res==''?'':':').$seg;
            } else {
                if (strpos($res,'::')===false) {
                    if (substr($res,-1)==':') continue;
                    $res.=':';
                    continue;
                }
                $res.=($res==''?'':':').'0';
            }
        }
        $ip=$res;
    }
    return $ip;
}
}
?>
