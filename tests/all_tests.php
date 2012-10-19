<?php
require_once('simpletest/autorun.php');

class AllTests extends TestSuite
{
	function AllTests()
	{
		$this->TestSuite( 'All Tests' );
		$this->addFile( 'core_test.php' );
		$this->addFile( 'data_test.php' );
		$this->addFile( 'input_validator_tests.php' );
	}
}
