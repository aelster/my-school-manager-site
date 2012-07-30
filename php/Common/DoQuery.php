<?php
function DoQuery()
{
	include( 'globals.php' );
	
	$num_args = func_num_args();
	$query = func_get_arg( 0 );
	$db = ( $num_args == 1 ) ? $gDb : func_get_arg( 1 );
	
	if( $gDebug ) $dmsg = "&nbsp;&nbsp;&nbsp;&nbsp;DoQuery($gDb): $query";
	
	$gResult = mysql_query( $query, $db );
	if( mysql_errno( $db ) != 0 )
	{
		if( ! $db ) { echo "  query: $query<br>\n"; }
		echo "  result: " . mysql_error( $db ) . "<br>\n";
		echo "I'm sorry but something unexpected occurred.  Please send all details<br>";
		echo "of what you were doing and any error messages to $gSupport<br>";
	}
	else
	{
		if( preg_match( "/^select/i", $query ) )
		{
			$gNumRows = mysql_num_rows( $gResult );
		}
		else
		{
			$gNumRows = mysql_affected_rows( $db );
		}
		if( $gDebug ) $dmsg .= sprintf( ", # rows: %d", $gNumRows );
	}
	
 	if( $gDebug ) Logger( $dmsg );
}
?>