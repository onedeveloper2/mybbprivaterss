<?php
/**
 * onedeveloper2
 *
 * Website: https://github.com/onedeveloper2/mybbprivaterss
 * License: just remember me
 *
 */

define("IN_MYBB", 1);
define("IGNORE_CLEAN_VARS", "fid");
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'rssfeed.php');

require_once "./global.php";
require_once MYBB_ROOT."inc/datahandler.php";
require_once MYBB_ROOT."inc/datahandlers/user.php";
require_once MYBB_ROOT."inc/class_feedgeneration.php";
require_once MYBB_ROOT."inc/class_parser.php";
$feedgenerator = new FeedGenerator();
$parser = new postParser;

// Find out the thread limit.
if($mybb->get_input('portal') && $mybb->settings['portal'] != 0)
{
	$thread_limit = $mybb->settings['portal_numannouncements'];
}
else
{
	$thread_limit = $mybb->get_input('limit', MyBB::INPUT_INT);
}

if($thread_limit > 50)
{
	$thread_limit = 50;
}
else if(!$thread_limit || $thread_limit < 0)
{
	$thread_limit = 50;
}

// verify user name and existence
$user = null;
if($mybb->get_input('key')) {
	$key = $mybb->get_input('key');
	$success = false;

	if( preg_match("/^[a-zA-Z0-9]+$/", $key) ) {
		$query = $db->simple_select("rss_keys", "uid", "hash='" . $key . "'");
		$res = $db->fetch_array($query);

		if($res != null) {
			$user = get_user($res['uid']);
			$success = true;
		}
	}

	// User name does not exists
	if($success == false) {
		$feedgenerator->output_feed();
		return;
	}
} else {
	// gen empty RSS
	$feedgenerator->output_feed();
	return;
}

// Set the feed type.
$feedgenerator->set_feed_format($mybb->get_input('type'));

// Set the channel header.
$channel = array(
	"title" => "Subscriptions feed",
	"link" => $mybb->settings['bburl']."/rssfeed.php?key=".$key,
	"date" => TIME_NOW,
	"description" => "Latest news",
);
$feedgenerator->set_channel($channel);

// Read the subscribtions list
$Subscriptions = array('forums' => null, 'threads' => null);

// get forums
$query = $db->simple_select("forumsubscriptions", "fid", "uid='" . $user['uid'] . "'", array());
while ($Fsubs = $db->fetch_array($query)) {
	$Subscriptions['forums'][] = $Fsubs['fid'];
}

// get threads
$query = $db->simple_select("threadsubscriptions", "tid", "uid='" . $user['uid'] . "'", array());
while ($Tsubs = $db->fetch_array($query)) {
	$Subscriptions['threads'][] = $Tsubs['tid'];
}

if( count($Subscriptions['threads']) > 0 &&  count($Subscriptions['forums']) > 0) {
	$condition = "fid IN (" . implode(",", $Subscriptions['forums']) . ") OR tid IN (" . implode(",", $Subscriptions['threads']) . ")";
} else if( count($Subscriptions['threads']) == 0 && count($Subscriptions['forums']) > 0) { // only forums

	$condition = "fid IN (" . implode(",", $Subscriptions['forums']) . ")";

} elseif ( count($Subscriptions['threads'])  > 0 && count($Subscriptions['forums']) == 0) { // only threads
	$condition = "tid IN (" . implode(",", $Subscriptions['threads']) . ")";
} else { // no subscriptions?
	$feedgenerator->output_feed();
	return;
}

// select subscriptions
$query = $db->simple_select("posts", "pid,tid,replyto,fid,subject,username,dateline,message", $condition,
							array('order_by' => 'dateline', 'order_dir' => 'DESC', 'limit' => $thread_limit));

// Parser options
$parser_options = array(
		"allow_html" => 1,
		"allow_mycode" => 1,
		"allow_smilies" => 1,
		"allow_imgcode" => 1,
		"allow_videocode" => 1,
		"filter_badwords" => 1,
		"filter_cdata" => 1
);

// Generate the feed
while ($post = $db->fetch_array($query)) {

		$thread = get_thread($post['tid']);
		$message = $parser->parse_message($post['message'], $parser_options);

		$author = get_user_by_username($post['username'], array('fields' => array('username', 'email')));

		if($post['replyto'] != 0)
			$items[$post['pid']]["title"] = "RE: " . $thread['subject'];
		else
			$items[$post['pid']]["title"] = $thread['subject'];

		$items[$post['pid']]["link"] = $mybb->settings['bburl'].'/'.get_thread_link($thread['tid'])."&amp;pid=". $post['pid'] .  "#pid" . $post['pid'];
		$items[$post['pid']]['description'] =  $message;
		$items[$post['pid']]['date'] = $post['dateline'];
		$items[$post['pid']]['author'] = $author['email'];
		$feedgenerator->add_item($items[$post['pid']]);
}

//get output
$feedgenerator->output_feed();
