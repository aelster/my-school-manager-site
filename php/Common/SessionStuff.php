<?php
function SessionStuff( $cmd )
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "SessionStuff($cmd)";
		if( $gAction != 'Logout') Logger();
	}

   if( $gFeature == 'eedge' ) {
      $gDb = $gDbEEdge;
   } elseif( $gFeature == 'control' ) {
      $gDb = $gDbControl;
   }
	
	switch( $cmd )
	{
		case( 'start' ):
			session_start();
			if( empty( $_SESSION['userid'] ) ) {
				if( $gTrace ) Logger( "  Starting new session" );
			} else {
				if( $gTrace ) Logger( "  Using existing session" );
				UserManager( 'load', $_SESSION['userid'] );
			}
			break;
			
		case( 'display' ):
			if( $gDebug == 0 ) return;
			foreach( $_COOKIE as $key => $val )
			{
				echo sprintf( "COOKIE[%s]=%s<br>", $key, $val );
			}
	
			if(  isset( $_SESSION ) )
			{
				echo sprintf( "session_name: %s<br>", session_name() );
				foreach( $_SESSION as $key => $val )
				{
					echo sprintf( "pre-SESSION[%s]=%s<br>", $key, $val );
				}
			}
			if(  isset( $_SESSION ) )
			{
				foreach( $_SESSION as $key => $val )
				{
					echo sprintf( "post-SESSION[%s]=%s<br>", $key, $val );
				}
			}
			break;
		
		case( 'logout' ):
			unset( $text );
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'logout'";
			$text[] = sprintf( "userid = '%d'", $GLOBALS['gUserId'] );
			$text[] = sprintf( "item = 'session_id: %s'", session_id() );
			$query = join( ",", $text );
			DoQuery( $query, $gDbControl );
			foreach( array( 'authenticated', 'userid', 'username' ) as $key ) {
				unset( $_SESSION[ $key ] );
			}
			$_SESSION = array();
			unset( $_COOKIE[session_name()] );
			if (isset($_COOKIE[session_name()]))
			{
				setcookie(session_name(), '', time()-42000, '/');
			}
			session_destroy();
			$gUserVerified = 0;
			break;
	}
	if( $gTrace ) array_pop( $gFunction);
}
?>