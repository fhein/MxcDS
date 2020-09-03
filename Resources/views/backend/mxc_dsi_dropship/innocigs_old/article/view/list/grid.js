//{block name="backend/article_list/view/main/grid" append}
Ext.define('Shopware.apps.MxcDsiArticleList.view.main.Grid', {
    override: 'Shopware.apps.ArticleList.view.main.Grid',

    getColumns: function () {
        let me = this;
        let columns = me.callOverridden(arguments);

        Ext.Array.push(columns, {
            header: 'DSI',
            width: 30,
            renderer: me.isDropshipProduct
        });

        return columns;
    },

    isDropshipProduct: function(value, metaData, record) {
        return record.raw.mxcbc_dsi_ic_dropship;
    }
});
//{/block}
