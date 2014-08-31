<?php
$expressions = array(
	'title' => '//div[@id="itineraryListView"]//div[@class="main-header"]/header/h1/text()',
	'items' => array(
		'title' => '//div[@id="itineraryListView"]//div[contains(@class,\'itinerary-block\')]//div[@class="info"]/a[contains(@class,\'name\')]/text()',
		'subTitle' => '//div[@id="itineraryListView"]//div[contains(@class,\'itinerary-block\')]//div[@class="info"]/a[contains(@class,\'name\')]/span[@class="subtitle"]/text()',
		'address' => '//div[@id="itineraryListView"]//div[contains(@class,\'itinerary-block\')]//div[@class="info"]/div[@class="address"]',
		'description' => '//div[@id="itineraryListView"]//div[contains(@class,\'itinerary-block\')]//div[@class="description"]/text()',
		'src' => '//div[@id="itineraryListView"]//div[contains(@class,\'itinerary-block\')]//div[@class="photo"]//img/@src'
	)

);
//echo "<pre>";
function query($xpath, $expressions) {
	$data = array();
	foreach ($expressions as $key => $expression) {
		if (is_array($expression)) {
			$data[$key] = query($xpath, $expression);
		}
		else {
			//print_r($expression);echo "<br/>";
			if ($elements = $xpath->evaluate($expression)) {
				//echo $elements->length . " elements<br/>";
				if ($key === "address") {
					if ($elements->length > 1) {
						$values = array();						
						foreach($elements as $element) {
							$valueA = array();
							foreach($element->childNodes as $child) {
								$valueA[] = trim($child->textContent);								
							}
							$value = implode(" ", $valueA);							
							if (!empty($value)) {
								$values[] = $value;
							}
						}
						$data[$key] = $values;						
					}
				}
				else {
					// multi element
					if ($elements->length == 1) {
						//print_r($elements->item(0)->textContent);
						$data[$key] = $elements->item(0)->textContent;
					}
					elseif ($elements->length > 1) {
						$values = array();
						foreach($elements as $element) {
							$value = trim($element->textContent);
							if (!empty($value)) {
								//echo $value . "<br/>";
								$values[] = $value;
							}
						}
						//print_r($values);
						if (count($values) == 1) {
							$data[$key] = $values[0];
						}
						else {
							$data[$key] = $values;
						}
						//print_r($data);
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
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo isset($data['title']) ? "Itinerary: " . $data['title'] : "Roadtrippers fixed printing";?></title>
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
		<form method="POST" class="noprint">
			<p>open the itinerary page in www.roadtrippers.com, copy the page source, in firefox CTRL+U, select all and insert into this field</p>
			<textarea name="pageContent" style="width: 90%;height: 80px"></textarea>
			<input type="submit" value="OK"/>
		</form>
		<?php //print_r($data); ?>
		<?php if (count($data)) { ?>
			<p class="noprint">above will not be printed</p>
			<hr class="noprint" />
			<h1><?php echo $data['title'];?></h1>
			<table class="list">
				<?php foreach($data['items']['title'] as $index => $title) { ?>
					<tr>
						<td class="title"><?php echo $index + 1; ?>. <?php echo $title; ?></td>
						<td class="src" rowspan="4"><?php echo isset($data['items']['src'][$index]) ? '<img src="' . $data['items']['src'][$index] . '"/>' : '&nbsp;';?></td>
					</tr>
					<tr>
						<td class="subtitle"><?php echo isset($data['items']['subTitle'][$index]) ? $data['items']['subTitle'][$index] : ""; ?></td>
					</tr>
					<tr>
						<td class="description"><?php echo isset($data['items']['description'][$index]) ? $data['items']['description'][$index] : ""; ?></td>
					</tr>
					<tr class="row">
						<td class="address"><?php echo isset($data['items']['address'][$index]) ? $data['items']['address'][$index] : "&nbsp;"; ?></td>
					</tr>
				<?php } ?>
			</table>
		<?php } ?>
	</body>
</html>
