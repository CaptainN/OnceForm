<?php
/*
The OnceForm - Write once HTML5 forms processing for PHP.

Copyright (C) 2012  adcSTUDIO LLC

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

/**
 * InputValidatr - validates a basic input type="text" and serves as
 * the base for other validators.
 * 
 * @author Kevin Newman <Kevin@adcSTUDIO.com>
 * @package The OnceForm
 * @copyright (C) 2012 adcSTUDIO LLC
 * @license GNU/GPL, see license.txt
 */
class InputValidator
{
	public $name;
	public $value;
	public $required;
	
	public $isValid;
	
	public $errors = array();
	
	/**
	 * Validates Input fields. Validates text fields, and is
	 * a base for other input type validators.
	 * @param stdObect|array The properties to validate.
	 */
	public function __construct( $props = NULL )
	{
		if ( is_array( $props ) )
			$props = (object) $props;
		
		if ( !is_null( $props ) )
		{
			if ( isset( $props->name ) )
				$this->name = $props->name;
			
			if ( isset( $props->value ) )
				$this->value = $props->value;
			
			if ( isset( $props->required ) )
				$this->required = $props->required;
		}
	}
	
	public function validate()
	{
		if ( $this->required && empty( $this->value ) )
			$this->errors[] = "required field is empty";
		
		return $this->isValid = empty( $this->errors );
	}
}

class NumbericValidator extends InputValidator
{
	public $step;
	public $max;
	public $min;
	
	public function __construct( $props = NULL )
	{
		parent::__construct( $props );
		
		if ( is_array( $props ) )
			$props = (object) $props;
		
		if ( !is_null( $props ) )
		{
			if ( isset( $props->min ) )
				$this->min = $props->min;
			
			if ( isset( $props->max ) )
				$this->max = $props->max;
			
			if ( isset( $props->step ) )
				$this->step = $props->step;
		}
	}
	
	public function validate()
	{
		parent::validate();
		
		switch ( $node->type )
		{
			case 'number':
			case 'range':
				if ( !is_numeric( $node->value ) )
					$this->errors[] = 'not a number';
				
				if ( !empty( $this->step ) &&
						!self::step( $node->value, $this->step ) )
					$this->errors[] = 'not a valid step of ' . $this->step;
				
				if ( !empty( $this->max ) &&
						!self::max( $node->value, $this->max ) )
					$this->errors[] = 'not below maximum of ' . $this->max;
				
				if ( !emtpy( $this->min ) &&
						!self::min( $node->value, $this->min ) )
					$this->errors[] = 'not above minimum of '. $this->min;
				
				break;
			case 'date':
			case 'datetime':
			case 'datetime-local':
			case 'month':
			case 'time':
			case 'week':
				break;
		}
		
		return $this->isValid = empty( $this->errors );
	}
	
	static public function step( $value, $step )
	{
		return $value % $step !== 0;
	}
	
	static public function max( $value, $max )
	{
		return $value <= $max;
	}
	
	static public function min( $value, $min )
	{
		return $value >= $min;
	}
}

class PatternValidator extends InputValidator
{
	public $pattern;
	
	public function __construct( $props = NULL )
	{
		parent::__construct( $props );
		
		if ( is_array( $props ) )
			$props = (object) $props;
		
		if ( !is_null( $props ) )
		{
			if ( isset( $props->pattern ) )
				$this->pattern = $props->pattern;
		}
	}
	
	public function validate()
	{
		parent::validate();
		
		if ( !empty($this->pattern) &&
				!preg_match( $this->$pattern, $this->value ) )
			$this->errors[] = 'not a valid value';
		
		return $this->isValid = empty( $this->errors );
	}
}

/**
 * Validates email fields.
 */
 
class EmailValidator extends PatternValidator
{
	static private $email_pattern = "/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/";
	
	public function __construct( $props = NULL )
	{
		parent::__construct( $props );
	}
	
	/**
	 * Validates under two conditions:
	 * <ul>
	 * <li>If the field is required, always validates.</li>
	 * <li>If the field is not required, but is filled in.</li>
	 * </ul>
	 * @return boolean Whether or not the field validates.
	 */
	public function validate()
	{
		parent::validate();
		
		if ( $this->required || strlen( $this->value ) > 0 ) { 
			if ( !preg_match( self::$email_pattern, $this->value ) )
				$this->errors[] = 'not a valid email address';
		}
		
		return $this->isValid = empty( $this->errors );
	}
}

/**
 * Validates Select fields.
 * @param stdObject|array The properties of the select box.
 * @param array The array of options to be checked. Can be array of 
 * arrays, stdObjects or a Ganon Node. must have keys: value, text.
 * The selected option should contain the key: selected.
 */
class SelectValidator extends InputValidator
{
	public function __construct( $props = NULL, $options = NULL )
	{
		parent::__construct( $props );
		
		if ( is_null( $options ) && is_callable( $props->select ) )
			$options = $props->select( 'option' );
		
		if ( !is_null( $options ) )
		{
			// get the value from the options list
			foreach( $options as $option )
			{
				// cast $option if array
				if ( is_array( $option ) )
					$option = (object) $option;
				
				// find the selection option and get the value
				if ( ( is_callable( $option->hasAttribute ) && 
							$option->hasAttribute( 'selected' ) )
					 || isset( $option->selected ) )
				{
					// get the value - it's either the value prop, or the text/innertext.
					if ( isset( $option->value ) )
						$this->value = $option->value;
					
					if ( is_null( $this->value ) )
					{
						// Allow the use of a ganon node list here - the default in OnceForm
						// `is_callable` didn't work here - some magic ganon thing I'm sure
						//try {
							$this->value = $option->getInnerText();
						/*}
						catch( e:Exception) {
							// :UNTESTED: The non-ganon path is not tested.
							$this->value = $option->text;
						}*/
					}
					break;
				}
			}
		}
	}
	
	// parent takes care of validation
	
}