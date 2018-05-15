<?php
/**
 * onedeveloper2
 *
 * Website: https://github.com/onedeveloper2/mybbprivaterss
 * License: just remember me
 *
 */

if(!defined("IN_MYBB")) {
	die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

$plugins->add_hook('datahandler_login_complete_end', 'gen_rss_key');
$plugins->add_hook('usercp_end', 'insert_rss_key');

function privaterss_info() {

	return array(
			"name"          => "Private RSS feed",
			"description"   => "Allows users to receive RSS feeds from subscriptions",
			"website"       => "https://github.com/onedeveloper2/mybbprivaterss",
			"author"        => "onedeveloper2",
			"authorsite"    => "https://github.com/onedeveloper2",
			"version"       => "1.0",
			"guid"          => "",
			"compatibility" => "18*"
	);
}

function privaterss_activate() {
  global $db;

  // create table
  $query = "CREATE TABLE `mybb_rss_keys` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `uid` INT NOT NULL ,
  `hash` VARCHAR(50) NOT NULL ,
   PRIMARY KEY (`id`)) ENGINE = InnoDB;";

   $result = $db->write_query($query);
}

function privaterss_deactivate() {
  global $db;

	// remove table
  $query = "DROP TABLE mybb_rss_keys";
  $result = $db->write_query();
}

/*
 * generates a new RSS key for a user if he doesn't have one already
*/
function gen_rss_key(&$obj) {
  global $db;
  $uid = $obj->login_data['uid'];

  // check if user has a hash associated
  $query = $db->simple_select("rss_keys", "hash", "uid=" . $uid);
  $res = $db->fetch_array($query);

  //generate users key and save it
  if($res == null) {
        $hash = sha1( uniqid($obj->login_data['username']) );
        $ins = $db->insert_query("rss_keys", array("uid" => $uid, "hash" => $hash));
  }
}

/*
 * Inserts RSS chanel URL to user cp
*/
function insert_rss_key(){
  global $db, $mybb, $rss_key;
  $query = $db->simple_select("rss_keys", "hash", "uid=" . $mybb->user['uid']);
  $res = $db->fetch_array($query);

  $rss_key = "<a href='rssfeed.php?key=". $res['hash'] ."' target='_blank'>" . $res['hash'] . '</a>';
}
