<?php
	namespace App\Components\Scraper;

	use \App\Components\Scraper\Configuration;
	use \Behat\Mink\Session;
	use \Behat\Mink\Element\DocumentElement;
	use \Behat\Mink\Element\NodeElement;

	class Scraper
	{
		/**
		* The Mink Session which will make the http requests and browser control
		*
		* @var Behat\Mink\Session
		*/
		private $session;

		/**
		* Create a new Scraper instance
		*
		* @return void
		*/
		public function __construct(Configuration $config)
		{
			$this->session = new Session( $config->getDriver() );
			$this->session->start();
		}

		/**
		* Perform a http request to an web page
		*
		* @return Behat\Mink\Element\DocumentElement
		*/
		public function visit( $url ) : DocumentElement
		{
			$this->session->visit( $url );
			return $this->session->getPage();
		} 


		/**
		* Submits a form and returns the resulting page
		*
		* @return Behat\Mink\Element\DocumentElement
		*/
		public function submit( NodeElement $form ) : DocumentElement
		{
			$form->submit();
			return $this->session->getPage();
		}
	}