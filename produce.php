<?php
try {
	if (!isset($_FILES["setting"]) || $_FILES["setting"]["error"] != 0) {
		throw new Exception("設定檔上傳失敗");
	}
	$setting=file_get_contents($_FILES["setting"]["tmp_name"]);
	$setting=json_decode($setting, true);
	if ($setting === null) {
		throw new Exception("設定檔格式錯誤");
	}
	preg_match("/(\d+)-(\d+)-(\d+)/", $setting["date_start"], $matches);
	$setting["year_start"]=$matches[1];
	$setting["month_start"]=$matches[2];
	$setting["date_start"]=$matches[3];
	preg_match("/(\d+)-(\d+)-(\d+)/", $setting["date_end"], $matches);
	$setting["year_end"]=$matches[1];
	$setting["month_end"]=$matches[2];
	$setting["date_end"]=$matches[3];

	if (!isset($_FILES["list"]) || $_FILES["list"]["error"] != 0) {
		throw new Exception("名單上傳失敗");
	}
	$listtext=file_get_contents($_FILES["list"]["tmp_name"]);
	$listtext=explode("\r\n", $listtext);
	$list=array();
	foreach ($listtext as $temp) {
		$temp=str_getcsv($temp);
		if ($temp !== null) {
			$list[]=$temp;
		}
	}

	$pagelist=array(0=>array());
	$index=0;
	$preclass=$list[0][1]*100+$list[0][2];
	$count=0;
	foreach ($list as $temp) {
		if ($temp[0] == "") {
			$index++;
			$count=0;
			continue;
		}
		if ( ($setting["page"] == "class" && $preclass != $temp[1]*100+$temp[2])
			|| $count >= 10) {
			$index++;
			$count=0;
		}
		$pagelist[$index][]=$temp;
		$preclass=$temp[1]*100+$temp[2];
		$count++;
	}
	foreach ($pagelist as $key => $temp) {
		$pagelist[$key]=array_pad($pagelist[$key], 10, array("","","","",""));
	}

	require("resource/element.php");
	require("function/checkbox.php");
	$body=file_get_contents("resource/body.html");
	$newpage=file_get_contents("resource/newpage.html");
	$output=file_get_contents("resource/header.html");
	
	foreach ($pagelist as $pagekey => $page) {
		/*if ($pagekey != 0) {
			$output.=$newpage;
		}*/
		$temp=$body;
		$temp=str_replace("{type_normal}", checkbox($setting["type"] == "normal"), $temp);
		$temp=str_replace("{type_event}", checkbox($setting["type"] == "event"), $temp);
		foreach ($element["produce"]["checkbox"] as $name) {
			$temp=str_replace("{".$name."}", checkbox(isset($setting[$name])), $temp);
		}
		foreach ($element["produce"]["text"] as $name) {
			$temp=str_replace("{".$name."}", $setting[$name], $temp);
		}

		foreach ($page as $personkey => $person) {
			$temp=str_replace("{name".$personkey."}", $person[0], $temp);
			$temp=str_replace("{gra".$personkey."}", $person[1], $temp);
			$temp=str_replace("{cla".$personkey."}", $person[2], $temp);
			$temp=str_replace("{num".$personkey."}", $person[3], $temp);
			$temp=str_replace("{place".$personkey."}", $person[4], $temp);
		}

		$output.=$temp;
	}

	$output.=file_get_contents("resource/footer.html");

	if ($_GET["type"] == "html") {
		header('Content-type:application/force-download');
		header('Content-Transfer-Encoding: UTF-8');
		header('Content-Disposition:attachment;filename=CAFPhtml.html');
		echo $output;
	} else {
		header('Content-type:application/force-download');
		header('Content-Transfer-Encoding: UTF-8');
		header('Content-Disposition:attachment;filename=CAFPdoc.doc');
		echo $output;
	}
} catch (Exception $e) {
?>
<html>
<head>
	<meta charset="UTF-8">
</head>
<body>
<center>
	<?php echo $e->getMessage(); ?>
<hr>
<?php
include("../../function/Xiplus-Facebook-Badge/badge.php");
?>
</center>
</body>
</html>
<?php
}
?>