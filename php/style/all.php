<?php
require_once('beercrush/beercrush.php');

$styles=BeerCrush::api_doc($BC->oak,'beerstyles');

include('../header.php');
?>
<h1>Beer Styles</h1>
<div id="styles">
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
</div>
<?php
include('../footer.php');
?>