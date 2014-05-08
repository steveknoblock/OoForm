<?php

class OoFormField {

	private $fieldName;
	private $fieldValue;
	private $fieldLabel;
	private $fieldRule; // expected to be a regular expression
	private $isRequired;
	private $fieldError;
	private $isInvalid;
	private $isSticky;
	
	public function __construct( $field ) {
	
		$this->fieldName = $field['name'];
		$this->fieldValue = $field['value'];
		$this->fieldLabel = $field['label'];
		$this->fieldRule = $field['rule'];
		$this->isRequired = $field['required'];
		$this->fieldError = $field['error'];
		$this->fieldValue = $field['value'];
		$this->isInvalid = 0;
		$this->isSticky = 1;
		
		/*
		fieldValue must be protected from the outside world and the value must only be accessible through an accessor function.
		
		*/
		
		$this->getFieldValue();
		
	} // end constructor



	/**
	 * Validate Field
	 */
	 
	public function validateField() {
		if( $this->validateRequired() && $this->validateValue() ) {
			print "<p>Field '".$this->fieldName."' is valid.";
			return true;
		} else {
			print "<p>Field '".$this->fieldName."' is invalid.";
			return false;
		}
	}


	private function validateRequired() {
		if( ($this->isRequired) && ($this->fieldValue == '') ) {
			return false;
		} else {
			return true;
		}
	}

	private function validateValue() {
			 /**
			  * /remark
			  * If a field is empty, its unnecessary to check for validity.
			  */
		if( ( $this->fieldValue != '' ) && ($this->fieldRule != '') && (! preg_match( $this->fieldRule, $this->fieldValue)) ) {
			$this->isInvalid = 1;
			return false;
		} else {
			return true;
		}
	}

/* Here's a good question: why should the Form know anything about where a FormField gets its value?
*/
	public function getFieldValue() {
		if( !$this->isSticky ) {
			//get value from $_REQUEST;
			return $_REQUEST[$this->fieldName];
		} else {
			//get value from self
			return $this->fieldValue;
		}
	}


	// experimental
	public function requiredValueExists() {
		if( ($this->isRequired) && ($this->fieldValue != '' ) ) {
			return 1;
		}
	}


} // end OoFormField