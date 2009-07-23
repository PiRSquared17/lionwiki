<?php
  /* LionWiki created by Adam Zivner adam.zivner@gmail.com http://lionwiki.0o.cz
   Based on WiKiss, http://wikiss.tuxfamily.org, itself based on TigerWiki
   Licensed under GPL v2, http://www.gnu.org/licenses/gpl-2.0.html */

  // fallback settings when no config file exists.

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
  
  $TIME_FORMAT = "%Y/%m/%d %R";
  $LOCAL_HOUR = "0"; // +1 for most of the Europe, -5 for eastern USA, etc.
  
  @error_reporting(E_ERROR | E_WARNING | E_PARSE);
  @ini_set("register_globals", "0");
  
  set_magic_quotes_runtime(0); // turn off magic quotes
  
  if(get_magic_quotes_gpc()) {
		foreach($_GET as $k => $v) $_GET[$k] = stripslashes($v);
    foreach($_POST as $k => $v) $_POST[$k] = stripslashes($v);
    foreach($_COOKIE as $k => $v) $_COOKIE[$k] = stripslashes($v);
    foreach($_REQUEST as $k => $v) $_REQUEST[$k] = stripslashes($v);
	}
  
  $BASE_DIR = $_GET["basedir"] ? $_GET["basedir"] . "/" : "";
  
	$warning = isset($_GET["warning"]);

  @include("_config.php"); // config file is not required, see settings above

	if(!empty($BASE_DIR))
	  @include($BASE_DIR . "_config.php"); // subdomain specific settings

  $WIKI_VERSION = "LionWiki 1.0.2";
  $PAGES_DIR = $BASE_DIR . "pages/";
  $HISTORY_DIR = $BASE_DIR . "history/";
  $PLUGINS_DIR = "plugins/";
  $LANG_DIR = "lang/";
  
  umask(0); // sets default mask
  
  // Default character set for auto content header
  ini_set("default_charset", "UTF-8");
  header("Content-type: text/html; charset=UTF-8");

  // some strings may not be translated, in that case, we'll use english translation, which *should* be always complete

  $T_HOME = "Main page";
  $T_HELP = "Help";
  $T_EDIT = "Edit";
  $T_DONE = "Save changes";
  $T_SEARCH = "Search";
  $T_SEARCH_RESULTS = "Search results";
  $T_LIST_OF_ALL_PAGES = "List of all pages";
  $T_RECENT_CHANGES = "Recent changes";
  $T_LAST_CHANGED = "Last changed";
  $T_HISTORY = "History";
  $T_NO_HISTORY = "No history.";
  $T_RESTORE = "Restore";
  $T_PASSWORD = "Password";
  $T_ERASE_COOKIE = "Erase cookies";
  $T_WIKI_CODE = "Wiki code";
  $T_MOVE_TEXT = "Move page to new name to:";
	$T_MOVE = "Move";
	$T_CREATE_PAGE = "Create page";
	$T_PROTECTED_READ = "You need to enter password to view content of site: ";
	$TE_WRONG_PASSWORD = "Password is incorrect.";

  if($LANG != "en")
    @include $LANG_DIR . $LANG . ".php";

  $template = "template.html"; // Page template

	$editable = true; // should be on the page "edit" link?
	
	// Installation - create directories pages and history, if possible
	
	if(!file_exists($PAGES_DIR)) // create pages directory if doesn't exist
    if(!mkdir($PAGES_DIR, 0777))
      die("Can't create directory $PAGES_DIR. Please create $PAGES_DIR and $HISTORY_DIR with 0777 rights.");
      
  if(!file_exists($HISTORY_DIR) && $USE_HISTORY)
    if(!mkdir($HISTORY_DIR, 0777)) {
      $USE_HISTORY = false;
    
      warning("Can't create directory $HISTORY_DIR. Please create $HISTORY_DIR with 0777 rights or turn off history feature in config file. Turning off history now.");
    }
	
	if($_GET['erasecookie']) {  // remove cookie without reloading
    setcookie('AUT_LIONWIKI');
    $_COOKIE['AUT_LIONWIKI'] = "";
  }

  $plugins = array();
	$plugin_files = array();
	$plugins_included = array();
	
	$plugin_saveok = true; // is OK to save page changes (from plugins)

	// We load common plugins for all subsites and then just for this subsite.

  if(is_dir($PLUGINS_DIR) && ($dir = opendir($PLUGINS_DIR))) // common plugins
    while(($file = readdir($dir)) !== false) {
      $plugin_files[] = $PLUGINS_DIR . $file;
      $plugins_included[] = $file;
    }
      
  if(!empty($BASE_DIR) && is_dir($BASE_DIR . $PLUGINS_DIR) && ($dir = opendir($BASE_DIR . $PLUGINS_DIR)))
    while(($file = readdir($dir)) !== false)
      if(!in_array($file, $plugins_included)) { // we don't want to load plugin twice
      	$plugin_files[] = $BASE_DIR . $PLUGINS_DIR . $file;
        $plugins_included[] = $file; // sic!
			}

	for($i = 0; $i < count($plugin_files); $i++)
  	if(preg_match("/^wkp_(.+)\.php$/", $plugins_included[$i], $matches) > 0) {
      require $plugin_files[$i];
      $plugins[] = new $matches[1]();
    }

	// list of variables for UTF-8 conversion and export
  $req_conv = array('action', 'query', 'sc', 'content', 'page', 'moveto', 'restore', 'f1', 'f2', 'error', 'gtime');

