<?php
	$WIKI_TITLE = "My new wiki"; // name of the site
	$PASSWORD = ""; // if left blank, no password is required to edit
	$LANG = "en"; // language code you want to use
	
  $START_PAGE = "Main page"; // Which page should be default (start page)?
  $HELP_PAGE = "Help"; // Which page contains help informations?
  $USE_HISTORY = true; // If you don't want to keep history of pages, change to false
  $HISTORY_COMPRESSION = "gzip"; // possible values: bzip2, gzip and plain
	$PROTECTED_READ = false; // if true, you need to fill password for reading pages too
	$COOKIE_LIFE_WRITE = 365 * 24 * 86400; // lifetime of cookies when password protection applies only to writing
  $COOKIE_LIFE_READ = 4 * 3600; // lifetime of cookies when $PROTECTED_READ = true
?>
