Ext.define('Shopware.apps.MxcDropshipLog.controller.Log', {
  extend: 'Enlight.app.Controller',

  init: function() {
    let me = this;

    me.mainWindow = me.getView('list.Window').create({ }).show();
  },

});
