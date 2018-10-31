({
    initialize: function(options)
    {
        this._super('initialize', [options]);
        this.context.on('button:cancel_button:click', this.cancelAction, this);
        this.context.on('button:save_button:click', this.disableSaveButton, this);
        this.context.on('systemdata:response', this.enableSaveButton, this);
    },

    enableSaveButton: function()
    {
        app.alert.dismiss('systemdata-wait');
        this.getField('save_button').setDisabled(false);
    },

    disableSaveButton: function()
    {
        app.alert.dismiss('systemdata-wait');
        app.alert.show('systemdata-wait', {
            level: 'info',
            messages: app.lang.get('LBL_SYSTEMDATA_WAIT', this.module),
            autoClose: false
        });

        this.getField('save_button').setDisabled(true);
    },

    cancelAction: function()
    {
        // back to admin
        app.router.navigate("#Administration", {trigger:true});
    },

    _dispose: function()
    {
        this.context.off('button:cancel_button:click', null, this);
        this.context.off('button:save_button:click', null, this);
        this.context.off('systemdata:response', null, this);
        this._super('_dispose');
    }
});
