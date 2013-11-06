<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_subscriptions_sync_objects($ciniki, &$sync, $business_id, $args) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'objects');
	return ciniki_subscriptions_objects($ciniki);
}
?>
