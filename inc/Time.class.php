<?

abstract class DTime {

	static $hebMonths = [
		'ינואר',
		'פברואר',
		'מרץ',
		'אפריל',
		'מאי',
		'יוני',
		'יולי',
		'אוגוסט',
		'ספטמבר',
		'אוקטובר',
		'נובמבר',
		'דצמבר'
	];

	static function timeToDB($time){

		return date('Y-m-d H:i:s', $time);
	}

	static function textToTime($datetime){

		$datetime = explode(' ', $datetime);

		$date = $datetime[0];

		$format = DATE_FORMAT;

		if(isset($datetime[1])) {

			$time = $datetime[1];

			if(empty(explode(':', $time)[2]))

				$time .= ':00';

			$date .= ' ' . $time;

			$format .= ' ' . TIME_FORMAT;
		}

		return DateTime::createFromFormat($format, $date) ->getTimestamp();
	}

	static function clientToDB($datetime){

		$time = self::textToTime($datetime);

		return self::timeToDB($time);
	}

	static function DBToClient($dbDateTime){

		$format = DATE_FORMAT . ' ' . TIME_FORMAT;

		$datetime = date($format, strtotime($dbDateTime));

		$datetime = explode(' ', $datetime);

		$time = explode(':', $datetime[1]);

		unset($time[2]);

		$datetime[1] = implode(':', $time);

		return $datetime;
	}

	static function formatTime($str, $length = 2){

		$zeros = '';

		while(strlen($zeros) < $length)
			$zeros .= '0';

		$zeros .= $str;

		$splitEnd = strlen($zeros) - $length;

		for($splitAt = 0; $zeros{$splitAt} === '0' && $splitAt < $splitEnd; $splitAt++);

		return substr($zeros, $splitAt);
	}

	static function timePicker($name = 'time', $step = 5){

		echo "<select name='$name' class='time-picker'>";

		for($hour = 0; $hour < 24; $hour++){

			$time = self::formatTime($hour);

			$time .= ':';

			for($minute = 0; $minute < 60; $minute += $step){

				$hm = $time . self::formatTime($minute);

				echo "<option>$hm</option>";
			}
		}

		echo '</select>';
	}

	static function minutesToHours($minutes){

		return ['hours' => floor($minutes / 60), 'minutes' => $minutes % 60];
	}

	static function formatMinutesToHours($minutes){

		$params = self::minutesToHours($minutes);

		foreach($params as & $param)
			$param = self::formatTime($param);

		return implode(':', $params);
	}
}