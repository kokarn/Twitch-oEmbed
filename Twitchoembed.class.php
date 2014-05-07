<?php

	class Twitchoembed {

		private $twitchApiBase = 'https://api.twitch.tv/kraken';
		private $twitchVideoBaseApi = '/videos/';
		private $twitchStreamBaseApi = '/streams/';
		private $streamEmbedCode = "
			<object type='application/x-shockwave-flash' height='378' width='620' id='live_embed_player_flash' data='http://www.twitch.tv/widgets/live_embed_player.swf?channel=LIVE_CHANNEL' bgcolor='#000000'>
				<param name='allowFullScreen' value='true'>
				<param name='allowScriptAccess' value='always'>
				<param name='allowNetworking' value='all'>
				<param name='movie' value='http://www.twitch.tv/widgets/live_embed_player.swf'>
				<param name='flashvars' value='hostname=www.twitch.tv&channel=LIVE_CHANNEL&auto_play=false&start_volume=25'>
			</object>";
		private $archiveEmbedCode = "
			<object type='application/x-shockwave-flash' height='378' width='620' id='clip_embed_player_flash' data='http://www.twitch.tv/widgets/archive_embed_player.swf' bgcolor='#000000'>
				<param name='movie' value='http://www.twitch.tv/widgets/archive_embed_player.swf'>
				<param name='allowFullScreen' value='true'>
				<param name='allowScriptAccess' value='always'>
				<param name='allowNetworking' value='all'>
				<param name='flashvars' value='channel=LIVE_CHANNEL&auto_play=true&start_volume=25&archive_id=VIDEO_ID'>
			</object>";
        private $chapterEmbedCode = "
			<object type='application/x-shockwave-flash' height='378' width='620' id='clip_embed_player_flash' data='http://www.twitch.tv/widgets/archive_embed_player.swf' bgcolor='#000000'>
				<param name='movie' value='http://www.twitch.tv/widgets/archive_embed_player.swf'>
				<param name='allowFullScreen' value='true'>
				<param name='allowScriptAccess' value='always'>
				<param name='allowNetworking' value='all'>
				<param name='flashvars' value='channel=LIVE_CHANNEL&auto_play=true&start_volume=25&chapter_id=VIDEO_ID'>
			</object>";
        private $videoEmbedCode;
		private $defaultVideoWidth = 400;
		private $defaultVideoHeight = 300;

		private $defaultStreamWidth = 620;
		private $defaultStreamHeight = 378;

		private $url;
		private $maxWidth;
		private $maxHeight;
		private $format = 'json';

		private $videoId;
		private $videoUrl;
		private $videoJsonData;
		private $videoData;

		private $streamName;
		private $streamUrl;
		private $streamJsonData;
		private $streamData;

		private $embedType;

		private $response = array(
			'type' => 'video',
			'version' => '1.0',
			'provider_name' => '** SET PROVIDER NAME HERE **',
			'provider_url' => '** SET URL HERE **',
			'cache_age' => 60
		);

		private $jsonResponse;

		public function __construct( $url ){
			$this->setUrl( $url );
		}

		public function setUrl( $url ){
			$this->url = $url;
		}

		public function setMaxWidth( $maxWidth ) {
			$this->maxWidth = $maxWidth;
		}

		public function setMaxHeight( $maxHeight ){
			$this->maxHeight = $maxHeight;
		}

		public function setFormat( $format ){
			$this->format = $format;
		}

		private function getTwitchType(){
			$response = preg_match( '/\/([abc])\//', $this->url, $realMatch );

			if( $response === 1 ) :
				$this->embedType = 'recording';
                if( $realMatch[ 1 ] == 'c' ) :
                    $this->videoEmbedCode = $this->chapterEmbedCode;
                else:
                    $this->videoEmbedCode = $this->archiveEmbedCode;
                endif;
			else :
				$this->embedType = 'stream';
			endif;
		}

		private function getResponse(){
			if( $this->embedType == 'recording' ) :
				$this->getVideoId();
				$this->setVideoDataUrl();
				$this->getVideoData();
				$this->buildVideoResponse();
			else :
				$this->getStreamName();
				$this->setStreamDataUrl();
				$this->getStreamData();
				$this->buildStreamResponse();
			endif;
		}

		private function getStreamName(){
			preg_match( '/twitch\.tv\/([a-z0-9]+)/', $this->url, $matches );
			$this->streamName = $matches[ 1 ];
		}

		private function setStreamDataUrl(){
			$this->streamUrl = $this->twitchApiBase . $this->twitchStreamBaseApi . $this->streamName;
		}

		private function getStreamData(){
			$this->streamJsonData = @file_get_contents( $this->streamUrl );
			if( ! $this->streamJsonData ) :
				header( 'HTTP/1.0 404 Not Found' );
				exit;
			endif;

			$this->streamData = json_decode( $this->streamJsonData );
		}

		private function buildStreamResponse(){
			if( isset( $this->streamData->stream->channel->display_name ) ) :
				$this->response[ 'title' ] = $this->streamData->stream->channel->status;
				$this->response[ 'author' ] = $this->streamData->stream->channel->display_name;
				$this->response[ 'author_url' ] = $this->streamData->stream->channel->url;
				$this->response[ 'thumbnail_width' ] = 630;
				$this->response[ 'thumbnail_height' ] = 473;
				$this->response[ 'thumbnail_url' ] = $this->streamData->stream->preview;
			else :
				$this->response[ 'title' ] = $this->streamName . ' stream';
				$this->response[ 'author' ] = $this->streamName;
				$this->response[ 'author_url' ] = 'http://twitch.tv/' . $this->streamName;
			endif;

			$html = str_replace( 'LIVE_CHANNEL', $this->streamName, $this->streamEmbedCode );
			$this->response[ 'html' ] = $html;

			$this->setWidthHeight();
		}

		private function getVideoId(){
			preg_match( '/([abc]\/\d+)/', $this->url, $matches );
			$this->videoId = str_replace( '/', '', $matches[ 1 ] );
			$this->videoId = str_replace( 'b', 'a', $this->videoId );
		}

		private function setVideoDataUrl(){
			$this->videoUrl = $this->twitchApiBase . $this->twitchVideoBaseApi . $this->videoId;
		}

		private function getVideoData(){
			$this->videoJsonData = @file_get_contents( $this->videoUrl );
			if( ! $this->videoJsonData ) :
				header( 'HTTP/1.0 404 Not Found' );
				exit;
			endif;
			$this->videoData = json_decode( $this->videoJsonData );
		}

		private function buildVideoResponse(){
			$author = substr( $this->videoData->_links->owner, strrpos( $this->videoData->_links->owner, '/' ) + 1 );
			$this->response[ 'title' ] = $this->videoData->title;
			$this->response[ 'author' ] = $author;
			$this->response[ 'author_url' ] = 'http://twitch.tv/' . $author;
			$this->response[ 'thumbnail_url' ] = $this->videoData->preview;
			$this->response[ 'html' ] = $this->getVideoEmbedCode();
			$this->response[ 'thumbnail_width' ] = 320;
			$this->response[ 'thumbnail_height' ] = 240;

			$this->setWidthHeight();
		}

		private function getVideoEmbedCode(){
			$embedCode = str_replace( 'LIVE_CHANNEL', $this->videoData->channel->name, $this->videoEmbedCode );
			$chapterId = preg_replace( '/[^0-9]/', '', $this->videoData->_id );
			$embedCode = str_replace( 'VIDEO_ID', $chapterId, $embedCode );

			return $embedCode;
		}

		private function setWidthHeight(){
			if( $this->embedType == 'recording' ) :
				$defaultWidth = $this->defaultVideoWidth;
				$defaultHeight = $this->defaultVideoHeight;
			else :
				$defaultWidth = $this->defaultStreamWidth;
				$defaultHeight = $this->defaultStreamHeight;
			endif;

			if( $this->maxHeight <= 0 && $this->maxWidth <= 0 ) :
				$this->response[ 'width' ] = $defaultWidth;
				$this->response[ 'height' ] = $defaultHeight;
				return true;
			endif;

			$defaultRatio = $defaultHeight / $defaultWidth;

			if( $this->maxHeight > 0 && $this->maxWidth > 0 ) :
				$ratio = $this->maxHeight / $this->maxWidth;
			elseif( $this->maxHeight > 0 ) :
				$ratio = $this->maxHeight / $defaultWidth;
			elseif( $this->maxWidth > 0 ) :
				$ratio = $defaultHeight / $this->maxWidth;
			endif;

			if( $ratio <= $defaultRatio ) :
				$width = $this->maxWidth;
				$height = $this->maxWidth * $defaultRatio;
			else :
				$height = $this->maxHeight;
				$width = $this->maxHeight / $defaultRatio;
			endif;

			$height = round( $height );
			$width = round( $width );

			$this->response[ 'html' ] = str_replace( "height='" . $defaultHeight . "'", "height='" . $height . "'", $this->response[ 'html' ] );
			$this->response[ 'html' ] = str_replace( "width='" . $defaultWidth . "'", "width='" . $width . "'", $this->response[ 'html' ] );
			$this->response[ 'width' ] = $width;
			$this->response[ 'height' ] = $height;

			return true;

		}

		private function echoJson(){
			header( 'Content-type: application/json' );
			ksort( $this->response );
			$jsonOembed = json_encode( $this->response );
			echo $jsonOembed;
		}

		private function echoXML(){
			header( 'HTTP/1.0 501 Not Implemented' );
		}

		private function echoOembed(){
			if( $this->format == 'json' ) :
				$this->echoJson();
			elseif( $this->format == 'xml' ) :
				$this->echoXML();
			endif;
		}

		public function getOembed(){
			$this->getTwitchType();
			$this->getResponse();
			$this->echoOembed();
		}

	}