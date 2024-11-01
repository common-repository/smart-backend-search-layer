<?php
class Guaven_SBSL_Bitap {

	public function match(
		$needle,
		$haystack,
		$threshold = null
	) {
		$needleLen    = strlen($needle);
		$haystackLen  = strlen($haystack);
		$patternMask  = [];
		$row          = [];
		$threshold    = $threshold === null ? floor($needleLen * 0.25) : (int) abs($threshold);
		// Empty needle or exact match
		if ( $needle === '' || $needle === $haystack ) {
			return true;
		}
		// Empty hay stack
		if ( $haystack === '' ) {
			return false;
		}
		// Initialise table
		for ( $i = 0; $i <= $threshold + 1; $i++ ) {
			$row[$i] = 1;
		}
		// Initialise pattern mask (255 gives us the full extended ASCII range)
		for ( $i = 0; $i < 256; $i++ ) {
			$patternMask[$i] = 0;
		}

		for ( $i = 0; $i < $needleLen; ++$i ) {
			$patternMask[ord($needle[$i])] |= 1 << $i;
		}
		// Loop through hay-stack chars
		for ( $i = 0; $i < $haystackLen; $i++ ) {
			$oldCol     = 0;
			$nextOldCol = 0;
			// Test for each level of errors
			for ( $d = 0; $d <= $threshold; ++$d ) {
				$replace = ($oldCol | ($row[$d] & $patternMask[ord($haystack[$i])])) << 1;
				$insert  = $oldCol | (($row[$d] & $patternMask[ord($haystack[$i])]) << 1);
				$delete  = ($nextOldCol | ($row[$d] & $patternMask[ord($haystack[$i])])) << 1;
				$oldCol     = $row[$d];
				$row[$d]    = $replace | $insert | $delete | 1;
				$nextOldCol = $row[$d];
			}
			// If we've got a match, we're done
			if ( 0 < ($row[$threshold] & (1 << $needleLen)) ) {
				return true;
			}
		}
		return false;
	}

	public function grep(
		$needle,
		$haystack,
		$threshold = null
	) {
		$results = [];
		foreach ( $haystack as $k => $v ) {
			if ( $this->match($needle, (string) $v, $threshold) ) {
				$results[$k] = $v;
			}
		}
		return $results;
	}
}

add_action('init',function(){

	function Guaven_SBSL_Character_Remover($str){
		$str=strtolower(str_replace(array(
		 "'",
		 "/",
		 '"',
		 "_"
		), "", stripslashes($str)));

		$ignorearr = explode(",", get_option('guaven_sbsl_index_exclude'));
		if (!empty($ignorearr)) {
			foreach ($ignorearr as $key => $value) {
				$str=str_replace(' '.$value.' ', "", $str);
				$str=str_replace(' '.$value.'.', "", $str);
			}
		}
		return $str;
	}

	$active_plugins=implode(",",get_option('active_plugins'));
  if (strpos($active_plugins,'backend-search-layer-pro')!==false) return;

	if (defined('Guaven_SBSL_PRO_DIR')) return;

	function Guaven_SBSL_Offsetter(){
		return 1000;
	}


	function Guaven_SBSL_Translitter($str)
	{
			if (function_exists('mb_strtolower'))
	    $str                = mb_strtolower($str);
			else {
				$str                = strtolower($str);
			}
	    return $str;
	}
	}
,9);
