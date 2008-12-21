<?php

/** List of installed plugins
 * Access through : ?action=list
 */
class ListPlugins
{
	public $description = "List of installed plugins";

	function action($a)
	{
	  global $plugins, $CON, $PAGE_TITLE, $editable;
	  
	  if($a == "list")
	  {
	     $CON = '';
	     $editable = false;
	     $PAGE_TITLE = "List of all plugins";

	     foreach($plugins as $p)
	        $CON .= get_class($p) . " : ". $p->description ."<br/>\n";
	        
	     return true;
	  }
	  return false;
	}

	function template()
	{
	  global $CON;
	
		str_replace("{plugin:LIST_OF_PLUGINS}", "<a href=\"?action=list\">List of installed plugins.</a>", $CON);
	}
}
?>
