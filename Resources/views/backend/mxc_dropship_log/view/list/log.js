
Ext.define('Shopware.apps.MxcDropshipLog.view.list.Log', {
  extend: 'Shopware.grid.Panel',
  alias:  'widget.mxc-dsi-dropship-log-listing-grid',
  region: 'center',

  configure: function() {
    let me = this;
    return {
      detailWindow: 'Shopware.apps.MxcDsiImport.view.detail.Window',
      columns: {
        created: {
          header: 'Time',
          renderer: me.timeColumn,
          flex: 2
        },
        level: {
          header: 'Severity',
          align: 'left',
          renderer: me.levelColumn,
          flex: 1
        },
        orderNumber:    { header: 'Order',flex: 1 },
        product:        { header: 'Product', flex: 2 },
        quantity:       { header: 'Amount', flex: 1 },
        module:         { header: 'Module',flex: 1},
        message:        { header: 'Message', flex: 8}
      },
      addButton: false,
      editColumn: false,
    };
  },

  // translate numeric PSR-3 severity level - which we use in the backend - to readable severities
  //
  levelColumn: function(value, metadata, record) {
   switch (value) {
      case 0: return 'EMERGENCY';
      case 1: return 'ALERT';
      case 2: return 'CRITICAL';
      case 3: return 'ERROR';
      case 4: return 'WARNING';
      case 5: return 'NOTICE';
      case 6: return 'INFO';
      case 7: return 'DEBUG';
    }
  },

  // render date and time in ISO format, remove the 'T', the milliseconds and the trailing Z
  // and correct the time zone (in ExtJs4 milliseconds are lost in server data binding)
  //
  timeColumn:function (value, metaData, record) {
    if ( value === Ext.undefined || value === null) {
      return value;
    }
    let tzOffset = (new Date().getTimezoneOffset()) * 60000;
    let localISOTime = new Date(value.getTime() - tzOffset);
    return localISOTime.toISOString().replace('T',' ').split('.')[0];
  }
})
