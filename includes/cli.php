<?php
namespace phan;

error_reporting(-1);
ini_set("memory_limit", -1);

// Parse command line args
$opts = getopt("f:m:o:c:hasuqbpigt::");
$pruneargv = array();
$files = [];
$dump_ast = $dump_scope = $dump_user_functions = $quick_mode = $progress_bar = $gv = $bc_checks = false;
$gv_node = '';
$pc_required = [];

foreach($opts as $key=>$value) {
	switch($key) {
		case 'h': usage(); break;
		case 'f':
			if(is_file($value) && is_readable($value)) {
				$files = file($value,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
			} else {
				Log::err(Log::EFATAL, "Unable to open $value");
			}
			break;
		case 'm':
			if(!in_array($value, ['verbose','short','json','csv'])) usage("Unknown output mode: $value");
			Log::setOutputMode($value);
			break;
		case 'a':
			$dump_ast = true;
			break;
		case 'c':
			$pc_required = explode(',',$value);
			break;
		case 's':
			$dump_scope = true;
			break;
		case 'u':
			$dump_user_functions = true;
			break;
		case 'q':
			$quick_mode = true;
			break;
		case 'b':
			$bc_checks = true;
			break;
		case 'p':
			$progress_bar = true;
			break;
		case 'o':
			Log::setFilename($value);
			break;
		case 'i':
			Log::setOutputMask(Log::getOutputMask()^Log::EUNDEF);
			break;
		case 't':
			Log::setOutputMask(Log::getOutputMask()^Log::ETYPE);
			break;
		case 'g':
			if(!empty($value)) $gv_node = $value;
			else $gv_node = '';
			$gv = true;
			break;

		default: usage("Unknown option '-$key'"); break;
	}
}
foreach($opts as $opt => $value) {
	foreach($argv as $key=>$chunk) {
		$regex = '/^'. (isset($opt[1]) ? '--' : '-') . $opt . '/';
		if ($chunk == $value && $argv[$key-1][0] == '-' || preg_match($regex, $chunk)) {
			array_push($pruneargv, $key);
		}
	 }
}
while($key = array_pop($pruneargv)) unset($argv[$key]);
if(empty($files) && count($argv) < 2) Log::err(Log::EFATAL, "No files to analyze");
foreach($argv as $arg) if($arg[0]=='-') usage("Unknown option '{$arg}'");

$files = array_merge($files,array_slice($argv,1));

function usage($msg='') {
	global $argv;

	if(!empty($msg)) echo "$msg\n";
	echo <<<EOB
Usage: {$argv[0]} [options] [files...]
  -f <filename>   A file containing a list of PHP files to be analyzed
  -q              Quick mode - doesn't recurse into all function calls
  -b              Check for potential PHP 5 -> PHP 7 BC issues
  -i              Ignore undeclared functions and classes
  -t              Ignore type errors
  -c              Comma-separated list of classes that require parent::__construct() to be called
  -m <mode>       Output mode: verbose, short, json, csv
  -o <filename>   Output filename
  -p              Show progress bar
  -g [node_name]  Output a graphviz (.gv) file of the class hierarchy
  -a              Dump AST of provides files (for debugging)
  -s              Dump scope tree (for debugging)
  -u              Dump user defined functions (for debugging)
  -h			  This help

EOB;
  exit;
}

function progress(string $msg, float $p) {
	echo "\r$msg ";
	$current = (int)($p * 60);
	$rest = 60 - $current;
	echo str_repeat("\u{25b0}", $current);
	echo str_repeat("\u{25b1}", $rest);
	echo " ".sprintf("% 3d",(int)(100*$p))."%";
}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
