Ext.define('Shopware.apps.MxcDropshipLog.view.list.Window', {
  extend: 'Shopware.window.Listing',
  alias: 'widget.mxc-dsi-dropship-log-list-window',
  height: 450,
  width: 1200,
  title : 'maxence Dropship Log',

  configure: function() {
    return {
      listingGrid: 'Shopware.apps.MxcDropshipLog.view.list.Log',
      listingStore: 'Shopware.apps.MxcDropshipLog.store.Log',
    };
  }
});