<?php
//roeka_bot
require('secret.php');	//defines SECRETSOURCE & TOKEN
$data = file_get_contents(SECRETSOURCE);
$data = json_decode($data);

function chat($text)
{
$write = file_get_contents('https://api.telegram.org/bot' . TOKEN . '/sendmessage?text=' .$text.'&chat_id=' . CHATID);
//print_r($write);
}

function read()
{
$get = file_get_contents('https://api.telegram.org/bot' . TOKEN . '/getupdates');

$content = json_decode($get, true);
$messages = count($content['result']);
echo $content['result'][$messages - 1]['message']['text'];
}

function spaces($spaces)
{
	if ($spaces > 0)
		return ' '. spaces($spaces-1);
	return '';
}

$class = 'MSS12';
$text = '';


$text .= "TTN Update $class (ohne Gewähr)\n";

foreach ($data as $a => $b) {
	
	$hours = array();
	
	if (is_array($b) && $b[0] != '') {

		$dateArray = explode(' ', $b[0]);
		$date = explode('.', $dateArray[1]);
		if (!empty($date[2]) && !isset($_GET['full'])) {
			if (new DateTime() > new DateTime($date[0].'-'.$date[1].'-'.$date[2]." 20:00:00")) {
				//$stamp = strtotime($date[0].'-'.$date[1].'-'.$date[2])
				continue;
			}
		}
		
		$text .= "$b[0] \n";
		//$b[0] : Montag 01.01.201X
		
		$ii = 0;
		
		//array content:
		// [0] hour
		// [1] grade
		// [2] class
		// [3] room
		// [4] substitute
		// [5] type ("fällt aus")
		// [6] exceptions 
		foreach ($b[1] as $i => $array) {
			if (strpos($array[1], $class) === 0 || strpos($array[1], str_replace(' ', '', $class)) === 0 || strpos(', '.$array[1], $class) !== false) {
				$text .= $array[0].".". spaces((2 - strlen($array[0])) * 2 + 1);
				$kurs = $array[2].(!empty($class2teacher[$array[2]])? ' ('.$class2teacher[$array[2]].')' : '').(!empty($array[3]) ? ' ['.$array[3].']'  : '');
				$text .= $kurs. spaces(19 - strlen($kurs) * 2) .$array[5].''.(!empty($array[4]) ? '('.$array[4].')' : '').' '.(!empty($array[6]) ? '-'.$array[6] : '')."\n";
				$hours[$array[0]]=true;
				$ii++;
			}
		}
		if ($ii == 0)
			$text .= 'Nichts fällt aus';
	}
}

chat(urlencode($text));
