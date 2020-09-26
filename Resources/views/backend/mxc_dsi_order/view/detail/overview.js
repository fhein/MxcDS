//{block name="backend/order/view/detail/overview" append}
Ext.override(Shopware.apps.Order.view.detail.Overview, {

    initComponent: function() {

        let me = this;

        me.callParent(arguments);
        me.items.insert(0, me.createMxcDsiStatusPanel());
        return me.items;
    },

    createMxcDsiStatusPanel: function() {
        let me = this;
        me.mxcDsiStatusPanel = me.createMxcDsiStatusContainer();

        me.dcContainer = Ext.create('Ext.container.Container', {
            minWidth:250,
            layout: {
                type: 'hbox',
                align: 'stretch'
            },
            defaults: {
                margin: '0 0 10 0'
            },
            items: [
                me.mxcDsiStatusPanel
            ]
        });
        return me.dcContainer;
    },

    createMxcDsiStatusContainer: function() {
        let me = this;

        return Ext.create('Ext.panel.Panel', {
            title: 'maxence Dropship',
            bodyPadding: 2,
            flex: 1,
            items:
                {
                    xtype: 'container',
                    renderTpl: me.getMxcDsiStatusPanel()
                }

        });
    },

    getMxcDsiStatusPanel:function () {
        let me = this;
        let url = '{url controller=MxcDropship action=getDropshipStatusPanel}';
        console.log(url);

        return new Ext.XTemplate(
            '{literal}<tpl>{[this.MxcDsiStatusPanel()]}</tpl>{/literal}', {
                MxcDsiStatusPanel: function() {
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
    }
});
//{/block}