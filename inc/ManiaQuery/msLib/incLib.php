<?php
header("Content-type: text/xml");
if ($_GET["path"]) {
	$path = $_GET["path"] . ".txt";
	if (strpos($_GET["path"], "/"))
		$path = "../../../" . $path;
	$data = file_get_contents($path);
	if ($data) {
		echo '<script><!--' . trim($data) . '--></script>';
	}
}