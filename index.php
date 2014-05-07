<?php
	include( 'Twitchoembed.class.php' );

	$url = $_GET[ 'url' ];
	$maxWidth = 0 + $_GET[ 'maxwidth' ];
	$maxHeight = 0 + $_GET[ 'maxheight' ];
	$format = $_GET[ 'format' ];

	$oembed = new Twitchoembed( $url );

	$oembed->setMaxWidth( $maxWidth );
	$oembed->setMaxHeight( $maxHeight );
	
	if( strlen( $format ) > 0 ) :
		$oembed->setFormat( $format );
	endif;

	$oembed->getOembed();

	die();
?>