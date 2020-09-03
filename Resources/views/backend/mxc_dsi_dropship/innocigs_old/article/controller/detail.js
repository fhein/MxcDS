//{block name="backend/article/controller/detail" append}
Ext.define('Shopware.apps.MxcDsiArticle.controller.Detail', {
    override: 'Shopware.apps.Article.controller.Detail',

    onSaveArticle: function(win, article, options) {
        var me = this,
            originalCallback = options.callback;

        var customCallbackMxcDsi = function(newArticle, success) {
            Ext.callback(originalCallback, this, arguments);

            var productNumber = me.getDetailForm().getForm().getFieldValues()['attribute[mxcbc_dsi_ic_productnumber]'];
            var active = me.getDetailForm().getForm().getFieldValues()['attribute[mxcbc_dsi_ic_active]'];
            var preferOwnStock = me.DetailForm().getForm().getFieldValues()['attribute[mxcbc_dsi_ic_preferownstock'];
            active = active ? 1 : 0;
            preferOwnStock = preferOwnStock ? 1 : 0;

            Ext.Ajax.request({
                method: 'POST',
                url: '{url controller=MxcDsiArticleInnocigs action=register}',
                params: {
                    articleId: newArticle.get('mainDetailId'),
                    productNumber: productNumber,
                    active: active,
                    preferOwnStock: preferOwnStock
                },
                success: function(responseData, request) {
                    var response = Ext.JSON.decode(responseData.responseText);
                    if (!response.success) {
                        Shopware.Notification.createGrowlMessage(response.info.title, response.info.message, 'MxcDropshipIntegrator');
                    } else {
                        me.getDetailForm().getForm().findField('attribute[mxcbc_dsi_ic_instock]').setValue(response.data.mxcbc_dsi_ic_instock);
                    }
                },
                failure: function(responseData, request) {
                    Shopware.Notification.createGrowlMessage('Fehler', 'InnoCigs-Werte konnten nicht gespeichert werden.', 'MxcDropshipIntegrator');
                    console.log(responseData);
                }
            });
        };

        if (!options.callback || options.callback.toString() !== customCallbackMxcDsi.toString()) {
            options.callback = customCallbackMxcDsi;
        }

        me.callParent([win, article, options]);
    }
});
//{/block}