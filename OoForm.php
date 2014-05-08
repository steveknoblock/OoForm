<?php

/**
 * OoForm Class
 * A form handler.
 * Provides form handling capability. Started life as a port of CGI:FormBuilder
 * from the perl CPAN library for folkstreams.net platform.
 * @author Steve Knoblock (Thanks to Nate Wiger for paitently answering my many
 * questions about CGI::FormBuilder's workings)
 * @copyright Copyright 2004-2014 Steve Knoblock
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package OoForm
 */


/**
 * Configuration
 */

require_once 'ooform-config.php';


/**
 * Dependencies
 */

require_once OOFORM_TEMPLATE_ENGINE_PATH;
require_once dirname(__File__) .'/'. 'ooform.template.'. OOFORM_TEMPLATE_ENGINE .'.class.php';
require_once dirname(__File__) .'/'. 'ooform-lang.php';
require_once dirname(__File__) .'/'. 'OoFormField.php';
require_once dirname(__File__) .'/'. 'OoFormMessages.php';


/**
 * Class OoForm
 *
 */

class OoForm
{

	/**
	 * Member Variables / Properties
	 */

	private $fields; // Assoc. array of Field objects storing meta data and state for each field
	private $fieldsList; // Array listing field names

	/**
	 * /remark
	 * Note: Remember, PHP's $_POST or $_GET are superglobals
	 * available within the class without explicity passing them.
	 */

	private $paramsList; // parameter array

	/**
	 * /remark
	 * OoForm provides a set of predefined validation rules
	 * for common formats.
	 */
 
	private $rulesList = array(
		'_mysqldate' => '#\d\d\d\d/\d\d\/\d\d#',
		'_name' => '/^[a-zA-Z]+$/',
		'_email' => '/^.+\@([a-z0-9]+(-[a-z0-9]+)?\.)+([a-z]{2,3})$/',
		'_zip' => '/^\d{5}$|^\d{5}\-\d{4}$/',
		'_date' => '#\d\d/\d\d/\d\d\d\d#'
	);

	private $validate_list; // assoc. array matching fields to validation rules
	private $required_list = array(); // array naming required fields
	private $messages_list; // array of messages for each field
	private $labels_list; // array of messages for each field
	
	private $ooform_base; // Base path for including delegate class files.

	
	/**
	 * Housekeeping
	 *
	 */

	private $error; // error message
	private $debug = ''; // 'verbose'
	public $sticky = 1;
	private $acceptHost;
	//var $submitted; // future use
	private $fail_validate;
	private $fail_require;
	private $usesTemplateEngine; // do we connect to a template engine?
	private $templateEngine; // the template engine
	private $action; // HTML form action URL
	private $fieldErrors;

	/**
	 * Class Member Functions
	 *
	 */
 

	/**
	 * Constructor
	 * OoForm( params )
	 * @param array list of recognized fields
	 */
	 
	 /* Expects an anonymous array of fields and values for each field.
	  $field_init = array(
			array(
			'name' => 'name',
			'label' => 'Name: ',
			'rule' => 'REGEX HERE',
			'required' => '0'
			),
			array(
			'name' => 'email_address',
			'label' => 'Email: ',
			'rule' => 'REGEX HERE',
			'required' => '1'
			)
		);
		
		Creates array of objects using name as key.
	*/

	function __construct( $fields_init ) {
	
			print_r( $fields_init );
		
		foreach( $fields_init AS $field ) {
			$this->fields[$field['name']] = new OoFormField(
				array(
				'name' => $field['name'],
				'value' => $field['value'],
				'label' => $field['label'],
				'rule' => $field['rule'],
				'required' => $field['required']
				)
			);
			//$this->fieldsList[] = $field['name'];
		}
	
		/**
		 * This code is used when
		 * OoForm delegates template handling to a template engine.
		 * At this point, it creates an instance of the template
		 * object for rendering form pages.
		 * You must pass an array of options meaningful to the
		 * particular template engine you are using in connection
		 * with ooForm.
		 */
	 
		//$this->templateEngine = new OoFormTemplate('templates');

		// reference list of Field objects now
		//$this->fields_list = $field_list;

		$this->paramsList = $_REQUEST;

		if( 1 )
		{
			print "<pre style='background: gray'>";
			print "OoForm: Dumping Constructor\n";
			print "Fields\n";
			print_r( $this->fields );
			print "Current HTTP Request Parameters\n";
			print_r( $this->paramsList );
			print "</pre>";
		}

	}


	/**
	 * Submitted Status
	 * returns true if form has been submitted
	 */
		 
