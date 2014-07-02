<?php

if(isset($_REQUEST['status'])){
    header('Content-Type: application/json');
    
    $cache['user'] = $cache_user;
    echo json_encode($cache);
    die();
}

if(isset($_REQUEST['clear'])){
    header('Content-Type: application/json');
    
    if($_REQUEST['clear']=='user'){
        apc_clear_cache('user');
    }else{
        apc_clear_cache('opcode');
    }
    
    
    echo json_encode(array('status'=>1,'text'=>'<span class="glyphicon glyphicon-ok-circle"></span> Cache Cleared'));
    die();
}

if(isset($_REQUEST['refresh_cache_file'])){
    header('Content-Type: application/json');
    apc_delete_file($_REQUEST['refresh_cache_file']);
    apc_compile_file($_REQUEST['refresh_cache_file']);
    
    echo json_encode(array('status'=>1,'text'=>date('c')));
    die();
}

if(isset($_REQUEST['remove_cache_file'])){
    header('Content-Type: application/json');
    if(apc_delete_file($_REQUEST['remove_cache_file'])){
        $status = 1;
    }else{
        $status = 0;
    }
    
    echo json_encode(array('status'=>$status,'text'=>date('c')));
    die();
}


if(isset($_REQUEST['refresh_cache_folder'])){
    header('Content-Type: application/json');
    apc_delete_directory($_REQUEST['refresh_cache_folder']);
    apc_add_directory($_REQUEST['refresh_cache_folder']);
    
    echo json_encode(array('status'=>1,'text'=>date('c')));
    die();
}

if(isset($_REQUEST['remove_cache_folder'])){
    header('Content-Type: application/json');
    if(apc_delete_directory($_REQUEST['remove_cache_folder'])){
        $status = 1;
    }else{
        $status = 0;
    }
    
    echo json_encode(array('status'=>$status,'text'=>date('c')));
    die();
}


if(isset($_REQUEST['add_cache_folder'])){
    header('Content-Type: application/json');
    
    $status = 0;
    
    if(is_dir($_REQUEST['add_cache_folder']) && apc_add_directory($_REQUEST['add_cache_folder'])){
        $status = 1;
    }
    
    echo json_encode(array('status'=>$status,'text'=>date('c')));
    die();
}

if(isset($_REQUEST['remove_cache_user'])){
    header('Content-Type: application/json');
    apc_delete($_REQUEST['remove_cache_user']);
    
    echo json_encode(array('status'=>1,'text'=>date('c')));
    die();
}

if(isset($_REQUEST['setvariable'])){
    
    if(isset($_REQUEST['value'])){
        apc_store($_REQUEST['setvariable'],$_REQUEST['value'],intval($_REQUEST['ttl']));
    }else{
        apc_store($_REQUEST['setvariable'],apc_fetch($_REQUEST['setvariable']),intval($_REQUEST['ttl']));
    }

    echo json_encode(array('status'=>1,'text'=>date('c')));
    die();
}


if(isset($_REQUEST['about_info'])){
    echo '<table><tbody>';
    if (defined('PROXY')) {
      $ctxt = stream_context_create( array( 'http' => array( 'proxy' => PROXY, 'request_fulluri' => True ) ) );
      $rss = @file_get_contents("http://pecl.php.net/feeds/pkg_apc.rss", False, $ctxt);
    } else {
      $rss = @file_get_contents("http://pecl.php.net/feeds/pkg_apc.rss");
    }
    if (!$rss) {
        echo '<tr class="td-last center"><td>Unable to fetch version information.</td></tr>';
    } else {
        $apcversion = phpversion('apc');

        preg_match('!<title>APC ([0-9.]+)</title>!', $rss, $match);
        echo '<tr class="tr-0 center"><td>';
        if (version_compare($apcversion, $match[1], '>=')) {
            echo '<div class="ok">You are running the latest version of APC ('.$apcversion.')</div>';
            $i = 3;
        } else {
            echo '<div class="failed">You are running an older version of APC ('.$apcversion.'), 
                newer version '.$match[1].' is available at <a href="http://pecl.php.net/package/APC/'.$match[1].'">
                http://pecl.php.net/package/APC/'.$match[1].'</a>
                </div>';
            $i = -1;
        }
        echo '</td></tr>';
        echo '<tr class="tr-0"><td><h3>Change Log:</h3><br/>';

        preg_match_all('!<(title|description)>([^<]+)</\\1>!', $rss, $match);
        next($match[2]); next($match[2]);

        while (list(,$v) = each($match[2])) {
            list(,$ver) = explode(' ', $v, 2);
            if ($i < 0 && version_compare($apcversion, $ver, '>=')) {
                break;
            } else if (!$i--) {
                break;
            }
            echo "<b><a href=\"http://pecl.php.net/package/APC/$ver\">".htmlspecialchars($v, ENT_QUOTES, 'UTF-8')."</a></b><br><blockquote>";
            echo nl2br(htmlspecialchars(current($match[2]), ENT_QUOTES, 'UTF-8'))."</blockquote>";
            next($match[2]);
        }
        echo '</td></tr>';
    }
    echo '</tbody></table>';
    die();
}








// Required lib functions

//  Add  files in a directory  to APC Cache
function apc_add_directory($dir) { 
	$result = array(); 
	$cdir 	= scandir($dir); 
	foreach ($cdir as $key => $value) { 
		if (!in_array($value,array(".",".."))) { 
			if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) { 
			   $result[$value] = apc_add_directory($dir . DIRECTORY_SEPARATOR . $value); 
			} else {
				if(strtolower(substr($value,-4))=='.php'){
					$result[] = $dir. DIRECTORY_SEPARATOR .$value;
					apc_compile_file($dir. DIRECTORY_SEPARATOR .$value);
				}
			} 
		} 
	} 
	return $result; 
}

function apc_delete_directory($dir,$cache=false){
    if($cache==false){
        $cache = $GLOBALS['cache'];
    }
    foreach($cache['cache_list'] as $list){
        if($list['type']=='file'){
            if(strcmp($dir,$list['filename'])>=0){
                apc_delete_file($list['filename']);
            }
        }
    }
    return true;
}


function displayVar($var){
	switch(gettype($var)){
		case 'array':
			print_r($var);
			break;
		case 'string':
			json_decode($var);
			if(json_last_error() == JSON_ERROR_NONE){
				print_r(json_decode($var));
			}elseif($arr = @unserialize($var)){
				print_r($arr);
			} else {
				echo $var;
			}
			break;
		default:
			echo $var;
			break;
	}
}