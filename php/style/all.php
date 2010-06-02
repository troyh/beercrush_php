<?php
require_once('beercrush/beercrush.php');

$styles=BeerCrush::api_doc($BC->oak,'beerstyles');

include('../header.php');
?>
<h1>Styles</h1>

<?php
print_styles($styles->styles);

function print_styles($styles) {
	print '<ul>';
	foreach ($styles as $style) {
		print '<li><a href="./'.$style->id.'">'.$style->name.'</a>';
		if (isset($style->styles))
			print_styles($style->styles);
		print '</li>';
	}
	print '</ul>';
}
?>

<?php
include('../footer.php');
?>