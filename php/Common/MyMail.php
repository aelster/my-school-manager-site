<?php
function MyMail( $message ) {
	include( "globals.php" );
	require_once('local-mail-setup.php');
	
	if( $gTrace ) {
		$gFunction[] = "MyMail()";
		Logger();
	}

	global $MyMailer;  	
	global $logger;

	$debug = 0;
	$sites = array();
	$dest_lists = array();
	$orig_to = $message->getTo();
	
	foreach( $orig_to as $email => $name ) {
		$site = preg_match( "/$gMailRemapFrom/", $email ) ? $gMailRemapTo : $gMailSite;
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
		if( ! empty( $gMailSetup[$site]['username'] ) ) {
			$username = $gMailSetup[$site]['username'];
			$password = $gMailSetup[$site]['password'];
			$login_required = 1;
		}
		Logger("gSite: $gMailSite, site: $site");
		$idx = $gMailSetup[$site]['idx'];
		$mail_server = $gMailSetup[$site]['server'];
		$port = $gMailSetup[$site]['port'];
		
		if( empty( $MyMailer[$idx] ) )
		{		
			if( $login_required )
			{
				$transport = Swift_SmtpTransport::newInstance($mail_server,$port);
				$transport->setUsername( $username );
				$transport->setPassword( $password );
				$transport->setEncryption('ssl');
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
				$msg->setTo(array( $gSupport => 'Support' ) );
				$msg->setSubject('Email failures');
				$msg->setBody($body,'text/html');
				$msg->setFrom( array($gSupport => 'Support') );
				$gNumSent = $MyMailer[$idx]->send($msg);
			}
		} else {
			if( !empty($debug) ) echo "sent ok<br>";
		}
	}
	
	if( $gTrace ) array_pop( $gFunction );
}
?>
