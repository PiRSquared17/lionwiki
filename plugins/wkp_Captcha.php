<?php
/*
	Captcha plugin is simple spam filtering plugin. It asks user simple questions
	(If today is saturday, what day is tomorrow?). It is active only when no
	password protection is used.

	(c) Adam Zivner 2008

	GPL'd, of course
*/

class Captcha
{
	public $description = "Text captcha";
  public $question_file;
  public $permanent = true; // remember first correct answer and don't ask again
	public $cookie_password;

	function __construct()
	{
    $this->question_file = dirname(__FILE__) . "/captcha_questions.txt";
    $this->cookie_password = md5($_SERVER["SCRIPT_FILENAME"]);
	}

  /*
    Functions return number of questions in question file. Method is very simple, it just counts number of occurence of "--" at the begining of the line.
  */

  function questionCount()
  {
    $count = 0;

    $q = fopen($this->question_file, "r");

    if(!$q) {
      warning("Captcha plugin: Can't open captcha questions file $this->question_file.");
    
      return 0; // Oops
		}

    while($line = fgets($q))
      if(!strcmp(trim($line), "--"))
        $count++;

    fclose($q);

    return $count;
  }

  /*
    Function return $line. line of $i. question. Convention is that 1. line is question and second line is answer(s). Numbering is Pascal-like, that means that getQuestion(1, 1) returns 1. line of 1. question.
  */

  function getQuestion($i, $line)
  {
    $count = 0;

    $q = fopen($this->question_file, "r");

    if(!$q) {
      warning("Captcha plugin: Can't open captcha questions file $this->question_file.");
    
      return 0; // Oops
		}

    $str = "";

    while($l = fgets($q)) {
      if(!strcmp(trim($l), "--")) {
        $count++;

        if($count == $i) {
          for($k = 0, $str = ""; $k < $line && $str = fgets($q); $k++);

          break;
        }
      }
    }

    fclose($q);

    return $str;
  }

	function checkCaptcha()
	{
	  global $PASSWORD, $error, $plugin_saveok;

    if(!empty($PASSWORD) || ($this->permanent && $_COOKIE["AUT_CAPTCHA"] == $this->cookie_password))
			return true;

    $question_id = $_REQUEST["qid"];
	  $answer = trim($_REQUEST["ans"]);

	  if(empty($question_id) || empty($answer) || !is_numeric($question_id)) {
			$plugin_saveok = false;

			return true;
		}

	  $right_answers = explode(",", $this->getQuestion($question_id, 2));

	  $equals = false;

	  foreach($right_answers as $a)
	    if(!strcasecmp(trim($a), $answer)) {
	      $equals = true;
	      
	      if($this->permanent)
	      	setcookie('AUT_CAPTCHA', $this->cookie_password, time() + 365 * 24 * 3600);

				break;
	    }

		if(!$equals) {
			$error = "Captcha plugin: Given answer is not correct. Try again.";
			
			$plugin_saveok = false;
		}

		return true;
	}
	
	function writingPage() { $this->checkCaptcha(); }
	function renamingPage() { $this->checkCaptcha(); }

	function template()
	{
  	global $html, $PASSWORD, $action;

		if($action != "edit" || !empty($PASSWORD) || ($this->permanent && $_COOKIE["AUT_CAPTCHA"] == $this->cookie_password))
			return;
			
		$question_count = $this->questionCount();
  	$question_id = rand(1, $question_count);
  	$question_text = trim($this->getQuestion($question_id, 1));

		$html = str_replace("{plugin:CAPTCHA_QUESTION}", $question_text, $html);
		$html = str_replace("{plugin:CAPTCHA_INPUT}", "<input type=\"hidden\" name=\"qid\" value=\"$question_id\" /><input type=\"text\" id=\"captcha-input\" name=\"ans\" class=\"input\" value=\"\" />", $html);
	}
}

?>
