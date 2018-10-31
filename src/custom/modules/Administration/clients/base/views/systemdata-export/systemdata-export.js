// Enrico Simonetti
// enricosimonetti.com
// 2018-10-26

({
    initialize: function(options)
    {
        this._super('initialize',[options]);
        this.context.on('button:save_button:click', this.submitAction, this);
    },

    submitAction: function()
    {
        var self = this;

        this.exportData = null;
        var sections = this.$el.find('input[name^=section]');
        var modules_string = '';
        $.each(sections, function(key, val){
            //console.log(val.value + ' ' + val.checked);
            if (val.checked) {
                modules_string += 'modules[]=' + encodeURIComponent(val.value) + '&';
            }
        });

        var url = app.api.buildURL('Administration/SystemData/export?' + modules_string);
        app.api.call('read', url, null, {
            success: function (response) {
                self.exportData = response;
                self.render();
            },
            error: function() {
                self.context.trigger('systemdata:response');
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
        var url = app.api.buildURL('Administration/SystemData/sections/export');
        app.api.call('read', url, null, {
            success: function (response) {
                //self.$el.find('#systemdata-export').text(response);
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