$page = sanitizeFilename($page);
$moveto = sanitizeFilename($moveto);
$f1 = sanitizeFilename($f1);
$f2 = sanitizeFilename($f2);
$gtime = sanitizeFilename($gtime);

function sanitizeFilename($filename)
{
	$ret = "";

	for($i = 0, $c = strlen($filename); $i < $c; $i++)
		if(!ctype_cntrl($filename[$i]))
			$ret .= $filename[$i];

	return trim(str_replace("..", "", $ret), "/");
}

  if(extension_loaded('mbstring')) { // Conversion to UTF-8
    ini_set("mbstring.language", "Neutral"); 
    ini_set("mbstring.internal_encoding", "UTF-8"); 
    ini_set("mbstring.http_output", "UTF-8"); 
    ini_set("mbstring.detect_order", "UTF-8,ISO8859-2,ISO-8859-1");
    ini_set("mbstring.func_overload", MB_OVERLOAD_STRING);

    foreach($req_conv as $req_key)
      $_REQUEST[$req_key] = mb_convert_encoding($_REQUEST[$req_key], "UTF-8", mb_detect_encoding($_REQUEST[$req_key]));
  } // if mbstring is not supported, nothing bad should happen
  
  // export variables to main namespace
  foreach($req_conv as $req)
    $$req = trim($_REQUEST[$req]);

	// setting $PAGE_TITLE
  if($page) {
    if(!file_exists($PAGES_DIR . $START_PAGE . ".txt"))
        $action = "edit";

		$PAGE_TITLE = $page;
	}
  else
    if($action == "search")
      if(empty($query))
        $PAGE_TITLE = $T_LIST_OF_ALL_PAGES;
			else
        $PAGE_TITLE = "$T_SEARCH_RESULTS $query";
    elseif($action == "recent")
      $PAGE_TITLE = $T_RECENT_CHANGES;
    else {
      if(!file_exists($PAGES_DIR . $START_PAGE . ".txt"))
        $action = "edit"; // for first run after installation
    
      $PAGE_TITLE = $START_PAGE;
		}

  if(version_compare(phpversion(), "5.1.0") >= 0)
		@date_default_timezone_set($TIME_ZONE);
		
  $datetw = date("Y/m/d H:i", mktime(date("H") + $LOCAL_HOUR));
  
  // does user need password to read content of site. If yes, ask for it.
  if(!authentified() && $PROTECTED_READ) {
		$CON = "<form action=\"\" method=\"post\"><p>$T_PROTECTED_READ <input id=\"password-input\" type=\"password\" name=\"sc\" /> <input class=\"submit\" type=\"submit\" /></p></form>";
		
		$action = "view-html";
		$editable = false;
	} else if($content && authentified()) { // do we have page to save?
    plugin_call_method("writingPage");
    
    if($plugin_saveok) { // are plugins happy with page? (no spam, etc)
	    if(!$file = @fopen($PAGES_DIR . $PAGE_TITLE . ".txt", "w"))
	      die("Could not write page $PAGES_DIR$PAGE_TITLE.txt!");

	    fputs($file, $content);
			fclose($file);

			if($USE_HISTORY) { // let's archive previous revision
	      $complete_dir = $HISTORY_DIR . $PAGE_TITLE . "/";

	      if(!$dir = @opendir($complete_dir))
	        mkdir($complete_dir, 0777);

				$filename = $complete_dir . date("Ymd-Hi", mktime(date("H") + $LOCAL_HOUR)) . ".bak";

	      if(!$bak = @lwopen($filename, "w"))
	        die("Could not write backup $filename of page!");

       lwwrite($bak, "\n// " . $datetw . " / " . " " . $_SERVER['REMOTE_ADDR'] . "\n");
	      lwwrite($bak, $content);
	      lwclose($bak);
	    }

	    plugin_call_method("writedPage", $file);
	    
	    header("Location:?page=" . urlencode($PAGE_TITLE) . "&error=" . urlencode($error));
	  	die();
		} else { // there's some problem with page, give user a chance to fix it (do not throw away submitted content)
			$CON = $content;
			$action = "edit";
		}
  } else if($content) { // wring password, give user another chance (do not throw away submitted content)
    $error = $TE_WRONG_PASSWORD;
    
    $CON = $content;
		$action = "edit";
	}
    
	// moving/renaming page
	if($moveto && authentified()) {
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
  if(file_exists($BASE_DIR . $template))
    $html = file_get_contents($BASE_DIR . $template);
	elseif(file_exists($template))
    $html = file_get_contents($template);
  else // there's no template file, we'll use default minimal template
    $html = fallback_template();

  if(!$CON && ($file = @fopen($PAGES_DIR . $PAGE_TITLE . ".txt", "r"))) {
    if($file)
      $LAST_CHANGED = date("Y/m/d H:i", @filemtime($PAGES_DIR . $PAGE_TITLE . ".txt") + $LOCAL_HOUR * 3600);
  
    // Restoring old version of page
    if($gtime && $restore && ($file = @lwopen($HISTORY_DIR . $PAGE_TITLE . "/" . $gtime, "r")))
      $CON = "\n" . @lwread($file) . "\n";
		else
		  $CON = @fread($file, @filesize($PAGES_DIR . $PAGE_TITLE . ".txt")) . "\n";
        
    @lwclose($file);
  }
  
  if($action == "edit") {
    $editable = false;
    
    $HISTORY = "<a href=\"?page=" . urlencode($PAGE_TITLE) . "&amp;action=history\" accesskey=\"6\" rel=\"nofollow\">" . $T_HISTORY . "</a><br />";

    if(!authentified()) { // if not logged on, require password
      $FORM_PASSWORD = $T_PASSWORD;
      $FORM_PASSWORD_INPUT = "<input id=\"password-input\" type=\"password\" name=\"sc\" />";
		}

    $RENAME_FORM_BEGIN = "<form id=\"rename-form\" method=\"post\" action=\"\">";
		$RENAME_FORM_END = "</form>";

		$RENAME_TEXT = $T_MOVE_TEXT;
		$RENAME_INPUT = "<input id=\"rename-input\" type=\"text\" name=\"moveto\" value=\"" . $PAGE_TITLE . "\" />";
		$RENAME_SUBMIT = "<input type=\"hidden\" name=\"page\" value=\"" . $PAGE_TITLE . "\" /><input id=\"rename-submit\" class=\"submit\" type=\"submit\" value=\"$T_MOVE\" accesskey=\"m\" />";

		$CON_FORM_BEGIN = "<form id=\"content-form\" method=\"post\" action=\"./\">";
		$CON_FORM_END = "</form>";

		$CON_TEXTAREA = "<textarea id=\"content-textarea\" name=\"content\" cols=\"83\" rows=\"30\">" . htmlspecialchars($CON) . "</textarea><input type=\"hidden\" name=\"page\" value=\"" . $PAGE_TITLE . "\" /><br />";
      
    $CON_SUBMIT = " <input id=\"content-submit\" class=\"submit\" type=\"submit\" value=\"$T_DONE\" accesskey=\"s\" />";
  } elseif($action == "history") {
    if(isset($gtime)) { // show old revision of page
      $complete_dir = $HISTORY_DIR . $PAGE_TITLE . "/";
      
      $HISTORY = "<a href=\"?page=" . $PAGE_TITLE . "&amp;action=history\" rel=\"nofollow\">" . $T_HISTORY . "</a>";

      if($file = @lwopen($HISTORY_DIR . $PAGE_TITLE . "/" . $gtime, "r")) {
         $HISTORY = "<a href=\"?page=" . $PAGE_TITLE . "&amp;action=edit&amp;gtime=" . $gtime . "&amp;restore=1\" rel=\"nofollow\">" . $T_RESTORE . "</a> " . $HISTORY;
        
        $action = "";
      }
    } else { // show whole history of page
      $complete_dir = $HISTORY_DIR . $PAGE_TITLE . "/";
      
      if($opening_dir = @opendir($complete_dir)) {
        while($filename = @readdir($opening_dir))
          if(preg_match('/(.+)\.bak.*$/', $filename))
            $files[] = $filename;
            
        rsort($files);
        
        $CON = "<form method=\"get\" action=\"./\">\n<input type=\"hidden\" name=\"action\" value=\"diff\" /><input type=\"hidden\" name=\"page\" value=\"" . $PAGE_TITLE . "\" />";
        
        foreach($files as $fname) {
          $fname = basename(basename($fname, ".bz2"), ".gz");
        
          $CON .= "<input type=\"radio\" name=\"f1\" value=\"$fname\" /><input type=\"radio\" name=\"f2\" value=\"$fname\" />";
          $CON .= "<a href=\"?page=" . urlencode($PAGE_TITLE) . "&amp;action=history&amp;gtime=" . $fname . "\">" . $fname . "</a><br />";
        }
        
        $CON .= "<input type=\"submit\" class=\"submit\" value=\"diff\" /></form>";
      } else
        $CON = $NO_HISTORY;
    }
  } elseif($action == "diff") {
    if(empty($f1)) { // diff is made on two last revisions
      $complete_dir = $HISTORY_DIR . $PAGE_TITLE . "/";
      
      if($opening_dir = @opendir($complete_dir)) {
        while($filename = @readdir($opening_dir))
          if(preg_match('/\.bak.*$/', $filename))
            $files[] = basename(basename($filename, ".gz"), ".bz2");
            
        rsort($files);
        
        header("Location: ?action=diff&page=" . urlencode($PAGE_TITLE) . "&f1=$files[0]&f2=$files[1]");
        
        die();
      }
    }

    $HISTORY = "<a href=\"?page=" . urlencode($PAGE_TITLE) . "&amp;action=history\">" . $T_HISTORY . "</a>";

    $CON = diff($f1, $f2);
  } elseif($action == "search") {
    $editable = false;
    $dir = opendir(getcwd() . "/$PAGES_DIR");
    
    // offer to create page if it doesn't exist
    if($query && !file_exists($PAGES_DIR . $query . ".txt"))
			$CON = "<p><a href=\"?action=edit&amp;page=" . urlencode($query) . "\">$T_CREATE_PAGE $query</a>.</p><br />";
    
    $files = array();
    
    while($file = readdir($dir)) {
      if(preg_match("/\.txt$/", $file)) {
        @$con = file_get_contents($PAGES_DIR . $file);
        
        if(empty($query) || stripos($con, $query) !== false || stripos($file, $query) !== false)
          $files[] = substr($file, 0, strlen($file) - 4);
      }
    }
    
    sort($files);
      
    foreach($files as $file)
      $CON .= "<a href=\"./?page=" . $file . "\">" . $file . "</a><br />";
    
    $PAGE_TITLE .= " (" . count($files) . ")";
  } elseif($action == "recent") { // recent changes
    $editable = false;
    
    $dir = opendir(getcwd() . "/$PAGES_DIR");
    
    while($file = readdir($dir))
      if(preg_match("/\.txt$/", $file))
        $filetime[$file] = filemtime($PAGES_DIR . $file);
        
    arsort($filetime);
    
    $filetime = array_slice($filetime, 0, 100); // just first 100 changed files
    
    foreach($filetime as $filename => $timestamp) {
      $filename = substr($filename, 0, strlen($filename) - 4);
      
      $CON .= "<a href=\"./?page=" . $filename . "\">" . $filename . "</a> (" . strftime("$TIME_FORMAT", $timestamp + $LOCAL_HOUR * 3600) . " - <a href=\"./?page=$filename&amp;action=diff\">diff</a>)<br />";
    }
    
  } elseif($action != "view-html" && $action != "")
    if(!plugin_call_method("action", $action))
      $action = "";

  if($action == "") { // substituting $CON to be viewed as HTML
    $CON = "\n" . $CON; // fix for {html} tag at the very start of the page
    $CON = str_replace("{/html}{html}", "{/html} {html}", $CON); // fix for adjacent {html} tags
  
    // save content not intended for substitutions ({html} tag)
    $n_htmlcodes = preg_match_all("/[^\^]\{html\}(.+)\{\/html\}/Us", $CON, $htmlcodes, PREG_PATTERN_ORDER);
    $CON = preg_replace("/[^\^]\{html\}(.+)\{\/html\}/Us", "{HTML}", $CON);

    $CON = str_replace("&", "&amp;", $CON); // escape HTML spec. chars
    $CON = str_replace("<", "&lt;", $CON);

    // escaping ^codes which protects them from substitution
    $CON = preg_replace("/\^(.)/Umsie", "'&#'.ord('$1').';'", $CON);

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
    $CON = preg_replace("/\([rR]\)/Umsi", "&reg;", $CON);  // (r)
    
    $CON = preg_replace("/^([^!\*#\n][^\n]+)$/Um", "<p>$1</p>", $CON);

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
        $CON = str_replace($match[0], '<a href="./?page=' . urlencode($match[2]) . '&amp;action=edit" class="pending">' . $match[1] . '</a>', $CON);
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

    /* remove anchors from a text */
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

    $CON = preg_replace("/'__(.*)__'/Um", '<u>$1</u>', $CON); // underlining
    $CON = preg_replace("/'''''(.*)'''''/Um", '<strong><em>$1</em></strong>', $CON); // bold and italic
    $CON = preg_replace("/'''(.*)'''/Um", '<strong>$1</strong>', $CON); // bold
    $CON = preg_replace("/''(.*)''/Um", '<em>$1</em>', $CON); // italic
    
    $CON = str_replace("{br}", "<br style=\"clear:both;\" />", $CON); // new line

    if(strpos($CON, "{TOC}")) {
      $CON = str_replace("{TOC}", "", $CON);

			$TOC = "";

			foreach($headings as $h)
        $TOC .= str_repeat("<ul>", $h[0] - 2) . '<li><a href="#' . urlencode($h[1]) . '">' . remove_a($h[2]) . '</a></li>' . str_repeat("</ul>", $h[0] - 2);
        
      for($i = 0; $i < 5; $i++) // five possible headings
    		$TOC = preg_replace('/<\/ul>\n*<ul>/', "", $TOC);
    		
      $TOC = "<ul id=\"toc\">" . $TOC . "</ul>";
    		
			$TOC = preg_replace('/<\/li><ul>/', "<ul>", $TOC);
	    $TOC = preg_replace('/<\/ul><li>/', "</ul></li><li>", $TOC);
	    $TOC = preg_replace('/<(\/?)ul><\/?ul>/', "<$1ul><$1li><$1ul>", $TOC);
    }
    
    // returning content of {{CODE}}
    if($nbcode > 0) 
      $CON = preg_replace(array_fill(0, $nbcode, "/{{CODE}}/Us"), $matches_code[1], $CON, 1);
    
    // {html} tag
    if($n_htmlcodes > 0)
			$CON = preg_replace(array_fill(0, $n_htmlcodes, "/{HTML}/Us"), $htmlcodes[1], $CON, 1);
    
    plugin_call_method("formatEnd");
  }

  // Template substitution

  plugin_call_method("template");
  
  $html = preg_replace("/\{plugin:[^}]*\}/U", "", $html); // getting rid of absent plugin tags

  if($editable && is_writable($PAGES_DIR . $PAGE_TITLE . ".txt"))
    $EDIT = "<a href=\"./?page=" . urlencode($PAGE_TITLE) . "&amp;action=edit\" accesskey=\"5\" rel=\"nofollow\">$T_EDIT</a>";

	$tpl_subs = array(
	  array("{SEARCH}", "<form method=\"get\" action=\"./?page=" . urlencode($PAGE_TITLE) . "\"><div><input type=\"hidden\" name=\"action\" value=\"search\" /><input type=\"text\" name=\"query\" value=\"" . htmlspecialchars($query) . "\" tabindex=\"1\" /> <input class=\"submit\" type=\"submit\" value=\"$T_SEARCH\" accesskey=\"q\" /></div></form>"),
	  array("{HELP}", $action == "edit" ? "<a href=\"./?page=" . urlencode($HELP_PAGE) . "\" accesskey=\"2\" rel=\"nofollow\">$T_HELP</a>" : ""),
	  array("{HOME}", "<a href=\"./?page=" . urlencode($START_PAGE) . "\" accesskey=\"1\">$T_HOME</a>"),
	  array("{RECENT_CHANGES}", "<a href=\"./?action=recent\" accesskey=\"3\">$T_RECENT_CHANGES</a>"),
		array("{ERROR}",  $error),
		array("{HISTORY}", $HISTORY),
		array("{PAGE_TITLE}", htmlspecialchars($PAGE_TITLE == $START_PAGE ? $WIKI_TITLE : $PAGE_TITLE)),
		array("{EDIT}", $EDIT),
		array("{TOC}", $TOC),
		array("{PAGE_TITLE_BRUT}", htmlspecialchars($PAGE_TITLE == $START_PAGE ? $T_HOME : $PAGE_TITLE)),
		array("{WIKI_TITLE}", $WIKI_TITLE),
		array("{LAST_CHANGED_TEXT}", $T_LAST_CHANGED),
		array("{LAST_CHANGED}", $LAST_CHANGED),
		array("{CONTENT}", $action != "edit" ? $CON : ""),
		array("{CONTENT_FORM}", $CON_FORM_BEGIN),
		array("{/CONTENT_FORM}", $CON_FORM_END),
		array("{CONTENT_TEXTAREA}", $CON_TEXTAREA),
		array("{CONTENT_SUBMIT}", $CON_SUBMIT),
		array("{RENAME_FORM}", $RENAME_FORM_BEGIN),
		array("{/RENAME_FORM}", $RENAME_FORM_END),
		array("{RENAME_TEXT}", $RENAME_TEXT),
		array("{RENAME_INPUT}", $RENAME_INPUT),
		array("{RENAME_SUBMIT}", $RENAME_SUBMIT),
		array("{FORM_PASSWORD}", $FORM_PASSWORD),
		array("{FORM_PASSWORD_INPUT}", $FORM_PASSWORD_INPUT),
		array("{LANG}", $LANG),
		array("{LIST_OF_ALL_PAGES}", "<a href=\"?action=search\">$T_LIST_OF_ALL_PAGES</a>"),
		array("{WIKI_VERSION}", $WIKI_VERSION),
		array("{DATE}", $datetw),
		array("{IP}", $_SERVER['REMOTE_ADDR']),
		array("{COOKIE}", $_COOKIE['AUT_LIONWIKI'] ? '<a href="./?page=' . urlencode($PAGE_TITLE) . '&amp;erasecookie=1" rel="nofollow">' . $T_ERASE_COOKIE . '</a>' : "")
	);

	foreach($tpl_subs as $subs) // substituting values
	  $html = str_replace($subs[0], $subs[1], $html);

  echo $html; // voila
  
  // Function library
  
  function diff($f1, $f2, $short_diff = 0)
  {
    global $PAGE_TITLE, $HISTORY_DIR;
  
    function pcolor($color, $txt) { return "<div style=\"color:$color\">$txt</div>"; }

    $fn1 = $HISTORY_DIR . $PAGE_TITLE . "/" . $f1;
    $fn2 = $HISTORY_DIR . $PAGE_TITLE . "/" . $f2;

    if($fn2 < $fn1)
      list($fn1, $fn2) = array($fn2, $fn1);

    $f1 = lwopen($fn1, "r");
    $f2 = lwopen($fn2, "r");

    $a1 = explode("\n", @lwread($f1));
    $a2 = explode("\n", @lwread($f2));

    lwclose($f1);
    lwclose($f2);

    $d1 = array_diff($a1, $a2);
    $d2 = array_diff($a2, $a1);

    $ret = "<div style=\"font-family : monospace;\">";

    for($i = 0; $i <= max(sizeof($a2), sizeof($a1)); $i++) {
      if($r1 = array_key_exists($i, $d1))
        $ret .= pcolor("red", "-" . $d1[$i]);
      if($r2 = array_key_exists($i, $d2))
        $ret .= pcolor("green", "+" . $d2[$i]);
      if(!$r1 && !$r2 && !$short_diff)
        $ret .= pcolor("black", " " . $a2[$i]);
    }

    return $ret . "</div>";
	}
  
  function warning($w)
  {
		global $warning;
		
		if($warning)
		  echo "<b>Warning:</b> ", $w, "<br />";
	}
  
  function lwopen($name, $par)
  {
    global $FILE_TYPE;
    global $HISTORY_COMPRESSION;

    if($par == "r") {
      if(file_exists($name)) {
        $FILE_TYPE = "plain";
        return fopen($name, $par);
      } elseif(file_exists($name . ".gz")) {
        $FILE_TYPE = "gzip";
        return @gzopen($name . ".gz", $par);
      } elseif(file_exists($name . ".bz2")) {
        $FILE_TYPE = "bzip2";
        return @bzopen($name . ".bz2", $par);
      }
    } elseif($par == "w") {
      $FILE_TYPE = $HISTORY_COMPRESSION;
    
      if($HISTORY_COMPRESSION == "plain")
        return @fopen($name, $par);
      elseif($HISTORY_COMPRESSION == "gzip")
        return @gzopen($name . ".gz", $par);
      elseif($HISTORY_COMPRESSION == "bzip2")
        return @bzopen($name . ".bz2", $par);
    }
    
    return 0;
  }

  function lwclose($handle)
  {
    global $FILE_TYPE;

    if($FILE_TYPE == "plain")
      return fclose($handle);
    elseif($FILE_TYPE == "gzip")
      return gzclose($handle);
    elseif($FILE_TYPE == "bzip2")
      return bzclose($handle);

    $FILE_TYPE = "";
  }

  function lwread($handle)
  {
    global $FILE_TYPE;
    
    $buffer = "";
    $ret = "";

    if($FILE_TYPE == "plain") {
      $stat = fstat($handle);
    
      return fread($handle, $stat["size"]);
		}
    elseif($FILE_TYPE == "gzip") {
      while($buffer = gzread($handle, 8192))
        $ret .= $buffer;
      
      return $ret;
		}
    elseif($FILE_TYPE == "bzip2") {
      while($buffer = bzread($handle, 8192))
        $ret .= $buffer;

      return $ret;
		}
  }

  function lwwrite($handle, $data)
  {
    global $FILE_TYPE;

    if($FILE_TYPE == "plain")
      return fwrite($handle, $data);
    elseif($FILE_TYPE == "gzip")
      return gzwrite($handle, $data);
    elseif($FILE_TYPE == "bzip2")
      return bzwrite($handle, $data);
  }

  // checks autentification
  function authentified()
  {
    global $PASSWORD, $sc;
    $pwd = md5($PASSWORD);
    if(empty($PASSWORD) || $_COOKIE['AUT_LIONWIKI'] == $pwd || $sc == $PASSWORD) {
      if(($PASSWORD != "" && empty($_COOKIE['AUT_LIONWIKI'])) || $_COOKIE['AUT_LIONWIKI'] != $pwd) {
        setcookie('AUT_LIONWIKI', $pwd, time() + $PROTECTED_READ ? $COOKIE_LIFE_READ : $COOKIE_LIFE_WRITE);
        $_COOKIE['AUT_LIONWIKI'] = $pwd;
      }
      return true;
    } else
      return false;
  }

  /** Call a method for all plugins
   * $mname: method name
   * [...] : method arguments
   * return: true if treated by a plugin
   */
  function plugin_call_method($mname)
  {
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
	<title>{WIKI_TITLE} - {PAGE_TITLE_BRUT}</title>
	<style type="text/css">
*{margin:0;padding:0;}
body{font-family:serif;font-size:12px;line-height:16px;padding:10px 20px 20px 20px;}
a:link,a:visited{color:#006600;text-decoration : none;border-bottom : 1px dotted #006600;}
p { margin : 5px 0 5px 0; }
a.pending{color:#990000;}
pre{border:1px dotted #ccc;padding:4px;width:640px;overflow:auto;margin:3px;}
img,a img{border:0px}
h1,h2,h3,h4,h5,h6{letter-spacing:2px;font-family:serif;font-weight:normal;margin:15px 0 15px 0px;color:#006600;}
h1{margin:18px 0 15px 15px;font-size : 22px;}
hr{margin:10px 0 10px 0;height:0px;overflow:hidden;border:0px;border-top:1px solid #006600;}
ul,ol{padding:5px 0px 5px 20px;}
table{text-align:left;}
.error{color:#F25A5A;font-weight:bold;}
form{display:inline}
#rename-form{display:block;margin-bottom:6px;}
#content-submit{margin-top:6px;}
#content-textarea{width:100%;}
#content-submit{float:right;}
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
	</style>
  {plugin:RSS}
</head>

<body>
<table border="0" width="100%" cellpadding="4" id="mainTable" cellspacing="0" summary="{PAGE_TITLE_BRUT}">
	<tr id="headerLinks">
		<td colspan="2">{HOME} {RECENT_CHANGES}</td>
		<td style="text-align : right;">{EDIT} {HELP} {HISTORY}</td>
	</tr>
	<tr><th colspan="3"><hr /><h1>{PAGE_TITLE}</h1></th></tr>
	<tr>
		<td id="mainContent" colspan="3">
      {TOC}
			<div class="error">{ERROR}</div>
{CONTENT}
{RENAME_FORM} {RENAME_TEXT} {RENAME_INPUT} {FORM_PASSWORD} {FORM_PASSWORD_INPUT} {plugin:CAPTCHA_QUESTION} {plugin:CAPTCHA_INPUT} {RENAME_SUBMIT} {/RENAME_FORM}
{CONTENT_FORM} {CONTENT_TEXTAREA} {CONTENT_SUBMIT} <p style="float:right;margin:6px">{FORM_PASSWORD} {FORM_PASSWORD_INPUT} {plugin:CAPTCHA_QUESTION} {plugin:CAPTCHA_INPUT}</p> {/CONTENT_FORM}
		</td>
	</tr>
	<tr><td colspan="3"><hr /></td></tr>
	<tr>
		<td><div>{SEARCH}</div></td>
		<td>Powered by <a href="http://lionwiki.0o.cz/">{WIKI_VERSION}</a>. {LAST_CHANGED_TEXT}: {LAST_CHANGED} {COOKIE}</td>
		<td style="text-align : right;">{EDIT} {HELP} {HISTORY}</td>
	</tr>
</table>
</body>
</html>
'; }
?>
