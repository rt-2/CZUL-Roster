<!DOCTYPE html>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//require_once(dirname(__FILE__).'/CANotAPI.inc.php');
//require_once(dirname(__FILE__).'/resources/fir.data.inc.php');

function curlGet($url)
{
	
	
	$curl = curl_init();

	curl_setopt_array($curl, [
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_URL => $url,
		CURLOPT_USERAGENT => 'Codular Sample cURL Request',
		CURLOPT_SSL_VERIFYPEER => false,
	]);

	$data = curl_exec($curl);

	curl_close($curl);
	return $data;
}


$ratingNames = [
	'', // 0 (not used)
	'OBS', // 1
	'S1', // 2
	'S2', // 3
	'S3', // 4
	'C1', // 5
	'C2', // 6 (not used)
	'C3', // 7
	'I1', // 8
	'I2', // 9 (not used)
	'I3', // 10
	'SUP', // 11
];


$resp = curlGet('https://vatcan.ca/api/v1/facility/roster?api_key=XXXXXXXXXXXX');


$allMembers = json_decode($resp)->facility->roster;
$allMembers = json_decode(json_encode($allMembers), true);

usort($allMembers, function($a, $b) {
    return $b['rating'] - $a['rating'];
});

$allInstructors = array();


$allControllerTrainingStates = file_get_contents('../CONTROLLERS_TRAINING_STATE.data.json');
//$allControllerTrainingStates = curlGet('https://raw.githubusercontent.com/rt-2/CZUL-Prod-Configs/master/Members/Controllers.data.json');
$allControllerTrainingStates = json_decode($allControllerTrainingStates)->CONTROLLERS_TRAINING_STATE;
$allControllerTrainingStates = json_decode(json_encode($allControllerTrainingStates), true);

$allGuestStates = file_get_contents('../GUESTS_TRAINING_STATE.data.json');
$allGuestStates = json_decode($allGuestStates)->GUESTS_TRAINING_STATE;
$allGuestStates = json_decode(json_encode($allGuestStates), true);


class RosterMemberInfosFinal
{
	public $data;
	
	public function __construct() {
		$this->data = [
			'cid' => '',
			'name' => '',
			'rating' => '',
			'active' => false,
			'position' => '',
			'instructor' => '',
		];
	}
	
}
class RosterGuestInfosFinal
{
	public $data;
	
	public function __construct() {
		$this->data = [
			'cid' => '',
			'name' => '',
			'rating' => '',
			'position' => '',
		];
	}
	
}

?>
<html>
    <head>
    <meta charset="UTF-8">
    <title>Roster</title>

    <!--<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>-->
	
	<style>
		body {
			font-family: sans-serif;
			color: rgb(45, 45, 75);
			font-size: 14px;
		}
		table td {
			text-align: center;
			padding: 5px 15px;
			white-space: nowrap;
			border: 1px dashed;
			border-color: lightgrey;
		}
		table {
			border: 7px ridge;
			border-color: lightblue;
			width: 100%;
		}
		.para {
			margin: 30px 0;
			width: 800px;
		}
		#mainContent {
			margin: 0 auto;
			padding: 20px 0px;
			width: 800px;
		}
	</style>
	
</head>
<body>

	<div id="mainContent">
		<div class="para">

			<h2>Guests: </h2>

			<table border="true" cellpadding="5">
				<tr>
				<?php
				$thoseColumnObj = new RosterGuestInfosFinal();
					foreach(array_keys($thoseColumnObj->data) as $columnName)
					{
						echo '<td><b>'.strToUpper($columnName).'</b></td>';
					}
				?>
				</tr>
				<?php
				
				
				foreach($allGuestStates as $cid => $attrs)
				{
					if($cid > 799999)
					{
						$thisAllInfos = new RosterGuestInfosFinal();
						$thisAllInfos->data['cid'] = $cid;
						
						
						$resp = curlGet('https://cert.vatsim.net/cert/vatsimnet/idstatusint.php?cid='.$thisAllInfos->data['cid']);

						
						$xml = simplexml_load_string($resp);
						$json = json_encode($xml);
						$array = json_decode($json,TRUE);
						$fetchGuestData = $array['user'];
						
						
						
						$thisAllInfos->data['name'] = ucwords($fetchGuestData['name_first'].' '.$fetchGuestData['name_last']);
						$thisAllInfos->data['rating'] = $fetchGuestData['rating'];
						
					
						if(array_key_exists('position', $attrs))
						{
							$thisAllInfos->data['position'] = $attrs['position'];
						}
						
						
						$thisAllInfos->data['rating'] = $ratingNames[$thisAllInfos->data['rating']];
						
						
						
						
						?>
						<tr>
							<?php
								foreach($thisAllInfos->data as $key => $value)
								{
									echo '<td>'.$value.'</td>';
								}
							
							?>
							
						</tr>
						<?php
					}
				}
				?>
			</table>
		</div>
		
		<div class="para">

			<h2>Members: </h2>
			
			<table border="true" cellpadding="5">
				<tr>
				<?php
				$thoseColumnObj = new RosterMemberInfosFinal();
					foreach(array_keys($thoseColumnObj->data) as $columnName)
					{
						echo '<td><b>'.strToUpper($columnName).'</b></td>';
					}
				?>
				</tr>
				<?php
				
				
				foreach($allMembers as $thisMember)
				{
					$thisAllInfos = new RosterMemberInfosFinal();
					$thisAllInfos->data['cid'] = $thisMember['cid'];
					$thisAllInfos->data['name'] = ucwords($thisMember['fname'].' '.$thisMember['lname']);
					$thisAllInfos->data['rating'] = $thisMember['rating'];
					
					if($thisAllInfos->data['rating'] >= 8)
					{
						$allInstructors[$thisAllInfos->data['cid']] = $thisAllInfos->data['name'];
					}
					
					if(array_key_exists($thisAllInfos->data['cid'], $allControllerTrainingStates))
					{
						$thisAllInfos->data['active'] = true;
						$thisTrainingState = $allControllerTrainingStates[$thisAllInfos->data['cid']];
						
						$thisAllInfos->data['position'] = $thisTrainingState['position'];
						
						if(array_key_exists('instructor', $thisTrainingState))
						{
							$thisAllInfos->data['instructor'] = $thisTrainingState['instructor'];
							if(strlen($thisAllInfos->data['instructor']) > 0)
							{
								if(array_key_exists($thisAllInfos->data['instructor'], $allInstructors))
								{
									$thisAllInfos->data['instructor'] = $allInstructors[$thisAllInfos->data['instructor']].' ('.$thisAllInfos->data['instructor'].')';
								}
							}
						}
					}
					
					if(strlen($thisAllInfos->data['position']) == 0)
					{
						if($thisAllInfos->data['rating'] >= 2)
						{
							$thisAllInfos->data['position'] = '<small>Requires<br>Recertification</small>';
						}
						else
						{
							$thisAllInfos->data['position'] = 'In Training';
						}
					}
					
					$thisAllInfos->data['rating'] = $ratingNames[$thisAllInfos->data['rating']];
					$thisAllInfos->data['active'] = ($thisAllInfos->data['active'] === true)? 'Oui': 'Non';
					
					
					
					
					?>
					<tr>
						<?php
							foreach($thisAllInfos->data as $key => $value)
							{
								echo '<td>'.$value.'</td>';
							}
						
						?>
						
					</tr>
					<?php
				}
				?>
			</table>
		</div>
	</div>
</body>
</html>
