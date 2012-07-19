<?php
function MyMail( $message ) {
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "MyMail()";
		Logger();
	}
  	
	global $MyMailer;
	global $logger;

	$sites = array();
	$dest_lists = array();
	$orig_to = $message->getTo();
	
	foreach( $orig_to as $email => $name ) {
		$site = $GLOBALS['gSite'];
		$sites[ $site ] = 1;
		$dest_lists[ $site ][ $email ] = $name;
	}

		
	foreach( array_keys( $sites ) as $site )
	{
		foreach( $dest_lists[$site] as $email => $name ) {
			if( empty( $name ) ) {
				$message->addTo( $email );
			} else {
				$message->addTo( $email, $name );
			}
		}
		$login_required = 0;
		switch( $site ) {
			case( "local" ):
				$idx = 0;
				$mail_server = "localhost";
				$port = 25;
				break;
		}

		if( empty( $MyMailer[$idx] ) )
		{		
			if( $login_required > 0 )
			{
				$transport = Swift_SmtpTransport::newInstance($mail_server,$port);
				$transport->setUsername( $username );
				$transport->setPassword( $password );
			} else {
				$transport =Swift_SmtpTransport::newInstance($mail_server,$port);
			}
			$MyMailer[$idx] = Swift_Mailer::newInstance( $transport );
			$MyMailer[$idx]->registerPlugin(new Swift_Plugins_AntiFloodPlugin(100,30));
			
		}
		if( ! empty($debug) ) {
			$logger = new Swift_Plugins_Loggers_EchoLogger();
			$MyMailer[$idx]->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));
		}
				
		$result = $MyMailer[$idx]->send($message,$failures);
				
		if( !empty($debug) ) {
			echo $logger->dump();	
		}

		if( ! $result ) 
		{
			if( !empty($debug) ) echo "error<br>";
			if( ! empty( $failures ) ) {
				$text = array();
				foreach( $failures as $key => $val ) {
					$text[] = sprintf( "%s -> %s", $key, $val );	
				}
				$body = join( "<br>", $text );
				$msg = Swift_Message::newInstance();
				$msg->setTo(array( $GLOBALS['gMailAdmin'] => 'Author' ) );
				$msg->setSubject('Email failures from ' . $GLOBALS['mysql_dbname'] );
				$msg->setBody($body,'text/html');
				$msg->setFrom( array('support@tarbut.com' => 'Author') );
				$gNumSent = $MyMailer[$idx]->send($msg);
			}
		} else {
			if( !empty($debug) ) echo "sent ok<br>";
		}
	}
	
	if( $gTrace ) array_pop( $gFunction );
}
?>
