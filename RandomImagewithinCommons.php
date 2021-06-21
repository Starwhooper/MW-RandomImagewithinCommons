<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'Random Image within Commons',
	'descriptionmsg' => 'riwc-desc',
	'version'        => '2.1 beta build 20140403',
	'author'         => '[http://www.mediawiki.org/wiki/User:Starwhooper Thiemo Schuff]',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:RandomImagewithinCommons',
	'license-name'  => 'cc-by-sa-de 3.0',
);

$wgExtensionMessagesFiles['riwc'] = dirname(__FILE__) . '/RandomImagewithinCommons.i18n.php';
$wgExtensionFunctions[] = 'wfRandomImageFunction';
 
function wfRandomImageFunction() {
	global $wgParser;
	$wgParser->setHook('riwc', 'wfRandomImagewithinsCommons');
}
 
function wfRandomImagewithinsCommons($input, array $args, Parser $parser, PPFrame $frame) {
	global $wgriwc;
	$parser->disableCache();
	$output = NULL;
	$imagefound = false;
	while ($output == NULL){

		//Get Image
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select('imagelinks', array( 'il_to','il_from' ), array(), $fname = 'Database::select', $options = array());
		foreach($res as $row) $images[] = array('file' => $row->il_to, 'articleid' => $row->il_from);
		if( count( $images ) > 1 ) $image = $images[ array_rand( $images, 1 ) ];
		if(isset($wgriwc['blacklist'])) if(in_array($image['file'], $wgriwc['blacklist'])) continue;;
		if(wfFindFile($image['file'])->mInfo['size'] <= 1000) continue;;
	
		//Get Article with the Image
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select('page', array( 'page_title' ), array('page_id = '.$image['articleid'], 'page_namespace = 0'), $fname = 'Database::select', $options = array('LIMIT' => '1'));
		foreach($res as $row) $article['title'] = str_replace('_',' ',$row->page_title);
		if(strlen($article['title']) <= 1) continue;
		
		//Prompt Tag
		if (!isset($wgriwc['size'])) $wgriwc['size'] = '200px';
		$output = $parser->recursiveTagParse( '<div>[[File:'.$image['file'].'|'.$wgriwc['size'].']]<br /> '.wfMessage('riwc-fromarticle').': [['.$article['title'].']]<div>');
		
	}
	return 	$output;
}
