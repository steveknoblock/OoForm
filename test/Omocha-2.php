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

/*
$field = new OoFormField(
    array(
        'name'		=> 'email',
        'label'		=> 'Email',
        'rule'		=> '_email'
    ) 
);

$field_list = array( 'name', 'email');
$fields = array();

foreach( $field_list as $field_name ) {
	$fields[] = new OoFormField(
    array(
        'name' => $field_name,
        'label' => $field_name
    ) 
);

}

print '<pre>';
var_dump( $fields );
print '</pre>';
*/

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
	
	
foreach( $field_init as $field ) {
	$fields[$field['name']] = new OoFormField(
    array(
        'name' => $field['name'],
        'label' => $field['label'],
    ) 
);
}

print '<p>I think this avoids confusion more than requiring the name to be the array key. At least it does, when duplicating like "first_name" => array "first_name" => "NAME HERE" ...';
print '<pre>';
var_dump( $fields );
print '</pre>';



// or maybe this
// would prohibit more than one field with the same name
// where anonymous field array would allow this confusion
// this means that I have to decide whether the name property
// should still be included or taken from the array key.
$field_init = array(
	'name' => array(
		'name' => 'name',
		'label' => 'Name: ',
		'rule' => 'REGEX HERE',
		'required' => '0'
	),
	'email' => array(
		'name' => 'email_address',
		'label' => 'Email: ',
		'rule' => 'REGEX HERE',
		'required' => '1'
	)
);


foreach( $field_init as $key=>$val ) {
	$field_list[] = $key;
	$fields[] = new OoFormField(
    array(
        'name' => $key,
        'label' => $val['label'],
        'rule' => $val['rule'],
        'required' => $val['required'],
    ) 
);
}

print '<pre>';
print_r( $fields_list );
var_dump( $fields );
print '</pre>';


