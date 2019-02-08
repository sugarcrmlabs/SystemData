// Enrico Simonetti
// enricosimonetti.com
// 2018-10-26

({
    initialize: function(options)
    {
        this._super('initialize',[options]);
        this.context.on('button:save_button:click', this.confirmSubmitAction, this);
    },

    confirmSubmitAction: function()
    {
        app.alert.show('systemdata-confirmation', {
            level: 'confirmation',
            messages: app.lang.get('LBL_SYSTEMDATA_CONFIRM_IMPORT', this.module),
            onConfirm: _.bind(this.submitAction, this),
            onCancel: _.bind(function() {
                this.context.trigger('systemdata:response');
            }, this)
        });
    },

    submitAction: function()
    {
        var self = this;

        app.alert.dismiss('systemdata-wait');
        app.alert.show('systemdata-wait', {
            level: 'info',
            messages: app.lang.get('LBL_SYSTEMDATA_WAIT', this.module),
            autoClose: false
        });

        var sections = this.$el.find('input[name^=section]');
        var modules_string = '';
        $.each(sections, function(key, val){
            if (val.checked) {
                modules_string += 'modules[]=' + encodeURIComponent(val.value) + '&';
            }
        });

        var data = this.$el.find('#import_data').val();

        var url = app.api.buildURL('Administration/SystemData/import?' + modules_string);
        app.api.call('create', url, {'data': data}, {
            success: function (response) {
                self.outputMessages = response;
                self.render();
            },
            error: function() {
                self.context.trigger('systemdata:response');
                app.error.handleHttpError(error);
            },
            complete: function() {
                self.context.trigger('systemdata:response');
            }
        });
    },

    loadData: function(options)
    {
        var self = this;

        // render on completion
        this.loadSections(
            function() {
                self.render();
            }
        );
    },

    loadSections: function(callback)
    {
        var self = this;
        var url = app.api.buildURL('Administration/SystemData/sections/import');
        app.api.call('read', url, null, {
            success: function (response) {
                self.sections = response;
            },
            complete: function()
            {
                // on complete, call the callback
                callback();
            }
        });
    },

    _dispose: function()
    {
        this.context.off('button:save_button:click', null, this);
        this._super('_dispose');
    }
})
