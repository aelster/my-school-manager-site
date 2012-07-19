<?php
function OpenDb() {
	include( 'globals.php');
	if( $gTrace ) {
		$gFunction[] = "OpenDb()";
		Logger();
	}
	
	$host = $gMysqlHost;
	$user = $gMysqlUser;
	$pass = $gMysqlPass;
	$dbname = $gMysqlDbname;
	if( ! empty( $gMysqlSuffix ) ) {
		$dbname .= "_" . $gMysqlSuffix;
	}
	
	$num_args = func_num_args();

	for( $i = 0; $i < $num_args; $i++ ) {
		if( $i == 0 ) $host = func_get_arg( $i );
		if( $i == 1 ) $user = func_get_arg( $i );
		if( $i == 2 ) $pass = func_get_arg( $i );
		if( $i == 3 ) $dbname = func_get_arg( $i );
	}
	
	$db = mysql_connect( $host, $user, $pass, true );
	if( ! $db )
	{
		die( "Could not connect to $host" . mysql_error() );
	}
  
	$stat = mysql_select_db( $dbname, $db );
	if( ! $stat )
	{
		die( "Can't select database [$dbname]: " . mysql_error() );
	}
	
	if( $gTrace ) array_pop( $gFunction );
	
	return $db;
}
?>