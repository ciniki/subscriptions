//
function ciniki_subscriptions_main() {
	//
	// Panels
	//
	this.main = null;

	this.cb = null;
	this.toggleOptions = {'no':'No', 'yes':'Yes'};
	this.statusOptions = {'10':'Active', 
		//'30':'Single Use',  // Future
		'50':'Archive'};
	this.subscriptionFlags = {'1':{'name':'Public'}, };

	this.init = function() {
		//
		// The main panel, which lists the options for production
		//
		this.main = new M.panel('Subscriptions',
			'ciniki_subscriptions_main', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.subscriptions.main');
		this.main.data = {};
        this.main.sections = { 
			'_info':{'label':'', 'type':'simplegrid', 'num_cols':1, 
				'headerValues':null,
				'cellClasses':[''],
				'noData':'No subscriptions',
				'addTxt':'Add Subscription',
				'addFn':'M.ciniki_subscriptions_main.showEdit(\'M.ciniki_subscriptions_main.showMain();\',0);',
				},
        };  
        this.main.sectionData = function(s) { return this.data; }
		this.main.cellValue = function(s, i, j, d) { return d.subscription.name + ' <span class="count">' + d.subscription.count + '</span>'; };
		this.main.rowFn = function(s, i, d) { 
			if( d.subscription.id > 0 ) {
				return 'M.ciniki_subscriptions_main.showSubscription(\'M.ciniki_subscriptions_main.showMain();\',\'' + d.subscription.id + '\');';
			}
			return '';
		};
		this.main.noData = function() { return 'No subscriptions'; }
		this.main.fieldValue = function(s, i, d) { return ''; }

		this.main.addButton('add', 'Add', 'M.ciniki_subscriptions_main.showEdit(\'M.ciniki_subscriptions_main.showMain();\',0);');
		this.main.addClose('Back');

		//  
		// The form to edit a subscription information
		//  
		this.edit = new M.panel('Edit Subscription',
			'ciniki_subscriptions_main', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.subscriptions.main.edit');
		this.edit.subscription_id = 0;
		this.edit.data = {};
		this.edit.sections = {
			'_name':{'label':'', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'status':{'label':'Status', 'type':'toggle', 'join':'yes', 'default':'10', 'toggles':this.statusOptions},
				'flags':{'label':'Options', 'type':'flags', 'join':'yes', 'flags':this.subscriptionFlags},
				}},
			'_desc':{'label':'Description', 'fields':{
				'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save subscription', 'fn':'M.ciniki_subscriptions_main.saveSubscription();'},
				//'delete':{'label':'Delete subscription', 'fn':'M.ciniki_subscriptions_main.deleteSubscription();'},
				}},
			};
		this.edit.fieldValue = function(s, i, d) { 	
			if( this.data[i] == null ) { return ''; }
			return this.data[i]; 
		}
		// Field history
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.subscriptions.subscriptionHistory', 'args':{'business_id':M.curBusinessID, 
				'subscription_id':this.subscription_id, 'field':i}};
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_subscriptions_main.saveSubscription();');
		this.edit.addClose('cancel');

		//
		// Panel to display information about a subscription, allow customer search
		// and
		this.subscription = new M.panel('Subscription',
			'ciniki_subscriptions_main', 'subscription',
			'mc', 'medium', 'sectioned', 'ciniki.subscriptions.subscription');
		this.subscription.subscription_id = 0;
		this.subscription.sections = {
			'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':2, 'hint':'customer name', 'noData':'No customers found', },
			'_actions':{'label':'Actions', 'type':'simplelist', 'list':{
//				'download':{'label':'Download Subscriber List', 'fn':'M.ciniki_subscriptions_main.showDownload(M.ciniki_subscriptions_main.subscription.subscription_id);'},
				'download':{'label':'Download Subscriber List', 'fn':'M.ciniki_subscriptions_main.showDownload(\'M.ciniki_subscriptions_main.showMain();\',M.ciniki_subscriptions_main.subscription.subscription_id);'},
			}},
			'customers':{'label':'Recent Additions', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'addTxt':'All Subscribers',
				'addFn':'M.ciniki_subscriptions_main.showSubscribers(\'M.ciniki_subscriptions_main.showSubscription();\',M.ciniki_subscriptions_main.subscription.subscription_id);',
				},
			};
		this.subscription.sectionData = function(s) {
			if( s == '_actions' ) { return this.sections[s].list; }
			if( s == 'customers') { return this.data[s]; }
		};
		this.subscription.listValue = function(s, i, d) { return d.label; };
		this.subscription.listFn = function(s, i, d) { 
			if( d.fn != null ) { return d.fn; } 
			return '';
		};
		this.subscription.fieldValue = function(s, i, d) { return ''; }
		this.subscription.liveSearchCb = function(s, i, value) {
			if( s == 'search' && value != '' ) {
				M.api.getJSONBgCb('ciniki.subscriptions.searchCustomers', {'business_id':M.curBusinessID, 
					'subscription_id':M.ciniki_subscriptions_main.subscription.subscription_id,
					'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_subscriptions_main.subscription.liveSearchShow('search', null, M.gE(M.ciniki_subscriptions_main.subscription.panelUID + '_' + s), rsp.customers); 
					});
				return true;
			}
		};
		this.subscription.liveSearchResultValue = function(s, f, i, j, d) {
			if( j == 0 ) {
				return d.customer.display_name;
			} else if( j == 1 ) {
				if( d.customer.status == 10 ) {
					return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscription.updateCustomer(event, event.srcElement.innerHTML, '" + d.customer.customer_id + "'); return false;\">Unsubscribe</button>";
				} else {
					return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscription.updateCustomer(event, event.srcElement.innerHTML, '" + d.customer.customer_id + "'); return false;\">Subscribe</button>";
				}
			}
		};
		this.subscription.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_subscriptions_main.showSubscription();\',\'mc\',{\'customer_id\':' + d.customer.customer_id + '});';
			};
		this.subscription.cellValue = function(s, i, j, d) {
			return d.customer.display_name;
		};
		this.subscription.rowFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_subscriptions_main.showSubscription();\',\'mc\',{\'customer_id\':' + d.customer.customer_id + '});';
		};
		this.subscription.updateCustomer = function(e, action, cid) {
			var status = 10;
			if( action == 'Unsubscribe' ) { status = 60; }
			M.api.getJSONCb('ciniki.subscriptions.updateSubscriber', 
				{'business_id':M.curBusinessID, 'subscription_id':this.subscription_id, 
				'customer_id':cid, 'status':status, 'latest':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					if( status == 10 ) {
						e.srcElement.innerHTML = 'Unsubscribe';
					} else {
						e.srcElement.innerHTML = 'Subscribe';
					}
					var p = M.ciniki_subscriptions_main.subscription;
					p.data.customers = rsp.latest;
					p.refreshSection('customers');
					p.show();
				});
		};
		this.subscription.addButton('edit', 'Edit', 'M.ciniki_subscriptions_main.showEdit(\'M.ciniki_subscriptions_main.showSubscription();\',M.ciniki_subscriptions_main.subscription.subscription_id);');
		this.subscription.addClose('Close');

		//
		// Download field options: 
		//		subscription: id, name
		//		customer: id, name, first, last, mailing_address, shipping_address, billing_address, primary_email, alternate_email
		//
		this.download = new M.panel('Download Subscription List',
			'ciniki_subscriptions_main', 'download',
			'mc', 'medium', 'sectioned', 'ciniki.subscriptions.edit');
		this.download.subscription_id = 0;
		this.download.data = {
			'_subscription_id':'No',
			'subscription_name':'No',
			'customer_id':'No',
			'customer_name':'Yes',
			'prefix':'No',
			'first':'No',
			'middle':'No',
			'last':'No',
			'suffix':'No',
			'shipping_address':'No',
			'billing_address':'No',
			'mailing_address':'Yes',
			'primary_email':'Yes',
			'alternatey_email':'No',
			};
		this.download.sections = {
			'_fields':{'label':'Available Fields', 'fields':{
				'_subscription_id':{'label':'Subscription ID', 'type':'toggle', 'toggles':this.toggleOptions},
				'subscription_name':{'label':'Subscription Name', 'type':'toggle', 'toggles':this.toggleOptions},
				'customer_id':{'label':'Customer ID', 'type':'toggle', 'toggles':this.toggleOptions},
				'customer_name':{'label':'Customer full name', 'type':'toggle', 'toggles':this.toggleOptions},
				'prefix':{'label':'Customer prefix', 'type':'toggle', 'toggles':this.toggleOptions},
				'first':{'label':'Customer first name', 'type':'toggle', 'toggles':this.toggleOptions},
				'middle':{'label':'Customer middle name', 'type':'toggle', 'toggles':this.toggleOptions},
				'last':{'label':'Customer last name', 'type':'toggle', 'toggles':this.toggleOptions},
				'suffix':{'label':'Customer suffix', 'type':'toggle', 'toggles':this.toggleOptions},
//				'shipping_address':{'label':'Shipping Address', 'type':'toggle', 'toggles':this.toggleOptions},
//				'billing_address':{'label':'Billing Address', 'type':'toggle', 'toggles':this.toggleOptions},
				'mailing_address':{'label':'Mailing Address', 'type':'toggle', 'toggles':this.toggleOptions},
				'primary_email':{'label':'Primary Email', 'type':'toggle', 'toggles':this.toggleOptions},
				'alternate_email':{'label':'Alternate Email', 'type':'toggle', 'toggles':this.toggleOptions},
				}},
			'_download':{'label':'', 'buttons':{
				'downloadXLS':{'label':'Download Excel', 'fn':'M.ciniki_subscriptions_main.downloadSubscriberList(M.ciniki_subscriptions_main.download.subscription_id, \'XLS\');'},
				}},
			};
		this.download.fieldValue = function(s, i, d) { 
			if( this.data[i] != null ) {
				return this.data[i];
			}
			return 'On'; 
		};
		this.download.addClose('Close');

		//
		// The subscription list fields available to download
		//
		this.subscribers = new M.panel('Subscribers',
			'ciniki_subscriptions_main', 'subscribers',
			'mc', 'medium', 'sectioned', 'ciniki.subscriptions.main.subscribers');
		this.subscribers.data = {};
		this.subscribers.subscription_id = 0;
		this.subscribers.sections = {
			'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':2, 'hint':'customer name', 'noData':'No customers found', },
			'customers':{'label':'Subscribers', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'sortable':'yes',
				'sortTypes':['text', 'text'],
				},
			};
		this.subscribers.sectionData = function(s) { return this.data[s]; }
		this.subscribers.liveSearchCb = function(s, i, value) {
			if( s == 'search' && value != '' ) {
				M.api.getJSONBgCb('ciniki.subscriptions.searchCustomers', {'business_id':M.curBusinessID, 
					'subscription_id':M.ciniki_subscriptions_main.subscribers.subscription_id,
					'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_subscriptions_main.subscribers.liveSearchShow('search', null, M.gE(M.ciniki_subscriptions_main.subscribers.panelUID + '_' + s), rsp.customers); 
					});
				return true;
			}
		};
		this.subscribers.liveSearchResultValue = function(s, f, i, j, d) {
			if( j == 0 ) {
				return d.customer.display_name;
			} else if( j == 1 ) {
				if( d.customer.status == 10 ) {
					return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscribers.updateCustomer(event, event.srcElement.innerHTML, '" + d.customer.customer_id + "'); return false;\">Unsubscribe</button>";
				} else {
					return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscribers.updateCustomer(event, event.srcElement.innerHTML, '" + d.customer.customer_id + "'); return false;\">Subscribe</button>";
				}
			}
		};
		this.subscribers.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_subscriptions_main.showSubscribers();\',\'mc\',{\'customer_id\':' + d.customer.customer_id + '});';
			};
		this.subscribers.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				return d.customer.display_name;
			} else if( j == 1 ) {
				if( d.customer.status == 10 ) {
					return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscribers.updateCustomer(event, event.srcElement.innerHTML, '" + d.customer.customer_id + "'); return false;\">Unsubscribe</button>";
				} else {
					return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscribers.updateCustomer(event, event.srcElement.innerHTML', '" + d.customer.customer_id + "'); return false;\">Subscribe</button>";
				}
			}
		};
		this.subscribers.rowFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_subscriptions_main.showSubscribers();\',\'mc\',{\'customer_id\':' + d.customer.customer_id + '});';
		};
		this.subscribers.updateCustomer = function(e, action, cid) {
			var status = 10;
			if( action == 'Unsubscribe' ) { status = 60; }
			M.api.getJSONCb('ciniki.subscriptions.updateSubscriber', 
				{'business_id':M.curBusinessID, 'subscription_id':this.subscription_id, 
				'customer_id':cid, 'status':status, 'subscribers':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					if( status == 10 ) {
						e.srcElement.innerHTML = 'Unsubscribe';
					} else {
						e.srcElement.innerHTML = 'Subscribe';
					}
					var p = M.ciniki_subscriptions_main.subscribers;
					p.data.customers = rsp.subscribers;
					p.refreshSection('customers');
					p.show();
				});
		};
		this.subscribers.addClose('Back');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_subscriptions_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.cb = cb;
		this.showMain(cb);
	}

	//
	// Grab the subscriptions for the business, with stats information
	//
	this.showMain = function(cb) {
		var rsp = M.api.getJSONCb('ciniki.subscriptions.stats', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_subscriptions_main.main;
			p.data = rsp.subscriptions;
			p.refresh();
			p.show(cb);
		});
	}

	this.showSubscription = function(cb, sid) {
		if( sid != null ) {
			this.subscription.subscription_id = sid;
		}
		var rsp = M.api.getJSONCb('ciniki.subscriptions.subscriptionGet', 
			{'business_id':M.curBusinessID, 'subscription_id':this.subscription.subscription_id, 'latest':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_subscriptions_main.subscription;
				p.title = rsp.subscription.name;
				p.data = rsp.subscription;
				p.data.customers = rsp.subscription.latest;
				p.refresh();
				p.show(cb);
			});
	}

	this.showEdit = function(cb, sid) {
		if( sid != null ) {
			this.edit.subscription_id = sid;
		}
		if( this.edit.subscription_id > 0 ) {
			var rsp = M.api.getJSONCb('ciniki.subscriptions.subscriptionGet', 
				{'business_id':M.curBusinessID, 'subscription_id':this.edit.subscription_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_subscriptions_main.edit;
					p.data = rsp.subscription;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.edit.reset();
			this.edit.data = {};
			this.edit.refresh();
			this.edit.show(cb);
		}
	}

	this.saveSubscription = function() {
		if( this.edit.subscription_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.subscriptions.subscriptionUpdate', 
					{'business_id':M.curBusinessID, 
					'subscription_id':M.ciniki_subscriptions_main.edit.subscription_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_subscriptions_main.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.subscriptions.subscriptionAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				} 
				M.ciniki_subscriptions_main.edit.close();
			});
		}
	}

	this.deleteSubscription = function() {
		
	}

