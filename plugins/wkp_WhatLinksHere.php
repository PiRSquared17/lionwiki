<?php 

class WhatLinksHere
{
	public $description = "What links here?";

	function action($a)
	{
	  global $PAGE_TITLE, $PAGES_DIR, $CON;

	  if($a == "whatlinkshere")
	  {
	    $CON = "";
	     
	    $editable = false;
    	$dir = opendir(getcwd() . "/$PAGES_DIR");

    	while($file = readdir($dir)) {
	      if(preg_match("/\.txt$/", $file)) {
	        @$con = file_get_contents($PAGES_DIR . $file);
	        $query = preg_quote($PAGE_TITLE);

	        if(@preg_match("/\[([^|\]]+\|)? *$query(#[^\]]+)? *\]/i", $con))
	          $files[] = substr($file, 0, strlen($file) - 4);
	      }
	    }
	    
	    if(is_array($files)) {
	      sort($files);

	      foreach($files as $file)
	        $CON .= "<a href=\"./?page=" . $file . "\">" . $file . "</a><br />";
	    }

	    $PAGE_TITLE = "What links to $PAGE_TITLE? (" . count($files) . ")";
	     
	    return true;
	  }
	  else
	  	return false;
	}

	function template()
	{
	  global $CON, $PAGE_TITLE;

		str_replace("{plugin:WHAT_LINKS_HERE}", "<a href=\"?action=whatlinkshere&page=".urlencode($PAGE_TITLE)."\">What links here?</a>", $CON);
	}
}

?>
