<?

class Agenda {

	const DAYS_TABLE = 'days';

	protected static $input;

	private static $salaries;

	private static $places = [];

	private static $transports = [];

	private static $listItems = [
		'date',
		'start',
		'end',
		'place',
		'getting_mean',
		'backing_mean',
		'getting_passengers',
		'backing_passengers',
		'total_price',
		'total_hours',
		'notes'
	];

	public $month;

	public $year;

	private $days = [];

	private $output;

	private $allPrices = 0;

	private $allMinutes = 0;

	private $allSalary = 0;

	private $ignoreDays = [5, 6, 7];

	function Agenda($month = null, $year = null){

		$now = time();

		$this->month = (int) ($month !== null ? $month : date('m', $now));

		$this->year = (int) ($year !== null ? $year : date('Y', $now));

		Database::createSql(DB_NAME);

		self::$input = new DBInput;

		$this->output = new DBOutput;

		$this->getPlacesFromDB();

		$this->getTransportsFromDB();

		$this->getSalaryFromDB();

		$this->getDaysFromDB();

		$this->checkNow();
	}

	/**
	 * @return array
	 */
	public static function getTransports(){

		return self::$transports;
	}

	/**
	 * @param string $date
	 * @return mixed[array|null]
	 */
	public static function getSalary($date = null){

		$salaries = self::$salaries;

		if($date) {

			foreach($salaries as $salary) {
				if($salary['from'] <= $date && $salary['to'] >= $date)
					return $salary;
			}

			return null;
		}

		return $salaries;
	}

	function addDay(){

		$date = $GLOBALS['clientData']['date'];

		$this->createDay(DTime::clientToDB($date));
	}

	/**
	 * @param AgendaDay $day
	 * @param string $tag
	 * @param string $cell
	 */
	function listDay($day, $tag = 'div', $cell = 'div'){

		$data = $day->getView();

		echo "<$tag class='day-row' data-id='$data[id]'>";

		unset($data['id']);

		foreach(self::$listItems as $name)
			echo "<$cell class='list-cell-$name'>{$data[$name]}</$cell>";

		echo "<$cell class='list-cell-edit'><button>עריכה</button></$cell>";

		echo "</$tag>";
	}

	/**
	 * @return int
	 */
	public function getAllSalary(){

		return $this->allSalary;
	}

	public function getDays(){

		return $this->days;
	}

	/**
	 * @return int
	 */
	public function getAllPrices(){

		return $this->allPrices;
	}

	/**
	 * @return int
	 */
	public function getAllMinutes(){

		return $this->allMinutes;
	}

	/**
	 * @return array
	 */
	public function getPlaces(){

		return Agenda::$places;
	}

	function updateDay(){

		/** @var AgendaDay $day */

		global $clientData, $constParams;

		$day = $this->days[$clientData['id']];

		$day->update($clientData);

		$location = APP_BASE;

		$extraParams = [];

		foreach($constParams as $key => $value) {
			$extraParams[] = implode('=', [$key, $value]);
		}

		if($extraParams)
			$location .= '?' . implode('&', $extraParams);

		header('location: ' . $location);

		exit;
	}

	/**
	 * @param string $date
	 */
	private function createDay($date){

		self::$input->query('insert', self::DAYS_TABLE, ['date' => $date]);

		$data = ['id' => self::$input->sql->insert_id, 'date' => $date];

		$this->registerDay($data);
	}

	private function getDaysFromDB(){

		$days = $this->output->query('SELECT * FROM' . ' ' . self::DAYS_TABLE . ' where month(date) = ' . $this->month . ' and year(date) = ' . $this->year . ' order by date');

		foreach($days as $day) {
			$newDay = $this->registerDay($day);
			$this->allPrices += $newDay->getTotalPrice();
			$this->allMinutes += $newDay->getTotalMinutes();
			$this->allSalary += $newDay->getTotalSalary();
		}
	}

	/**
	 * @param array $data
	 * @return \AgendaDay
	 */
	private function registerDay($data){

		$day = new AgendaDay($data);

		$this->days[$data['id']] = $day;

		return $day;
	}

	private function getPlacesFromDB(){

		$places = $this->output->query('SELECT * FROM places');

		Agenda::$places = Database::groupArray('id', $places);
	}

	private function checkNow(){

		$time = time();

		$day = date('w', $time);

		if(in_array($day, $this->ignoreDays))
			return;

		$date = date('Y-m-d', $time);

		$isExist = $this->output->query("SELECT id FROM " . self::DAYS_TABLE . " where date = '$date'");

		if(! $isExist)
			$this->createDay($date);
	}