	public function submitted() {

		if( $_REQUEST['submitted'] || $_POST['submit'] || $_GET['submit'] ) {
		// or return value of submit button, it's so-called caption
		// return $_POST['submit'] ? $_POST['submit'] : $_GET['submit'];
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Validated
	 * Ask all fields if they are valid.
	 */

	public function validated() {

		foreach( $this->fields AS $field ) {
			print '<pre>';
			print_r( $field );
			print '</pre>';
		$status = $field->validateField();
		if( !$status ) {
				$this->fieldErrors++;
		}
			
			
			

		} // end fe

		if($this->fieldErrors) {
				return 0;
			} else {
				return 1;
			}

	} // end fn


	/**
	 * Helper function for value() from php manual entry by mike-php at emerge2 dot com
	 */
 
	private function stripslashes_array( $given )
	{
		return is_array( $given ) ? array_map( 'stripslashes', $given ) : stripslashes( $given );
	}

	 /**
	  * The purpose of this function is to return the dynamic
	  * auto sticky value of a field. It is used internally to
	  * encapsulate returning of field values. Used by field().
	  * @param string name of field
	  */

	private function value( $field_name )
	{

			/**
			 * /remark
			 * This is a trick. The code looks dynamically to either
			 * the form field or the CGI parameter for the value
			 * based on the current state of stickiness. When sticky
			 * it returns the CGI parameter, when not it returns
			 * current value of form field (from the model).
			 * This can be clearly seen in the code:
			 *
			 * $this->paramsList[$field_name];
			 * returns CGI value from our local copy
			 *
			 * $this->fields[$field_name]['value'];
			 * returns field value from our model.
			 */

		if( $this->sticky ) {
			// return cgi param value, cgi beats default
		
			/**
			 * Note: params_list is a copy of $_POST or $_GET or
			   or $_REQUEST array.
		   
			   */

			// handle magic quotes nightmare!  
			if ( get_magic_quotes_gpc() ) {
			/**
			 * \remark params_list[key] holds the value for a particular CGI parameter. It does not need to specify a second key to get the value. Just to clear up a point of possible confusion, as to why it does not say params_list[key]['value'] like accessing a field.
			 */
				$value = $this->paramsList[$field_name];
				// this does not handle multiple select arrays
				$value = $this->stripslashes_array( $value );
			} else {
				$value = $this->paramsList[$field_name];
			}
	
		return $value;
		
		} else {
			return $this->fields[$field_name]['value'];
		}
	}


	/**
	 * field 
	 * get the value of a form field
	 */

	public function getFormField()
	{
	/**
	 * /remark
	 * Note: This function depends on special PHP functions, which
	 * cannot be used outside of a function definition. The behavior
	 * of the functions is somewhat inconsistent with the behavior
	 * of normal functions.
	 */
	 
		$numargs = func_num_args(); // function cannot be used directly as a function parameter
	   
		if ($numargs > 1) {

			/**
			 * Field Assign
			 *
			 */
			   
			$arg_list = func_get_args();
		   
			$field_name = $arg_list[0];
			$field_value = $arg_list[1];
		
			// debug	
			//print "Assigning value " 	. $field_value ." to ". $field_name ." field<br>";
	
			$this->fields[$field_name]['value'] = $field_value;
		   
		} else {
	
		/**
		 * Field Retrieve
		 *
		 */
			
		$arg_list = func_get_args();

		$field_name = $arg_list[0];
		   
		/**
		 * I suppose this would work too
		 * $field_name = func_get_arg(0);
		 */
	 
		return $this->value( $field_name );
		}
	}


	/**
	 * getAction
	 *
	 */

	function getFormAction()
	{
		$action = (! empty( $this->action ) ) ? ($this->action) : ($_SERVER['PHP_SELF']); 
		return $action;
	}


	/**
	 * render
	 *
	 * @param string name of template file
	 * @param integer sticky mode flag on or off
	 */
 
	public function render($template_file, $sticky)
	{
		$this->sticky = $sticky;
		return $this->templateEngine->render($this, array( 'template' => $template_file));
	}

	/**
	 * options
	 *
	 * @param string name of field
	 * @param array options list
	 */
 
	// IMPORTANT Set options array.
	/**
	 * options
	 * Expects associative array of option values and labels
	 array(
		'va' => 'Virginia',
		... etc. ...
		)
	 */
	public function options( $field_name, $options )
	{

		$this->fields[$field_name]['options'] = $options;
	}

	/**
	 * setfield
	 * this is function to set field properties
	 * one stop shopping style
	 * @param string name of field
	 * @param array option list
	 */

	public function setfield( $field_name, $options )
	{

	if( $debug )
	{
		print "<pre>";
		print "Options:\n-----------------------------\n";
		print_r( $options );
		print $options['name'];
		print $options['value'];
		print "\n--------------------------------------\n";
		print "</pre>";
	}

	/**
	 * Options (to this function, not an HTML menu option) is an associative array, which gives great flexibility in setting any number of options you want. E.g.
 
		array(
			'name' => 'email',
			'value'	=> 'foo@bar.com',
			'label' => 'Email Address',
			'required' => 1
			);
	 */
 
		$this->fields[$field_name]['name'] = ( $options['name'] ) ? $options['name'] : '';

		$this->fields[$field_name]['value'] = ( $options['value'] ) ? $options['value'] : '';

		if( $debug )
		{
			print "<pre>";
			print "Value (option): " . $options['value'] . "\n";
			print "Value: " . $this->fields[$field_name]['value'] . "\n";
			print "</pre>";
		}

		$this->fields[$field_name]['label'] = ( $options['label'] ) ? $options['label'] : '';

		$this->fields[$field_name]['required'] = ( $options['required'] ) ? $options['required'] : '';

	}



	/**
	 * tmpl_param
	 * Communicates value directly to template placeholder
	 * independent of form hander.
	 *
	 * @param string name of field
	 * @param variant a valid PHP value
	 */
 
	public function tmpl_param( $name, $value )
	{
		$this->templateEngine->assignp($this, array( 'name' => $name, 'value' => $value));
	}


/******************************************************************
 *                        Accesor Functions/Methods
 */
 
// setParams
	function params( $array )
    {
		$this->paramsList = $array;
	}

	/**
     * specify field names recognized by handler
     *
     * @param array list of fields
     */
//setFields
	function fields( $array )
    {
		$this->fields_list = $array;
	}
		
	/**
     * specify list of fields and rules to validate against
     *
     * @param array associative list of fields and validation rules 
     */

	public function setValidateFields( $array ) {
		$this->validate_list = $array;
	}
		
	/**
     * specify required fields
     *
     * @param array list of required fields
     */

	public function setRequiredFields( $array ) {
	    $this->required_list = $array;
	    // with foreach and the field props we really don't need a
	    // list of required fields do we?
	    foreach ( $array as $field_name ) {
            $this->fields[$field_name]['is_required'] = 1;
	    }
	}


	/**
     * specify field error messages
     *
     * @param array associative array of fields and messages
     */

	public function setMessages( $array )
    {
		$this->messages_list = $array;
	}
	
	
	//setAcceptHost
	public function acceptHost( $domain )
    {
		$this->acceptHost = $domain;
	}


 	/**
 	 * label, required, error status properties
 	 */
 
  	/**
     * Sets the label for a form field.
     * 
     */	
     
	public function setFieldLabel( $field_name, $label_name ) {
		$this->fields[$field_name]['label'] = $label_name;
	}	 


 	/**
     * Returns the label for a form field.
     * 
     */	
     
	public function getFieldLabel( $field_name ) {
		//$this->fields[$field_name]['label'];
	}

    /**
     * Sets the required status for a field.
     * Expected to be 1/0
     */	

	public function setFieldRequiredStatus( $field_name, $status ) {
		//$this->fields[$field_name]['required'] = $status;
	}
	
    /**
     * Returns the required status for a field.
     * Expected to be 1/0
     */	

	public function getFieldRequiredStatus( $field_name ) {
		//$this->fields[$field_name]['required'];
	}

	/**
	 * No setErrorForField()
	 * Errors are assigned during the validation process. 
	 */


    /**
     * Returns the last error for a form field.
     * 
     */

	public function getErrorForField( $field_name ) {
		//return $this->fields[$field_name]['error'];
	}


    /**
     * Returns an array containing
     * a list of recognized field names.
     */	

	function getFields( )
    {
		return $this->fields_list;
	}


/**
 * Helpers
 *
 */
 

	/**
	 * debug
	 *
	 * @param string text
	 * @param variant a valid PHP value
	 */
 
	function debug( $text, $value )
	{
		print "<p>(Debug) $text: $value</p>";
		if( is_array( $value ) )
		{
			print_r( $value );
		}
	}

	/**
	 * Display debugging information.
	 *
	 */	
	
    function dbg() {
        print "<h3>Debugging Information</h3>";
        print "<pre>";
        
        print "Field props\n";
        foreach ($this->fields_list as $f ) {
            print_r($this->fields);
        }
        print "Fields<br>";
        print_r( $this->fields_list );
        print "Parameters<br>";
        print_r( $this->paramsList );
        print "Validate<br>";
        print_r( $this->validate_list );
        print "Required<br>";
        print_r( $this->required_list );
        print_r( $this->messages_list );
        print $this->error;
        print "End Debugging";
        print "</pre>";
    }

}
