<?php


ini_set( 'display_errors', 'On' );

ini_set( 'xdebug.var_display_max_depth', 5 );
ini_set( 'xdebug.var_display_max_children', 9999999 );
ini_set( 'xdebug.var_display_max_data', 999999 );

include_once __DIR__ . '/vendor/autoload.php';

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

$url = $_GET['url'] ? $_GET['url']  : 'https://www.amazon.co.uk/Winning-Moves-29612-Trivial-Pursuit/dp/B075716WLM/';

$client = new Client();

function clean( $str ) {
    $enc = mb_detect_encoding($str);
    if ( $enc != "UTF-8" ){
        $str = iconv( 'utf-8', 'us-ascii//TRANSLIT', $str );
    }
	$str = str_replace( "&nbsp;", " ", $str );
	$str = preg_replace( '/\s+/', ' ', $str );
	$str = trim( $str );

	return $str;
}

$products = [];

try{
	$crawler = $client->request( 'GET', $url );
	$title = $crawler->filter( '#productTitle' )->text();
	$price = $crawler->filter( '#cerberus-data-metrics' )->extract( array( 'data-asin-price' ) );
	$description = $crawler->filter( '#feature-bullets ul' )->text();
	$asin = $crawler->filter( '#ASIN' )->extract( array( 'value' ) );
	$images = $crawler->filter( '#landingImage' )->extract( array( 'data-a-dynamic-image' ) );
	$price = reset($price);
	$asin = reset($asin);
	$images = reset($images);
	
	if ( json_decode($images) ){
		$images = json_decode($images, true);
		$images = array_keys($images);
	}
	
	$specifications = [];
	
	$credit_scores = $crawler->filter( '#prodDetails table tr' )->each( function ( $node ) use ( &$specifications ) {
		$key = $node->filter( 'td.label' )->count() > 0 ? trim( $node->filter( 'td.label' )->text() ) : '';
		$value = $node->filter( 'td.value' )->count() > 0 ?  ( $node->filter( 'td.value' )->text() ) : '';
		
		if ( $node->filter( 'td.label' )->count() > 0 ){
			$specifications[] = [
				'name' => clean($key),	
				'value' => clean($value)	
			];
		}
	} );
	
	$products[] = [
		'title' => clean($title),	
		'price' => clean($price),	
		'description' => clean($description),	
		'asin' => clean($asin),	
		'specifications' => $specifications,	
		'images' => $images,	
	];
} catch(\Exception $e){
	
}

echo json_encode($products);
exit();