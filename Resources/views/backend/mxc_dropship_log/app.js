Ext.define('Shopware.apps.MxcDropshipLog', {
  extend: 'Enlight.app.SubApplication',

  name:'Shopware.apps.MxcDropshipLog',

  loadPath: '{url action=load}',
  bulkLoad: true,

  controllers: [ 'Log' ],

  views: [
    'list.Log',
    'list.Window',
  ],

  models: [ 'Log' ],
  stores: [ 'Log' ],

  launch: function() {
    return this.getController('Log').mainWindow;
  }
});