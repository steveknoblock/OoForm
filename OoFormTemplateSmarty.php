<?php

/**********************************************************************
 * $Id: ooform.template.smarty.class.php,v 1.3 2005/10/21 19:40:30 Steve Knoblock Exp $
 **********************************************************************/

/**
 * ooForm Smarty Template Class
 * Class file implements connection to Smarty template engine.
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
  * requirement.
  */

  /**
   * /remark
   * This is hardwired to Folkstreams, must be replaced.
   */

define('BASE_DIR','/public/vhost/f/folkstreams');
require_once(BASE_DIR . '/includes/smarty.php');

// should be called ooTemplateBigFoot
class ooFormTemplate extends Smarty  {

function ooFormTemplate() {

// smarty setup through extended class
// you cannot create the template engine object
// from outside the context of the form handler
// and expect this adapter to work The form handler
// must create and control the template engine object
// and set its properties
// we set basic folder info for smarty here
$this->template_dir = DOCROOT_DIR . '/templates/';
$this->compile_dir = DOCROOT_DIR . '/templates_c/';
$this->config_dir = DOCROOT_DIR . '/configs/';
$this->cache_dir = DOCROOT_DIR . '/cache/';

/**
 * enable for debugging
 */
//$smarty->compile_check = true;
//$smarty->debugging = true;
}


// adds a render function to bigfoot, which only has a fetch function to fetch the rendered template. Why was it not called render? Because it was to have fetch and display, one returning the rendered output and one that sent it directly to the browser (print).

// assign an arbitrary placeholder in template not mentioned in form field list
	function assignp ( &$ooform, $params ) {
	
	// params are placeholder name and value
	
	$this->assign( $params['name'], $params['value'] );
	
	}
	
	function render ( &$ooform, $params ) {
	
	// debug
	//print "ooForm debug: Rendering Template: " . $params['template'];
	
	/* Note: This function must be generic enough to accept the reference to the form object and any parameters that need to be passed to the template engine in its own format. So the arugments are first the reference to the form handler object and second, an array of parameters to pass to the template engine.
	 */

	
	foreach ( $ooform->get_fields() as $fieldname) {
	
	$value = $ooform->field( $fieldname );
	
	/* Debug
	print "<p>(Debug) Template Adapter Processing Field</p>";
	print '<p>(Debug) Field: '. $fieldname .'</p>';
	print "<p>(Debug) Value: $value</p>";
	*/
	
	/* Note: The assignment of error messages cannot take place in the form class, it must take place in the template class. This is because the form hanlder must work with the template engine through this template adapter.
	 */
	
	// CGI::FormBuilder of the same looks like this
	//$tmplvar{"error-$field"} = $field->message if $field->invalid;
	
		// old
		//$this->assign( 'error_' . $fieldname, $ooform->error_list[$fieldname] );
	
		if( $ooform->fields[$fieldname][invalid] == 1
			|| $ooform->fields[$fieldname][is_required] == 1 ) {	
			$this->assign( 'error_' . $fieldname, $ooform->fields[$fieldname][error] );
		}
		
		$this->assign( 'label_' . $fieldname, $ooform->fields[$fieldname][label] );

/* Here we assign the value contained in the form field to the template placeholder. This is the function implemented by the template engine we built the adapter for.

	Why does this fail for array values? Is it?

*/

/* Handle fields of type select menu. This assigns an associative array of option values and labels for a select menu to the template placeholder. This is then used on the template. This is where the adapter really shines. Smarty ships with a special function to handle select menus in one construct by accepting an associative array of option values and labels. Other template engines might require the adaptor to do more work, to setup for a "template loop" construct such as the Smarty section, which I could also use in this case.

One reason for all this: It is possible to assign option values and labels to fields given to the form hanlder that are 'dummy' fields, but this is inelegant and hackish. It works, but speicfies nonexistient form fields as fields. Yuck.

 */

 	// debug
 	//print "<p>(Debug) Options Field: $fieldname</p>";

// print_r( $ooform->fields[$fieldname][options] );
 
 // I could store form type, but that may not be necessary given the system does not _generate_ form fields, but works through a template engine.
if( ! empty( $ooform->fields[$fieldname][options] ) ) {
	
	// debug
	//print "----------------------------------------------------";
	//print "<p>(Debug) Field: $fieldname</p>";
	//print "<p>(Debug) Options:</p>";
	//print_r( $ooform->fields[$fieldname][options] );
	$this->assign( $fieldname . '_options', $ooform->fields[$fieldname][options] );


/*

assign selected placeholder

this is what smarty expects

{html_options options=$film_options selected=$option_selected }

we (the program and me) created film_options automatically above

I need to send the current field value to the placeholder for selected option.

$this->assign( $fieldname . '_selected', $ooform->fields[$fieldname][value] );

my($slct, $chk) = ismember($o, @value) ? ('selected', 'checked') : ('','');
            debug 2, "<tmpl_loop loop-$field> = adding { label => $n, value => $o }";
            push @tmpl_loop, {
                label => $n,
                value => $o,
                checked => $chk,
                selected => $slct,
            };

*/

// delete this line
//$value = $ooform->field( $fieldname );
// debug
	//print '<p>(Debug) Field: '. $fieldname .'_selected</p>';
	//print "<p>(Debug) Selected Value:</p>";
	//print $value;
	
$this->assign( $fieldname . '_selected', $value );

}

// look down, I think the next statement should be in an else clause, if it's an option, we don't need to assign, it's already been assigned to _selected


// debug
//print "<p>(Debug) $fieldname: $value</p>";
//if( is_array( $value ) )
 //{
 //	print_r( $value );
 //}
 

/* Here we assign values to placeholders from form property values.

the value for title field in properties is transferred to the template thorugh a placeholder named after the fieldname.

so field title
submits "Title"
and then field title property called value
is set to "Title" from cgi parameters
then the value of the field is
transferred to template placeholder
the placeholder name is the name of the field,
which is the name of the form input, etc.

Now, it might be preferable, like CFB, to give these placeholders the name

value_[fieldname]

[fieldname]_value

instead of just the field name, sort of like a OO properties syntax. Like the field error and field label placeholders.

error_title
label_title
value_title
(options_title ?)

Here, it grabs the field value through invoking the field() method from ooForm.

*/ 

 // Modified Saturday, April 16, 2005 to support automated field_fieldname placeholders
		//print "Assinging Field: " . ('field_' . $fieldname) ."<br>";
		//print "With Value: " . $value ."<br>";
		$this->assign( 'value_' . $fieldname, $ooform->field( $fieldname ) );
// old
//$this->assign( $fieldname, $ooform->field( $fieldname ) );
	}
	
	// special system placeholders
	$this->assign( 'form_action', $ooform->action() );
	
	// test
	//$this->assign( 'here', "You are here" );
	
	// debug
	//print "Smarty Template Dir: -->" . $this->template_dir ."<--";
	//print "Rendering Template: " . $params['template'];
		
		/* Unfortunatley, debugging console is only available when displaying the template through Smarty. For the purposes of this adapter, we cannot do that.

		$this->debugging=true;
		
		{$debug}
		
		does not work either through fetch, at least I couldn't get it to work.
		
		*/
	
	return $this->fetch( $params['template'] );
	
	}

}

?>