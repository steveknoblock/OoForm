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
require_once OOFORMBASE . 'ooform.template.'. OOFORM_TEMPLATE_ENGINE .'.class.php';
require_once OOFORMBASE . 'ooform-lang.php';
require_once OOFORMBASE . 'OoFormMessages.php';


/**
 * Class OoForm
 *
 */

class OoForm
{

	/**
	 * Private Member Variables (Properties)
	 */
	
	private $ooform_base; // Base path for including delegate class files.
	
	private $fields; // Assoc. array storing meta data and state for each field
	private $fields_list; // Array listing field names

	/**
	 * /remark
	 * Note: Remember, PHP's $_POST or $_GET are superglobals
	 * available within the class without explicity passing them.
	 */

	private $params_list; // parameter array

	/**
	 * /remark
	 * ooForm provides a set of predefined validation rules
	 * for common tasks.
	 */
	 
	private $rules_list = array(
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
	
	/**
	 * Housekeeping
	 * /remark Need to organize these.
	 */

	private $error; // error message
	private $debug = ''; // 'verbose'
	private $sticky = 1;
	private $acceptHost;
	//var $submitted; // future use
	private $fail_validate;
	private $fail_require;
	private $usesTemplateEngine; // do we connect to a template engine?
	private $templateobj; // the template engine


/* 
 * Class Member Functions
 *
 */
 

/**
 * Constructor
 *
 * @param array list of recognized fields
 */

function __construct( $field_list )
{
	
	/**
	 * OoForm delegates template handling to a template engine.
	 * At this point, it creates an instance of the template
	 * object for rendering form pages.
	 * You must pass an array of options meaningful to the
	 * particular template engine you are using in connection
	 * with ooForm.
	 */
	 
	$this->templateobj = new OoFormTemplate('templates');

	$this->fields_list = $field_list;

	$this->params_list = $_REQUEST;

	if( $debug )
	{
		print "<pre>";
		print "Dumping Constructor\n";
		print "Field List\n";
		print_r( $this->fields_list );
		print "CGI Parameters\n";
		print_r( $this->params_list );
		print "</pre>";
	}

	if( ! empty( $this->fields_list ) ) {
	 
	foreach ( $this->fields_list as $field_name ) {
		$this->fields[$field_name] = array(
			'name'			=> $field_name,
			'value'			=> '',
			'invalid'		=> 0,
			'is_required'	=> 0,
			'options'		=> array(),
			'label'			=> '',
			'error'			=> '' // Set error to empty by default
		);
	}

 /**
  * An empty label field returns the default message: "please
  * enter a value for the field."
  */
 
	if( $debug ) {
		print "<pre>";
		print "Initialized Fields<br>";
		print_r( $this->fields );
		print "</pre>";
	}

	 } else {
	  die("Critical Error: No fields specifed. A form must have at least one field.");
	 }
	 

}


/**
 * Submitted Status
 * returns true if form has been submitted
 */
		 
function submitted() {

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
 * Validate inputs and check if required.
 */

function validated() {

    // clear flags
    $this->fail_validate = 0;
    $this->fail_require = 0;

    /**
     * ooForm delegates messages to a message class.
     */
    $msgengine = new OoFormMessages;
	
    // debug
    if( $debug ) {
        print "Fields to validate: <br />";
        print_r( $this->fields_list );
    }
    
	 foreach ( $this->fields_list as $field_name ) {
		
	 	// debug
		if( $this->debug == 'verbose' ) {
		print "<pre>";
		print "Processing Field\n";
		print "Field: " . $field_name ."<BR>";
		print "Value: " . $this->value($field_name) ."<BR><br>";
		print "Fields<br>";
		print_r( $this->fields );
  		print "</pre>";
		}
		
        /**
         * /remark
         * This is a trick. The code looks dynamically to either
         * the form field or the CGI parameter for the value
         * based on the current state of stickiness. See below.
         */
         

		if( $this->params_list[$field_name] == '' ) {
		
				if( $this->debug == 'verbose' ) {
				print "Param: " . $field_name ." is empty<BR>";
				}
				
			// we know the field is empty, check if required
			if(	in_array($field_name, $this->required_list) ) {
			
			
				// set flag
				$this->fail_require = 1;
			
				// if empty and on required list, field is invalid
				$this->fields[$field_name][invalid] = 1;
			
            
            /**
             * /remark
             * IMPORTANT: If the intention is to render the form again
             * with fields filled in for correction and error messages
             * beside the fields, then we must store the required errors,
             * not just stop at the first one. To prepare for that I will
             * try setting the invalid flag in the stored fields array
             * for this field.
             */

				$this->fields[$field_name][required] = 1;
			
				// set the error message for this field
			
				// move to messages config
				//$temp = "<span>Please enter a value for the '$field_name' field.</span>";
				//$this->fields[$field_name][error] = $temp

				$this->fields[$field_name][error] = $msgengine->message('field_required', $this->fields[$field_name][label]);

				// debug
				if( $this->debug == '1' ) {
					print "Parameter " . $field_name ." failed required.<BR>";
				}
				
			}
		} else {
		
		/**
		 * Validate Fields
		 */
		 
		 /**
          * /remark
          * Note: if a field is empty, we don't check it for validity,
          * we only check non-empty fields for validity, we check empty
          * fields to see if they are required, once that check is done,
          * we don't have to check if valid.
          */
		
		if( $this->debug == 'verbose' ) {
		print "Validating " . $field_name ."<BR>";
		print "With Value: " . $this->value($field_name) ."<BR>";
		}
		
		// get regex
		if( array_key_exists($this->validate_list[$field_name], $this->rules_list) 
			&& preg_match('/_[a-zA-Z]+$/', $this->validate_list[$field_name])
			) {
				if( $this->debug == 'verbose' ) {
					print "Using rule";
				}
		       $regex = $this->rules_list[$this->validate_list[$field_name]];
		} else {
			    if( $this->debug == 'verbose' )
                {
			        print "Using user defined rule";
			    }
		       $regex = $this->validate_list[$field_name];
		    }
			if( $regex != '' && (! preg_match( $regex, $this->value($field_name))) )
			{
			// error validation
			$this->fail_validate = 1;
						
			// experimental code to set invalid flag for this field
			$this->fields[$field_name][invalid] = 1;
$this->fields[$field_name][error] = $msgengine->message('field_invalid', $this->fields[$field_name][label]);

			}
		}
	
	} // end foreach
	
	// debug
    if( $this->debug == 'verbose' ) {
    	//print "Fail Validate: ". $this->fail_validate ."<br>";;
	    //print "Fail Require: ". $this->fail_require ."<BR><br>";;
	}
    
	if( $this->fail_validate
	|| $this->fail_require ) {
		return false;
	} else {
		return true;
	}
}


		/**
		 * Trusted Source
		 * Returns true if the GET/POST information comes from a trusted source
		 */


public function trusted() {

	if(getenv('HTTP_REFERRER'))
    {
		$ref_url_parts=parse_url(getenv('HTTP_REFERRER'));
	} else {
		$ref_url_parts=parse_url(getenv('HTTP_REFERER'));
	}
	if($ref_url_parts['host'] !== $this->acceptHost)
    {
		return false;
	} else {
		return true;
	}
}


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
         * $this->params_list[$field_name];
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
			$value = $this->params_list[$field_name];
			// this does not handle multiple select arrays
			$value = $this->stripslashes_array( $value );
		} else {
			$value = $this->params_list[$field_name];
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

public function field()
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
} // end function


/**
 * action
 *
 */

function action()
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
 
function render($template_file, $sticky)
{
	$this->sticky = $sticky;
	return $this->templateobj->render($this, array( 'template' => $template_file));
}


/**
 * label
 *
 * @param string name of field
 * @param string human readable label for field
 */

// this is not an acessor function because fields are not yet objects
// but it acts like an accessor
public function label( $field_name, $label )
{

	$this->fields[$field_name][label] = $label;
}


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
 
function tmpl_param( $name, $value )
{
    $this->templateobj->assignp($this, array( 'name' => $name, 'value' => $value));
}


/******************************************************************
 *                        Accesor Functions/Methods
 */
 
// setParams
	function params( $array )
    {
		$this->params_list = $array;
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
//setValidateFields
	function validatefields( $array ) {
		$this->validate_list = $array;
		}
		
	/**
     * specify required fields
     *
     * @param array list of required fields
     */
//setRequiredFields
	function requiredfields( $array ) {
		//print "<p>In requiredfields()";
	    $this->required_list = $array;
	    // with foreach and the field props we really don't need a
	    // list of required fields do we?
	    foreach ( $array as $field_name ) {
            $this->fields[$field_name][is_required] = 1;
	    }
	}


	/**
     * specify field error messages
     *
     * @param array associative array of fields and messages
     */
	//setMessages
	function messages( $array )
    {
		$this->messages_list = $array;
	}
	//setAcceptHost
	function acceptHost( $domain )
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
     
	public function setLabel( $field_name, $label_name ) {
		$this->fields[$field_name]['label'] = $label_name;
	}	 
 	 
 	/**
     * Returns the label for a form field.
     * 
     */	
     
	public function getLabel( $field_name ) {
		$this->fields[$field_name]['label'];
	}

    /**
     * Sets the required status for a field.
     * Expected to be 1/0
     */	

	public function setRequiredStatus( $field_name, $status ) {
		$this->fields[$field_name]['required'] = $status;
	}
	
    /**
     * Returns the required status for a field.
     * Expected to be 1/0
     */	

	public function getRequiredStatus( $field_name ) {
		$this->fields[$field_name]['required'];
	}

	/**
	 * No setErrorForField()
	 * Errors are assigned during the validation process. 
	 */


    /**
     * Returns the last error for a form field.
     * 
     */

	public function geterrorforfield( $field_name ) {
		return $this->fields[$field_name]['error'];
	}


    /**
     * Returns an array containing
     * a list of recognized field names.
     */	
	function getfields( )
    {
		return $this->fields_list;
	}
		
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
        print_r( $this->params_list );
        print "Validate<br>";
        print_r( $this->validate_list );
        print "Required<br>";
        print_r( $this->required_list );
        print_r( $this->messages_list );
        print $this->error;
        print "End Debugging";
        print "</pre>";
    }

} // end OoForm
