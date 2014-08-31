<?php
$data = array();


$expressions = array(
	'title' => '//div[@id="itineraryView"]//div[@class="itinerary-block itinerary-description"]/h2/text()',
	'items' => array(
		'block' => '//div[@id="itineraryView"]//div[@class="itinerary-section-content"]/div[contains(@class,\'itinerary-block\')]/div[contains(@class,\'itinerary-waypoint\')]',
		'way' => '//div[@id="itineraryView"]//div[@class="itinerary-section-content"]/div[contains(@class,\'itinerary-leg-view\')]',
	)
);
//echo "<pre>";





function query($xpath, $expressions)
{
	foreach ($expressions as $key => $expression) {
		if (is_array($expression)) {
			$data[$key] = query($xpath, $expression);
		}
		else {
			//print_r($expression);echo "<br/>";
			if ($elements = $xpath->evaluate($expression)) {
				//echo $elements->length . " elements<br/>";
				if ($key === "block") {
					if ($elements->length > 1) {
						$values = array();
						foreach ($elements as $element) {
							$simpleXml = simplexml_import_dom($element);
							$title = $subTitle = $src = $address = "";
							foreach ($simpleXml as $el) {
								$attr = $el->attributes();
								if (isset($attr['class'])) {
									if (preg_match("/photo/", $attr['class'])) {
										$src = isset($el->a->img['src']) ? (string) $el->a->img['src'] : "";
									}
									elseif (preg_match("/name/", $attr['class'])) {
										$title = trim((string) $el);
										$subTitle = trim((string) $el->span);
									}
									elseif (preg_match("/address/", $attr['class'])) {
										$v = array();
										foreach ($el->span as $span) {
											$v[] = trim((string) $span);
										}
										$address = implode(" ", $v);
									}
								}
							}
							$data[$key][] = array(
								'title' => trim($title),
								'src' => trim($src),
								'subTitle' => $subTitle,
								'address' => $address,
							);
							//print_r($simpleXml);
						}
					}
				}
				elseif ($key === "way") {
					if ($elements->length > 1) {
						$values = array();
						foreach ($elements as $element) {
							$value = preg_replace("/mi/", "mi ", preg_replace("/\s+/", "", trim($element->textContent)));
							$values[] = $value;
						}
						$data[$key] = $values;
					}
				}
			}
		}
	}
	return $data;
}

if (isset($_POST['pageContent'])) {
	$dom = new \DOMDocument('1.0', 'utf-8');
	$dom->strictErrorChecking = false;
	$dom->validateOnParse = false;
	$dom->preserveWhiteSpace = false;
	@$dom->loadHTML('<?xml encoding="UTF-8">' . $_POST['pageContent']);

	$xpath = new \DOMXPath($dom);
	$data = query($xpath, $expressions);
}

if (count($data)) {
	//print_r($data);
	//die;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo isset($data['title']) ? "Itinerary: " . $data['title'] : "Roadtrippers fixed printing"; ?></title>
		<meta charset="UTF-8">

		<style media="screen,print">
			body {font-family: Arial, Helvetica, sans-serif}
			.list {
				width: 800px;
			}
			table {
				border-collapse: collapse;
			}
			tr.row {
				border-bottom: 1px solid black;
			}
			td {
				text-align:left;
				//border: 1px solid black;
			}
			.title {
				font-size: 14px;
				font-weight: bold;
			}
			.subtitle {
				font-size: 10px;
			}
			.description {
				font-size: 12px;
			}
			td.src img {
				height: 150px;
			}
		</style>

		<style media="print">
			.noprint {
				visibility: hidden;
				display: none;
			}
		</style>
	</head>
	<body>
		<h1 class="noprint">Roadtrippers fixed printing itinerary/lists</h1>
		<ul class="noprint">
			<a href="index.php">bucket lists</a>
			<a href="index_directions.php">directions</a>
			<a href="index_itinerary.php">itinerary</a>
		</ul>
		<form method="POST" class="noprint">
			<p>open the itinerary page in www.roadtrippers.com, copy the page source, in firefox CTRL+U, select all and insert into this field</p>
			<textarea name="pageContent" style="width: 90%;height: 80px"></textarea>
			<input type="submit" value="OK"/>
		</form>
<?php //print_r($data);   ?>
		<?php if (count($data)) { ?>
			<p class="noprint">above will not be printed</p>
			<hr class="noprint" />
			<h1><?php echo $data['title']; ?></h1>
			<table class="list">
				<?php foreach ($data['items']['block'] as $index => $block) { ?>
					<tr>
						<td class="title"><?php echo $index + 1; ?>. <?php echo $block['title']; ?></td>
						<td class="src" rowspan="3"><?php echo isset($block['src']) ? '<img src="' . $block['src'] . '"/>' : '&nbsp;'; ?></td>
					</tr>
					<tr>
						<td class="subtitle"><?php echo $block['subTitle']; ?></td>
					</tr>
					<tr class="row">
						<td class="address"><?php echo $block['address']; ?></td>
					</tr>
					<?php if (isset($data['items']['way'][$index])) { ?>
						<tr class="row">
							<td class="way"><?php echo isset($data['items']['way'][$index]) ? $data['items']['way'][$index] : "&nbsp;"; ?></td>
						</tr>
					<?php } ?>
				<?php } ?>
			</table>
			<?php } ?>
	</body>
</html>
