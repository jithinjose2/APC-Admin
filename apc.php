<?php
error_reporting(E_ERROR);
ini_set('display_errors',1);

// include "init.php";


$VERSION='$Id$';

////////// READ OPTIONAL CONFIGURATION FILE ////////////
if (file_exists("apc.conf.php")) include("apc.conf.php");
////////////////////////////////////////////////////////

////////// BEGIN OF DEFAULT CONFIG AREA ///////////////////////////////////////////////////////////

defaults('USE_AUTHENTICATION',1);			// Use (internal) authentication - best choice if 
											// no other authentication is available
											// If set to 0:
											//  There will be no further authentication. You 
											//  will have to handle this by yourself!
											// If set to 1:
											//  You need to change ADMIN_PASSWORD to make
											//  this work!
defaults('ADMIN_USERNAME','flock'); 			// Admin Username
defaults('ADMIN_PASSWORD','flock6454');  	// Admin Password - CHANGE THIS TO ENABLE!!!

// (beckerr) I'm using a clear text password here, because I've no good idea how to let 
//           users generate a md5 or crypt password in a easy way to fill it in above

//defaults('DATE_FORMAT', "d.m.Y H:i:s");	// German
defaults('DATE_FORMAT', 'Y/m/d H:i:s'); 	// US

defaults('GRAPH_SIZE',200);					// Image size

//defaults('PROXY', 'tcp://127.0.0.1:8080');

////////// END OF DEFAULT CONFIG AREA /////////////////////////////////////////////////////////////


// "define if not defined"
function defaults($d,$v) {
	if (!defined($d)) define($d,$v); // or just @define(...)
}

// rewrite $PHP_SELF to block XSS attacks
//
$PHP_SELF= isset($_SERVER['PHP_SELF']) ? htmlentities(strip_tags($_SERVER['PHP_SELF'],''), ENT_QUOTES, 'UTF-8') : '';
$time = time();
$host = php_uname('n');
if($host) { $host = '('.$host.')'; }
if (isset($_SERVER['SERVER_ADDR'])) {
  $host .= ' ('.$_SERVER['SERVER_ADDR'].')';
}

// operation constants
define('OB_HOST_STATS',1);
define('OB_SYS_CACHE',2);
define('OB_USER_CACHE',3);
define('OB_SYS_CACHE_DIR',4);
define('OB_VERSION_CHECK',9);

// check validity of input variables
$vardom=array(
	'OB'	=> '/^\d+$/',			// operational mode switch
	'CC'	=> '/^[01]$/',			// clear cache requested
	'DU'	=> '/^.*$/',			// Delete User Key
	'SH'	=> '/^[a-z0-9]+$/',		// shared object description

	'IMG'	=> '/^[123]$/',			// image to generate
	'LO'	=> '/^1$/',				// login requested

	'COUNT'	=> '/^\d+$/',			// number of line displayed in list
	'SCOPE'	=> '/^[AD]$/',			// list view scope
	'SORT1'	=> '/^[AHSMCDTZ]$/',	// first sort key
	'SORT2'	=> '/^[DA]$/',			// second sort key
	'AGGR'	=> '/^\d+$/',			// aggregation by dir level
	'SEARCH'	=> '~^[a-zA-Z0-9/_.-]*$~'			// aggregation by dir level
);

// default cache mode
$cache_mode='opcode';

// cache scope
$scope_list=array(
	'A' => 'cache_list',
	'D' => 'deleted_list'
);

// handle POST and GET requests
if (empty($_REQUEST)) {
	if (!empty($_GET) && !empty($_POST)) {
		$_REQUEST = array_merge($_GET, $_POST);
	} else if (!empty($_GET)) {
		$_REQUEST = $_GET;
	} else if (!empty($_POST)) {
		$_REQUEST = $_POST;
	} else {
		$_REQUEST = array();
	}
}

// check parameter syntax
foreach($vardom as $var => $dom) {
	if (!isset($_REQUEST[$var])) {
		$MYREQUEST[$var]=NULL;
	} else if (!is_array($_REQUEST[$var]) && preg_match($dom.'D',$_REQUEST[$var])) {
		$MYREQUEST[$var]=$_REQUEST[$var];
	} else {
		$MYREQUEST[$var]=$_REQUEST[$var]=NULL;
	}
}

// check parameter sematics
if (empty($MYREQUEST['SCOPE'])) $MYREQUEST['SCOPE']="A";
if (empty($MYREQUEST['SORT1'])) $MYREQUEST['SORT1']="H";
if (empty($MYREQUEST['SORT2'])) $MYREQUEST['SORT2']="D";
if (empty($MYREQUEST['OB']))	$MYREQUEST['OB']=OB_HOST_STATS;
if (!isset($MYREQUEST['COUNT'])) $MYREQUEST['COUNT']=20;
if (!isset($scope_list[$MYREQUEST['SCOPE']])) $MYREQUEST['SCOPE']='A';

$MY_SELF=
	"$PHP_SELF".
	"?SCOPE=".$MYREQUEST['SCOPE'].
	"&SORT1=".$MYREQUEST['SORT1'].
	"&SORT2=".$MYREQUEST['SORT2'].
	"&COUNT=".$MYREQUEST['COUNT'];
$MY_SELF_WO_SORT=
	"$PHP_SELF".
	"?SCOPE=".$MYREQUEST['SCOPE'].
	"&COUNT=".$MYREQUEST['COUNT'];

// authentication needed?
//
if (!USE_AUTHENTICATION) {
	$AUTHENTICATED=1;
} else {
	$AUTHENTICATED=0;
	if (ADMIN_PASSWORD!='password' && ($MYREQUEST['LO'] == 1 || isset($_SERVER['PHP_AUTH_USER']))) {

		if (!isset($_SERVER['PHP_AUTH_USER']) ||
			!isset($_SERVER['PHP_AUTH_PW']) ||
			$_SERVER['PHP_AUTH_USER'] != ADMIN_USERNAME ||
			$_SERVER['PHP_AUTH_PW'] != ADMIN_PASSWORD) {
			Header("WWW-Authenticate: Basic realm=\"APC Login\"");
			Header("HTTP/1.0 401 Unauthorized");

			echo <<<EOB
				<html><body>
				<h1>Rejected!</h1>
				<big>Wrong Username or Password!</big><br/>&nbsp;<br/>&nbsp;
				<big><a href='$PHP_SELF?OB={$MYREQUEST['OB']}'>Continue...</a></big>
				</body></html>
EOB;
			exit;
			
		} else {
			$AUTHENTICATED=1;
		}
	}
}
	
// select cache mode
if ($AUTHENTICATED && $MYREQUEST['OB'] == OB_USER_CACHE) {
	$cache_mode='user';
}
// clear cache
if ($AUTHENTICATED && isset($MYREQUEST['CC']) && $MYREQUEST['CC']) {
	apc_clear_cache($cache_mode);
}

