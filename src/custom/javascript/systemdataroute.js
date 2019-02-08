(function(app) {
    app.events.on('router:init', function() {
        app.router.route('SystemData/export', 'systemdata-export', function() {
            if (app.acl.hasAccess('admin', 'Administration')) {
                app.controller.loadView({
                    module: 'Administration',
                    layout: 'systemdata-export',
                    create: true
                });
            } else {
                app.alert.show('error-systemdata-access', {
                    level: 'error',
                    messages: 'EXCEPTION_NOT_AUTHORIZED',
                    autoClose: true
                });
                app.router.navigate('', {trigger: true});
            }
        });
        app.router.route('SystemData/import', 'systemdata-import', function() {
            if (app.acl.hasAccess('admin', 'Administration')) {
                app.controller.loadView({
                    module: 'Administration',
                    layout: 'systemdata-import',
                    create: true
                });
            } else {
                app.alert.show('error-systemdata-access', {
                    level: 'error',
                    messages: 'EXCEPTION_NOT_AUTHORIZED',
                    autoClose: true
                });
                app.router.navigate('', {trigger: true});
            }
        });
    });
})(SUGAR.App);
