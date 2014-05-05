<?php

// Simple Test
// Without template engine.

require_once '../OoForm.php';

/**
 * Create form object.
 */

$form = new OoForm(
    array(
        'name',
        'email',
        'ticket'
    )
);


/**
 * Specify source for CGI parameters.
 */
 
$form->params( $_REQUEST );

/**
 * Specify validation rules.
 */
 
$form->setValidateFields(
    array(
        'name'      => '/[A-Za-z]/',
        'email'     => '_email',
        'ticket'    => '/[0-9]/'
    ) 
);


/**
 * Specify required fields.
 */

$form->setRequiredFields(
    array(
        'name',
        'email'
    )
);


if( $form->submitted() )
{
    if( $form->validated() )
    {	
		// form input valid
        print "<p>Name: ".$form->getFormField('name');
        print "<p>Email: ".$form->getFormField('email');
        print "<p>Ticket No: ".$form->getFormField('ticket');
        
    } else {
    
        // form input invalid
?>

<form action="<?php $PHP_SELF; ?>" method="POST">
Name: <input type="text" name="name" value="<?php echo $form->getFormField('name'); ?>"><?php echo $form->getErrorForField('name'); ?><br>
Email: <input type="text" name="email" value="<?php echo $form->getFormField('email'); ?>"><?php echo $form->getErrorForField('email'); ?><br>
Ticket: <input type="text" name="ticket" value="<?php echo $form->getFormField('ticket'); ?>"><?php echo $form->getErrorForField('ticket'); ?><br>
<input type="submit" name="submit">
</form>

<?php
    }
} else {
	// display initial form
?>

<form action="<?php $PHP_SELF; ?>" method="POST">
Name: <input type="text" name="name"><br>
Email: <input type="text" name="email"><br>
Ticket: <input type="text" name="ticket"><br>
<input type="submit" name="submit">
</form>

<?php
}   
?>