if ($AUTHENTICATED && !empty($MYREQUEST['DU'])) {
	apc_delete($MYREQUEST['DU']);
}

if(!function_exists('apc_cache_info') || !($cache=@apc_cache_info($cache_mode))) {
	echo "No cache info available.  APC does not appear to be running.";
  exit;
}


if(isset($cache['nmisses'])){
	$cache['num_misses'] = $cache['nmisses'];
}


$cache_user = apc_cache_info('user');

// INFO FORMAT UPGRADE, IF NEEDED //

if(!isset($cache_user['num_hits'])){
	$cache_user['num_hits'] = $cache_user['nhits'];
}

if(!isset($cache_user['num_misses'])){
	$cache_user['num_misses'] = $cache_user['nmisses'];
}
if(!isset($cache['start_time'])){
	$cache['start_time'] = $cache['stime'];
}

foreach($cache_user['cache_list'] as $key=>$row){
	if(!isset($row['type'])) $row['type'] = 'user';
	if(!isset($row['info'])) $row['info'] = $row['key'];
	if(!isset($row['num_hits'])) $row['num_hits'] = $row['nhits'];
	if(!isset($row['creation_time'])) $row['creation_time'] = $row['ctime'];
	if(!isset($row['access_time'])) $row['access_time'] = $row['atime'];
	if(!isset($row['num_hits'])) $row['num_hits'] = $row['nhits'];
	if(!isset($row['num_hits'])) $row['num_hits'] = $row['nhits'];
	
	$cache_user['cache_list'][$key] = $row;
}

// INFO FORMAT UPGRADE,COMPLETE //



$mem=apc_sma_info();
if(!$cache['num_hits']) { $cache['num_hits']=1; $time++; }  // Avoid division by 0 errors on a cache clear

// don't cache this page
//
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                                    // HTTP/1.0

function duration($ts) {
    global $time;
    $years = (int)((($time - $ts)/(7*86400))/52.177457);
    $rem = (int)(($time-$ts)-($years * 52.177457 * 7 * 86400));
    $weeks = (int)(($rem)/(7*86400));
    $days = (int)(($rem)/86400) - $weeks*7;
    $hours = (int)(($rem)/3600) - $days*24 - $weeks*7*24;
    $mins = (int)(($rem)/60) - $hours*60 - $days*24*60 - $weeks*7*24*60;
    $str = '';
    if($years==1) $str .= "$years year, ";
    if($years>1) $str .= "$years years, ";
    if($weeks==1) $str .= "$weeks week, ";
    if($weeks>1) $str .= "$weeks weeks, ";
    if($days==1) $str .= "$days day,";
    if($days>1) $str .= "$days days,";
    if($hours == 1) $str .= " $hours hour and";
    if($hours>1) $str .= " $hours hours and";
    if($mins == 1) $str .= " 1 minute";
    else $str .= " $mins minutes";
    return $str;
}

