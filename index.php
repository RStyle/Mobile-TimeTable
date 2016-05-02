<?php

$startTime = microtime(true);

if (isset($_GET['logout'])) {
	setcookie("ScheduleUser", '', time() - 3600);
	setcookie("SchedulePW", '', time() - 3600);
	setcookie("ScheduleStufe", '', time() - 3600);
	$_COOKIE['ScheduleUser'] = '';
	$_COOKIE['SchedulePW'] = '';
	$_COOKIE['ScheduleStufe'] = '';
}
	

$login = false;
$loginViaCookie = false;
function checkLogin($user = '', $password = '')
{
	global $loginViaCookie, $login;

	if (!empty($_COOKIE['ScheduleUser']) && !empty($_COOKIE['SchedulePW']) && !empty($user) && !empty($password)) {
		$loginViaCookie = true;
		$login = true;
		return;
	}
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://roeka-kh.de/vertretungsplan/aktuell.pdf');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if (isset($_COOKIE['ScheduleUser']) && isset($_COOKIE['SchedulePW'])) {
		curl_setopt($ch, CURLOPT_USERPWD, $_COOKIE['ScheduleUser'].':'.$_COOKIE['SchedulePW']);
		$loginViaCookie = true;
	}
	else
		curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$password);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	$file = curl_exec($ch);
	curl_close($ch);

	file_put_contents('../../aktuell.pdf', $file);

	if (filesize('../../aktuell.pdf') > 5000) {

		$downloads = file_get_contents('downloads.txt');
		file_put_contents('downloads.txt', $downloads + 0.5);

		if ($loginViaCookie == false) {
			setcookie("ScheduleUser", $_POST['login'], time() + 60 * 60 * 24 * 30);
			setcookie("SchedulePW", $_POST['password'], time() + 60 * 60 * 24 * 30);
			setcookie("ScheduleStufe", $_POST['stufe'], time() + 60 * 60 * 24 * 30);
			$_COOKIE['ScheduleStufe'] = $_POST['stufe'];
		}
		
		$login = true;
		
	}elseif ($loginViaCookie == true) {
		setcookie("ScheduleUser", '', time() - 3600);
		setcookie("SchedulePW", '', time() - 3600);
		setcookie("ScheduleStufe", '', time() - 3600);
	}
}

if (isset($_POST['login']) && isset($_POST['password']))
	checkLogin($_POST['login'], $_POST['password']);
elseif (isset($_COOKIE['ScheduleUser']) && isset($_COOKIE['SchedulePW']))
	checkLogin();

