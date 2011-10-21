<?php
//
// Description
// -----------
// This function will return the array of rulesets available to the subscriptions module.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_subscriptions_getRulesets($ciniki) {

	//
	// Permissions can be in the form of=> 
	//		- owners, any employee in the group 0x0001 (owner) in business_users.
	//		- group, any employee in the group 0x8000 (subscriptions) in business_users.
	//		- employee, any employee in the group 0x0002 (employee) in business_users.
	//		- employees, customer, customers
	//
	// - business_group - 0x8001, (any owners) or (employees in group Bug Tracker)
	// - business_group - 0x8003, (any owners) or (any employees) or (employees in group Bug Tracker)
	// - business_group - blank/non-existent, ignored
	//
	// business_group rules are OR'd together with customers rules
	//
	// - customers - 'any', (any customers of the business)
	// - customers - 'self', (the session user_id must be the same as requested user_id)
	//
	// *note* A function can only be allowed to customers, if there is no business_group rule.
	//

	return array(
		//
		// The default for nothing selected is to have access restricted to nobody
		//
		''=>array('label'=>'Nobody',
			'description'=>'Nobody has access, no even owners.',
			'details'=>array(
				'owners'=>'no access.',
				'employees'=>'no access.',
				'customers'=>'no access.'
				),
			'default'=>array(),
			'methods'=>array()
			),

		//
		// For all methods, you must be in the group Bug Tracker.  Only need to specify
		// the default permissions, will automatically be applied to all methods.
		//
		'employees'=>array('label'=>'Employees', 
			'description'=>'This permission setting allows all owners and employees of the business to manage wineproduction',
			'details'=>array(
				'owners'=>'all tasks',
				'employees'=>'all tasks',
				'customers'=>'no access.'
				),
			'default'=>array('business_group'=>0x8003),
			'methods'=>array()
			),

		//
		// For all methods, you must be in the group Bug Tracker.  Only need to specify
		// the default permissions, will automatically be applied to all methods.
		//
		'group_restricted'=>array('label'=>'Group Restricted', 
			'description'=>'This permission setting allows only users in the subscriptions group to manage subscriptions',
			'details'=>array(
				'owners'=>'all tasks',
				'employees'=>'all tasks',
				'customers'=>'no access.'
				),
			'default'=>array('business_group'=>0x8000),
			'methods'=>array()
			),
	);
}
?>