// create graphics
//
function graphics_avail() {
	return extension_loaded('gd');
}
if (isset($MYREQUEST['IMG']))
{
	if (!graphics_avail()) {
		exit(0);
	}

	function fill_arc($im, $centerX, $centerY, $diameter, $start, $end, $color1,$color2,$text='',$placeindex=0) {
		$r=$diameter/2;
		$w=deg2rad((360+$start+($end-$start)/2)%360);

		
		if (function_exists("imagefilledarc")) {
			// exists only if GD 2.0.1 is avaliable
			imagefilledarc($im, $centerX+1, $centerY+1, $diameter, $diameter, $start, $end, $color1, IMG_ARC_PIE);
			imagefilledarc($im, $centerX, $centerY, $diameter, $diameter, $start, $end, $color2, IMG_ARC_PIE);
			imagefilledarc($im, $centerX, $centerY, $diameter, $diameter, $start, $end, $color1, IMG_ARC_NOFILL|IMG_ARC_EDGED);
		} else {
			imagearc($im, $centerX, $centerY, $diameter, $diameter, $start, $end, $color2);
			imageline($im, $centerX, $centerY, $centerX + cos(deg2rad($start)) * $r, $centerY + sin(deg2rad($start)) * $r, $color2);
			imageline($im, $centerX, $centerY, $centerX + cos(deg2rad($start+1)) * $r, $centerY + sin(deg2rad($start)) * $r, $color2);
			imageline($im, $centerX, $centerY, $centerX + cos(deg2rad($end-1))   * $r, $centerY + sin(deg2rad($end))   * $r, $color2);
			imageline($im, $centerX, $centerY, $centerX + cos(deg2rad($end))   * $r, $centerY + sin(deg2rad($end))   * $r, $color2);
			imagefill($im,$centerX + $r*cos($w)/2, $centerY + $r*sin($w)/2, $color2);
		}
		if ($text) {
			if ($placeindex>0) {
				imageline($im,$centerX + $r*cos($w)/2, $centerY + $r*sin($w)/2,$diameter, $placeindex*12,$color1);
				imagestring($im,4,$diameter, $placeindex*12,$text,$color1);	
				
			} else {
				imagestring($im,4,$centerX + $r*cos($w)/2, $centerY + $r*sin($w)/2,$text,$color1);
			}
		}
	} 

	function text_arc($im, $centerX, $centerY, $diameter, $start, $end, $color1,$text,$placeindex=0) {
		$r=$diameter/2;
		$w=deg2rad((360+$start+($end-$start)/2)%360);

		if ($placeindex>0) {
			imageline($im,$centerX + $r*cos($w)/2, $centerY + $r*sin($w)/2,$diameter, $placeindex*12,$color1);
			imagestring($im,4,$diameter, $placeindex*12,$text,$color1);	
				
		} else {
			imagestring($im,4,$centerX + $r*cos($w)/2, $centerY + $r*sin($w)/2,$text,$color1);
		}
	} 
	
	function fill_box($im, $x, $y, $w, $h, $color1, $color2,$text='',$placeindex='') {
		global $col_black;
		$x1=$x+$w-1;
		$y1=$y+$h-1;

		imagerectangle($im, $x, $y1, $x1+1, $y+1, $col_black);
		if($y1>$y) imagefilledrectangle($im, $x, $y, $x1, $y1, $color2);
		else imagefilledrectangle($im, $x, $y1, $x1, $y, $color2);
		imagerectangle($im, $x, $y1, $x1, $y, $color1);
		if ($text) {
			if ($placeindex>0) {
			
				if ($placeindex<16)
				{
					$px=5;
					$py=$placeindex*12+6;
					imagefilledrectangle($im, $px+90, $py+3, $px+90-4, $py-3, $color2);
					imageline($im,$x,$y+$h/2,$px+90,$py,$color2);
					imagestring($im,2,$px,$py-6,$text,$color1);	
					
				} else {
					if ($placeindex<31) {
						$px=$x+40*2;
						$py=($placeindex-15)*12+6;
					} else {
						$px=$x+40*2+100*intval(($placeindex-15)/15);
						$py=($placeindex%15)*12+6;
					}
					imagefilledrectangle($im, $px, $py+3, $px-4, $py-3, $color2);
					imageline($im,$x+$w,$y+$h/2,$px,$py,$color2);
					imagestring($im,2,$px+2,$py-6,$text,$color1);	
				}
			} else {
				imagestring($im,4,$x+5,$y1-16,$text,$color1);
			}
		}
	}


	$size = GRAPH_SIZE; // image size
	if ($MYREQUEST['IMG']==3)
		$image = imagecreate(2*$size+150, $size+10);
	else
		$image = imagecreate($size+50, $size+10);

	$col_white = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
	$col_red   = imagecolorallocate($image, 0xD0, 0x60,  0x30);
	$col_green = imagecolorallocate($image, 0x60, 0xF0, 0x60);
	$col_black = imagecolorallocate($image,   0,   0,   0);
	imagecolortransparent($image,$col_white);

	switch ($MYREQUEST['IMG']) {
	
	case 1:
		$s=$mem['num_seg']*$mem['seg_size'];
		$a=$mem['avail_mem'];
		$x=$y=$size/2;
		$fuzz = 0.000001;

		// This block of code creates the pie chart.  It is a lot more complex than you
		// would expect because we try to visualize any memory fragmentation as well.
		$angle_from = 0;
		$string_placement=array();
		for($i=0; $i<$mem['num_seg']; $i++) {	
			$ptr = 0;
			$free = $mem['block_lists'][$i];
			uasort($free, 'block_sort');
			foreach($free as $block) {
				if($block['offset']!=$ptr) {       // Used block
					$angle_to = $angle_from+($block['offset']-$ptr)/$s;
					if(($angle_to+$fuzz)>1) $angle_to = 1;
					if( ($angle_to*360) - ($angle_from*360) >= 1) {
						fill_arc($image,$x,$y,$size,$angle_from*360,$angle_to*360,$col_black,$col_red);
						if (($angle_to-$angle_from)>0.05) {
							array_push($string_placement, array($angle_from,$angle_to));
						}
					}
					$angle_from = $angle_to;
				}
				$angle_to = $angle_from+($block['size'])/$s;
				if(($angle_to+$fuzz)>1) $angle_to = 1;
				if( ($angle_to*360) - ($angle_from*360) >= 1) {
					fill_arc($image,$x,$y,$size,$angle_from*360,$angle_to*360,$col_black,$col_green);
					if (($angle_to-$angle_from)>0.05) {
						array_push($string_placement, array($angle_from,$angle_to));
					}
				}
				$angle_from = $angle_to;
				$ptr = $block['offset']+$block['size'];
			}
			if ($ptr < $mem['seg_size']) { // memory at the end 
				$angle_to = $angle_from + ($mem['seg_size'] - $ptr)/$s;
				if(($angle_to+$fuzz)>1) $angle_to = 1;
				fill_arc($image,$x,$y,$size,$angle_from*360,$angle_to*360,$col_black,$col_red);
				if (($angle_to-$angle_from)>0.05) {
					array_push($string_placement, array($angle_from,$angle_to));
				}
			}
		}
		foreach ($string_placement as $angle) {
			text_arc($image,$x,$y,$size,$angle[0]*360,$angle[1]*360,$col_black,bsize($s*($angle[1]-$angle[0])));
		}
		break;
		
	case 2: 
		$s=$cache['num_hits']+$cache['num_misses'];
		$a=$cache['num_hits'];
		
		fill_box($image, 30,$size,50,-$a*($size-21)/$s,$col_black,$col_green,sprintf("%.1f%%",$cache['num_hits']*100/$s));
		fill_box($image,130,$size,50,-max(4,($s-$a)*($size-21)/$s),$col_black,$col_red,sprintf("%.1f%%",$cache['num_misses']*100/$s));
		break;
		
	case 3:
		$s=$mem['num_seg']*$mem['seg_size'];
		$a=$mem['avail_mem'];
		$x=130;
		$y=1;
		$j=1;

		// This block of code creates the bar chart.  It is a lot more complex than you
		// would expect because we try to visualize any memory fragmentation as well.
		for($i=0; $i<$mem['num_seg']; $i++) {	
			$ptr = 0;
			$free = $mem['block_lists'][$i];
			uasort($free, 'block_sort');
			foreach($free as $block) {
				if($block['offset']!=$ptr) {       // Used block
					$h=(GRAPH_SIZE-5)*($block['offset']-$ptr)/$s;
					if ($h>0) {
                                                $j++;
						if($j<75) fill_box($image,$x,$y,50,$h,$col_black,$col_red,bsize($block['offset']-$ptr),$j);
                                                else fill_box($image,$x,$y,50,$h,$col_black,$col_red);
                                        }
					$y+=$h;
				}
				$h=(GRAPH_SIZE-5)*($block['size'])/$s;
				if ($h>0) {
                                        $j++;
					if($j<75) fill_box($image,$x,$y,50,$h,$col_black,$col_green,bsize($block['size']),$j);
					else fill_box($image,$x,$y,50,$h,$col_black,$col_green);
                                }
				$y+=$h;
				$ptr = $block['offset']+$block['size'];
			}
			if ($ptr < $mem['seg_size']) { // memory at the end 
				$h = (GRAPH_SIZE-5) * ($mem['seg_size'] - $ptr) / $s;
				if ($h > 0) {
					fill_box($image,$x,$y,50,$h,$col_black,$col_red,bsize($mem['seg_size']-$ptr),$j++);
				}
			}
		}
		break;
	case 4: 
		$s=$cache['num_hits']+$cache['num_misses'];
		$a=$cache['num_hits'];
	        	
		fill_box($image, 30,$size,50,-$a*($size-21)/$s,$col_black,$col_green,sprintf("%.1f%%",$cache['num_hits']*100/$s));
		fill_box($image,130,$size,50,-max(4,($s-$a)*($size-21)/$s),$col_black,$col_red,sprintf("%.1f%%",$cache['num_misses']*100/$s));
		break;
	
	}
	header("Content-type: image/png");
	imagepng($image);
	exit;
}

// pretty printer for byte values
//
function bsize($s) {
	foreach (array('','K','M','G') as $i => $k) {
		if ($s < 1024) break;
		$s/=1024;
	}
	return sprintf("%5.1f %sBytes",$s,$k);
}

// sortable table header in "scripts for this host" view
function sortheader($key,$name,$extra='') {
	global $MYREQUEST, $MY_SELF_WO_SORT;
	
	if ($MYREQUEST['SORT1']==$key) {
		$MYREQUEST['SORT2'] = $MYREQUEST['SORT2']=='A' ? 'D' : 'A';
	}
	return "<a class=sortable href=\"$MY_SELF_WO_SORT$extra&SORT1=$key&SORT2=".$MYREQUEST['SORT2']."\">$name</a>";

}