	private function getTransportsFromDB(){

		$transports = $this->output->query('SELECT * FROM transportation');

		Agenda::$transports = Database::groupArray('id', $transports);
	}

	private function getSalaryFromDB(){

		Agenda::$salaries = $this->output->query('SELECT * FROM salary');
	}
}

class AgendaDay extends Agenda {

	private $termsList = [
		'id',
		'date',
		'start',
		'end',
		'place',
		'getting_mean',
		'backing_mean',
		'getting_passengers',
		'backing_passengers',
		'notes'
	];

	private $terms = [];

	private $itemsToSave = [
		'start',
		'end',
		'place',
		'getting_mean',
		'backing_mean',
		'getting_passengers',
		'backing_passengers',
		'notes'
	];

	private $transportTerms = ['getting_mean', 'backing_mean'];

	private $timeTerms = ['start', 'end'];

	private $totalPrice = 0;

	private $totalMinutes = 0;

	private $totalSalary = 0;

	function AgendaDay($data){

		foreach($this->termsList as $termName)
			$this->terms[$termName] = isset($data[$termName]) ? $data[$termName] : null;

		$this->calcTotalPrice();

		$this->calcTotalHours();

		$this->calcTotalSalary();

		$this->parseData();
	}

	/**
	 * @return int
	 */
	public function getTotalSalary(){

		return $this->totalSalary;
	}

	/**
	 * @return int
	 */
	public function getTotalPrice(){

		return $this->totalPrice;
	}

	/**
	 * @return int
	 */
	public function getTotalMinutes(){

		return $this->totalMinutes;
	}

	/**
	 * @return array
	 */
	public function getTerms(){

		return $this->terms;
	}

	function getParsedData(){

		$data = $this->terms;

		$data['date'] = DTime::DBToClient($data['date'])[0];

		foreach($this->timeTerms as $name) {
			if($data[$name])
				$data[$name] = substr($data[$name], 0, strlen($data[$name]) - 3);
		}

		return $data;
	}

	protected function update($data){

		foreach($data as $key => $value)
			$this->terms[$key] = $value;

		$this->save();
	}

	protected function getView(){

		$data = $this->getParsedData();

		foreach($data as $name => $term) {

			if(in_array($name, $this->transportTerms) && $term) {

				$str = '<ul>';

				foreach($term as $transport)
					$str .= "<li>$transport[name]</li>";

				$str .= '</ul>';

				$data[$name] = $str;
			}
		}

		$places = parent::getPlaces();

		$data['place'] = isset($places[$data['place']]) ? $places[$data['place']]['name'] : null;

		$data['total_price'] = number_format($data['total_price'], 2);

		$data['total_hours'] = DTime::formatMinutesToHours($this->totalMinutes);

		return $data;
	}

	private function save(){

		$data = [];

		foreach($this->itemsToSave as $item) {
			$term = $this->terms[$item];
			$data[$item] = is_array($term) ? json_encode($term) : $term;
		}

		self::$input->query('update', parent::DAYS_TABLE, $data, "where id = {$this->terms['id']}");
	}

	private function parseData(){

		$this->terms['total_price'] = $this->totalPrice;
	}

	private function calcTotalPrice(){

		$data = &$this->terms;

		$transportation = Agenda::getTransports();

		foreach($this->transportTerms as $term) {

			if(empty($data[$term]))
				continue;

			$transport = json_decode($data[$term]);

			$data[$term] = [];

			$price = 0;

			foreach($transport as $mean) {
				if(isset($transportation[$mean])) {
					$data[$term][$mean] = $transportation[$mean];
					$price += $transportation[$mean]['price'];
				}
			}

			$this->totalPrice += $price;
		}
	}

	private function calcTotalHours(){

		$times = ['start' => [], 'end' => []];

		foreach($times as $name => $time) {

			if(empty($this->terms[$name]))
				return;

			$timeParams = explode(':', $this->terms[$name]);

			$datetime = new DateTime;

			$datetime->setTime($timeParams[0], $timeParams[1]);

			$times[$name]['time'] = $datetime->getTimestamp();
		}

		$rangeStamp = $times['end']['time'] - $times['start']['time'];

		$this->totalMinutes = $rangeStamp / 60;
	}

	private function calcTotalSalary(){

		$salaryPeriod = Agenda::getSalary($this->terms['date']);

		$sum = $salaryPeriod['sum'];

		$this->totalSalary = $this->totalMinutes / 60 * $sum;
	}
}