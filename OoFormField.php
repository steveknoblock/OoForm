<?php

class OoFormField {

	private $fieldName;
	private $fieldValue;
	private $fieldLabel;
	private $fieldRule; // expected to be a regular expression
	private $isRequired;
	private $fieldError;
	private $isValid;
	
	public function __construct( $field ) {
		$this->fieldName = $field['name'];
		$this->fieldValue = $field['value'];
		$this->fieldLabel = $field['label'];
		$this->fieldRule = $field['rule'];
		$this->isRequired = $field['required'];
		$this->fieldError = $field['error'];
		$this->fieldValue = $field['value'];
		$this->isInvalid = 0;
	}

	public function getFieldValue() {
		if( !$sticky ) {
			//get value from $_REQUEST;
		} else {
			//get value from self
			return $this->fieldValue;
		}
	return $this->fieldValue;
	}


	/**
	 * Validate Field
	 */
			 
	public function validateField() {
		// check against rule
		
		 /**
		  * /remark
		  * Note: if a field is empty, we don't check it for validity,
		  * we only check non-empty fields for validity, we check empty
		  * fields to see if they are required, once that check is done,
		  * we don't have to check if valid.
		  */
		if( !$this->fieldValue ) {
				return;
		}
		if( $this->fieldRule != '' && (! preg_match( $this->fieldRule, $this->fieldValue)) ) {
				$this->isInvalid = 1;
				return 0;
		//$this->fields[$field_name]['error'] = $msgengine->message('field_invalid', $this->fields[$field_name]['label']);
		}

	}

	public function isRequired() {

	}

}