<?php
	/* LionWiki created by Adam Zivner adam.zivner@gmail.com http://lionwiki.0o.cz
	 Based on WiKiss, http://wikiss.tuxfamily.org, itself based on TigerWiki
	 Licensed under GPL v2, http://www.gnu.org/licenses/gpl-2.0.html */

	// SETTINGS - fallback settings when no config file exists.

	$WIKI_TITLE = "My new wiki"; // name of the site
	$PASSWORD = ""; // if left blank, no password is required to edit. Consider also $PASSWORD_MD5 below
	$USE_AUTOLANG = true; // should we try to detect language from browser?
	$LANG = "en"; // language code you want to use, used only when $USE_AUTOLANG = false
	$TEMPLATE = "template_new.html"; // Page template

	// More secure way to use password protection, just insert MD5 hash into $PASSWORD_MD5
	// if not empty, $PASSWORD is ignored and $PASSWORD_MD5 is used instead
	$PASSWORD_MD5 = "";
	$PROTECTED_READ = false; // if true, you need to fill password for reading pages too
	$HISTORY_COMPRESSION = "gzip"; // possible values: bzip2, gzip and plain
	$NO_HTML = false; // XSS protection, meaningful only when password protection is enabled

	$USE_META = true; // use and create meta data. Small overhead, but edit summary and IP info
	$USE_HISTORY = true; // If you don't want to keep history of pages, change to false

	$START_PAGE = "Main page"; // Which page should be default (start page)?
	$SYNTAX_PAGE = "Syntax reference"; // Which page contains help informations?

	$COOKIE_LIFE_WRITE = 365 * 24 * 86400; // lifetime of cookies when password protection applies only to writing
	$COOKIE_LIFE_READ = 4 * 3600; // lifetime of cookies when $PROTECTED_READ = true
	
	// END OF SETTINGS
	
	$DATE_FORMAT = "Y/m/d H:i";
	
	$LOCAL_HOUR = "0";
	
	$EDIT_SUMMARY_LEN = "128"; // don't play with this!!!
	
	@error_reporting(E_ERROR | E_WARNING | E_PARSE);

	set_magic_quotes_runtime(0); // turn off magic quotes
	
	if(get_magic_quotes_gpc()) { // magic_quotes_gpc can't be turned off
		foreach($_GET as $k => $v) $_GET[$k] = stripslashes($v);
		foreach($_POST as $k => $v) $_POST[$k] = stripslashes($v);
		foreach($_COOKIE as $k => $v) $_COOKIE[$k] = stripslashes($v);
		foreach($_REQUEST as $k => $v) $_REQUEST[$k] = stripslashes($v);
	}
	
	$BASE_DIR = $_GET["basedir"] ? $_GET["basedir"] . "/" : "";
	
	@include("_config.php"); // config file is not required, see settings above

	if(!empty($BASE_DIR))
		@include($BASE_DIR . "_config.php"); // subdomain specific settings
		
	if(empty($PASSWORD_MD5) && !empty($PASSWORD))
		$PASSWORD_MD5 = md5($PASSWORD);

	$WIKI_VERSION = "LionWiki 2.1.6";
	$PAGES_DIR = $BASE_DIR . "pages/";
	$HISTORY_DIR = $BASE_DIR . "history/";
	$PLUGINS_DIR = "plugins/";
	$LANG_DIR = "lang/";
	
	umask(0); // sets default mask

	// some strings may not be translated, in that case, we'll use english translation, which should be always complete

	$T_HOME = "Main page";
	$T_SYNTAX = "Syntax";
	$T_EDIT = "Edit";
	$T_DONE = "Save changes";
	$T_PREVIEW = "Preview";
	$T_SEARCH = "Search";
	$T_SEARCH_RESULTS = "Search results";
	$T_LIST_OF_ALL_PAGES = "List of all pages";
	$T_RECENT_CHANGES = "Recent changes";
	$T_LAST_CHANGED = "Last changed";
	$T_HISTORY = "History";
	$T_NO_HISTORY = "No history.";
	$T_RESTORE = "Restore";
	$T_REV_DIFF = "<b>Difference between revisions from {REVISION1} and {REVISION2}.</b>";
	$T_REVISION = "'''This revision is from {TIME}. You can {RESTORE} it.'''\n\n";
	$T_PASSWORD = "Password";
	$T_EDIT_SUMMARY = "Edit summary";
	$T_ERASE_COOKIE = "Erase cookies";
	$T_WIKI_CODE = "Wiki code";
	$T_MOVE_TEXT = "New name";
	$T_MOVE = "Move";
	$T_DIFF = "diff";
	$T_CREATE_PAGE = "Create page";
	$T_PROTECTED_READ = "You need to enter password to view content of site: ";
	$T_EDIT_CONFLICT = "Edit conflict: somebody saved this page after you started editing. It is strongly encouraged to see last {DIFF} before saving it. After reviewing and possibly merging changes, you can save page by clicking on save button.";
	$TE_WRONG_PASSWORD = "Password is incorrect.";
	
	// Default character set for auto content header
	@ini_set("default_charset", "UTF-8");
	header("Content-type: text/html; charset=UTF-8");
	
	// consider only first language, don't consider language variant (like en-us or pt-br)
	if($USE_AUTOLANG)
		$LANG = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

	$LANG = !empty($_COOKIE["LW_LANG"]) ? $_COOKIE["LW_LANG"] : $LANG;

	if(!empty($_GET["lang"])) {
		$LANG = $_GET["lang"];
		setcookie('LW_LANG', $LANG, time() + 365 * 86400);
	}
	
	if($LANG != "en" && @file_exists($LANG_DIR . $LANG . ".php"))
		@include $LANG_DIR . $LANG . ".php";
	else
		$LANG = "en";
	
	// Installation - create directories pages and history, if possible
	
	if(!file_exists($PAGES_DIR) && !mkdir(rtrim($PAGES_DIR, "/"))) 
		die("Can't create directory $PAGES_DIR. Please create $PAGES_DIR and $HISTORY_DIR with 0777 rights.");
			
	if($USE_HISTORY && !file_exists($HISTORY_DIR) && !mkdir(rtrim($HISTORY_DIR, "/")))
		die("Can't create directory $HISTORY_DIR. Please create $HISTORY_DIR with 0777 rights or turn off history feature in config file. Turning off history now.");
	
	if($_GET["erasecookie"])	// remove cookie without reloading
		foreach($_COOKIE as $key => $value)
			if(!strcmp(substr($key, 0, 3), "LW_")) {
				setcookie($key);
				unset($_COOKIE[$key]);
			}

	$plugins = array();
	$plugin_files = array();

	$plugin_saveok = true; // is OK to save page changes (from plugins)

	// We load common plugins for all subsites and then just for this subsite.

	if(!empty($BASE_DIR) && ($dir = @opendir($BASE_DIR . $PLUGINS_DIR)))
		while($file = readdir($dir))
			$plugin_files[] = $BASE_DIR . $PLUGINS_DIR . $file;

	if($dir = @opendir($PLUGINS_DIR)) // common plugins
		while($file = readdir($dir))
			if(!in_array($PLUGINS_DIR . $BASE_DIR . $file, $plugin_files)) // we don't want to load plugin twice
				$plugin_files[] = $PLUGINS_DIR . $file;

	foreach($plugin_files as $pfile)
		if(preg_match("/^.*wkp_(.+)\.php$/", $pfile, $matches) > 0) {
			require $pfile;
			$plugins[] = new $matches[1]();
		}
		
	plugin_call_method("pluginsLoaded");

	// list of variables for UTF-8 conversion and export
	$req_conv = array("action", "query", "sc", "content", "page", "moveto", "restore", "f1", "f2", "error", "time", "esum", "preview", "last_changed", "econfprot", "gtime");

	if(extension_loaded("mbstring")) { // Conversion to UTF-8
		@ini_set("mbstring.language", "Neutral"); 
		@ini_set("mbstring.internal_encoding", "UTF-8"); 
		@ini_set("mbstring.http_output", "UTF-8"); 
		@ini_set("mbstring.detect_order", "UTF-8,ISO8859-2,ISO-8859-1");
		@ini_set("mbstring.func_overload", MB_OVERLOAD_STRING);

		foreach($req_conv as $req_key)
			$_REQUEST[$req_key] = mb_convert_encoding($_REQUEST[$req_key], "UTF-8", mb_detect_encoding($_REQUEST[$req_key]));
	} // if mbstring is not supported, nothing bad should happen
	
	foreach($req_conv as $req) // export variables to main namespace
		$$req = trim($_REQUEST[$req]);

	$editable = $page || empty($action); // should be on the page "edit" link?
	
	if(!empty($preview)) {
		$action = "edit";
		$CON = $content;
	}

	// setting $PAGE_TITLE
	if($page || empty($action)) {
		$page = $page_nolang = $TITLE = $page ? $page : $START_PAGE;
		
		if($action == "" && file_exists($PAGES_DIR . $page . ".$LANG.txt")) // language variant
			$page = $TITLE = $page_nolang . "." . $LANG;
		else if(!file_exists($PAGES_DIR . $page . ".txt") && $action != "save")
			$action = "edit"; // create page if it doesn't exist
			
		if(!empty($preview))
			$TITLE = $T_PREVIEW . ": " . $page;
	}
	else if($action == "search")
		$TITLE = empty($query) ? $T_LIST_OF_ALL_PAGES : "$T_SEARCH_RESULTS $query";
	elseif($action == "recent")
		$TITLE = $T_RECENT_CHANGES;

	if(version_compare(phpversion(), "5.1.0") >= 0)
		@date_default_timezone_set($TIME_ZONE);
		
	$datetw = date("Y/m/d H:i", time() + $LOCAL_HOUR * 3600);
	
	// does user need password to read content of site. If yes, ask for it.
	if(!authentified() && $PROTECTED_READ) {
		$CON = "<form action=\"\" method=\"post\"><p>$T_PROTECTED_READ <input id=\"passworInput\" type=\"password\" name=\"sc\" /> <input class=\"submit\" type=\"submit\" /></p></form>";
	
		$action = "view-html";
	}
	else if($action == "save" && authentified()) { // do we have page to save?
		$LAST_CHANGED_TIMESTAMP = @filemtime($PAGES_DIR . $page . ".txt");
		
		if($last_changed < $LAST_CHANGED_TIMESTAMP && $econfprot) {
			$action = "edit";
			$error = str_replace("{DIFF}", "<a href=\"?page=".urlencode($page)."&amp;action=diff\">$T_DIFF</a>", $T_EDIT_CONFLICT);
		}
		else if(!plugin_call_method("writingPage") || $plugin_saveok) { // are plugins happy with page? (no - spam, etc)
			if(!$file = @fopen($PAGES_DIR . $page . ".txt", "w"))
				die("Could not write page $PAGES_DIR$page.txt!");

			fputs($file, $content);
			fclose($file);

			if($USE_HISTORY) { // let's archive previous revision
				$complete_dir = $HISTORY_DIR . $page;

				if(!is_dir($complete_dir))
					mkdir($complete_dir);

				$rightnow = date("Ymd-Hi-s", time() + $LOCAL_HOUR * 3600);

				$filename = $complete_dir . "/" . $rightnow . ".bak";

				if(!$bak = @lwopen($filename, "w"))
					die("Could not write backup $filename of page!");

				lwwrite($bak, $content);
				lwclose($bak);

				if($USE_META)
					$es = fopen($complete_dir . "/meta.dat", "ab");
				
				if($es) {
					$filesize = filesize($PAGES_DIR . "/" . $page . ".txt");
					
					// Strings are in UTF-8, it's dangerous to just cut off piece of string, therefore +2
					fwrite($es, "!" . $rightnow . 
						str_pad($_SERVER['REMOTE_ADDR'], 16, " ", STR_PAD_LEFT) . 
						str_pad($filesize, 11, " ", STR_PAD_LEFT) . " " .
						str_pad(substr($esum, 0, $EDIT_SUMMARY_LEN), $EDIT_SUMMARY_LEN + 2)) . "\n";
					
					fclose($es);
				}
			}

			plugin_call_method("pageWritten", $file);
			
			header("Location:?page=" . urlencode($page) . ($error ? ("&error=" . urlencode($error)) : ""));
			die();
		} else { // there's some problem with page, give user a chance to fix it (do not throw away submitted content)
			$CON = $content;
			$action = "edit";
		}
	} else if($action == "save") { // wrong password, give user another chance (do not throw away submitted content)
		$error = $TE_WRONG_PASSWORD;
		
		$CON = $content;
		$action = "edit";
	}
		
	if($moveto && authentified()) { // moving/renaming page
		plugin_call_method("renamingPage");
		
		if($plugin_saveok) {
			if(!rename($PAGES_DIR . $page . ".txt", $PAGES_DIR . $moveto . ".txt"))
				die("Moving page was not succesful! Page was not moved.");
			else if(!rename($HISTORY_DIR . $page, $HISTORY_DIR . $moveto)) {
				rename($PAGES_DIR . $moveto, $PAGES_DIR . $page); // revert previous change
				die("Moving history of the was not succesful! Page was not moved.");
			}
			else {
				@touch($PAGES_DIR . $moveto . ".txt"); // moved page should be at the top of recent ch.
				header("Location:?page=" . urlencode($moveto));
				die();
			}
		}
	} else if($moveto)
		$error = $TE_WRONG_PASSWORD;

	// lets check first subsite specific template, then common, then fallback
	if(file_exists($BASE_DIR . $TEMPLATE))
		$html = file_get_contents($BASE_DIR . $TEMPLATE);
	elseif(file_exists($TEMPLATE))
		$html = file_get_contents($TEMPLATE);
	else // there's no template file, we'll use default minimal template
		$html = fallback_template();

	if(!$CON && @file_exists($PAGES_DIR . $page . ".txt")) {
		$LAST_CHANGED_TIMESTAMP = @filemtime($PAGES_DIR . $page . ".txt");
		$LAST_CHANGED = date("Y/m/d H:i", $LAST_CHANGED_TIMESTAMP + $LOCAL_HOUR * 3600);
	
		$HISTORY = "<a href=\"?page=" . urlencode($page) . "&amp;action=history\" rel=\"nofollow\">" . $T_HISTORY . "</a>";
	
		// Restoring old version of page
		if($gtime && ($restore || $action == "rev") && ($file = @lwopen($HISTORY_DIR . $page . "/" . $gtime, "r"))) {
			if($action == "rev") { 
				$rev_restore = "[$T_RESTORE|./?page=" . urlencode($page) . "&amp;action=edit&amp;gtime=" . $gtime . "&amp;restore=1]";

				$CON = str_replace(array("{TIME}", "{RESTORE}"), array(revTime($gtime), $rev_restore), $T_REVISION);
			}

			$CON .= @lwread($file);
			@lwclose($file);
		}
		else {
			$CON = @file_get_contents($PAGES_DIR . $page . ".txt");

			if(substr($CON, 0, 10) == "{redirect:" && $action == "") {
				header("Location:?page=" . substr($CON, 10, strpos($CON, "}") - 10)); // urlencode?
				die();
			}
		}
	}
	
	if($action == "edit") {
		if(!authentified()) { // if not logged on, require password
			$FORM_PASSWORD = $T_PASSWORD;
			$FORM_PASSWORD_INPUT = "<input id=\"passwordInput\" type=\"password\" name=\"sc\" />";
		}

		$EDIT_SUMMARY_TEXT = $T_EDIT_SUMMARY;
		$EDIT_SUMMARY = "<input type=\"text\" name=\"esum\" id=\"esum\" value=\"\" />";

		if(!$preview) {
			$RENAME_FORM_BEGIN = "<form id=\"renameForm\" method=\"post\" action=\"\">";
			$RENAME_FORM_END = "</form>";
	
			$RENAME_TEXT = $T_MOVE_TEXT;
			$RENAME_INPUT = "<input id=\"renameInput\" type=\"text\" name=\"moveto\" value=\"" . $page . "\" />";
			$RENAME_SUBMIT = "<input type=\"hidden\" name=\"page\" value=\"$page\" /><input id=\"renameSubmit\" class=\"submit\" type=\"submit\" value=\"$T_MOVE\" />";
		}

		$CON_FORM_BEGIN = "<form id=\"contentForm\" method=\"post\" action=\"./\"><input type=\"hidden\" name=\"action\" value=\"save\" /><input type=\"hidden\" name=\"last_changed\" value=\"$LAST_CHANGED_TIMESTAMP\" />";
		
		if(empty($econfprot))
			$CON_FORM_BEGIN .= "<input type=\"hidden\" name=\"econfprot\" value=\"1\" />";
		
		$CON_FORM_END = "</form>";

		$CON_TEXTAREA = "<textarea id=\"contentTextarea\" name=\"content\" cols=\"83\" rows=\"30\">" . htmlspecialchars($CON) . "</textarea><input type=\"hidden\" name=\"page\" value=\"$page\" />";
			
		$CON_SUBMIT = "<input id=\"contentSubmit\" class=\"submit\" type=\"submit\" value=\"$T_DONE\" />";
		$CON_PREVIEW = "<input id=\"contentPreview\" class=\"submit\" type=\"submit\" name=\"preview\" value=\"$T_PREVIEW\" />";
		
		if($preview)
			$action = "";
	} elseif($action == "rev" && !empty($gtime)) // show old revision of page
		$action = "";
	elseif($action == "history") { // show whole history of page
		$complete_dir = $HISTORY_DIR . $page . "/";
		
		if($opening_dir = @opendir($complete_dir)) {
			while($filename = @readdir($opening_dir))
				if(preg_match('/(.+)\.bak.*$/', $filename))
					$files[] = $filename;
					
			rsort($files);
			
			$CON = "<form method=\"get\" action=\"./\">\n<input type=\"hidden\" name=\"action\" value=\"diff\" /><input type=\"hidden\" name=\"page\" value=\"$page\" />";

			if($USE_META)
				$meta = @fopen($complete_dir . "meta.dat", "rb");
			
			$i = 1;
			
			foreach($files as $fname) {
				$fname = basename(basename($fname, ".bz2"), ".gz");
				
				if($USE_META)
					$m = meta_getline($meta, $i);
				
				if($m && !strcmp(basename($fname, ".bak"), $m[0])) {
					$ip = $m[1];
					$size = " - ($m[2] B)";
					$esum = htmlspecialchars($m[3]);
					
					$i++;
				} else
					$ip = $size = $esum = "";
			
				$CON .= "<input type=\"radio\" name=\"f1\" value=\"$fname\" /><input type=\"radio\" name=\"f2\" value=\"$fname\" />";
				$CON .= "<a href=\"?page=" . urlencode($page) . "&amp;action=rev&amp;gtime=" . $fname . "\" rel=\"nofollow\">" . revTime($fname) . "</a> $size $ip <i>$esum</i><br />";
			}
			
			$CON .= "<input id=\"diffButton\" type=\"submit\" class=\"submit\" value=\"$T_DIFF\" /></form>";
		} else
			$CON = $NO_HISTORY;
	}
	elseif($action == "diff") {
		if(empty($f1) && $opening_dir = @opendir($HISTORY_DIR . $page . "/")) { // diff is made on two last revisions
			while($filename = @readdir($opening_dir))
				if(preg_match('/\.bak.*$/', $filename))
					$files[] = basename(basename($filename, ".gz"), ".bz2");
					
			rsort($files);
			
			header("Location: ?action=diff&page=" . urlencode($page) . "&f1=$files[0]&f2=$files[1]");	
			die();
		}

		$r1 = "<a href=\"?page=".urlencode($page)."&action=rev&gtime=$f1\" rel=\"nofollow\">".revTime($f1)."</a>";
		$r2 = "<a href=\"?page=".urlencode($page)."&action=rev&gtime=$f2\" rel=\"nofollow\">".revTime($f2)."</a>";

		$CON = str_replace(array("{REVISION1}", "{REVISION2}"), array($r1, $r2), $T_REV_DIFF);

		$CON .= diff($f1, $f2);
	} elseif($action == "search") {
		$dir = opendir($PAGES_DIR);
		
		// offer to create page if it doesn't exist
		if($query && !file_exists($PAGES_DIR . $query . ".txt"))
			$CON = "<p><i><a href=\"?action=edit&amp;page=" . urlencode($query) . "\" rel=\"nofollow\">$T_CREATE_PAGE $query</a>.</i></p><br />";
		
		$files = array();
		
		while($file = readdir($dir))
			if(preg_match("/\.txt$/", $file) && (@$con = file_get_contents($PAGES_DIR . $file)))
				if(empty($query) || stristr($con, $query) !== false || stristr($file, $query) !== false)
					$files[] = substr($file, 0, strlen($file) - 4);
		
		sort($files);
			
		foreach($files as $file)
			$CON .= "<a href=\"?page=".urlencode($file)."\" rel=\"nofollow\">" . htmlspecialchars($file) . "</a> (<a href=\"?page=".urlencode($file)."&amp;action=edit\">$T_EDIT</a>)<br />";
		
		$TITLE .= " (" . count($files) . ")";
	} elseif($action == "recent") { // recent changes
		$dir = opendir($PAGES_DIR);
		
		while($file = readdir($dir))
			if(preg_match("/\.txt$/", $file))
				$filetime[$file] = filemtime($PAGES_DIR . $file);
				
		arsort($filetime);
		
		$filetime = array_slice($filetime, 0, 100); // just first 100 changed files
		
		foreach($filetime as $filename => $timestamp) {
			$filename = substr($filename, 0, strlen($filename) - 4);

			if($USE_META && ($meta = @fopen($HISTORY_DIR . basename($filename, ".txt") . "/meta.dat", "r"))) {
				$m = meta_getline($meta, 1);
				fclose($meta);

				$ip = $m[1];
				$size = " - ($m[2] B)";
				$esum = htmlspecialchars($m[3]);
			} else
				$ip = $size = $esum = "";
			
			$CON .= "<a href=\"./?page=" . urlencode($filename) . "\">" . htmlspecialchars($filename) . "</a> (" . date($DATE_FORMAT, $timestamp + $LOCAL_HOUR * 3600) . " - <a href=\"./?page=".urlencode($filename)."&amp;action=diff\">$T_DIFF</a>) $size $ip <i>$esum</i><br />";
		}
		
	} else if(!plugin_call_method("action", $action) && $action != "view-html")
			$action = "";

	if($action == "") { // substituting $CON to be viewed as HTML
		$CON = "\n" . $CON . "\n";
	
		// Subpages
		while(preg_match("/([^\^]){include:([^}]+)}/Um", $CON, $match)) {
			if(!strcmp($match[2], $page)) // limited recursion protection
				$CON = str_replace($match[0], "'''Warning: subpage recursion!'''", $CON);
			elseif(file_exists($PAGES_DIR . $match[2] . ".txt")) {
				$tpl = file_get_contents($PAGES_DIR . $match[2] . ".txt");
				
				$CON = str_replace($match[0], $match[1] . $tpl, $CON);
			} else
				$CON = str_replace($match[0], "'''Warning: subpage $match[2] was not found!'''", $CON);
		}
	
		// save content not intended for substitutions ({html} tag)
		
		if($NO_HTML == false) { // XSS protection
			$n_htmlcodes = preg_match_all("/[^\^]\{html\}(.+)\{\/html\}/Ums", $CON, $htmlcodes, PREG_PATTERN_ORDER);
			$CON = preg_replace("/[^\^]\{html\}(.+)\{\/html\}/Ums", "{HTML}", $CON);
		}
		
		$CON = preg_replace("/[^\^]<!--.*-->/U", "", $CON); // internal comments

		// escaping ^codes which protects them from substitution
		$CON = preg_replace("/\^(.)/Umsie", "'&#'.ord('$1').';'", $CON);
		
		$CON = str_replace("<", "&lt;", $CON);
		
		$CON = str_replace("&", "&amp;", $CON); // & => amp;
		$CON = preg_replace("/&amp;([a-z]+;|\#[0-9]+;)/U", "&$1", $CON); // keep HTML entities

		$CON = preg_replace("/(\r\n|\r)/", "\n", $CON); // unifying newlines to Unix ones
		
		// {{CODE}}
		$nbcode = preg_match_all("/{{(.+)}}/Ums", $CON, $matches_code, PREG_PATTERN_ORDER);
		$CON = preg_replace("/{{(.+)}}/Ums", "<pre><code>{{CODE}}</code></pre>", $CON);

		plugin_call_method("formatBegin");

		// substituting special characters
		$CON = str_replace("&lt;-->", "&harr;", $CON); // <-->
		$CON = str_replace("-->", "&rarr;", $CON); // -->
		$CON = str_replace("&lt;--", "&larr;", $CON); // <--
		$CON = preg_replace("/\([cC]\)/Umsi", "&copy;", $CON); // (c)
		$CON = preg_replace("/\([rR]\)/Umsi", "&reg;", $CON);	// (r)
		
		$CON = preg_replace("/^([^!\*#\n][^\n]+)$/Um", "<p>$1</p>", $CON);
		
		// sup and sub
		
		$CON = preg_replace("/\{sup\}(.*)\{\/sup\}/U", "<sup>$1</sup>", $CON);
		$CON = preg_replace("/\{sub\}(.*)\{\/sub\}/U", "<sub>$1</sub>", $CON);
		
		// small
		
		$CON = preg_replace("/\{small\}(.*)\{\/small\}/U", "<small>$1</small>", $CON);

		// TODO: verif & / &amp;
		$rg_url = "[0-9a-zA-Z\.\#/~\-_%=\?\&,\+\:@;!\(\)\*\$']*";
		$rg_img_local = "(" . $rg_url . "\.(jpeg|jpg|gif|png))";
		$rg_img_http = "h(ttps?://" . $rg_url . "\.(jpeg|jpg|gif|png))";
		$rg_link_local = "(" . $rg_url . ")";
		$rg_link_http = "h(ttps?://" . $rg_url . ")";

		// IMAGES
		// [http.png] / [http.png|right]
		$CON = preg_replace('#\[' . $rg_img_http . '(\|(right|left))?\]#', '<img src="xx$1" alt="xx$1" style="float:$4;"/>', $CON);
		// [local.png] / [local.png|left]
		$CON = preg_replace('#\[' . $rg_img_local . '(\|(right|left))?\]#', '<img src="$1" alt="$1" style="float:$4"/>', $CON);
		// image link [http://wikiss.tuxfamily.org/img/logo_100.png|http://wikiss.tuxfamily.org/img/logo_100.png]

		// [http|http]
		$CON = preg_replace('#\[' . $rg_img_http . '\|' . $rg_link_http . '(\|(right|left))?\]#U', '<a href="xx$3" class="url"><img src="xx$1" alt="xx$3" title="xx$3" style="float:$5;"/></a>', $CON);
		// [http|local]
		$CON = preg_replace('#\[' . $rg_img_http . '\|' . $rg_link_local . '(\|(right|left))?\]#U', '<a href="$3" class="url"><img src="xx$1" alt="$3" title="$3" style="float:$5;"/></a>', $CON);
		// [local|http]
		$CON = preg_replace('#\[' . $rg_img_local . '\|' . $rg_link_http . '(\|(right|left))?\]#U', '<a href="xx$3" class="url"><img src="$1" alt="xx$3" title="xx$3" style="float:$5;"/></a>', $CON);
		// [local|local]
		$CON = preg_replace('#\[' . $rg_img_local . '\|' . $rg_link_local . '(\|(right|left))?\]#U', '<a href="$3" class="url"><img src="$1" alt="$3" title="$3" style="float:$5;"/></a>', $CON);

		// LINKS
		$CON = preg_replace('#\[([^\]]+)\|' . $rg_link_http . '\]#U', '<a href="xx$2" class="url">$1</a>', $CON);
		// local links has to start either with / or ./
		$CON = preg_replace('#\[([^\]]+)\|\.\/' . $rg_link_local . '\]#U', '<a href="$2" class="url">$1</a>', $CON);
		$CON = preg_replace('#' . $rg_link_http . '#i', '<a href="$0" class="url">xx$1</a>', $CON);
		$CON = preg_replace('#xxttp#', 'http', $CON);
		$CON = preg_replace('#\[\?(.*)\]#Ui', '<a href="http://' . $LANG . '.wikipedia.org/wiki/$1" class="url" title="Wikipedia">$1</a>', $CON); // Wikipedia
		
		preg_match_all("/\[([^|\]]+\|)?([^\]#]+)(#[^\]]+)?\]/", $CON, $matches, PREG_SET_ORDER); // matching Wiki links
		
		foreach($matches as $match) {
			if(empty($match[1])) // is page label same as its name?
				$match[1] = $match[2];
			else
				$match[1] = rtrim($match[1], "|");
				
			if($match[3]) // link to the heading
				$match[3] = "#" . preg_replace("/[^\da-z]/i", "_", urlencode(substr($match[3], 1, strlen($match[3]) - 1)));
		
			if(file_exists($PAGES_DIR . "$match[2].txt"))
				$CON = str_replace($match[0], '<a href="./?page=' . urlencode($match[2]) . $match[3] . '">' . $match[1] . '</a>', $CON);
			else
				$CON = str_replace($match[0], '<a href="./?page=' . urlencode($match[2]) . '&amp;action=edit" class="pending" rel=\"nofollow\">' . $match[1] . '</a>', $CON);
		}

		$CON = preg_replace('#([0-9a-zA-Z\./~\-_]+@[0-9a-z\./~\-_]+)#i', '<a href="mailto:$0">$0</a>', $CON); // mail recognition

		// LIST, ordered, unordered
		$CON = preg_replace('/^\*\*\*(.*)(\n)/Um', "<ul><ul><ul><li>$1</li></ul></ul></ul>$2", $CON);
		$CON = preg_replace('/^\*\*(.*)(\n)/Um', "<ul><ul><li>$1</li></ul></ul>$2", $CON);
		$CON = preg_replace('/^\*(.*)(\n)/Um', "<ul><li>$1</li></ul>$2", $CON);
		$CON = preg_replace('/^\#\#\#(.*)(\n)/Um', "<ol><ol><ol><li>$1</li></ol></ol></ol>$2", $CON);
		$CON = preg_replace('/^\#\#(.*)(\n)/Um', "<ol><ol><li>$1</li></ol></ol>$2", $CON);
		$CON = preg_replace('/^\#(.*)(\n)/Um', "<ol><li>$1</li></ol>$2", $CON);

		// Fixing crappy job of parsing *** and ###. 3 times for 3 levels.
		for($i = 0; $i < 3; $i++)
			$CON = preg_replace('/(<\/ol>\n*<ol>|<\/ul>\n*<ul>)/', "", $CON);

		// still fixing. Following three lines fix only XHTML validity
		$CON = preg_replace('/<\/li><([uo])l>/', "<$1l>", $CON);
		$CON = preg_replace('/<\/([uo])l><li>/', "</$1l></li><li>", $CON);
		$CON = preg_replace('/<(\/?)([uo])l><\/?[uo]l>/', "<$1$2l><$1li><$1$2l>", $CON);

		// remove anchors from a text 
		function remove_a($link)
		{
			preg_match_all("#(<a.+>)*([^<>]+)(</a>)*#", $link, $txt); 
			return trim(join("", $txt[2]));
		}
		// remove_a

		function name_title($matches) // replace headings
		{
			global $headings;
			$headings[] = $h = array(strlen($matches[1]) + 1, preg_replace("/[^\da-z]/i", "_", remove_a($matches[2])), $matches[2]);
			return "<h" . $h[0] . "><a name=\"" . $h[1] . "\">" . $h[2] . "</a></h" . $h[0] . ">";
		}
		
		$CON = preg_replace_callback('/^(!+?)(.*)$/Um', "name_title", $CON);
		
		// do not join adjacent spaces into one (nasty)
		$CON = preg_replace('/^( +) ([^ ])/Um', '$1&nbsp;&nbsp;&nbsp;&nbsp;$2', $CON);
		
		$CON = preg_replace('/(-----*)/m', '<hr />', $CON); // horizontal line
		
		$CON = preg_replace("/<\/([uo])l>\n\n/Us", "</$1l>", $CON);

		$CON = preg_replace('#(</h[23456]>)<br />#', "$1", $CON);

		$CON = preg_replace("/'--(.*)--'/Um", '<del>$1</del>', $CON); // strikethrough

		$CON = str_replace("--", "&mdash;", $CON); // --

		$CON = preg_replace("/'__(.*)__'/Um", '<u>$1</u>', $CON); // underlining
		$CON = preg_replace("/'''''(.*)'''''/Um", '<strong><em>$1</em></strong>', $CON); // bold and italic
		$CON = preg_replace("/'''(.*)'''/Um", '<strong>$1</strong>', $CON); // bold
		$CON = preg_replace("/''(.*)''/Um", '<em>$1</em>', $CON); // italic
		
		$CON = str_replace("{br}", "<br style=\"clear:both;\" />", $CON); // new line

		if(strpos($CON, "{TOC}") || strpos($html, "{TOC}")) {
			$TOC = "";

			if(!empty($headings))
			foreach($headings as $h)
				$TOC .= str_repeat("<ul>", $h[0] - 2) . '<li><a href="#' . urlencode($h[1]) . '">' . remove_a($h[2]) . '</a></li>' . str_repeat("</ul>", $h[0] - 2);
				
			for($i = 0; $i < 5; $i++) // five possible headings
				$TOC = preg_replace('/<\/ul>\n*<ul>/', "", $TOC);
				
			$TOC = "<ul id=\"toc\">" . $TOC . "</ul>";
				
			$TOC = preg_replace('/<\/li><ul>/', "<ul>", $TOC);
			$TOC = preg_replace('/<\/ul><li>/', "</ul></li><li>", $TOC);
			$TOC = preg_replace('/<(\/?)ul><\/?ul>/', "<$1ul><$1li><$1ul>", $TOC);
			
			$CON = str_replace("{TOC}", $TOC, $CON);
		}
		
		// returning content of {{CODE}}
		if($nbcode > 0) 
			$CON = preg_replace(array_fill(0, $nbcode, "/{{CODE}}/Us"), $matches_code[1], $CON, 1);
		
		// {html} tag
		if($NO_HTML == false && $n_htmlcodes > 0)
			$CON = preg_replace(array_fill(0, $n_htmlcodes, "/{HTML}/Us"), $htmlcodes[1], $CON, 1);
		
		plugin_call_method("formatEnd");
	}

	if($preview)
		$html = preg_replace("/\{RENAME_FORM\}.*\{\/RENAME_FORM\}/Um", "", $html);

	// including pages in pure HTML
	while(preg_match("/{include:([^}]+)}/U", $html, $match)) {
		$inc = @file_get_contents($PAGES_DIR . $match[1] . ".txt");
		$inc = str_replace(array("{html}", "{/html}"), array("", ""), $inc);
		$html = str_replace($match[0], $inc, $html);
	}

	plugin_call_method("template"); // plugin specific template substitutions
	
	$html = preg_replace("/\{([^}]* )?plugin:.+( [^}]*)?\}/U", "", $html); // getting rid of absent plugin tags

	if($editable && is_writable($PAGES_DIR . $page . ".txt"))
		$EDIT = "<a href=\"./?page=" . urlencode($page) . "&amp;action=edit\" rel=\"nofollow\">$T_EDIT</a>";

	$tpl_subs = array(
		array("HEAD", $HEAD),
		array("SEARCH_FORM", "<form method=\"get\" id=\"searchForm\" action=\"\"><span><input type=\"hidden\" name=\"action\" value=\"search\" /><input type=\"submit\" style=\"display:none;\" />"),
		array("\/SEARCH_FORM", "</span></form>"),
		array("SEARCH_INPUT", "<input type=\"text\" id=\"searchInput\" name=\"query\" value=\"" . htmlspecialchars($query) . "\" tabindex=\"1\" />"),
		array("SEARCH_SUBMIT", "<input class=\"submit\" type=\"submit\" value=\"$T_SEARCH\" />"),
		array("HOME", "<a href=\"./?page=" . urlencode($START_PAGE) . "\">$T_HOME</a>"),
		array("RECENT_CHANGES", "<a href=\"./?action=recent\">$T_RECENT_CHANGES</a>"),
		array("ERROR",	$error),
		array("HISTORY", $HISTORY),
		array("PAGE_TITLE", htmlspecialchars($page_nolang == $START_PAGE ? $WIKI_TITLE : $TITLE)),
		array("PAGE_TITLE_HEAD", htmlspecialchars($page_nolang == $START_PAGE ? "" : $TITLE)),
		array("EDIT", $EDIT),
		array("WIKI_TITLE", $WIKI_TITLE),
		array("LAST_CHANGED_TEXT", $LAST_CHANGED ? $T_LAST_CHANGED : ""),
		array("LAST_CHANGED", $LAST_CHANGED),
		array("TOC", $TOC), // must be before replacing CONTENT_FORM
		array("CONTENT", $action != "edit" ? $CON : ""),
		array("CONTENT_FORM", $CON_FORM_BEGIN),
		array("\/CONTENT_FORM", $CON_FORM_END),
		array("CONTENT_TEXTAREA", $CON_TEXTAREA),
		array("CONTENT_SUBMIT", $CON_SUBMIT),
		array("CONTENT_PREVIEW", $CON_PREVIEW),
		array("RENAME_FORM", $RENAME_FORM_BEGIN),
		array("\/RENAME_FORM", $RENAME_FORM_END),
		array("RENAME_TEXT", $RENAME_TEXT),
		array("RENAME_INPUT", $RENAME_INPUT),
		array("RENAME_SUBMIT", $RENAME_SUBMIT),
		array("EDIT_SUMMARY_TEXT", $USE_META ? $EDIT_SUMMARY_TEXT : ""),
		array("EDIT_SUMMARY_INPUT", $USE_META ? $EDIT_SUMMARY : ""),
		array("FORM_PASSWORD", $FORM_PASSWORD),
		array("FORM_PASSWORD_INPUT", $FORM_PASSWORD_INPUT),
		array("LANG", $LANG),
		array("LIST_OF_ALL_PAGES", "<a href=\"?action=search\">$T_LIST_OF_ALL_PAGES</a>"),
		array("WIKI_VERSION", $WIKI_VERSION),
		array("DATE", $datetw),
		array("IP", $_SERVER['REMOTE_ADDR']),
		array("SYNTAX", $action == "edit" || $preview ? "<a href=\"./?page=" . urlencode($SYNTAX_PAGE) . "\" rel=\"nofollow\">$T_SYNTAX</a>" : ""),
		array("COOKIE", '<a href="./?page=' . urlencode($page) . '&amp;action='. urlencode($action) .'&amp;erasecookie=1" rel="nofollow">' . $T_ERASE_COOKIE . '</a>')
	);

	foreach($tpl_subs as $subs) // substituting values
		$html = template_replace($subs[0], $subs[1], $html);

	echo $html; // voila
	
	// Function library
	
	function template_replace($what, $subs, $where) {
		return preg_replace("/\{(([^}]*) )?$what( ([^}]*))?\}/U", empty($subs) ? "" : "\${2}".trim($subs)."\${4}", $where);
	}
	
	function template_match($what, $where, &$dest = NULL) {
		return preg_match("/\{(([^}]*) )?$what( ([^}]*))?\}/U", $where, $dest);
	}
	
	function revTime($time) {
		global $DATE_FORMAT, $LOCAL_HOUR;
	
		preg_match("/([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])-([0-9][0-9])([0-9][0-9])-([0-9][0-9])/U", $time, $m);
		
		return date($DATE_FORMAT, mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]));
	}
	
	function diff($f1, $f2, $short_diff = 0) {
		global $page, $HISTORY_DIR;
	
		$complete_dir = $HISTORY_DIR . $page . "/";

		if($f2 < $f1)
			list($f1, $f2) = array($f2, $f1);

		$s1 = lwopen($complete_dir . $f1, "r");
		$s2 = lwopen($complete_dir . $f2, "r");

		$a1 = explode("\n", @lwread($s1));
		$a2 = explode("\n", @lwread($s2));

		lwclose($f1);
		lwclose($f2);

		$d1 = array_diff($a1, $a2);
		$d2 = array_diff($a2, $a1);

		$ret = "<pre id=\"diff\">";

		for($i = 0; $i <= max(sizeof($a2), sizeof($a1)); $i++) {
			if($r1 = array_key_exists($i, $d1)) $ret .= "<del>".htmlspecialchars(trim($d1[$i]))."</del>\n";
			if($r2 = array_key_exists($i, $d2)) $ret .= "<ins>".htmlspecialchars(trim($d2[$i]))."</ins>\n";
			if(!$r1 && !$r2 && !$short_diff) $ret .= "<span class=\"orig\">".htmlspecialchars(trim($a2[$i]))."</span>\n";
		}

		return $ret . "</pre>";
	}
	
	function lwopen($name, $par) {
		global $HISTORY_COMPRESSION;

		if($par == "r") {
			if(file_exists($name)) return array(@fopen($name, $par), "plain");
			elseif(file_exists($name . ".gz")) return array(@gzopen($name . ".gz", $par), "gzip");
			elseif(file_exists($name . ".bz2")) return @bzopen($name . ".bz2", $par, "bzip2");
		} elseif($par == "w") {
			if($HISTORY_COMPRESSION == "plain") return array(@fopen($name, $par), $HISTORY_COMPRESSION);
			elseif($HISTORY_COMPRESSION == "gzip") return array(@gzopen($name . ".gz", $par), $HISTORY_COMPRESSION);
			elseif($HISTORY_COMPRESSION == "bzip2") return array(@bzopen($name . ".bz2", $par), $HISTORY_COMPRESSION);
		}
	}

	function lwclose($h) {
		if($h[1] == "plain") return fclose($h[0]);
		elseif($h[1] == "gzip") return gzclose($h[0]);
		elseif($h[1] == "bzip2") return bzclose($h[0]);
	}

	function lwread($h) {
		$ret = $buffer = "";

		if($h[1] == "plain") {
			$stat = fstat($h[0]);
			return fread($h[0], $stat["size"]);
		} elseif($h[1] == "gzip") {
			while($buffer = gzread($h[0], 8192))
				$ret .= $buffer;
			
			return $ret;
		} elseif($h[1] == "bzip2") {
			while($buffer = bzread($h[0], 8192))
				$ret .= $buffer;

			return $ret;
		}
	}

	function lwwrite($h, $data) {
		if($h[1] == "plain") return fwrite($h[0], $data);
		elseif($h[1] == "gzip") return gzwrite($h[0], $data);
		elseif($h[1] == "bzip2") return bzwrite($h[0], $data);
	}

	// checks autentification
	function authentified() {
		global $PASSWORD_MD5, $sc;
		
		if(empty($PASSWORD_MD5) || $_COOKIE['LW_AUT'] == $PASSWORD_MD5 || md5($sc) == $PASSWORD_MD5) {
			setcookie('LW_AUT', $PASSWORD_MD5, time() + $PROTECTED_READ ? $COOKIE_LIFE_READ : $COOKIE_LIFE_WRITE);
			$_COOKIE['LW_AUT'] = $PASSWORD_MD5;
			
			return true;
		} else
			return false;
	}
	
	// returns "line" from meta.dat files. $lnum is number of line from the end of file starting with 1
	function meta_getline($file, $lnum) {
		global $EDIT_SUMMARY_LEN;
	
		if(fseek($file, -($lnum * 175), SEEK_END) != 0)
			return false;
			
		$line = fread($file, 175);
		
		if($line[0] != "!") // control character
			return false;
		
		$date = substr($line, 1, 16);
		$ip = trim(substr($line, 19, 15));
		$size = trim(substr($line, 35, 10));
		$esum = trim(substr($line, 45, $EDIT_SUMMARY_LEN));
		
		return array($date, $ip, $size, $esum);
	}

	/** Call a method for all plugins
	 * $mname: method name
	 * [...] : method arguments
	 * return: true if treated by a plugin
	 */
	function plugin_call_method($mname) {
		global $plugins;
		$ret = false;
		
		foreach($plugins as $plugin)
			if(method_exists($plugin, $mname)) {
				$args = func_get_args();
				$ret |= call_user_func_array(array($plugin, $mname), array_slice($args, 1));
			}
		return $ret;
	}
	
		function fallback_template() { return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{LANG}" lang="{LANG}">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>{WIKI_TITLE} {- PAGE_TITLE_HEAD}</title>
	<style type="text/css">
*{margin:0;padding:0;}
body{font-size:12px;line-height:16px;padding:10px 20px 20px 20px;}
a:link,a:visited{color:#006600;text-decoration : none;border-bottom : 1px dotted #006600;}
p{margin: 5px 0 5px 0;}
a.pending{color:#990000;}
pre{border:1px dotted #ccc;padding:4px;width:640px;overflow:auto;margin:3px;}
img,a img{border:0px}
h1,h2,h3,h4,h5,h6{letter-spacing:2px;font-weight:normal;margin:15px 0 15px 0px;color:#006600;}
h1{margin:18px 0 15px 15px;font-size : 22px;}
hr{margin:10px 0 10px 0;height:0px;overflow:hidden;border:0px;border-top:1px solid #006600;}
ul,ol{padding:5px 0px 5px 20px;}
table{text-align:left;}
.error{color:#F25A5A;font-weight:bold;}
form{display:inline}
#renameForm{display:block;margin-bottom:6px;}
#contentSubmit{margin-top:6px;}
#contentTextarea{width:100%;}
input,select,textarea{border:1px solid #AAAAAA;padding:2px;font-size:12px;}
.submit{padding:1px;}
textarea{padding:3px;}
#toc{border:1px dashed #11141A;margin:5px 0 5px 10px;padding:6px 5px 7px 0px;float:right;     padding-right:2em;list-style:none;}
#toc ul{list-style:none;padding:3px 0 3px 10px;}
#toc li{font-size:11px;padding-left:10px;}
#toc ul li{font-size:10px;}
#toc ul ul li{font-size:9px;}
#toc ul ul ul li{font-size:8px;}
#toc ul ul ul ul li{font-size:7px;}
#diff ins{color:green;text-decoration:none;font-weight:bold;}
#diff del{color:red;text-decoration:line-through;}
#diff .orig{color:#666;font-size:90%;}
	</style>
  {HEAD}
</head>

<body>
<table border="0" width="100%" cellpadding="4" id="mainTable" cellspacing="0" summary="{PAGE_TITLE_BRUT}">
	<tr id="headerLinks">
		<td colspan="2">{HOME} {RECENT_CHANGES}</td>
		<td style="text-align : right;">{EDIT} {SYNTAX} {HISTORY}</td>
	</tr>
	<tr><th colspan="3"><hr /><h1>{PAGE_TITLE}</h1></th></tr>
	<tr>
		<td id="mainContent" colspan="3">
			{<div class="error"> ERROR </div>}
{CONTENT}
{RENAME_FORM} {RENAME_TEXT} {RENAME_INPUT} {FORM_PASSWORD} {FORM_PASSWORD_INPUT} {RENAME_SUBMIT} {/RENAME_FORM}
{CONTENT_FORM} {CONTENT_TEXTAREA}<p style="float:right;margin:6px">{FORM_PASSWORD} {FORM_PASSWORD_INPUT} {plugin:CAPTCHA_QUESTION} {plugin:CAPTCHA_INPUT} {EDIT_SUMMARY_TEXT} {EDIT_SUMMARY_INPUT} {CONTENT_SUBMIT} {CONTENT_PREVIEW}</p> {/CONTENT_FORM}
		</td>
	</tr>
	<tr><td colspan="3"><hr /></td></tr>
	<tr>
		<td><div>{SEARCH_FORM}{SEARCH_INPUT}{SEARCH_SUBMIT}{/SEARCH_FORM}</div></td>
		<td>Powered by <a href="http://lionwiki.0o.cz/">LionWiki</a>. {LAST_CHANGED_TEXT}: {LAST_CHANGED} {COOKIE}</td>
		<td style="text-align : right;">{EDIT} {SYNTAX} {HISTORY}</td>
	</tr>
</table>
</body>
</html>'; }
?>