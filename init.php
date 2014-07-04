<?php

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