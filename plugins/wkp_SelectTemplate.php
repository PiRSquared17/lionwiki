<?php

class SelectTemplate
{
	var $desc = array(
		array("SelectTemplate", "creates select box in which you can choose template.")
	);

	var $tpls = array(
		"templates/dandelion.html" => "Dandelion",
		"templates/red.html" => "Red panel",
		"templates/minimal.html" => "Minimal",
		"templates/terminal.html" => "Green terminal",
		"templates/wikiss.html" => "WiKiss"
	);

	function template()
	{
		global $html, $page, $action, $TEMPLATE, $CON;

		$select = "
<form action=\"$self\" method=\"get\">
<input type=\"hidden\" name=\"page\" value=\"" . htmlspecialchars($page) . "\" />
<input type=\"hidden\" name=\"action\" value=\"" . htmlspecialchars($action) . "\" />
<input type=\"hidden\" name=\"permanent\" value=\"1\" />
<select name=\"template\" id=\"selectTemplate\" onchange=\"this.form.submit();\">
";

		foreach($this->tpls as $t_file => $t_name) {
			$selected = $TEMPLATE == $t_file ? " selected " : "";

			$select .= "<option value=\"$t_file\"$selected>".htmlspecialchars($t_name)."</option>\n";
		}

		$select .= "</select></form>\n";

		$html = template_replace("plugin:SELECT_TEMPLATE", $select, $html);
		$CON = template_replace("SELECT_TEMPLATE", $select, $CON);
	}

	function pluginsLoaded()
	{
		global $TEMPLATE;

		if(!empty($_REQUEST["template"])) {
			$TEMPLATE = $_REQUEST["template"];

			if($_REQUEST["permanent"] == 1)
				setcookie('LW_TEMPLATE', $TEMPLATE, time() + 365 * 86400);
		}
		else if(!empty($_COOKIE["LW_TEMPLATE"]))
			$TEMPLATE = $_COOKIE["LW_TEMPLATE"];
	}
}