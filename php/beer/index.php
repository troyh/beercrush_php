<?php
require_once('OAK/oak.class.php');

include('../header.php');
?>

<h1>Beers</h1>

<ul>
	<li><a href="/beers/">By Name</a></li>
	<li><a href="/beers/bydate/">By Date</a></li>
</ul>
	
<?php
$oak=new OAK;

switch ($_GET['view'])
{
	case 'date':
		$beers=json_decode(file_get_contents($oak->get_config_info()->api->base_uri."/beers?view=date"));
		if (isset($_GET['date']) && preg_match('/(\d+)-(\d+)-(\d+)/',$_GET['date'],$matches))
		{
			$selected_year=$matches[1];
			$selected_month=$matches[2];
			$selected_day=$matches[3];
		}
		else
		{	// Get the 1st date in the $beers list
			$selected_year=null;
			$selected_month=null;
			$selected_day=null;
		}
		
		?>
		<div>
		<?php
		foreach ($beers->days as $year=>$months)
		{
			if (is_null($selected_year))
				$selected_year=$year;
				
			print "<div>$year</div>";
			foreach ($months as $month=>$days)
			{
				if (is_null($selected_month))
					$selected_month=$month;
					
				$t=mktime(0,0,0,$month,1,$year);
				print "<div>".date('F',$t)."</div>";
				foreach ($days as $day=>$b)
				{
					if (is_null($selected_day))
						$selected_day=$day;
						
					if ($selected_year==$year && $selected_month==$month && $selected_day==$day)
						print "<div>$day (".count($beers->days->$year->$month->$day).")</div>";
					else
						print "<div><a href=\"/beers/bydate/$year/$month/$day\">$day</a> (".count($beers->days->$year->$month->$day).")</div>";
				}
			}
		}
		?>
		</div>
		<div id="beers_list">
		<?php
		foreach ($beers->days->$selected_year->$selected_month->$selected_day as $beer)
		{
			$brewery=json_decode(file_get_contents($oak->get_config_info()->api->base_uri.'/'.str_replace(':','/',$beer->brewery_id)));
		?>
			<div><a href="/<?=str_replace(':','/',$beer->id)?>"><?=$beer->name?></a> by <a href="/<?=str_replace(':','/',$beer->brewery_id)?>"><?=$brewery->name?></a></div>
		<?php
		}
		?>
		</div>
		<?php
		break;
	case 'name':
	default:
		$beers=json_decode(file_get_contents($oak->get_config_info()->api->base_uri."/beers"));
	
		if (empty($_GET['letter']))
			$page='#';
		else
			$page=$_GET['letter'];
?>

		<div id="letters">
		<?php foreach ($beers as $letter=>$data) { ?>
			<a href="/beers/<?=$letter?>"><?=$letter?></a>
		<?php } ?>
		</div>
	
		<div id="beers_list">
		<?php foreach ($beers->$page as $beer) { 
			$brewery=json_decode(file_get_contents($oak->get_config_info()->api->base_uri.'/'.str_replace(':','/',$beer->brewery_id)));
		?>
			<div><a href="/<?=str_replace(':','/',$beer->id)?>"><?=$beer->name?></a> by <a href="/<?=str_replace(':','/',$beer->brewery_id)?>"><?=$brewery->name?></a></div>
		<?php } ?>
		</div>
<?php
		break;
}
?>

<script type="text/javascript">

function BeerCrushMain()
{
}

</script>

<?php 
include('../footer.php');
?>
