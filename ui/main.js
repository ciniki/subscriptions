//
function ciniki_subscriptions_main() {
    //
    // Panels
    //
    this.cb = null;
    this.toggleOptions = {'no':'No', 'yes':'Yes'};
    this.statusOptions = {'10':'Active', 
        //'30':'Single Use',  // Future
        '50':'Archive'};
    this.subscriptionFlags = {'1':{'name':'Public'}, '2':{'name':'Auto Subscribe'}};

    //
    // The main panel, which lists the options for production
    //
    this.menu = new M.panel('Subscriptions',
        'ciniki_subscriptions_main', 'menu',
        'mc', 'large', 'sectioned', 'ciniki.subscriptions.main.menu');
    this.menu.data = {};
    this.menu.sections = { 
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'10', 'tabs':{
            '10':{'label':'Active', 'fn':'M.ciniki_subscriptions_main.menu.switchTab("10");'},
            '50':{'label':'Archived', 'fn':'M.ciniki_subscriptions_main.menu.switchTab("50");'},
            }},
        '_info':{'label':'', 'type':'simplegrid', 'num_cols':4, 
            'headerValues':['Name', '# Customers', 'Public', 'Auto Subscribe'],
            'sortable':'yes',
            'sortTypes':['text', 'number', 'text', 'text'],
            'cellClasses':[''],
            'noData':'No subscriptions',
            'addTxt':'Add Subscription',
            'addFn':'M.ciniki_subscriptions_main.edit.open(\'M.ciniki_subscriptions_main.menu.open();\',0);',
            },
    };  
    this.menu.sectionData = function(s) { return this.data; }
    this.menu.cellValue = function(s, i, j, d) { 
        console.log(d);
        switch(j) {
            case 0: return d.name;
            case 1: return d.count;
            case 2: return d.public;
            case 3: return d.auto_subscribe;
        }
    }
    this.menu.rowFn = function(s, i, d) { 
        if( d.id > 0 ) {
            return 'M.ciniki_subscriptions_main.subscription.open(\'M.ciniki_subscriptions_main.menu.open();\',\'' + d.id + '\');';
        }
        return '';
    };
    this.menu.noData = function() { return 'No subscriptions'; }
    this.menu.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.open();
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.subscriptions.stats', {'tnid':M.curTenantID, 'status':this.sections._tabs.selected}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_subscriptions_main.menu;
            p.data = rsp.subscriptions;
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addButton('add', 'Add', 'M.ciniki_subscriptions_main.edit.open(\'M.ciniki_subscriptions_main.menu.open();\',0);');
    this.menu.addClose('Back');

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
            'save':{'label':'Save subscription', 'fn':'M.ciniki_subscriptions_main.edit.save();'},
            'delete':{'label':'Delete subscription', 'fn':'M.ciniki_subscriptions_main.edit.remove();'},
            }},
        };
    this.edit.fieldValue = function(s, i, d) {  
        if( this.data[i] == null ) { return ''; }
        return this.data[i]; 
    }
    // Field history
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.subscriptions.subscriptionHistory', 'args':{'tnid':M.curTenantID, 
            'subscription_id':this.subscription_id, 'field':i}};
    };
    this.edit.open = function(cb, sid) {
        if( sid != null ) { this.subscription_id = sid; }
        if( this.subscription_id > 0 ) {
            M.api.getJSONCb('ciniki.subscriptions.subscriptionGet', 
                {'tnid':M.curTenantID, 'subscription_id':this.subscription_id}, function(rsp) {
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
            this.reset();
            this.data = {};
            this.refresh();
            this.show(cb);
        }
    }
    this.edit.save = function() {
        if( this.subscription_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.subscriptions.subscriptionUpdate', 
                    {'tnid':M.curTenantID, 'subscription_id':this.subscription_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_subscriptions_main.edit.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            var rsp = M.api.postJSONCb('ciniki.subscriptions.subscriptionAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_subscriptions_main.edit.close();
            });
        }
    }
    this.edit.remove = function() {
        M.confirm("Are you sure you want to remove '" + this.data.name + "'? This will unsubscribe all customers from this list, and remove any history of the subscription.",null,function() {
            M.api.getJSONCb('ciniki.subscriptions.subscriptionDelete', 
                {'tnid':M.curTenantID, 'subscription_id':this.subscription_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_subscriptions_main.subscription.close();
                });
        });
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_subscriptions_main.edit.save();');
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
//              'download':{'label':'Download Subscriber List', 'fn':'M.ciniki_subscriptions_main.showDownload(M.ciniki_subscriptions_main.subscription.subscription_id);'},
//              'download':{'label':'Download Subscriber List', 'fn':'M.ciniki_subscriptions_main.showDownload(\'M.ciniki_subscriptions_main.menu.open();\',M.ciniki_subscriptions_main.subscription.subscription_id);'},
            'download':{'label':'Download Subscriber List', 'fn':'M.startApp(\'ciniki.customers.download\',null,\'M.ciniki_subscriptions_main.subscription.open();\',\'mc\',{\'subscription_id\':M.ciniki_subscriptions_main.subscription.subscription_id});'},
            'downloadmailmerge':{'label':'Download Excel Mail Merge', 'fn':'M.ciniki_subscriptions_main.download.mailMerge(M.ciniki_subscriptions_main.subscription.subscription_id);'},
            'addallcustomers':{'label':'Add All Customers', 'fn':'M.ciniki_subscriptions_main.subscription.addAllCustomers(M.ciniki_subscriptions_main.subscription.subscription_id);'},
        }},
        'customers':{'label':'Recent Additions', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'addTxt':'All Subscribers',
            'addFn':'M.ciniki_subscriptions_main.subscribers.open(\'M.ciniki_subscriptions_main.subscription.open();\',M.ciniki_subscriptions_main.subscription.subscription_id);',
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
            M.api.getJSONBgCb('ciniki.subscriptions.searchCustomers', {'tnid':M.curTenantID, 
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
                return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscription.updateCustomer(event, event.target.innerHTML, '" + d.customer.customer_id + "'); return false;\">Unsubscribe</button>";
            } else {
                return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscription.updateCustomer(event, event.target.innerHTML, '" + d.customer.customer_id + "'); return false;\">Subscribe</button>";
            }
        }
    };
    this.subscription.liveSearchResultRowFn = function(s, f, i, j, d) { 
        var ctype = '';
        if( (M.curTenant.modules['ciniki.customers'].flags&0x02) > 0 
            && d.customer.member_status > 0 
            ) {
            ctype = ', \'member\':\'yes\'';
        }
        return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_subscriptions_main.subscription.open();\',\'mc\',{\'customer_id\':' + d.customer.customer_id + ctype + '});';
        };
    this.subscription.cellValue = function(s, i, j, d) {
        return d.customer.display_name;
    };
    this.subscription.rowFn = function(s, i, d) {
        var ctype = '';
        if( (M.curTenant.modules['ciniki.customers'].flags&0x02) > 0 
            && d.customer.member_status > 0 
            ) {
            ctype = ', \'member\':\'yes\'';
        }
        return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_subscriptions_main.subscription.open();\',\'mc\',{\'customer_id\':' + d.customer.customer_id + ctype + '});';
    };
    this.subscription.updateCustomer = function(e, action, cid) {
        var status = 10;
        if( action == 'Unsubscribe' ) { status = 60; }
        M.api.getJSONCb('ciniki.subscriptions.updateSubscriber', 
            {'tnid':M.curTenantID, 'subscription_id':this.subscription_id, 
            'customer_id':cid, 'status':status, 'latest':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                if( status == 10 ) {
                    e.target.innerHTML = 'Unsubscribe';
                } else {
                    e.target.innerHTML = 'Subscribe';
                }
                var p = M.ciniki_subscriptions_main.subscription;
                p.data.customers = rsp.latest;
                p.refreshSection('customers');
                p.show();
            });
    };
    this.subscription.open = function(cb, sid) {
        if( sid != null ) { this.subscription_id = sid; }
        var rsp = M.api.getJSONCb('ciniki.subscriptions.subscriptionGet', 
            {'tnid':M.curTenantID, 'subscription_id':this.subscription_id, 'latest':'yes'}, function(rsp) {
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
    this.subscription.addAllCustomers = function(sid) {
        M.confirm("Are you sure you add all your customers to this mailing list?",null,function() {
            M.api.getJSONCb('ciniki.subscriptions.subscriptionAddAllCustomers', 
                {'tnid':M.curTenantID, 'subscription_id':sid}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_subscriptions_main.subscription.open();
                });
        });
    };
    this.subscription.addButton('edit', 'Edit', 'M.ciniki_subscriptions_main.edit.open(\'M.ciniki_subscriptions_main.subscription.open();\',M.ciniki_subscriptions_main.subscription.subscription_id);');
    this.subscription.addClose('Close');

    //
    // Download field options: 
    //      subscription: id, name
    //      customer: id, name, first, last, mailing_address, shipping_address, billing_address, primary_email, alternate_email
    //
    this.download = new M.panel('Download Subscription List',
        'ciniki_subscriptions_main', 'download',
        'mc', 'medium', 'sectioned', 'ciniki.subscriptions.edit');
    this.download.subscription_id = 0;
    this.download.data = {
        '_subscription_id':'No',
        'subscription_name':'No',
//          'customer_id':'No',
        'customer_name':'Yes',
        'callsign':'No',
        'prefix':'No',
        'first':'No',
        'middle':'No',
        'last':'No',
        'suffix':'No',
        'shipping_address':'No',
        'billing_address':'No',
        'mailing_address':'Yes',
        'emails':'Yes',
//          'alternatey_email':'No',
        };
    this.download.sections = {
        '_fields':{'label':'Available Fields', 'fields':{
//              '_subscription_id':{'label':'Subscription ID', 'type':'toggle', 'toggles':this.toggleOptions},
            'subscription_name':{'label':'Subscription Name', 'type':'toggle', 'toggles':this.toggleOptions},
//              'customer_id':{'label':'Customer ID', 'type':'toggle', 'toggles':this.toggleOptions},
            'callsign':{'label':'Customer Callsign', 'type':'toggle', 'toggles':this.toggleOptions,
                'visible':function() {return M.modFlagSet('ciniki.customers', 0x0400); },
                },
            'display_name':{'label':'Customer full name', 'type':'toggle', 'toggles':this.toggleOptions},
            'prefix':{'label':'Customer prefix', 'type':'toggle', 'toggles':this.toggleOptions},
            'first':{'label':'Customer first name', 'type':'toggle', 'toggles':this.toggleOptions},
            'middle':{'label':'Customer middle name', 'type':'toggle', 'toggles':this.toggleOptions},
            'last':{'label':'Customer last name', 'type':'toggle', 'toggles':this.toggleOptions},
            'suffix':{'label':'Customer suffix', 'type':'toggle', 'toggles':this.toggleOptions},
//              'shipping_address':{'label':'Shipping Address', 'type':'toggle', 'toggles':this.toggleOptions},
//              'billing_address':{'label':'Billing Address', 'type':'toggle', 'toggles':this.toggleOptions},
            'mailing_address':{'label':'Mailing Address', 'type':'toggle', 'toggles':this.toggleOptions},
            'emails':{'label':'Emails', 'type':'toggle', 'toggles':this.toggleOptions},
//              'alternate_email':{'label':'Alternate Email', 'type':'toggle', 'toggles':this.toggleOptions},
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
    this.download.mailMerge = function(sid) {
        M.api.openFile('ciniki.subscriptions.downloadMailMerge', {'tnid':M.curTenantID, 'subscription_id':sid});
    };
    this.download.addClose('Close');

    //
    // The subscription list fields available to download
    //
    this.subscribers = new M.panel('Subscribers',
        'ciniki_subscriptions_main', 'subscribers',
        'mc', 'large', 'sectioned', 'ciniki.subscriptions.main.subscribers');
    this.subscribers.data = {};
    this.subscribers.subscription_id = 0;
    this.subscribers.sections = {
        'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':3, 'hint':'customer name', 'noData':'No customers found', },
        'customers':{'label':'Subscribers', 'type':'simplegrid', 'num_cols':3,
            'headerValues':null,
            'headerValues':['Name', 'Email', ''],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text'],
            },
        };
    this.subscribers.sectionData = function(s) { return this.data[s]; }
    this.subscribers.liveSearchCb = function(s, i, value) {
        if( s == 'search' && value != '' ) {
            M.api.getJSONBgCb('ciniki.subscriptions.searchCustomers', {'tnid':M.curTenantID, 
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
            return d.customer.emails;
        } else if( j == 2 ) {
            if( d.customer.status == 10 ) {
                return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscribers.updateCustomer(event, event.target.innerHTML, '" + d.customer.customer_id + "'); return false;\">Unsubscribe</button>";
            } else {
                return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscribers.updateCustomer(event, event.target.innerHTML, '" + d.customer.customer_id + "'); return false;\">Subscribe</button>";
            }
        }
    };
    this.subscribers.liveSearchResultRowFn = function(s, f, i, j, d) { 
        var ctype = '';
        if( (M.curTenant.modules['ciniki.customers'].flags&0x02) > 0 
            && d.customer.member_status > 0 
            ) {
            ctype = ', \'member\':\'yes\'';
        }
        return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_subscriptions_main.subscribers.open();\',\'mc\',{\'customer_id\':' + d.customer.customer_id + ctype + '});';
        };
    this.subscribers.cellValue = function(s, i, j, d) {
        if( j == 0 ) {
            return d.customer.display_name;
        } else if( j == 1 ) {
            return d.customer.emails;
        } else if( j == 2 ) {
            if( d.customer.status == 10 ) {
                return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscribers.updateCustomer(event, event.target.innerHTML, '" + d.customer.customer_id + "'); return false;\">Unsubscribe</button>";
            } else {
                return "<button onclick=\"event.stopPropagation(); M.ciniki_subscriptions_main.subscribers.updateCustomer(event, event.target.innerHTML', '" + d.customer.customer_id + "'); return false;\">Subscribe</button>";
            }
        }
    };
    this.subscribers.rowFn = function(s, i, d) {
        var ctype = '';
        if( (M.curTenant.modules['ciniki.customers'].flags&0x02) > 0 
            && d.customer.member_status > 0 
            ) {
            ctype = ', \'member\':\'yes\'';
        }
        return 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_subscriptions_main.subscribers.open();\',\'mc\',{\'customer_id\':' + d.customer.customer_id + ctype + '});';
    };
    this.subscribers.updateCustomer = function(e, action, cid) {
        var status = 10;
        if( action == 'Unsubscribe' ) { status = 60; }
        M.api.getJSONCb('ciniki.subscriptions.updateSubscriber', 
            {'tnid':M.curTenantID, 'subscription_id':this.subscription_id, 
            'customer_id':cid, 'status':status, 'subscribers':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                if( status == 10 ) {
                    e.target.innerHTML = 'Unsubscribe';
                } else {
                    e.target.innerHTML = 'Subscribe';
                }
                var p = M.ciniki_subscriptions_main.subscribers;
                p.data.customers = rsp.subscribers;
                p.refreshSection('customers');
                p.show();
            });
    };
    this.subscribers.open = function(cb, sid) {
        if( sid != null ) { this.subscription_id = sid; }
        M.api.getJSONCb('ciniki.subscriptions.subscriptionSubscriberList', 
            {'tnid':M.curTenantID, 'subscription_id':this.subscription_id}, function(rsp) {
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
    this.subscribers.addClose('Back');

    //
    // The panel to display the subscriptions for a customer
    //
    this.customer = new M.panel('Customer Subscriptions',
        'ciniki_subscriptions_main', 'customer',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.subscriptions.main.customer');
    this.customer.data = {};
    this.customer.subscription_id = 0;
    this.customer.sections = {
        'customer_details':{'label':'Customer', 'aside':'yes', 'type':'simplegrid', 'num_cols':2, 
            'cellClasses':['label', ''],
//            'changeTxt':'View Customer',
//            'changeFn':'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_customers_reminders.reminder.open();\',\'mc\',{\'customer_id\':M.ciniki_customers_reminders.reminder.data.customer_id});',
            },
        'subscriptions':{'label':'Subscriptions', 'fields':{
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save subscriptions', 'fn':'M.ciniki_subscriptions_main.customer.save();'},
            }},
        };
    this.customer.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return (d.label == 'Email' ? M.linkEmail(d.value):d.value);
            }
        }
    }
    this.customer.open = function(cb, cid) {
        if( cid != null ) { this.customer_id = cid; }
        M.api.getJSONCb('ciniki.subscriptions.customerSubscriptionsGet', 
            {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_subscriptions_main.customer;
                p.data = rsp;
                p.sections.subscriptions.fields = {};
                for(var i in rsp.subscriptions) {
                    p.sections.subscriptions.fields['sub_' + rsp.subscriptions[i].id] = {'label':rsp.subscriptions[i].name,
                        'type':'toggle', 
                        'toggles':{'10':'Subscribed', '60':'Unsubscribed'},
                        };
                    p.data['sub_' + rsp.subscriptions[i].id] = rsp.subscriptions[i].status;
                }
                p.refresh();
                p.show(cb);
            });
    };
    this.customer.save = function() {
        var subs = '';
        var unsubs = '';
        if( this.data.subscriptions != null ) {
            for(i in this.data.subscriptions) {
                var fname = 'sub_' + this.data.subscriptions[i].id;
                var o = this.fieldValue('subscriptions', fname, this.sections.subscriptions.fields[fname]);
                var n = this.formValue(fname);
                if( o != n && n > 0 ) {
                    if( n == 10 ) {
                        subs += (subs != '' ? ',' : '') + this.data.subscriptions[i].id;
                    } else if( n == 60 ) {
                        unsubs += (unsubs != '' ? ',' : '') + this.data.subscriptions[i].id;
                    }
                }   
            }
        }
        if( subs != '' || unsubs != '' ) {
            M.api.getJSONCb('ciniki.subscriptions.customerSubscriptionsUpdate', 
                {'tnid':M.curTenantID, 'customer_id':this.customer_id, 'subs':subs, 'unsubs':unsubs}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_subscriptions_main.customer.close();
                });
        }
    }
    this.customer.addClose('Back');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        var slabel = 'Contact';
        var plabel = 'Contacts';
        if( M.modOn('ciniki.sapos') || M.modOn('ciniki.poma') || M.modOn('ciniki.products') ) {
            slabel = 'Customer';
            plabel = 'Customers';
        }
        this.subscription.sections._actions.list.addallcustomers.label = 'Add All ' + plabel;
        this.download.sections._fields.fields.callsign.label = slabel + ' Callsign';
        this.download.sections._fields.fields.display_name.label = slabel + ' Full Name';
        this.download.sections._fields.fields.prefix.label = slabel + ' Prefix';
        this.download.sections._fields.fields.first.label = slabel + ' First Name';
        this.download.sections._fields.fields.middle.label = slabel + ' Middle Name';
        this.download.sections._fields.fields.last.label = slabel + ' Last Name';
        this.download.sections._fields.fields.suffix.label = slabel + ' Suffix';

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_subscriptions_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.customer_id != null && args.customer_id > 0 ) {    
            this.customer.open(cb, args.customer_id);
        } else {
            this.menu.open(cb);
        }
    }
}
