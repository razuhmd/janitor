<?php
global $action;
global $model;

$taglists = $model->getTaglists();
//print_r($taglists);
?>

<div class="scene defaultList taglistList">
	<h1>Taglists</h1>

	<ul class="actions">
		<?= $JML->listNew(array("label" => "New taglist")) ?>
	</ul>

	<div class="all_items i:defaultList filters"<?= $HTML->jsData(["order", "search"]) ?>>
<?		if($taglists): ?>
		<ul class="items">
<?			foreach($taglists as $taglist): ?>
			<li class="item item_id:<?= $taglist["id"] ?>">
				<h3><?= strip_tags($taglist["name"]) ?></h3>

				<?= $JML->listActions($taglist,  ["modify"=>[
					"delete"=>[
						"url"=>"/janitor/admin/taglist/deleteTaglist/".$taglist["id"]
					]
				]]) ?>
			 </li>
<?			endforeach; ?>
		</ul>
<?		else: ?>
		<p>No Taglists.</p>
<?		endif; ?>
	</div>

</div>
