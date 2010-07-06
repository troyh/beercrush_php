<style type="text/css">
#flavors-accordion .ui-selecting { background: #FECA40; }
#flavors-accordion .ui-selected { background: #F39814; color: white; }
#flavors-accordion { list-style-type: none; margin: 0; padding: 0; width: 100%; }
#flavors-accordion li { margin: 3px; padding: 0.4em; font-size: 1.0em; height: 10px; }
</style>
<?php
require_once('beercrush/beercrush.php');
$flavors=BeerCrush::api_doc($BC->oak,'flavors');

function output_flavors($flavors) {
	foreach ($flavors as $flavor) {
		if (isset($flavor->flavors)) {
			print '<h3><a href="#">'.$flavor->title.'</a></h3><div><ul>';
			output_flavors($flavor->flavors);
			print '</ul></div>';
		}
		else
		{
			print '<li id="flavor-'.$flavor->id.'">'.$flavor->title.'</li>';
		}
	}
}

?>

<form id="review_form">
	<input type="hidden" name="beer_id" value="" />
	<input type="hidden" name="purchase_place_id" value="" /></p>
	<input type="hidden" name="poured_from" value="" />
	<input type="hidden" name="body" value="0" />
	<input type="hidden" name="balance" value="0" />
	<input type="hidden" name="aftertaste" value="0" />
	<input type="hidden" name="flavors" value="" />
	<div class="cf"><div class="label">Rating:</div>
		<div id="star-rating">
			<input type="radio" name="rating" value="1" title="1 star"  />
			<input type="radio" name="rating" value="2" title="2 stars" />
			<input type="radio" name="rating" value="3" title="3 stars" />
			<input type="radio" name="rating" value="4" title="4 stars" />
			<input type="radio" name="rating" value="5" title="5 stars" />
		</div>
	</div>
	<div class="cf"><div class="label">Body:</div><div id="body-slider" style="left:120px;width:100px"></div></div>
	<div class="cf"><div class="label">Balance:</div><div id="balance-slider" style="left:120px;width:100px"></div></div>
	<div class="cf"><div class="label">Aftertaste:</div><div id="aftertaste-slider" style="left:120px;width:100px"></div></div>
	<div>
		<fieldset><legend>Optional: Improve our beer menus, share where you got it</legend>
		<p>Place: <input name="purchase_place_name" type="text" size="30" />
		Poured: 
		<ul id="poured">
			<li id="ontap"      class="cl"><input name="poured_from" type="radio" value="ontap"      /><div></div>Draft</li>
			<li id="inbottle"   class="cl"><input name="poured_from" type="radio" value="inbottle"   /><div></div>Bottle</li>
			<li id="inbottle22" class="cl"><input name="poured_from" type="radio" value="inbottle22" /><div></div>Large Bottle</li>
			<li id="incan"      class="cl"><input name="poured_from" type="radio" value="incan"      /><div></div>Can</li>
			<li id="oncask"     class="cl"><input name="poured_from" type="radio" value="oncask"     /><div></div>Cask</li>
		</ul>
		<p>Price: <input name="purchase_price" type="text" size="10" /></p>
		<p>Date: <input name="date_drank" type="text" size="10" /></p>
		</fieldset>
	</div>
	<div id="flavors-accordion">
		<?output_flavors($flavors->flavors);?>
	</div>
	<div>
		<p>Comments:</p>
		<textarea name="comments" rows="5" cols="40"></textarea>
	</div>
	
	<input id="post_review_button" type="button" value="Post my review" />
	<div id="review_result_msg" class="hidden"></div><p></p>
</form>