//	this.showDownload = function(subscriptionID) {
//		this.download.subscription_id = subscriptionID;
//		this.download.cb = 'M.ciniki_subscriptions_main.showSubscription();';
//		this.download.refresh();
//		this.download.show();
//	}

	this.downloadSubscriberList = function(subscriptionID, type) {
		if( type == 'XLS' ) {
			var content = this.download.serializeForm('yes');
			window.open(M.api.getUploadURL('ciniki.subscriptions.downloadXLS', 
				{'business_id':M.curBusinessID, 'subscription_id':subscriptionID}) + '&' + content);
		}
	}

	this.showDownload = function(cb, sid) {
		if( sid != null ) { this.sublist.subscription_id = sid; }
		this.sublist.refresh();
		this.sublist.show(cb);
	};

	this.downloadListExcel = function() {	
		var cols = '';
		var fields = this.sublist.sections.options.fields;
		for(i in fields) {
			if( this.sublist.formFieldValue(fields[i], i) == 'yes' ) {
				cols += (cols!=''?'::':'') + i;
			}
		}
		window.open(M.api.getUploadURL('ciniki.subscriptions.subscriptionDownloadExcel', 
			{'business_id':M.curBusinessID, 'subscription_id':this.sublist.subscription_id, 'columns':cols}));
	};

	this.showSubscribers = function(cb, sid) {
		if( sid != null ) { this.subscribers.subscription_id = sid; }
		M.api.getJSONCb('ciniki.subscriptions.subscriptionSubscriberList', 
			{'business_id':M.curBusinessID, 'subscription_id':this.subscribers.subscription_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_subscriptions_main.subscribers;
				p.data = rsp;
				p.refresh();
				p.show(cb);
			});
	};
}