// create menu entry 
function menu_entry($ob,$title) {
	global $MYREQUEST,$MY_SELF;
	if ($MYREQUEST['OB']!=$ob) {
		return "<li><a href=\"$MY_SELF&OB=$ob\">$title</a></li>";
	} else if (empty($MYREQUEST['SH'])) {
		return "<li><span class=active>$title</span></li>";
	} else {
		return "<li><a class=\"child_active\" href=\"$MY_SELF&OB=$ob\">$title</a></li>";	
	}
}

function put_login_link($s="Login")
{
	global $MY_SELF,$MYREQUEST,$AUTHENTICATED;
	// needs ADMIN_PASSWORD to be changed!
	//
	if (!USE_AUTHENTICATION) {
		return;
	} else if (ADMIN_PASSWORD=='password')
	{
		print <<<EOB
			<a href="#" onClick="javascript:alert('You need to set a password at the top of apc.php before this will work!');return false";>$s</a>
EOB;
	} else if ($AUTHENTICATED) {
		print <<<EOB
			'{$_SERVER['PHP_AUTH_USER']}'&nbsp;logged&nbsp;in!
EOB;
	} else{
		print <<<EOB
			<a href="$MY_SELF&LO=1&OB={$MYREQUEST['OB']}">$s</a>
EOB;
	}
}

function block_sort($array1, $array2)
{
	if ($array1['offset'] > $array2['offset']) {
		return 1;
	} else {
		return -1;
	}
}


$mem_size = $mem['num_seg']*$mem['seg_size'];
$mem_avail= $mem['avail_mem'];
$mem_used = $mem_size-$mem_avail;
$seg_size = bsize($mem['seg_size']);
$req_rate = sprintf("%.2f",($cache['num_hits']+$cache['num_misses'])/($time-$cache['start_time']));
$hit_rate = sprintf("%.2f",($cache['num_hits'])/($time-$cache['start_time']));
$miss_rate = sprintf("%.2f",($cache['num_misses'])/($time-$cache['start_time']));
$insert_rate = sprintf("%.2f",($cache['num_inserts'])/($time-$cache['start_time']));
$req_rate_user = sprintf("%.2f",($cache_user['num_hits']+$cache_user['num_misses'])/($time-$cache_user['start_time']));
$hit_rate_user = sprintf("%.2f",($cache_user['num_hits'])/($time-$cache_user['start_time']));
$miss_rate_user = sprintf("%.2f",($cache_user['num_misses'])/($time-$cache_user['start_time']));
$insert_rate_user = sprintf("%.2f",($cache_user['num_inserts'])/($time-$cache_user['start_time']));
$apcversion = phpversion('apc');
$phpversion = phpversion();
$number_files = $cache['num_entries']; 
$size_files = bsize($cache['mem_size']);
$number_vars = $cache_user['num_entries'];
$size_vars = bsize($cache_user['mem_size']);

// include "api.php";

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

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
		<link href="//www.techzonemind.com/labz/apc-admin/css/todc-bootstrap.min.css" rel="stylesheet">
		
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/highcharts/4.0.1/highcharts.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/highcharts/4.0.1/highcharts-more.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/highcharts/4.0.1/modules/exporting.js"></script>
		<script src="//www.techzonemind.com/labz/apc-admin/js/jquery.timeago.js"></script>
		<script src="//www.techzonemind.com/labz/apc-admin/js/jquery.tablesorter.min.js"></script>

		<script>
			var req_time = (new Date()).getTime();
			var opcode_progress = Array();
			var ctime = (new Date()).getTime();
			var opcode_chart;
			var cache;
			
			function refreshData(){
				req_time = (new Date()).getTime();
				$.getJSON("apc.php?status",function(data){
					
					cache  = data;
					
					var curr_time = (new Date()).getTime();
					var delay = curr_time-req_time;
					
					if (delay > 1000) {
						refreshData();
					}else{
						setTimeout(refreshData,(1000-(curr_time-req_time)));
					}
					
				});
			};
			
			function applyData(){
				if(typeof cache != 'undefined'){
					var curr_time = (new Date()).getTime();
					
					
					console.log(cache);
					
					opcode_progress[0].addPoint([curr_time, cache.num_hits],false,true);
					opcode_progress[1].addPoint([curr_time, cache.num_misses],false,true);
					
					opcode_chart.redraw();
					
					user_progress[0].addPoint([curr_time, cache.user.num_hits],false,true);
					user_progress[1].addPoint([curr_time, cache.user.num_misses],false,true);
					
					user_chart.redraw();
					
				}
				setTimeout(applyData,1000);
			};
			
			$(document).ready(function(){
				$(".timeago").timeago();
				$(".sorter").tablesorter();
				refreshData();
				applyData();
			});
		</script>
    </head>
    <body>
        
		<div  class="container">
			
			<ul class="nav nav-tabs nav-tabs-google">
				<li class="active"><a href="#status" data-toggle="tab"><b>APC Admin</b></a></li>
				<li class="dropdown">
					<a href="#" id="opcode-drop" class="dropdown-toggle" data-toggle="dropdown">Opcode Cache <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="opcode-drop">
						<li><a href="#opcode" tabindex="-1" data-toggle="tab">Cache Info</a></li>
						<li><a href="#per-file" tabindex="-1" data-toggle="tab">Cached Files</a></li>
						<li><a href="#per-directory" tabindex="-1" data-toggle="tab">Per Directory Entrys</a></li>
					</ul>
				</li>
				<li class="dropdown">
					<a href="#" id="user-drop" class="dropdown-toggle" data-toggle="dropdown">User Cache <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="user-drop">
						<li><a href="#user" tabindex="-1" data-toggle="tab">Cache Info</a></li>
						<li><a href="#per-user" tabindex="-1" data-toggle="tab">Cached User Variables</a></li>
					</ul>
				</li>
				<li><a href="#fragmentation" data-toggle="tab">Memory Fragmentation</a></li>
				<li><a href="#config" data-toggle="tab">Configuration</a></li>
				<li><a href="#about" data-toggle="tab" id="about_link">About</a></li>
				
			</ul>
			
			<div class="tab-content" style="padding-top: 20px">
		
				<div class="tab-pane active" id="status">
					
					<?php if(count(ini_get_all('apc'))==0 && count(ini_get_all('apcu'))>0){ ?>
						<div class="alert alert-warning" role="alert">
							You are running APCu instead of APC, some options will not work properly
						</div>
					<?php } ?>
					
					
					<div class="col-md-6" style="padding-left: 0px">
    
						<div class="panel panel-info">
							<div class="panel-heading">
								<h3 class="panel-title">General Cache Information</h3>
							</div>
							<div class="panel-body" style="padding: 0px">
								<table class="table table-striped table-bordered">
									<tbody>
										<tr><td>APC Version</td><td><?php echo $apcversion ?></td></tr>
										<tr><td>PHP Version</td><td><?php echo $phpversion ?></td></tr>
										<?php if(!empty($_SERVER['SERVER_NAME'])) { ?>
											<tr><td>APC Host</td><td><?php echo $_SERVER['SERVER_NAME']." ".$host?></td></tr>
											
										<?php } if(!empty($_SERVER['SERVER_SOFTWARE'])) { ?>
											<tr><td>Server Software</td><td><?php echo $_SERVER['SERVER_SOFTWARE']?></td></tr>
										<?php } ?>
										
										<tr><td>Shared Memory</td><td><?php echo $mem['num_seg']?> Segment(s) with <?php echo $seg_size?> 
											<br/> (<?php echo $cache['memory_type']?> memory, <?php echo $cache['locking_type']?> locking)
											</td>
										</tr>
										<tr><td>Start Time</td><td><?php echo date(DATE_FORMAT,$cache['start_time'])?></td></tr>
										<tr><td>Uptime</td><td><?php echo duration($cache['start_time'])?></td></tr>
										<tr><td>File Upload Support</td><td><?php echo $cache['file_upload_progress']?></td></tr>
									</tbody>
								</table>
							</div>
						</div>
						
					</div>
					
					<div class="col-md-6" style="padding-left: 0px">
						<div class="panel panel-info">
							<div class="panel-heading">
								<h3 class="panel-title">Memory allocation and usage</h3>
							</div>
							<div class="panel-body">
							
								<div id="chat_container_status"></div>
								<script>
									$(function () {
										$('#chat_container_status').highcharts({
											chart: {
												plotBackgroundColor: null,
												plotBorderWidth: null,
												plotShadow: false
											},
											tooltip: {
												pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
											},
											plotOptions: {
												pie: {
													allowPointSelect: true,
													cursor: 'pointer',
													dataLabels: {
														enabled: false
													},
													showInLegend: true
												}
											},
											credits: {
												enabled: false
											},
											series: [{
												type: 'pie',
												name: 'Host Status Diagrams - Memory Usage',
												data: [
													['Cached Files - ' + '<?php echo $size_files?>',   <?php echo $cache['mem_size'];?> ],
													['Cached Variables - ' + '<?php echo $size_vars?>',  <?php echo $cache_user['mem_size'];?>],
													['Free Space - ' + '<?php echo bsize($mem_avail)?>',    <?php echo $mem_avail?>]
												]
											}]
										});
									});
								</script>
							</div>
						</div>
					</div>
				</div>
				
				<?php

