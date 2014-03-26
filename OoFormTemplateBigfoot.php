<?php

/**********************************************************************
 * $Id: ooform.template.bigfoot.class.php,v 1.3 2005/10/21 19:40:30 Steve Knoblock Exp $
 **********************************************************************/

 /**
 * ooForm Bigfoot Template Class
 * Class file implements connection to Bigfoot template engine.
 * @author Steve Knoblock
 * @version $Revision: 1.3 $
 * @date begin: November 03, 2004
 * @date revised: $Date: 2005/10/21 19:40:30 $
 */
 
/**
 * /remark
 * The main ooForm class delegates responsibility for
 * template generation to this class. This file is part
 * of the ooForm class package. It is useless for anything else.
 */

  /**
  * /remark
  * The location of the template engine is the only
  * requirement. The dependency is handled by the ooform class.
  */

// should be called ooTemplateBigFoot
class ooFormTemplate extends BigFoot  {

// adds a render function to bigfoot, which only has a fetch function to fetch the rendered template. Why was it not called render? Because it was to have fetch and display, one returning the rendered output and one that sent it directly to the browser (print).

// assign an arbitrary placeholder in template not mentioned in form field list
	function assignp ( &$ooform, $params ) {
	
	// params are placeholder name and value
	
	$this->assign( $params['name'], $params['value'] );
	
	}
	
	function render ( &$ooform, $params ) {
	
	print "Rendering Template: " . $params['template'];
	
	/* Note: This function must be generic enough to accept the reference to the form object and any parameters that need to be passed to the template engine in its own format. So the arugments are first the reference to the form handler object and second, an array of parameters to pass to the template engine.
	 */
	
		// if this extends bigfoot, then I don't need new BigFoot
		//$template = new BigFoot( 'templates' );
		// and this should be $this->
		//$template->set_template_path( 'templates' );
	// because this is a class and we are inside the class, like
	// inside bigfoot, calling bigfoot functions from within an
	// extended version of bigfoot
		$this->set_template_path( 'templates' );
	
	foreach ( $ooform->get_fields() as $fieldname) {
	
	// the assignment of error messages cannot take place in the form class, it must take place in the template class

	//$tmplvar{"error-$field"} = $field->message if $field->invalid;
	
	$msg = "<p>Please enter a valid value for the  '$field_name' field because it is: {$messages[$field_name]}</p>";

		// old
		//$this->assign( 'error_' . $fieldname, $ooform->error_list[$fieldname] );
	
		if( $ooform->fields[$fieldname][invalid] == 1
			|| $ooform->fields[$fieldname][is_required] == 1 ) {	
			$this->assign( 'error_' . $fieldname, $ooform->fields[$fieldname][error] );
		}
		
		$this->assign( 'label_' . $fieldname, $ooform->fields[$fieldname][label] );

		$this->assign( $fieldname, $ooform->field( $fieldname ) );
	}
	
	// special system placeholders
	$this->assign( 'form_action', $ooform->action() );
	
	return $this->fetch( $params['template'] );
	
	}

}

?>