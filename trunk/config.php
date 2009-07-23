<?php
/* Configuration file for LionWiki. Changing of first one or two lines should be fine for most users */

$WIKI_TITLE = "My new wiki"; // name of the site
$PASSWORD = ""; // if left blank, no password is required to edit. Consider also $PASSWORD_MD5 below
// More secure way to use password protection, just insert MD5 hash into $PASSWORD_MD5
// if not empty, $PASSWORD is ignored and $PASSWORD_MD5 is used instead
$PASSWORD_MD5 = "";

$TEMPLATE = "templates/dandelion.html"; // presentation template

$USE_AUTOLANG = true; // should we try to detect language from browser?
$LANG = "en"; // language code you want to use, used only when $USE_AUTOLANG = false

$PROTECTED_READ = false; // if true, you need to fill password for reading pages too

$NO_HTML = false; // XSS protection, meaningful only when password protection is enabled