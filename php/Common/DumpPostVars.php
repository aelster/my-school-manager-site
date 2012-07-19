<?php
function DumpPostVars() {
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "DumpPostVars()";
		Logger();
	}

	if( func_num_args() > 0 ) {
		echo func_get_arg(0) . "<br>";
	}

	$dump_server = 0;
	$bars = "---------------------------------------";

	ksort( $_POST );
	$i = 0;
	foreach( $_POST as $var => $val ) {
		if( $i++ == 0 ) Logger($bars);
		if( preg_match( "/password/i", $var ) ) {
			$str = sprintf( "dpv:  %-20s: %s, length: %d", $var, "******", strlen($val) );
			Logger($str);
		} else {
			if( is_array( $val ) ) {
				foreach( $val as $k => $v ) {
					$str = sprintf( "dpav:  %-20s[%s]: %s", $var, $k, $v );
					Logger($str);
				}
			}
			else
			{
				$str = sprintf( "dpv:  %-20s: %s", $var, $val );
				Logger($str);
			}
		}
	}

	$i = 0;
	if( $dump_server > 0 ) {
		if( $i++ == 0 ) Logger($bars);
		foreach( $gServer as $var => $val ) {
			if( $var != "passwd" ) {
				$str = sprintf( "dsv:  %-20s: %s", $var, $val );
			} else {
				$str = sprintf( "dsv:  %-20s: %s", $var, "******" );
			}
			Logger($str);
		}
	}

	$i = 0;
	if( isset( $_SESSION ) ) {
		foreach( $_SESSION as $var => $val ) {
			if( $i++ == 0 ) Logger($bars);
			if( $var != "passwd" ) {
				$str = sprintf( "sess:  %-20s: %s", $var, $val );
			} else {
				$str = sprintf( "sess:  %-20s: %s", $var, "******" );
			}
			Logger($str);
		}
	}
	Logger($bars);
	if( $gTrace ) array_pop( $gFunction );
}
?>