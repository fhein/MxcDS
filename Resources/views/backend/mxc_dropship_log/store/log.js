Ext.define('Shopware.apps.MxcDropshipLog.store.Log', {
  extend:'Shopware.store.Listing',
  model: 'Shopware.apps.MxcDropshipLog.model.Log',

  configure: function() {
    return {
      controller: 'MxcDropshipLog'
    };
  }
});