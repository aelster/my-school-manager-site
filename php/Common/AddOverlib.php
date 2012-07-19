<?php
function AddOverlib()
{
	include('globals.php');
	if( $gTrace ) {
		$gFunction[] = "AddOverlib()";
		Logger();
	}

	echo <<<END
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
END;
}
?>