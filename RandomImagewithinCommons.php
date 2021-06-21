<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'Random Image within Commons',
	'descriptionmsg' => 'riwc-desc',
	'version'        => '2.2 beta build 20140830',
	'author'         => '[http://www.mediawiki.org/wiki/User:Starwhooper Thiemo Schuff]',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:RandomImagewithinCommons',
	'license-name'  => 'cc-by-sa-de 4.0',
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
	$dbr = wfGetDB( DB_SLAVE );
	
	while ($output == NULL){

		//Get IDs of forbitten files
		$res = $dbr->select('categorylinks', array('cl_from'), 'cl_to = "ricw_blacklist" and cl_type = "file"', $fname = 'Database::select',array());
		foreach($res as $row) $forbitten_cl_from = 'page_id = '.$row->cl_from.' or';
		$forbitten_cl_from = substr($blacklist,0,-3);

		//Get Filename of forbitten files
		$res = $dbr->select('page', array('page_title'), $forbitten_cl_from, $fname = 'Database::select',array());
		foreach($res as $row) $conditions[] = 'il_to != "'.$row->page_title.'"';
		
		//Get Imagename
		$res = $dbr->select('imagelinks', array( 'il_to' ), $conditions, $fname = 'Database::select', array('LIMIT' => 1, 'GROUP BY' => 'il_to', 'ORDER BY' => 'RAND()'));
		foreach($res as $row) {
			$imagename = $row->il_to;
			break;
		}
		
		//check if imagefilesize good enought
		if(wfFindFile($imagename)->mInfo['size'] <= 1000) continue;;
		
		//Get Article IDs with the image
		$res = $dbr->select('imagelinks', array('il_from'), 'il_to = "'.$imagename.'"', $fname = 'Database::select', array());
		$articlelistconditions = '(';
		foreach($res as $row) $articlelistconditions .= 'page_id = '.$row->il_from.' or ';
		$articlelistconditions = substr($articlelistconditions,0,-4) . ') and page_namespace = 0';
		
		//Get Article with the Image
		$res = $dbr->select('page', array( 'page_title' ), $articlelistconditions, $fname = 'Database::select', array());
		foreach($res as $row) $article['title'] .= '[['.str_replace('_',' ',$row->page_title).']] & ';		
		$article['title'] = substr($article['title'],0,-3);		

		//Prompt Tag
		if (!isset($wgriwc['size'])) $wgriwc['size'] = '200px';
		$output = $parser->recursiveTagParse( '<div>[[File:'.$imagename.'|'.$wgriwc['size'].']]<br /> '.wfMessage('riwc-fromarticle').': '.$article['title'].'</div>');
		
	}
	return 	$output;
}