$hits       = array();
$missess    =  array();
for($i=20; $i>=0; $i--){
   $hits[] = "{x:ctime-".($i*1000).",y:".intval($cache['num_hits'])."}";
   $missess[] = "{x:ctime-".($i*1000).",y:".intval($cache['num_misses'])."}";
}

?>
<script>
    
    
    $(document).ready(function(){
        
        
        
        var chart_options = {
            chart: {
                type: 'line',
                renderTo: 'opcode_progress',
                animation: {
                    duration : 980,
                    easing : 'linear'
                },
                events: {
                    load: function() {
                        opcode_progress[0] = this.series[0];
                        opcode_progress[1] = this.series[1];
                    }
                }
            },
            title: {
                text: ''
            },
            subtitle: {
                text: 'Cached files Hits & Missess'
            },
            xAxis: {
                type: 'datetime',
                tickPixelInterval: 150
            },
            yAxis: {
                min : 0,
                title: {
                    text: ''
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                shared: true
            },
            plotOptions: {
                area: {
                    stacking: 'normal',
                    lineColor: '#666666',
                    lineWidth: 1,
                    marker: {
                        lineWidth: 1,
                        lineColor: '#666666'
                    }
                }
            },
            series: [{
                name: 'Hits',
                data: [<?php echo implode(',',$hits)?>]
            }, {
                name: 'Miss',
                data: [<?php echo implode(',',$missess)?>]
            }],
            credits: {
                enabled: false
            }
        };
        opcode_chart = new Highcharts.Chart(chart_options);
        
        $("[ajax]").click(function(){
            $(this).attr('disabled','disabled');
            var value  = $(this).attr("ajax");
            var clicked_btn = $(this);
            var btn_text = $(this).html();
            $.getJSON('apc.php?'+value,function(response){
                console.log(response);
                if(response.status==1){
                    clicked_btn.html(response.text);
                    setInterval(function(){clicked_btn.html(btn_text);clicked_btn.removeAttr('disabled');},5000);
                }else{
                    clicked_btn.html(response.text);
                    clicked_btn.removeAttr('disabled');
                }
            });
        });
        
        $(".refresh_cache_file,.refresh_cache_folder").click(function(){
            $(this).attr('disabled','disabled');
            var value  = $(this).attr("ajaxr");
            var clicked_btn = $(this);
            var btn_text = $(this).html();
            $(this).html('<img src="./img/489.GIF"/>');
            $.getJSON('apc.php?'+value,function(response){
                if(response.status==1){
                    clicked_btn.html('<span class="glyphicon glyphicon-ok-circle"></span>');
                    setInterval(function(){clicked_btn.html(btn_text);clicked_btn.removeAttr('disabled');},5000);
                }else{
                    clicked_btn.html('<span class="glyphicon glyphicon-remove-circle"></span>');
                    setInterval(function(){clicked_btn.html(btn_text);clicked_btn.removeAttr('disabled');},5000);
                }
            });
        });
        
        
        $(".remove_cache_file,.remove_cache_folder,.remove_cache_user").click(function(){
            $(this).attr('disabled','disabled');
            var value  = $(this).attr("ajaxr");
            var clicked_btn = $(this);
            var btn_text = $(this).html();
            $(this).html('<img src="./img/489.GIF"/>');
            $.getJSON('apc.php?'+value,function(response){
                if(response.status==1){
                    clicked_btn.html('<span class="glyphicon glyphicon-ok-circle"></span>');
                    setInterval(function(){
                        clicked_btn.html(btn_text);
                        clicked_btn.removeAttr('disabled');
                        clicked_btn.parents('tr').remove();
                    },3000);
                }else{
                    clicked_btn.html('<span class="glyphicon glyphicon-remove-circle"></span>');
                    setInterval(function(){clicked_btn.html(btn_text);clicked_btn.removeAttr('disabled');},5000);
                }
            });
        });
        
        
        $("#add_directory").click(function(){
            var dir = $("[name='add_directory']").val();
            $(this).attr('disabled','disabled');
        })
        
        
        
    })
    
    
</script>
<style>
    .sorter th:hover{
        text-decoration: underline;
    }
    .sorter th{
        cursor: pointer;
    }
    td.options .btn{
        padding: 4px 6px;
    }
    td.options{
        padding: 3px !important;
    }
    .bs-callout-info {
        background-color: #F0F7FD;
        border-color: #D0E3F0;
    }
    .bs-callout {
        margin: 0px;
        padding: 10px;
        border-left: 6px solid #ADDBFF;
    }
</style>

<div class="tab-pane" id="opcode">
    <div class="col-md-12" style="padding-left: 0px">
        
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">
                    File Cache Information
                    <button type="button" class="btn btn-primary" ajax="clear=opcode" style="float: right;width: 160px;top: -8px;position: relative">Clear Opcode Cache</button>
                </h3>
            </div>
            <div class="panel-body" style="padding: 0px">
                
                <div class="col-md-6">
                    <div id="opcode_progress" style="width:550px;overflow: hidden"></div>
                </div>
                
                <div class="col-md-6">
                    <h4>Opcode Cache Information</h4>
                    <table cellspacing=0 class="table table-striped">
                        <tbody>
                            <tr class=tr-0><td class=td-0>Cached Files</td><td><?php echo $number_files;?> (<?php echo $size_files?>)</td></tr>
                            <tr class=tr-1><td class=td-0>Hits</td><td><?php echo $cache['num_hits']?></td></tr>
                            <tr class=tr-0><td class=td-0>Misses</td><td><?php echo $cache['num_misses']?></td></tr>
                            <tr class=tr-1><td class=td-0>Request Rate (hits, misses)</td><td><?php echo $req_rate?> cache requests/second</td></tr>
                            <tr class=tr-0><td class=td-0>Hit Rate</td><td><?php echo $hit_rate?> cache requests/second</td></tr>
                            <tr class=tr-1><td class=td-0>Miss Rate</td><td><?php echo $miss_rate?> cache requests/second</td></tr>
                            <tr class=tr-0><td class=td-0>Insert Rate</td><td><?php echo $insert_rate?> cache requests/second</td></tr>
                            <tr class=tr-1><td class=td-0>Cache full count</td><td><?php echo $cache['expunges']?></td></tr>
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
        
    </div>
</div>

<div class="tab-pane" id="per-directory">
    <div class="col-md-12" style="padding-left: 0px">
        
        <div class="panel panel-info" style="clear: both">
            <div class="panel-heading">
                <h3 class="panel-title">File Cache For Each Directories</h3>
            </div>
            <div class="panel-body" style="padding: 0px">
                
                <div class="bs-callout bs-callout-info" style="overflow: hidden">
                    <div class="col-md-4" style="padding-left: 0px">
                        <h5>Add A Directory To Cache</h5>
                    </div>
                    <div class="col-md-8" style="padding-left: 0px">
                        <div class="input-group">
                          <input type="text" class="form-control"  name="add_directory">
                          <span class="input-group-btn">
                            <button class="btn btn-default" type="button" id="add_directory">Add Directory</button>
                          </span>
                        </div><!-- /input-group -->
                    </div>
                </div>
                
                
                <table cellspacing=0 class="table table-striped sorter">
                    <thead>
                        <tr>
                            <th>Directory</th>
                            <th>Number of Files</th>
                            <th>Total Hits</th>
                            <th>Total Size</th>
                            <th>Avg. Hits</th>
                            <th>Avg. Size</th>
                            <th>Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tmp = $list = array();
                        foreach($cache[$scope_list[$MYREQUEST['SCOPE']]] as $entry) {
                            $n = dirname($entry['filename']);
                            if ($MYREQUEST['AGGR'] > 0) {
                                $n = preg_replace("!^(/?(?:[^/\\\\]+[/\\\\]){".($MYREQUEST['AGGR']-1)."}[^/\\\\]*).*!", "$1", $n);
                            }
                            if (!isset($tmp[$n])) {
                                $tmp[$n] = array('hits'=>0,'size'=>0,'ents'=>0);
                            }
                            $tmp[$n]['hits'] += $entry['num_hits'];
                            $tmp[$n]['size'] += $entry['mem_size'];
                            ++$tmp[$n]['ents'];
                        }
                    
                        foreach ($tmp as $k => $v) {
                            switch($MYREQUEST['SORT1']) {
                                case 'A': $kn=sprintf('%015d-',$v['size'] / $v['ents']);break;
                                case 'T': $kn=sprintf('%015d-',$v['ents']);		break;
                                case 'H': $kn=sprintf('%015d-',$v['hits']);		break;
                                case 'Z': $kn=sprintf('%015d-',$v['size']);		break;
                                case 'C': $kn=sprintf('%015d-',$v['hits'] / $v['ents']);break;
                                case 'S': $kn = $k;					break;
                            }
                            $list[$kn.$k] = array($k, $v['ents'], $v['hits'], $v['size']);
                        }
                        
                        if ($list) {
                            $i = 0;
                            foreach($list as $entry) {
                                echo
                                    '<tr>',
                                    "<td>",$entry[0],'</a></td>',
                                    '<td>',$entry[1],'</td>',
                                    '<td>',$entry[2],'</td>',
                                    '<td>',$entry[3],'</td>',
                                    '<td>',round($entry[2] / $entry[1]),'</td>',
                                    '<td>',round($entry[3] / $entry[1]),'</td>'
                                    ;?>
                                    <td class="options">
                                        <button type="button" class="btn btn-info refresh_cache_folder" ajaxr="refresh_cache_folder=<?php echo $entry[0] ?>"><span class="glyphicon glyphicon-refresh"></span></button>
                                        <button type="button" class="btn btn-danger remove_cache_folder" ajaxr="remove_cache_folder=<?php echo $entry[0] ?>"><span class="glyphicon glyphicon-trash"></span></button>
                                    </td>
                                    <?php
                                    echo '</tr>';
                    
                                if (++$i == $MYREQUEST['COUNT']) break;
                            }
                            
                        } else {
                            echo '<tr class=tr-0><td class="center" colspan=6><i>No data</i></td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<div class="tab-pane" id="per-file">
    <div class="col-md-12" style="padding-left: 0px">
        
        <div class="panel panel-info" style="clear: both">
            <div class="panel-heading">
                <h3 class="panel-title">File Cache For Each Files</h3>
            </div>
            <div class="panel-body" style="padding: 0px">
                <table cellspacing=0 class="table table-striped sorter">
                    <thead>
                        <tr>
                            <th>File</td>
                            <th>Hits</td>
                            <th>Size</td>
                            <th>Created</td>
                            <th>Modified</td>
                            <th>Last Accessed</td>
                            <th>Options</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach($cache['cache_list'] as $row){
                            if($row['type']=='file'){
                                ?>
                                <tr>
                                    <td><?php echo $row['filename'] ?> </td>
                                    <td><?php echo $row['num_hits']?> </td>
                                    <td><?php echo $row['mem_size']?> </td>
                                    <td>
                                        <span class="timeago" title="<?php echo date('c',$row['creation_time'])?>">
                                            <?php echo date('r ',$row['creation_time'])?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="timeago" title="<?php echo date('c',$row['mtime'])?>">
                                            <?php echo date('r ',$row['mtime'])?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="timeago" title="<?php echo date('c',$row['access_time'])?>">
                                            <?php echo date('r ',$row['access_time'])?>
                                        </span>
                                    </td>
                                    <td class="options">
                                        <button type="button" class="btn btn-info refresh_cache_file" ajaxr="refresh_cache_file=<?php echo $row['filename'] ?>"><span class="glyphicon glyphicon-refresh"></span></button>
                                        <button type="button" class="btn btn-danger remove_cache_file" ajaxr="remove_cache_file=<?php echo $row['filename'] ?>"><span class="glyphicon glyphicon-trash"></span></button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>
				<?php

$hits       = array();
$missess    = array();

for($i=20; $i>=0; $i--){
   $hits[] = "{x:ctime-".($i*1000).",y:".intval($cache_user['num_hits'])."}";
   $missess[] = "{x:ctime-".($i*1000).",y:".intval($cache_user['num_misses'])."}";
}

?>
<style>
    div{
        border-radius:0px !important;
    }
</style>
<script>
    
    
    $(document).ready(function(){
                
        var user_chart_options = {
            chart: {
                type: 'line',
                renderTo: 'user_progress',
                animation: {
                    duration : 1000,
                    easing : 'linear'
                },
                events: {
                    load: function() {
                        user_progress[0] = this.series[0];
                        user_progress[1] = this.series[1];
                    }
                }
            },
            title: {
                text: ''
            },
            subtitle: {
                text: 'Cached files Hits & Missess'
            },
            xAxis: {
                type: 'datetime',
                tickPixelInterval: 150
            },
            yAxis: {
                min : 0,
                title: {
                    text: ''
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                shared: true
            },
            plotOptions: {
                area: {
                    stacking: 'normal',
                    lineColor: '#666666',
                    lineWidth: 1,
                    marker: {
                        lineWidth: 1,
                        lineColor: '#666666'
                    }
                }
            },
            series: [{
                name: 'Hits',
                data: [<?php echo implode(',',$hits)?>]
            }, {
                name: 'Miss',
                data: [<?php echo implode(',',$missess)?>]
            }],
            credits: {
                enabled: false
            }
        };
        user_chart = new Highcharts.Chart(user_chart_options);
        
        
        $(".var_details").click(function(){
            var title = $(this).parent().find('.var_details_body').attr('title');
            var body  = $(this).parent().find('.var_details_body').html();
            var ajaxr = $(this).parent().find()
            
            $("#myModal .modal-title").html(title);
            $("#myModal .modal-body").html(body);
            
            $("#myModal").modal('show');
            
        })
        
        
        $("#save_variable").click(function(){
            var name,value,ttl;
            name  = $("#myModal [name='name']").val();
            ttl   = $("#myModal [name='ttl']").val();
            value = '';
            if($("#myModal [name='value']").length>0){
                value  = '&value='+encodeURI($("#myModal [name='value']").val());
            }
            
            $(this).attr('disabled','disabled');
            $(this).html('<img src="./img/489.GIF"/> Please wait');
            $.getJSON('apc.php?setvariable=' + encodeURI(name) + '&ttl=' + encodeURI(ttl) + value,function(data){
                $("#myModal").modal('hide');
                $("#save_variable").removeAttr('disabled');
                $("#save_variable").html('Save changes');
            })
            
        });
        
    });
</script>

<div class="tab-pane" id="user">
    <div class="col-md-12" style="padding-left: 0px">
        
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">
                    User Variables Cache Information
                    <button type="button" class="btn btn-primary" ajax="clear=user" style="float: right;width: 160px;top: -8px;position: relative">Clear User Cache</button>
                </h3>
            </div>
            <div class="panel-body" style="padding: 0px">
                
                <div class="col-md-6">
                    <div id="user_progress" style="width:550px;overflow: hidden"></div>
                </div>
                
                <div class="col-md-6">
                    <h4>User Variable Cache Information</h4>
                    <table cellspacing=0 class="table table-striped">
                        <tbody>
                            <tr class=tr-0><td class=td-0>Cached Variables</td><td><?php echo $number_vars?> (<?php echo $size_vars?>)</td></tr>
                            <tr class=tr-1><td class=td-0>Hits</td><td><?php echo $cache_user['num_hits']?></td></tr>
                            <tr class=tr-0><td class=td-0>Misses</td><td><?php echo $cache_user['num_misses']?></td></tr>
                            <tr class=tr-1><td class=td-0>Request Rate (hits, misses)</td><td><?php echo $req_rate_user?> cache requests/second</td></tr>
                            <tr class=tr-0><td class=td-0>Hit Rate</td><td><?php echo $hit_rate_user?> cache requests/second</td></tr>
                            <tr class=tr-1><td class=td-0>Miss Rate</td><td><?php echo $miss_rate_user?> cache requests/second</td></tr>
                            <tr class=tr-0><td class=td-0>Insert Rate</td><td><?php echo $insert_rate_user?> cache requests/second</td></tr>
                            <tr class=tr-1><td class=td-0>Cache full count</td><td><?php echo $cache_user['expunges']?></td></tr>
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
        
    </div>
</div>



<div class="tab-pane" id="per-user">
    <div class="col-md-12" style="padding-left: 0px">
        
        <div class="panel panel-info" style="clear: both">
            <div class="panel-heading">
                <h3 class="panel-title">User Variabled in Cache</h3>
            </div>
            <div class="panel-body" style="padding: 0px">
                <?php
                if(count($cache_user['cache_list'])==0){
                    ?>
                    <div class="alert alert-warning fade in">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        Sorry, no user varibles found in opcode cache
                    </div>
                    <?php
                } else {
                ?>
                <table cellspacing=0 class="table table-striped sorter">
                    <thead>
                        <tr>
                            <th>Variable</td>
                            <th>Hits</td>
                            <th>Size</td>
                            <th>Created</td>
                            <th>Modified</td>
                            <th>Last Accessed</td>
                            <th>Options</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach($cache_user['cache_list'] as $row){
                            if((isset($row['type']) && $row['type']=='user') || !isset($row['type'])){
                                ?>
                                <tr>
                                    <td>
                                        <span class="var_details" style="cursor: pointer">
                                            <?php echo $row['info'] ?>
                                        </span>
                                        <div style="display: none" class="var_details_body" title="<?php echo $row['info'] ?> Details" ajaxr="remove_cache_user=<?php echo $row['info'] ?>">
                                            <?php
                                            $var  = apc_fetch($row['info']);
                                            $type = gettype($var);
                                            ?>
                                            <input type="hidden" name="name" value="<?php echo $row['info'] ?>"/> 
                                            <table class="table table-striped">
                                                <tr>
                                                    <td style="width: 150px">Name</td>
                                                    <td colspan=2><?php echo $row['info'] ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Type</td>
                                                    <td colspan=2><?php echo gettype($var); ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Value</td>
                                                    <td colspan=2><pre style="max-width: 450px;max-height: 300px;overflow: auto;"><?php echo displayVar($var); ?></td>
                                                </tr>
                                                <?php if($type=='double' || $type=='string' || $type=='integer'){ ?>
                                                <tr>
                                                    <td>Edit value</td>
                                                    <td colspan=2><textarea class="form-control" name="value"><?php echo $var ?></textarea></td>
                                                </tr>
                                                <?php  } ?>
                                                <tr>
                                                    <td>Expire on</td>
                                                    <td><input name="ttl" type="text" style="width: 200px" class="form-control" value="<?php echo ($row['ttl']==0)?'never':$row['ttl'] ?>"/></td>
                                                    <td>Seconds <small>(0 if never expires)</small></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                    <td><?php echo $row['num_hits']?> </td>
                                    <td><?php echo $row['mem_size']?> </td>
                                    <td>
                                        <span class="timeago" title="<?php echo date('c',$row['creation_time'])?>">
                                            <?php echo date('r ',$row['creation_time'])?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="timeago" title="<?php echo date('c',$row['mtime'])?>">
                                            <?php echo date('r ',$row['mtime'])?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="timeago" title="<?php echo date('c',$row['access_time'])?>">
                                            <?php echo date('r ',$row['access_time'])?>
                                        </span>
                                    </td>
                                    <td class="options">
                                        <button type="button" class="btn btn-danger remove_cache_user" ajaxr="remove_cache_user=<?php echo $row['info'] ?>"><span class="glyphicon glyphicon-trash"></span></button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
                <?php } ?>
            </div>
        </div>
        
    </div>
</div>


<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="modal-body">
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="save_variable">Save changes</button>
        <button type="button" class="btn btn-danger remove_cache_user_popup" style="float: left">
            <span class="glyphicon glyphicon-trash"></span>
        </button>
      </div>
    </div>
  </div>
</div>
				
				<div class="tab-pane" id="fragmentation">
					<?php

function getFragments($mem) {
	$frags = array();
	foreach($mem['block_lists'][0] as $block){
		$frags[$block['offset']] = $block['size'];
	}
	ksort($frags);
	
	$text = array();
	$prev_val = 0;
	$i = 0;
	foreach($frags as $key=>$value){
		$i++;
		$key = $key - $prev_val;
		$text[] = "{name: 'Used-".bsize($key)."',data: [".($key)."]}";
		$text[] = "{name: 'Free-".bsize($value)."',data: [".($value)."]}";
		$prev_val = $key + $prev_val + $value;
	}
	return implode(',',array_reverse($text));
}



?>
<style>
    .memblock{
        height: 20px;
        width:20px;
        float: left;
        border: 1px solid cyan;
        cursor: pointer;
    }
    .memblock:hover{
        border: 1px solid blue !important;
    }
</style>
<script>
    $(function () {
    
        Highcharts.setOptions({
             colors: [ '#50B432','#ED561B']
        });
    
        $('#frag_container').highcharts({
            chart: {
                type: 'column',
                zoomType:'y'
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['Block 1']
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Memory Fragmentaions   (Bytes)'
                },
                stackLabels: {
                    enabled: true,
                    style: {
                        fontWeight: 'bold',
                        color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                    }
                }
            },
            tooltip: {
                formatter: function() {
                    return '<b>'+ this.series.name+'</b>'
                }
            },
            plotOptions: {
                column: {
                    stacking: 'normal',
                    dataLabels: {
                        enabled: true,
                        color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                        style: {
                            textShadow: '0 0 3px black, 0 0 3px black'
                        }
                    }
                }
            },
            series: [<?php echo getFragments($mem); ?>],
            credits: {
                enabled: false
            },
            legend : {
                enabled: false
            }
        });
    });
</script>
<div class="col-md-3" style="padding-left: 0px">
    <div id="frag_container" style="width: 250px;height: 100%"></div>
</div>
<div class="col-md-9" style="padding-left: 0px">
    
    <ul class="nav nav-tabs">
        <li class="active"><a href="#fragmentation-opcode" data-toggle="tab">Memory Segments - Opcode Cache</a></li>
        <li><a href="#fragmentation-user" data-toggle="tab">Memory Segments - User Cache</a></li>
    </ul>
    
    <div class="tab-content" style="padding-top: 20px">
        <div class="tab-pane active" id="fragmentation-opcode">
            <h3>Memory Segments - Opcode Cache</h3>
            <?php
                        
            $slot = $cache['slot_distribution'];
                        
            echo'<div>';
            foreach($slot as $key=>$value){
                if($value==1){
                    echo "<div class='memblock bg-primary'></div>";
                }else{
                    echo "<div class='memblock $key'></div>";
                }
            }
            echo '</div>';
            ?>
        </div>
        
        <div class="tab-pane" id="fragmentation-user">
            <h3>Memory Segments - User Cache</h3>
            <?php
            
            $slot = apc_cache_info('user');
            $slot = $slot['slot_distribution'];
            
            echo'<div>';
            foreach($slot as $key=>$value){
                if($value==1){
                    echo "<div class='memblock bg-primary'></div>";
                }else{
                    echo "<div class='memblock $key'></div>";
                }
            }
            echo '</div>';
            ?>
        </div>
    </div>
    
</div>
				</div>
				
				<div class="tab-pane" id="config">
					


<div class="col-md-12">
    
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">Runtime Settings</h3>
        </div>
        <div class="panel-body" style="padding: 0px">
            
            <table class="table table-striped table-bordered">
                <tbody>
                    <?php
                    $i = 0;
                    $configs = ini_get_all('apc');
                    if(count($configs)==0){
                        $configs = ini_get_all('apcu');
                    }
                    foreach ($configs as $k => $v) {
                        if($i%2==0){
                            echo '<tr>';
                        }
                        $i++;
                        echo "<td>",$k,"</td><td>",str_replace(',',',<br />',$v['local_value']),"</td>";
                        if($i%2==0){
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
            
        </div>
    </div>
    
</div>
				</div>
				
				<div class="tab-pane" id="about">
					<div class="col-md-12">
    <script>
        $(document).ready(function(){
            $("#about_link").click(function(){
                $.ajax({url:'apc.php?about_info=full'}).done(function(data){
                    $("#apc_about").html(data);
                })
            })
        })
    </script>
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">About APC Admin</h3>
        </div>
        <div class="panel-body">
            <div class="col-md-10">
                Author : JITHIN JOSE</br>
                Version : 1.2</br>
                APC Admin is created and maintained by <a href="http://www.techzonemind.com/"><img src="http://www.techzonemind.com/labz/logo.jpg" style="height: 12px;margin-bottom: 1px;margin-right: 2px"/>TechZoneMid</a>. Free to use,modify and and redistribute. </br>
                For anyquerys/bug reports please visit github rep
            </div>
            <div class="col-md-2">
                <a href="http://www.techzonemind.com/">
                    <img src="http://www.techzonemind.com/labz/logo.jpg"/>
                </a>
            </div>
        </div>
    </div>
    
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">APC Version Information</h3>
        </div>
        <div class="panel-body" id="apc_about">
            <div class="alert alert-info">Please wait loading updated  information</div>
        </div>
    </div>

</div>
				</div>
				
			</div>
			
		</div>
		
    </body>
</html>