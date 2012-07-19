<?php
function Logger () {
	include('globals.php');
	if( func_num_args() > 0 ) {
		$str = func_get_arg(0);
	} else {
		$str = join( '>', $gFunction );
	}
	echo <<<END
<script type="text/javascript">MyDebug('$str');</script>
END;
}
?>