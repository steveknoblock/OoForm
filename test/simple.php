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
//$form->debug = 1;
print "<pre>";
//var_dump( $form );
print "</pre>";

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

//$form->dbg();


if( $form->submitted() )
{
    if( $form->validated() )
    {	
        print "<p>Form input valid";
        print "<p>Name: ".$form->field('name');
        print "<p>Email: ".$form->field('email');
        print "<p>Ticket No: ".$form->field('ticket');
    } else {
        print "Form input invalid";
?>

<form action="<?php $PHP_SELF; ?>" method="POST">
Name: <input type="text" name="name" value="<?php echo $form->field('name'); ?>"><?php echo $form->getErrorForField('name'); ?><br>
Email: <input type="text" name="email" value="<?php echo $form->field('email'); ?>"><?php echo $form->getErrorForField('email'); ?><br>
Ticket: <input type="text" name="ticket" value="<?php echo $form->field('ticket'); ?>"><?php echo $form->getErrorForField('ticket'); ?><br>
<input type="submit" name="submit">
</form>

<?php
    }
} else {
 print "Form input valid";
?>

<form action="<?php $PHP_SELF; ?>" method="POST">
Name: <input type="text" name="name"><br>
Email: <input type="text" name="email"><br>
Ticket: <input type="text" name="ticket"><br>
<input type="submit" name="submit">
</form>

<?php
print "<p>Test simple";
print_r( $form->validate_fields );
}
        
?>
