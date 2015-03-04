<?

require 'Global.lib.php';

require 'api/class.agenda.php';

$month = ! empty($_GET['month']) ? $_GET['month'] : null;

$year = ! empty($_GET['year']) ? $_GET['year'] : null;

$agenda = new Agenda($month, $year);

$days = $agenda->getDays();

$transports = $agenda->getTransports();

$prevMonth = $agenda->month - 1;

$nextMonth = $agenda->month + 1;

$prevYear = $nextYear = $agenda->year;

if($prevMonth <= 0) {
	$prevMonth = 12;
	$prevYear--;
}

if($agenda->month + 1 > 12) {
	$nextMonth = 1;
	$nextYear++;
}

$title = sprintf('יומן עבודה - %s %d', DTime::$hebMonths[$agenda->month - 1], $agenda->year);

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?= $title ?></title>
	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="fa/font-awesome.min.css">
	<script src="js/jquery.js"></script>
	<script src="js/script.js"></script>
</head>
<body>
<div id="agenda">
	<h1><?= $title ?></h1>

	<div id="nav">
		<div id="prev-month">
			<a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">לחודש הקודם</a>
		</div>
		<div id="next-month">
			<a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">לחודש הבא</a>
		</div>
	</div>
	<? if($days) : ?>
		<table id="main-table">
			<thead>
			<tr>
				<th>תאריך</th>
				<th>שעת התחלה</th>
				<th>שעת סיום</th>
				<th>מקום</th>
				<th style="width: 16%">אמצעי הגעה</th>
				<th style="width: 16%">אמצעי חזרה</th>
				<th>מספר נוסעים בהלוך</th>
				<th>מספר נוסעים בחזור</th>
				<th>סה"כ הוצאות</th>
				<th>סה"כ שעות</th>
				<th style="width: 15%">הערות</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?
			foreach($days as $day)
				$agenda->listDay($day, 'tr', 'td');
			?>
			</tbody>

		</table>
		<div id="total">
			<h4>סך הכל:</h4>
			<table>
				<tr>
					<th>שעות</th>
					<th>הוצאות נסיעה</th>
					<th>ימי עבודה</th>
					<th>ממוצע שעות ליום</th>
					<th>ממוצע הוצאות ליום</th>
					<th>הכנסה</th>
					<th> שכר ברוטו</th>
				</tr>
				<tr>
					<?
					$allDays = count($days);
					$allMinutes = $agenda->getAllMinutes();
					$allPrices = $agenda->getAllPrices();
					$allSalary = $agenda->getAllSalary();
					?>
					<td><?= DTime::formatMinutesToHours($allMinutes) ?></td>
					<td><?= $allPrices ?> ₪</td>
					<td><?= $allDays ?></td>
					<td><?= DTime::formatMinutesToHours(floor($allMinutes / $allDays)) ?></td>
					<td><?= number_format($allPrices / $allDays, 2) ?> ₪</td>
					<td><?= number_format($allSalary, 2) ?> ₪</td>
					<td><?= number_format($allSalary + $allPrices, 2) ?> ₪</td>
				</tr>
			</table>
		</div>
		<form id="day-edit" action="api/" method="post">
			<input id="de-id" type="hidden" name="id">
			<input type="hidden" name="constParams[month]" value="<?= $agenda->month ?>">
			<input type="hidden" name="constParams[year]" value="<?= $agenda->year ?>">
			<input type="hidden" name="subject" value="agenda">
			<input type="hidden" name="action" value="updateDay">
			<table>
				<tr>
					<th>שעת התחלה</th>
					<th>שעת סיום</th>
					<th>מקום</th>
					<th style="width: 20%">אמצעי הגעה</th>
					<th style="width: 20%">אמצעי חזרה</th>
					<th>מספר נוסעים בהלוך</th>
					<th>מספר נוסעים בחזור</th>
					<th style="width: 20%">הערות</th>
					<th></th>
				</tr>
				<tr>
					<td><? DTime::timePicker('start') ?></td>
					<td><? DTime::timePicker('end') ?></td>
					<td>
						<select name="place">
							<? foreach($agenda->getPlaces() as $key => $place) {
								echo "<option value='$key'>$place[name]</option>";
							} ?>
						</select>
					</td>
					<td>
						<div class="checkbox-group">
							<? foreach($transports as $key => $transport) { ?>
								<div class="checkbox-wrapper">
									<input id="gm-<?= $key ?>" type="checkbox" name='getting_mean[]'
										   value='<?= $key ?>'>
									<label for="gm-<?= $key ?>"><?= $transport['name'] ?></label>
								</div>
							<? } ?>
						</div>
					</td>
					<td>
						<div class="checkbox-group">
							<? foreach($transports as $key => $transport) { ?>
								<div class="checkbox-wrapper">
									<input id="bm-<?= $key ?>" type='checkbox' name='backing_mean[]'
										   value='<?= $key ?>'>
									<label for="bm-<?= $key ?>"><?= $transport['name'] ?></label>
								</div>
							<? } ?>
						</div>
					</td>
					<td>
						<select name="getting_passengers">
							<? foreach(range(1, 20) as $num) { ?>
								<option><?= $num ?></option>
							<? } ?>
						</select>
					</td>
					<td>
						<select name="backing_passengers">
							<? foreach(range(1, 20) as $num) { ?>
								<option><?= $num ?></option>
							<? } ?>
						</select>
					</td>
					<td>
						<textarea name="notes"></textarea>
					</td>
					<td>
						<button id="de-cancel">ביטול</button>
						<input type="submit" value="עדכון">
					</td>
				</tr>
			</table>
		</form>
	<? else : ?>
		<div>לא נמצאו נתונים לחודש המבוקש</div>
	<? endif ?>
</div>
<script>
	<?
	$terms = [];

	/** @var AgendaDay $day */

	foreach($days as $day){
		$dayTerms = $day -> getParsedData();
		$terms[$dayTerms['id']] = $dayTerms;
	}

	?>
	editForm.data = <?= json_encode($terms) ?>;
</script>
</body>
</html>