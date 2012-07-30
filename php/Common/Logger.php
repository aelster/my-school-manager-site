<?php
function Logger () {
	include('globals.php');
	if( func_num_args() > 0 ) {
		$str = func_get_arg(0);
	} else {
		$str = join( '>', $gFunction );
	}
	$tstr = str_replace( "'", "\'", $str );
#	echo "<pre>$tstr</pre>";
#	return;
	echo <<<END
<script type="text/javascript">
MyDebug('$tstr');
</script>
END;
}
?>