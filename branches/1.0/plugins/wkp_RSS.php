<?php # coding: utf-8

/* Code basé sur ModuleRss.php de TigerWiki
 ******
 * Génération d'un flux rss des 10 dernières modifications
 * lorsque on écrit une page.
 * Fichier généré: rss.xml à la racine du wiki.
 * Ajoutez {RSS} dans entre les tags <head></head> du template.html
 * pour que vos visiteurs découvrent le flux.
 */

class RSS
{
  public $description = "RSS export tracking last changes.";

	public $max_changes = 50; // RSS contains $max_changes last changes
	public $short_diff = false; // RSS omits unchanged lines

  public $template = '<rss version="2.0">
<channel>
<title>{WIKI_TITLE}</title>
<link>{PAGE_LINK}</link>
<description>{WIKI_DESCRIPTION}</description>
<language>{LANG}</language>
{CONTENT_RSS}
</channel>
</rss>'; // don't change template. This exact form is needed for correct functioning.

	function writedPage($file)
	{
	  global $WIKI_TITLE, $PAGES_DIR, $PAGE_TITLE, $HISTORY_DIR, $LANG, $TIME_FORMAT, $BASE_DIR, $USE_HISTORY;
	  
	  if(!$USE_HISTORY)
	    return true;
	    
    // Attention, bug si https ou port différent de 80 ?
		$pagelink = "http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];

		preg_match("/<\/language>(.*)<\/channel>/s", @file_get_contents($BASE_DIR . "rss.xml"), $matches);
		
		$items = $matches[1];
		
		$pos = -1;
		
		// count items
		for($i = 0; $i < $this->max_changes - 1; $i++)
		  if(!($pos = strpos($items, "</item>", $pos + 1)))
		    break;
		
		if($pos) // if count is higher than $max_changes - 1, cut out the rest
		  $items = substr($items, 0, $pos + 7);

    if($opening_dir = @opendir($HISTORY_DIR . $PAGE_TITLE . "/")) {
      // find two last revisions of page
      while($filename = @readdir($opening_dir))
        if(preg_match('/\.bak.*$/', $filename))
          $files[] = basename(basename($filename, ".gz"), ".bz2");

      rsort($files);
      
      $newest = diff($files[0], $files[1], $this->short_diff); // LionWiki diff function
      $timestamp = filemtime($PAGES_DIR . $PAGE_TITLE . ".txt");
      
      $n_item = "
	<item>
	  <title>$PAGE_TITLE</title>
	  <pubDate>". date("r", $timestamp)."</pubDate>
	  <link>$pagelink?page=".urlencode($PAGE_TITLE)."</link>
	  <description>$newest</description>
	</item>";
	  } else
	    warning("RSS plugin: can't open history directory!");
	
	  $rss = str_replace('{WIKI_TITLE}', $WIKI_TITLE, $this->template);
	  $rss = str_replace('{PAGE_LINK}', $page_link, $rss);
	  $rss = str_replace('{LANG}', $LANG, $rss);
	  $rss = str_replace('{WIKI_DESCRIPTION}', "RSS feed from " . $WIKI_TITLE, $rss);
	  $rss = str_replace('{CONTENT_RSS}', $n_item . $items, $rss);
	
	  if(!$file = @fopen($BASE_DIR."rss.xml", "w")) {
	     warning("Opening file for writing RSS file is not possible! Please adjust directory rights to 777.");

	     return true;
		}

		fwrite($file, $rss);
		fclose($file);

		return true;
	}

	function template()
	{
	  global $html;

	  $html = str_replace('{plugin:RSS}','<link rel="alternate" type="application/rss+xml" title="RSS" href="rss.xml" />', $html);

	  return FALSE;
	}
}

?>
