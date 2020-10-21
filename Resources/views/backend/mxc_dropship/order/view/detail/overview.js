//{block name="backend/order/view/detail/overview" append}
Ext.override(Shopware.apps.Order.view.detail.Overview, {

    initComponent: function() {
        let me = this;

        me.callParent(arguments);
        me.items.insert(0, me.createMxcDropshipStatusPanel());
        return me.items;
    },

    createMxcDropshipStatusPanel: function() {
        let me = this;
        me.mxcDropshipStatusPanel = me.createMxcDropshipStatusContainer();

        me.mxcDropshipContainer = Ext.create('Ext.container.Container', {
            minWidth:250,
            layout: {
                type: 'hbox',
                align: 'stretch'
            },
            defaults: {
                margin: '0 0 10 0'
            },
            items: [
                me.mxcDropshipStatusPanel
            ]
        });
        return me.mxcDropshipContainer;
    },

    createMxcDropshipStatusContainer: function() {
        let me = this;

        return Ext.create('Ext.panel.Panel', {
            title: 'maxence Dropship - Warenkorb',
            bodyPadding: 2,
            flex: 1,
            items:
                {
                    xtype: 'container',
                    renderTpl: me.getMxcDropshipStatusPanel()
                }

        });
    },

    getMxcDropshipStatusPanel:function () {
        let me = this;
        let url = '{url controller=MxcDropship action=getDropshipStatusPanel}';

        return new Ext.XTemplate(
            '{literal}<tpl>{[this.mxcDropshipStatusPanel()]}</tpl>{/literal}', {
                mxcDropshipStatusPanel: function() {
                    let response = Ext.Ajax.request({
                        async: false,
                        url: url,
                        method: 'GET',
                        params: {
                            orderId: me.record.data.id
                        },
                        failure: function(response){
                            console.info('error on request: '+response.toString());
                        }
                    });

                    let object = Ext.decode(response.responseText);
                    return object.panel;
                }
            }
        );
    },

    createRightDetailElements: function () {
        let me = this;
        let items = me.callParent(arguments);
        items.push(me.createResetErrorButton())
        return items;
    },

    createResetErrorButton: function() {
        var me = this;
        let url = '{url controller=MxcDropship action=resetDropshipError}';
        debugger;
        return {
            disabled: me.record.raw.mxcbc_dsi_status < 90,
            action:'resetError',
            xtype: 'button',
            cls: 'primary',
            text: 'Dropship-Fehler zurücksetzen',
            margin: '10 0 0 10',
            handler: function () {
                let response = Ext.Ajax.request({
                    async: false,
                    url: url,
                    method: 'GET',
                    params: {
                        orderId: me.record.data.id
                    },
                    success: function(response) {
                        Ext.MessageBox.alert('maxence Dropship', 'Dropship-Fehler wurden zurückgesetzt. Setzen Sie den Bestellstatus auf Offen, um den Auftrag erneut zu versenden.');
                    },
                    failure: function(response){
                        Ext.MessageBox.alert('maxence Dropship - Fehler', 'Dropship-Fehler konnte nicht zurückgesetzt werden.');
                    }
                });
            }
        }
    },
});
//{/block}