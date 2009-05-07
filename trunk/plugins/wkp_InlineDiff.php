<?php

/**
 * This plugin works as a middle-man between LionWiki and stripped down Text_Diff
 * package from PEAR. Thanks to its authors, it works great!
 */

class InlineDiff
{
  var $desc = array(
		array("InlineDiff", "provides inline diff which is much better than default built-in")
	);

	protected function mapNewline($arr) {
		$ret = array();

		foreach($arr as $row)
			$ret[] = $row . "\n";

		return $ret;
	}

	function diff($f1, $f2, &$ret)
	{
		require_once 'InlineDiff/diff.php';
		require_once 'InlineDiff/renderer.php';
		require_once 'InlineDiff/inline.php';

		// Load the lines of each file.

		$c1 = lwread($f1);
		$c2 = lwread($f2);

		$lines1 = empty($c1) ? array() : explode("\n", $c1);
		$lines2 = empty($c2) ? array() : explode("\n", $c2);

		$lines1 = $this->mapNewline($lines1);
		$lines2 = $this->mapNewline($lines2);

		// Create the Diff object.
		$diff = new Text_Diff($lines1, $lines2);

		$renderer = new Text_Diff_Renderer_inline();

		global $plugin_ret_diff;

		$plugin_ret_diff = "<pre id=\"diff\">" . $renderer->render($diff) . "</pre>";

		return true;
	}
}
?>
