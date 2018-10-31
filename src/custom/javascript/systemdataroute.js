(function(app) {
    app.events.on('router:init', function() {
        app.router.route('SystemData/export', 'systemdata-export', function() {
            app.controller.loadView({
                module: 'Administration',
                layout: 'systemdata-export',
                create: true
            });
        });
        app.router.route('SystemData/import', 'systemdata-import', function() {
            app.controller.loadView({
                module: 'Administration',
                layout: 'systemdata-import',
                create: true
            });
        });
    });
})(SUGAR.App);
