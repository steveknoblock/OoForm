<?php

// test of Field class

require_once '../OoFormField.php';

/*
$field = new OoFormField(
	array(
		'name' => 'email',
		'label' => 'Email:',
		'rule' => '_email'
		)
);
*/

$field = new OoFormField(
    array(
        'name'      => 'email',
        'label'     => 'Email',
        'rule'    => '_email'
    ) 
);
