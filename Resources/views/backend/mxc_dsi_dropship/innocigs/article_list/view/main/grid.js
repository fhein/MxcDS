//{block name="backend/article_list/view/main/grid" append}
Ext.define('Shopware.apps.MxcDropship.innocigs.ArticleList.view.main.Grid', {
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
        // let color = record.raw.mxcbc_dsi_ic_dropship;
        // let part1 = '<div style="width:16px;height:16px;';
        // let part2 = 'color:white;margin:0 auto;text-align:center;border-radius:3px;padding-top:0;></div>';
        //
        // if (color == null) return part1 + part2;
        // return part1 + 'background:' + color + ';' + part2;
    }
});
//{/block}