?><!DOCTYPE HTML>
<html lang="de">
	<head>
		<title>Vertretungsplan</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.3/jquery.mobile.min.css" />
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
		<script src="http://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.3/jquery.mobile.min.js"></script>

		<link rel="shortcut icon" href="icon.png" type="image/png" />		

		<style type="text/css">
		<!--
			[data-role=page]{height: 100% !important; position:relative !important;}
			[data-role=footer]{bottom:0; position:fixed !important; top: auto !important; width:100%;} 
			
			
			.ui-controlgroup-horizontal .ui-controlgroup-controls { display: block !important; }
			
			@media ( max-width: 350px ) {
				* {font-size:0.9em;}
			}
			
			
			@media ( min-width: 170px ) {
				/* Show the table header rows and set all cells to display: table-cell */
				.my-custom-breakpoint td,
				.my-custom-breakpoint th,
				.my-custom-breakpoint tbody th,
				.my-custom-breakpoint tbody td,
				.my-custom-breakpoint thead td,
				.my-custom-breakpoint thead th {
					display: table-cell;
					margin: 0;
				}
				/* Hide the labels in each cell */
				.my-custom-breakpoint td .ui-table-cell-label,
				.my-custom-breakpoint th .ui-table-cell-label {
					display: none;
				}
			}
		-->
		</style>
	</head>
	<body>
	
	
		<div data-role="page" id="phome" data-title="Vertretungsplan">
		
	<?php if ($login === true) { ?>
		
<?php
	require('secret.php');	//defines SECRETSOURCE - gets the online time table in json
	$data = file_get_contents(SECRETSOURCE);
	$data = json_decode($data);
	
	$classes = array('MSS 13', 'MSS 12', 'MSS 11');
	$class = $classes[$_COOKIE['ScheduleStufe']];
	$teachers = array();
	$teacherData = file_get_contents('teacher.json');	//file containing the names of the teachers
	$teacherData = json_decode($teacherData);
	$teachers = $teacherData;
	$kurs2teacher = array();
	
	if (str_replace(' ', '', $class) == 'MSS13') {
		foreach ($teachers as $kurs => $lehrer) {
			$kurs2teacher[$lehrer[0]] = $lehrer[1];
		}
	}

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
			
			echo '
			<div data-role="header"><h3 style="text-overflow:initial;overflow:visible">'.$b[0].'</h3></div>
			<table data-role="table" id="temp-table" class="my-custom-breakpoint">
				<thead>
					<tr>
						<th data-priority="persist">Std</th>
						<!--<th>Kl</th>-->
						<th>Fach</th>
						<th>Raum</th>
						<th>Art (Vertretung)</th>
					</tr>
				</thead>
				<tbody>';

			$ii = 0;
			foreach ($b[1] as $i => $array) {
				
				if (strpos($array[1], $class) === 0 || strpos($array[1], str_replace(' ', '', $class)) === 0 || strpos(', '.$array[1], $class) !== false) {
					echo'
						<tr>
							<th>'.($hours[$array[0]] !== true ? $array[0] : '').'</th>
							<!--<td>'.str_replace('MSS ', '', $array[1]).'</td>-->
							<td>'.$array[2].(!empty($kurs2teacher[$array[2]])? ' ('.$kurs2teacher[$array[2]].')' : '').'</td>
							<td>'.$array[3].'</td>
							<td>'.$array[5].' '.(!empty($array[4]) ? '('.$array[4].')' : '').' '.(!empty($array[6]) ? '-'.$array[6] : '').'</td>
						</tr>
					';
					$hours[$array[0]]=true;
					$ii++;
				}
			}
			if ($ii == 0)
				echo'
						<tr>
							<th>Nichts fällt aus</th>
						</tr>
					';
			
			echo'
				</tbody>
			</table>';

		}
	}
	
	} else {
?>
			<div data-role="content">
				
					Gebe ein mal deine Logindaten an; danach kannst du den Vertretungsplan jederzeit auf "tiny.cc/roeka" aufrufen. <br>
					Am besten du erstellst dir ein Lesezeichen für diese Website auf deinen Homescreen :)
				
					<form action="index.php" method="post" data-ajax="false">
					<div data-role="fieldcontain">
						<label for="stufe">Stufe:</label>
						<select name="stufe" id="stufe-1">
							<option value="0">MSS 13</option>
							<option value="1">MSS 12</option>
							<option value="2">MSS 11</option>
						</select>
						<label for="login">Username:</label>
						<input type="text" name="login" id="login" value="<?php echo $_COOKIE['ScheduleUser']; ?>" data-mini="true"/>
						<label for="password">Passwort:</label>
						<input type="password" name="password" id="pw" value="<?php echo $_COOKIE['SchedulePW']; ?>" data-mini="true"/>
					</div>
					<div data-role="fieldcontain">
						<button type=​"submit">Login</button>​
					</div>
					</form>
				
			</div>
			
	<?php } ?>
			<div data-role="header">
				<h1 style="text-overflow:initial;overflow:visible">Vertretungsplan<?php if (!empty($class)) echo '<br>'.$class; ?></h1>
			</div>
			<?php echo $data[0]. '<br>StyleUI 0.2.2 <i>last update: 10.12.15</i><br> (ohne Gewähr)' ?><br><a href="?logout">Logout</a><br><a href="?full">+past data (experimental)</a>
			<?php if (isset($_GET['full'])) echo '<br><i>performance:'. number_format(( microtime(true) - $startTime), 4) .'s</i>'; ?>
			<div style="height:33px"></div>	
			<div data-role="footer">
				<div data-role="navbar">
					<ul>
						<li>© 2015 <?php if (rand(0,1) == 0) echo 'Roman S. &amp; Florian S.'; else echo 'Florian S. &amp; Roman S.'; ?></li>
					</ul>
				</div>
			</div>		
		</div> 

			</body>
</html>
