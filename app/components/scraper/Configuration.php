<?php 
	namespace App\Components\Scraper;

	use \Behat\Mink\Driver\GoutteDriver;

	class Configuration
	{

		/**
	     * The driver that will be used by behat/mink.
	     *
	     * @var string
	     */
		private $driver;

		/**
		* Creates a new Configuration instance
		*
		* @return void
		*/
		public function __construct(Array $config)
		{
			$option = $config['driver'] ?? 'goutte';

			switch ($option) {
				case 'goutte':
				default:
					$this->driver = new GoutteDriver();
					break;
			}
		}

		/**
		* Returns the Configuration::driver value
		*
		* @return mixed
		*/
		public function getDriver()
		{
			return $this->driver;
		}
	}