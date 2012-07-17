<?php
function SiteLoad( $library )
{
	$pathArray = explode( PATH_SEPARATOR, get_include_path() );

	$found = 0;
	$i = 0;
	while( $i < count( $pathArray ) && ! $found )
	{
		$path = $pathArray[$i++] . DIRECTORY_SEPARATOR . $library;
		if( file_exists( $path ) )
		{
			$found = 1;
		}
	}
	
	if( $found == 0 )
	{
		printf( "Can't find library %s using path %s", $library, get_include_path() );
		return;
	}

	$d = dir( $path );
	while( false !== ( $file = $d->read()))
	{
		if( preg_match( "/^local_/", $file ) ) {
			continue;
		}
		if( preg_match( "/\.php/", $file ) ) {
			$str = $path . DIRECTORY_SEPARATOR . $file;
			require_once( $str );
			if( preg_match( "/Init.php$/", $file ) ) {
				call_user_func( $library . "Init" );
			}
		}
	}
}
?>
