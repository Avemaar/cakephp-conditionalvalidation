#CakePHP-ConditionalValidation

This plugin allows you to have conditional validation in your CakePHP Model validation rules.

Example scenarios can include:

* if you only want validation to kick in if a checkbox was selected,
* you only want to validate IF a field was actually filled in
* you need to validate one field based on the existence or value of another

## Requirements

* CakePHP 1.x

## Installation

For now, just create the behaviour file in your Model/Behavior folder until this is turned into a proper plugin.

## Usage

This behavior allows you to have validation rules for individual fields that only run when you want them to. For example you don't always need to validate file sizes and extensions, unless a file has actually been specified. It works by checking `Model->validate->rules->field->rules` for a special key (`'if'`).

If that key exists as one of the field's rule parameters, then this behaviour expects that it will contain either a single condition or an array of conditions that will be used to determine if we need the validation rule to actually run.

First, add the behavior to your model

```php

class Something extends AppModel
{
	public $actsAs = array('ConditionalValidation');
}	
```

Then, in your validation section, configure the conditions for any field needed
```php

class Something extends AppModel
{
	public $actsAs = array('ConditionalValidation');
	
	public $validate = array(
		'guild_name' => array('isActive' => array(
			'rule' => 'isUnique',
			'if' => array('is_active', true),
			)
		);
	);
}	
```

## Examples

Run this rule if a certain field `exists` and is `!= ''`

```php

class Something extends AppModel
{
	public $actsAs = array('ConditionalValidation');
	
	public $validate = array(
		'guild_name' => array('isActive' => array(
			'rule' => 'isUnique',
			'if' => array('is_active'),
			)
		);
	);
}	
```
Run this rule if a certain field `exists` and is `== 1`

```php

class Something extends AppModel
{
	public $actsAs = array('ConditionalValidation');
	
	public $validate = array(
		'guild_name' => array('isActive' => array(
			'rule' => 'isUnique',
			'if' => array('is_active', 1),
			)
		);
	);
}	
```
Run this rule if a certain field `exists` and is `>= 1`

```php

class Something extends AppModel
{
	public $actsAs = array('ConditionalValidation');
	
	public $validate = array(
		'guild_name' => array('isActive' => array(
			'rule' => 'isUnique',
			'if' => array('status', 1),
			)
		);
	);
}	
```
Run this rule if **several** conditions are met. E.g. `is_active == true` and `status >= 1`

```php

class Something extends AppModel
{
	public $actsAs = array('ConditionalValidation');
	
	public $validate = array(
		'guild_name' => array('isActive' => array(
			'rule' => 'isUnique',
			'if' => array(
				array('status', 1, '>='),
				array('is_active', 1)
			)
		);
	);
}	
```