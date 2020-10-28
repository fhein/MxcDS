<?php
return [
    'sMxcDsiOrder' => [
        'name' => 'sMxcDsiOrder',
        'type' => 1,
        'is_html' => true,
        'from_mail' => '{config name=mail}',
        'from_name' => '{config name=shopName}',
        'subject' => 'Eine neue Bestellung ist eingegangen!',
        'content_text' => '',
        'content_html' => '<!DOCTYPE HTML>
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <!-- Define Charset -->
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
                <!-- Responsive Meta Tag -->
                <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;"/>
                <style type="text/css">
                    body{
                    width: 100%;
                    background-color: #ffffff;
                    margin:0;
                    padding:0;
                    -webkit-font-smoothing: antialiased;
                    }
                    p,h1,h2,h3,h4{
                    margin-top:0;
                    margin-bottom:0;
                    padding-top:0;
                    padding-bottom:0;
                    }
                    span.preheader{
                    display: none; font-size: 1px;}
                    html{
                    width: 100%;
                    }
                    table{
                    font-size: 14px;
                    border: 0;
                    }
                    td{
                    color: #808080;
                    }
                    td.main-header{
                    color: #5d6775;
                    }
                    /* ----------- responsivity ----------- */
                    @media only screen and (max-width: 640px){
                    /*------ top header ------ */
                    .header-bg{
                    width: 440px !important; height: auto !important;}
                    .rounded-edg-bg{
                    width: 420px !important; height: 5px !important;}
                    .main-header{
                    line-height: 28px !important;}
                    .main-subheader{
                    line-height: 28px !important;}
                    /*--------logo-----------*/
                    .logo{
                    width: 400px !important;}
                    /*----- main image -------*/
                    .main-image{
                    width: 400px !important; height: auto !important;}
                    .main-text-container{
                    width: 340px !important; height: auto !important;}
                    /*-------- container --------*/
                    .container600{
                    width: 440px !important;}
                    .container580{
                    width: 420px !important;}
                    .container560{
                    width: 400px !important;}
                    .container540{
                    width: 380px !important;}
                    .main-content{
                    width: 418px !important;}
                    /*-------- secions ----------*/
                    .section-item{
                    width: 380px !important;}
                    .section-img{
                    width: 380px !important; height: auto !important;}
                    .order--article-container{
                    width: 250px !important;float:none;margin: 0 auto;}
                    .article--content{
                    margin-left:30px;}
                    .order--article-title{
                    text-align: center;}
                    }
                    @media only screen and (max-width: 479px){
                    /*------ top header ------ */
                    .header-bg{
                    width: 280px !important; height: auto !important;}
                    .rounded-edg-bg{
                    width: 260px !important; height: 5px !important;}
                    .main-header{
                    font-size: 24px !important; line-height: 28px !important;}
                    .main-subheader{
                    line-height: 28px !important;}
                    /*--------logo-----------*/
                    .logo{
                    width: 240px !important;}
                    /*----- main image -------*/
                    .main-image{
                    width: 240px !important; height: auto !important;}
                    .main-text-container{
                    width: 180px !important;}
                    /*-------- container --------*/
                    .container600{
                    width: 280px !important;}
                    .container580{
                    width: 260px !important;}
                    .container560{
                    width: 240px !important;}
                    .container540{
                    width: 220px !important;}
                    .main-content{
                    width: 258px !important;}
                    /*-------- secions ----------*/
                    .section-item{
                    width: 220px !important;}
                    .section-img{
                    width: 220px !important; height: auto !important;}
                    .section-title{
                    line-height: 28px !important; font-size: 16px !important;}
                    .order--article-container{
                    width: 180px !important;float:none;margin: 0 auto;}
                    .article--content{
                    margin-left:0;}
                    .article--text-left{
                    width: 60px;}
                    .article--text-right{
                    width: 120px;}
                    .article--text-inner{
                    float:right;}
                    /*-------- footer ----------*/
                    .footer{
                    width: 280px !important;}
                    }
                </style>
            </head>
            <body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
                <table border="0" width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff">
                    <tr>
                        <td align="center">
                            <table border="0" align="center" width="600" cellpadding="0" cellspacing="0" bgcolor="ffffff" class="container600">
                                <tr>
                                    <td height="40"/>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <table border="0" width="560" align="center" cellpadding="0" cellspacing="0" class="container560">
                                            <tr>
                                                <td>
                                                    <table border="0" align="center" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td align="center">
                                                                <a href="{$sShopURL}" style="display: block; border-style: none !important; border: 0 !important;">
                                                                    <img editable="true" mc:edit="logo" height="150" width="inherit" border="0" style="display: block;" src="https://vapee.de/media/image/f1/b3/00/vapee-logo-500x480.png" alt="logo"/>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table border="0" align="center" width="600" cellpadding="0" cellspacing="0" bgcolor="ffffff" class="container600">
                                <tr>
                                    <td>
                                        <table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
                                            <tr>
                                                <td height="40"/>
                                            </tr>
                                            <tr>
                                                <td align="center">
                                                    <img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="40"/>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table border="0" align="center" width="600" cellpadding="0" cellspacing="0" bgcolor="ffffff" class="container600">
                                <tr>
                                    <td>
                                        <table border="0" align="center" width="580" cellpadding="0" cellspacing="0" bgcolor="ffffff" class="container580">
                                            <tr>
                                                <td>
                                                    <table border="0" align="center" width="578" cellpadding="0" cellspacing="0" bgcolor="ffffff" class="main-content">
                                                        <repeater>
                                                            <layout label="main-section">
                                                                <tr>
                                                                    <td>
                                                                        <table border="0" align="center" width="560" cellpadding="0" cellspacing="0" class="container560">
                                                                            <tr>
                                                                                <td align="center" mc:edit="title1" style="font-size: 28px; font-family: Helvetica, Arial, sans-serif;" class="main-header">
                                                                                    <multiline>
                                                                                        Neue Bestellung
                                                                                    </multiline>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td height="20"/>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center" mc:edit="subtitle1" style="font-size: 15px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
                                                                                    <multiline>
                                                                                        Liebes Team von {config name=shopName},
                                                                                    </multiline>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td height="20"/>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center" mc:edit="subtitle1" style="font-size: 15px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
                                                                                    <multiline>
                                                                                        am {$sOrderDay|date:"DATE_MEDIUM"} um {$sOrderTime} ist eine neue Bestellung eingegangen. Bestellnummer: {$sOrderNumber}
                                                                                    </multiline>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center" mc:edit="subtitle1" style="font-size: 15px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
                                                                                    <multiline>
                                                                                        Zahlungsart: {$additional.payment.description} {if $additional.payment.name == "prepayment"}{/if}
                                                                                    </multiline>
                                                                                </td>
                                                                            </tr>
                                                                            {if $mxcbc_dsi.orderType gt 0}
                                                                            <tr>
                                                                                <td height="20"/>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center" mc:edit="subtitle1" style="font-size: 15px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
                                                                                    <multiline>
                                                                                        {if $mxcbc_dsi.orderType == 1}
                                                                                            Die Bestellung enthält ausschließlich Produkte aus dem Lager von {config name=shopName}.
                                                                                        {elseif $mxcbc_dsi.orderType == 2}
                                                                                            Die Bestellung enthält ausschließlich Dropship-Produkte.
                                                                                        {elseif $mxcbc_dsi.orderType == 3}
                                                                                            Die Bestellung enthält sowohl Dropship-Produkte als auch Produkte aus dem Lager von {config name=shopName}.
                                                                                        {/if}
                                                                                    </multiline>
                                                                                </td>
                                                                            </tr>
                                                                            {if $mxcbc_dsi.orderType gt 1}
                                                                            <tr>
                                                                                <td height="20"/>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center" mc:edit="subtitle1" style="font-size: 15px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
                                                                                    <multiline>
                                                                                        {if $additional.payment.name == "prepayment"}
                                                                                            Da Vorkasse als Zahlungsart gewählt ist, wird der Dropship-Auftrag erst dann automatisch erteilt, wenn der Bezahlstatus auf \'vollständig bezahlt\' gesetzt wurde. Bitte überwachen Sie den Zahlungseingang.
                                                                                        {else}
                                                                                            Der Dropship-Auftrag wird automatisch erteilt, sobald der Shop den Zahlungseingang feststellt.
                                                                                        {/if}
                                                                            
                                                                                    </multiline>
                                                                                </td>
                                                                            </tr>
                                                                            {/if}
                                                                            {/if}
                                                                            <tr>
                                                                                <td>
                                                                                    <table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
                                                                                        <tr>
                                                                                            <td height="40"/>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="center">
                                                                                                <img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td height="40"/>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center" mc:edit="title1" style="font-size: 28px; font-family: Helvetica, Arial, sans-serif;" class="main-header">
                                                                                    <multiline>
                                                                                        Informationen zur Bestellung
                                                                                    </multiline>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
                                                                                        <tr>
                                                                                            <td height="40"/>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="center">
                                                                                                <img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td height="40"/>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            {foreach item=details key=position from=$sOrderDetails}
                                                                            <tr>
                                                                                <td align="center">
                                                                                    <table border="0" width="80%" align="center" cellpadding="0" cellspacing="0" class="container540">
                                                                                        <tr>
                                                                                            <td>
                                                                                                <table border="0" width="150" align="left" cellpadding="0" cellspacing="0" class="section-item">
                                                                                                    <tr>
                                                                                                        <td align="center">
                                                                                                            {if $details.image.src.0 && $details.modus != 2}
                                                                                                            <a href="{$sShopURL}/{$details.additional_details.linkDetails}" style=" border-style: none !important; border: 0 !important;">
                                                                                                                <img editable="true" mc:edit="image1" src="{$details.image.src.0}" style="display: block;max-width: 150px;" border="0" alt="{$details.articlename}" class="article-img"/>
                                                                                                            </a>
                                                                                                            {else} {/if}
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                </table>
                                                                                                <table border="0" width="50%" align="right" cellpadding="0" cellspacing="0" class="section-item order--article-container">
                                                                                                    <tr>
                                                                                                        <td height="10"/>
                                                                                                    </tr>
                                                                                                    <tr>
                                                                                                        <td mc:edit="title3" style="color: #58656e; font-size: 17px; font-weight: bold; font-family: Helvetica, Arial, sans-serif;" class="section-title order--article-title">
                                                                                                            {$details.articlename|wordwrap:80|indent:4}
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                    <tr>
                                                                                                        <td height="15"/>
                                                                                                    </tr>
                                                                                                    <tr style="display:block;" class="article--content">
                                                                                                        <td mc:edit="subtitle3" width="120" style="color: #808080; font-size: 13px; font-family: Helvetica, Arial, sans-serif; line-height: 24px;" class="article--text-left">
                                                                                                            <multiline>
                                                                                                                Position:<br>
                                                                                                                    Artikel Nr.:<br>
                                                                                                                        Menge:<br>
                                                                                                                            Preis:<br>
                                                                                                                                Summe:
                                                                                                                                {if $details.additional_details.articleID}<br>Lager:{/if}
                                                                                                                            </multilane>
                                                                                                                        </td>
                                                                                                                        <td mc:edit="subtitle3" style="color: #808080; font-size: 13px; font-family: Helvetica, Arial, sans-serif; line-height: 24px;" class="article--text-right">
                                                                                                                            <multiline class="article--text-inner">
                                                                                                                                {$position+1|fill:4}<br>
                                                                                                                                    {$details.ordernumber|fill:20}<br>
                                                                                                                                        {$details.quantity|fill:6}<br>
                                                                                                                                            {$details.price} {$sCurrency}<br>
                                                                                                                                                {$details.amount} {$sCurrency}
                                                                                                                                                {if $details.additional_details.articleID}<br><strong>{$details.additional_details.mxcbc_dsi_supplier}<strong>{/if}
                                                                                                                                            </multilane>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td height="20"/>
                                                                                                                                    </tr>
                                                                                                                                </table>
                                                                                                                            </td>
                                                                                                                        </tr>
                                                                                                                    </table>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td>
                                                                                                                    <table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
                                                                                                                        <tr>
                                                                                                                            <td height="20"/>
                                                                                                                        </tr>
                                                                                                                        <tr>
                                                                                                                            <td align="center">
                                                                                                                                <img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
                                                                                                                            </td>
                                                                                                                        </tr>
                                                                                                                        <tr>
                                                                                                                            <td height="20"/>
                                                                                                                        </tr>
                                                                                                                    </table>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            {/foreach}
                                                                                                            <tr>
                                                                                                                <td height="20"/>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td align="center">
                                                                                                                    <table border="0" width="540" align="center" cellpadding="0" cellspacing="0" class="container540">
                                                                                                                        <tr>
                                                                                                                            <td>
                                                                                                                                <table border="0" width="29.5%" align="left" cellpadding="0" cellspacing="0" class="section-item">
                                                                                                                                    <tr>
                                                                                                                                        <td align="center" mc:edit="title4" style="color: #58656e; font-size: 16px; font-family: Helvetica, Arial, sans-serif;">
                                                                                                                                            <multiline>
                                                                                                                                                Versandkosten
                                                                                                                                            </multiline>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td height="15"/>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td align="center" mc:edit="subtitle3" style="color: #808080; font-size: 13px; font-family: Helvetica, Arial, sans-serif; line-height: 24px;">
                                                                                                                                            <multiline>
                                                                                                                                                {$sShippingCosts}
                                                                                                                                            </multiline>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                </table>
                                                                                                                                <table border="0" width="2.5%" align="left" cellpadding="0" cellspacing="0">
                                                                                                                                    <tr>
                                                                                                                                        <td width="15" height="40"/>
                                                                                                                                    </tr>
                                                                                                                                </table>
                                                                                                                                <table border="0" width="29.5%" align="left" cellpadding="0" cellspacing="0" class="section-item">
                                                                                                                                    <tr>
                                                                                                                                        <td align="center" mc:edit="title5" style="color: #58656e; font-size: 16px; font-family: Helvetica, Arial, sans-serif;">
                                                                                                                                            <multiline>
                                                                                                                                                Gesamtkosten Netto
                                                                                                                                            </multiline>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td height="15"/>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td align="center" mc:edit="subtitle4" style="color: #808080; font-size: 13px; font-family: Helvetica, Arial, sans-serif; line-height: 24px;">
                                                                                                                                            <multiline>
                                                                                                                                                {$sAmountNet}
                                                                                                                                            </multiline>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                </table>
                                                                                                                                {if !$sNet}
                                                                                                                                <table border="0" width="2.5%" align="left" cellpadding="0" cellspacing="0">
                                                                                                                                    <tr>
                                                                                                                                        <td width="15" height="40"/>
                                                                                                                                    </tr>
                                                                                                                                </table>
                                                                                                                                <table border="0" width="29.5%" align="left" cellpadding="0" cellspacing="0" class="section-item">
                                                                                                                                    <tr>
                                                                                                                                        <td align="center" mc:edit="title6" style="color: #58656e; font-size: 16px; font-family: Helvetica, Arial, sans-serif;">
                                                                                                                                            <multiline>
                                                                                                                                                Gesamtkosten Brutto
                                                                                                                                            </multiline>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td height="15"/>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td align="center" mc:edit="subtitle5" style="color: #808080; font-size: 13px; font-family: Helvetica, Arial, sans-serif; line-height: 24px;">
                                                                                                                                            <multiline>
                                                                                                                                                {$sAmount}
                                                                                                                                            </multiline>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                </table>
                                                                                                                                {/if}
                                                                                                                            </td>
                                                                                                                        </tr>
                                                                                                                    </table>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td>
                                                                                                                    <table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
                                                                                                                        <tr>
                                                                                                                            <td height="40"/>
                                                                                                                        </tr>
                                                                                                                        <tr>
                                                                                                                            <td align="center">
                                                                                                                                <img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
                                                                                                                            </td>
                                                                                                                        </tr>
                                                                                                                        <tr>
                                                                                                                            <td height="40"/>
                                                                                                                        </tr>
                                                                                                                    </table>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td height="20"/>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td align="center">
                                                                                                                    <table border="0" width="540" align="center" cellpadding="0" cellspacing="0" class="container540">
                                                                                                                        <tr>
                                                                                                                            <td>
                                                                                                                                <table border="0" width="46%" align="left" cellpadding="0" cellspacing="0" class="section-item">
                                                                                                                                    <tr>
                                                                                                                                        <td align="center" mc:edit="title3" style="color: #58656e; font-size: 16px; font-family: Helvetica, Arial, sans-serif;">
                                                                                                                                            <multiline>
                                                                                                                                                Rechnungsadresse
                                                                                                                                            </multiline>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td height="20"/>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td align="center" mc:edit="subtitle3" style="color: #808080; font-size: 14px; font-family: Helvetica, Arial, sans-serif; line-height: 25px;">
                                                                                                                                            <multiline>
                                                                                                                                                {if $billingaddress.company}
                                                                                                                                                {$billingaddress.company}<br/>
                                                                                                                                                {/if}
                                                                                                                                                {$billingaddress.firstname} {$billingaddress.lastname}<br/>
                                                                                                                                                {$billingaddress.street}<br/>
                                                                                                                                                {$billingaddress.zipcode} {$billingaddress.city}<br/>
                                                                                                                                                {if $billingaddress.phone}
                                                                                                                                                {$billingaddress.phone}<br/>
                                                                                                                                                {else}{/if}
                                                                                                                                                {$additional.country.countryname}<br/>
                                                                                                                                            </multiline>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                </table>
                                                                                                                                <table border="0" width="3.5%" align="left" cellpadding="0" cellspacing="0">
                                                                                                                                    <tr>
                                                                                                                                        <td width="20" height="40"/>
                                                                                                                                    </tr>
                                                                                                                                </table>
                                                                                                                                <table border="0" width="46%" align="left" cellpadding="0" cellspacing="0" class="section-item">
                                                                                                                                    <tr>
                                                                                                                                        <td align="center" mc:edit="title4" style="color: #58656e; font-size: 16px; font-family: Helvetica, Arial, sans-serif;">
                                                                                                                                            <multiline>
                                                                                                                                                Lieferadresse
                                                                                                                                            </multiline>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td height="20"/>
                                                                                                                                    </tr>
                                                                                                                                    <tr>
                                                                                                                                        <td align="center" mc:edit="subtitle4" style="color: #808080; font-size: 14px; font-family: Helvetica, Arial, sans-serif; line-height: 25px;">
                                                                                                                                            <multiline>
                                                                                                                                                {if $shippingaddress.company}
                                                                                                                                                {$shippingaddress.company}<br/>
                                                                                                                                                {/if}
                                                                                                                                                {$shippingaddress.firstname} {$shippingaddress.lastname}<br/>
                                                                                                                                                {$shippingaddress.street}<br/>
                                                                                                                                                {$shippingaddress.zipcode} {$shippingaddress.city}<br/>
                                                                                                                                                {$additional.countryShipping.countryname}<br/>
                                                                                                                                            </multiline>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                </table>
                                                                                                                            </td>
                                                                                                                        </tr>
                                                                                                                    </table>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            </table>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                </repeater>
                                                                                                <tr>
                                                                                                    <td>
                                                                                                        <table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
                                                                                                            <tr>
                                                                                                                <td height="40"/>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td align="center">
                                                                                                                    <img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td height="40"/>
                                                                                                            </tr>
                                                                                                        </table>
                                                                                                    </td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                    <td align="center" mc:edit="subtitle1" style="font-size: 10px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
                                                                                                    <multiline>
                                                                                                        &copy; 2019 - {$smarty.now|date_format:"%Y"} <a href="http://www.maxence.de/">maxence	business consulting gmbh</a><br/>All rights reserved.
                                                                                                    </multiline>
                                                                                                    </td>
                                                                                                    </tr>
                                                                                                    <tr>
                                                                                                        <td height="30"/>
                                                                                                    </tr>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="40"/>
                                                                </tr>
                                                            </table>
                                                        </body>
                                                    </html>',
  ],
  'sMxcDsiDropshipStatus' => [
      'name' => 'sMxcDsiDropshipStatus',
      'type' => 1,
      'is_html' => true,
      'from_mail' => '{config name=mail}',
      'from_name' => '{config name=shopName}',
      'subject' => '{config name=shopName}',
      'content_text' => '',
      'content_html' => '<!DOCTYPE HTML>
	<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<!-- Define Charset -->
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
			<!-- Responsive Meta Tag -->
			<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;"/>
			<style type="text/css">body{	width: 100%;	background-color: #ffffff;	margin:0;	padding:0;	-webkit-font-smoothing: antialiased;	}	p,h1,h2,h3,h4{	margin-top:0;	margin-bottom:0;	padding-top:0;	padding-bottom:0;	}	span.preheader{	display: none; font-size: 1px;}	html{	width: 100%;	}	table{	font-size: 14px;	border: 0;	}	td{	color: #808080;	}	td.main-header{	color: #5d6775;	}	/* ----------- responsivity ----------- */	@media only screen and (max-width: 640px){	/*------ top header ------ */	.header-bg{	width: 440px !important; height: auto !important;}	.rounded-edg-bg{	width: 420px !important; height: 5px !important;}	.main-header{	line-height: 28px !important;}	.main-subheader{	line-height: 28px !important;}	/*--------logo-----------*/	.logo{	width: 400px !important;}	/*----- main image -------*/	.main-image{	width: 400px !important; height: auto !important;}	.main-text-container{	width: 340px !important; height: auto !important;}	/*-------- container --------*/	.container600{	width: 440px !important;}	.container580{	width: 420px !important;}	.container560{	width: 400px !important;}	.container540{	width: 380px !important;}	.main-content{	width: 418px !important;}	/*-------- secions ----------*/	.section-item{	width: 380px !important;}	.section-img{	width: 380px !important; height: auto !important;}	.order--article-container{	width: 250px !important;float:none;margin: 0 auto;}	.article--content{	margin-left:30px;}	.order--article-title{	text-align: center;}	}	@media only screen and (max-width: 479px){	/*------ top header ------ */	.header-bg{	width: 280px !important; height: auto !important;}	.rounded-edg-bg{	width: 260px !important; height: 5px !important;}	.main-header{	font-size: 24px !important; line-height: 28px !important;}	.main-subheader{	line-height: 28px !important;}	/*--------logo-----------*/	.logo{	width: 240px !important;}	/*----- main image -------*/	.main-image{	width: 240px !important; height: auto !important;}	.main-text-container{	width: 180px !important;}	/*-------- container --------*/	.container600{	width: 280px !important;}	.container580{	width: 260px !important;}	.container560{	width: 240px !important;}	.container540{	width: 220px !important;}	.main-content{	width: 258px !important;}	/*-------- secions ----------*/	.section-item{	width: 220px !important;}	.section-img{	width: 220px !important; height: auto !important;}	.section-title{	line-height: 28px !important; font-size: 16px !important;}	.order--article-container{	width: 180px !important;float:none;margin: 0 auto;}	.article--content{	margin-left:0;}	.article--text-left{	width: 60px;}	.article--text-right{	width: 120px;}	.article--text-inner{	float:right;}	/*-------- footer ----------*/	.footer{	width: 280px !important;}	}</style>
		</head>
		<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
			<table border="0" width="100%" cellpadding="0" cellspacing="0" bgcolor="#ffffff">
				<tr>
					<td align="center">
						<table border="0" align="center" width="600" cellpadding="0" cellspacing="0" bgcolor="ffffff" class="container600">
							<tr>
								<td height="40"/>
							</tr>
							<tr>
								<td align="center">
									<table border="0" width="560" align="center" cellpadding="0" cellspacing="0" class="container560">
										<tr>
											<td>
												<table border="0" align="center" cellpadding="0" cellspacing="0">
													<tr>
														<td align="center">
															<a href="{$sShopURL}" style="display: block; border-style: none !important; border: 0 !important;">
																<img editable="true" mc:edit="logo" height="150" width="inherit" border="0" style="display: block;" src="https://vapee.de/media/image/f1/b3/00/vapee-logo-500x480.png" alt="logo"/>
															</a>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" align="center" width="600" cellpadding="0" cellspacing="0" bgcolor="ffffff" class="container600">
							<tr>
								<td>
									<table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
										<tr>
											<td height="40"/>
										</tr>
										<tr>
											<td align="center">
												<img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
											</td>
										</tr>
										<tr>
											<td height="40"/>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" align="center" width="600" cellpadding="0" cellspacing="0" bgcolor="ffffff" class="container600">
							<tr>
								<td>
									<table border="0" align="center" width="580" cellpadding="0" cellspacing="0" bgcolor="ffffff" class="container580">
										<tr>
											<td>
												<table border="0" align="center" width="578" cellpadding="0" cellspacing="0" bgcolor="ffffff" class="main-content">
													<repeater>
														<layout label="main-section">
															<tr>
																<td>
																	<table border="0" align="center" width="560" cellpadding="0" cellspacing="0" class="container560">
																		<tr>
																			<td align="center" mc:edit="title1" style="font-size: 28px; font-family: Helvetica, Arial, sans-serif;" class="main-header">
																				<multiline>{$mailTitle}</multiline>
																			</td>
																		</tr>
																		<tr>
																			<td height="20"/>
																		</tr>
																		<tr>
																			<td align="center" mc:edit="subtitle1" style="font-size: 15px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
																				<multiline>Liebes Team von {config name=shopName},</multiline>
																			</td>
																		</tr>
																		<tr>
																			<td height="20"/>
																		</tr>
																		<tr>
																			<td align="center" mc:edit="subtitle1" style="font-size: 15px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
																				<multiline>{$mailBody}</multiline>
																			</td>
																		</tr>
																		<tr>
																			<td>
																				<table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
																					<tr>
																						<td height="40"/>
																					</tr>
																					<tr>
																						<td align="center">
																							<img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
																						</td>
																					</tr>
																					<tr>
																						<td height="40"/>
																					</tr>
																				</table>
																			</td>
																		</tr>{if $shippingaddress}<tr>
																			<td align="center" mc:edit="title1" style="font-size: 28px; font-family: Helvetica, Arial, sans-serif;" class="main-header">
																				<multiline>Lieferadresse</multiline>
																			</td>
																		</tr>
																		<tr>
																			<td height="20"/>
																		</tr>
																		<tr>
																			<td align="center" mc:edit="subtitle1" style="font-size: 15px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
																				<multiline>{if $shippingaddress.company}	{$shippingaddress.company}<br/>{/if}	{if $shippingaddress.department}	{$shippingaddress.department}<br/>{/if}	{$shippingaddress.firstname} {$shippingaddress.lastname}<br/>{$shippingaddress.street}<br/>{$shippingaddress.zipcode} {$shippingaddress.city}<br/>{$additional.countryShipping.countryname}<br/>
																				</multiline>
																			</td>
																		</tr>
																		<tr>
																			<td>
																				<table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
																					<tr>
																						<td height="40"/>
																					</tr>
																					<tr>
																						<td align="center">
																							<img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
																						</td>
																					</tr>
																					<tr>
																						<td height="40"/>
																					</tr>
																				</table>
																			</td>
																		</tr>{/if}	
																		{if $errors}
																		<tr>
																			<td align="center" mc:edit="title1" style="font-size: 28px; font-family: Helvetica, Arial, sans-serif;" class="main-header">
																				<multiline>Fehler</multiline>
																			</td>
																		</tr>{foreach item=error key=position from=$errors}<tr>
																			<td height="20"/>
																		</tr>
																		<tr>
																			<td align="center" mc:edit="subtitle1" style="font-size: 15px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
																				<multiline>{$error.message}</multiline>
																			</td>
																		</tr>{/foreach}<tr>
																			<td>
																				<table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
																					<tr>
																						<td height="40"/>
																					</tr>
																					<tr>
																						<td align="center">
																							<img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
																						</td>
																					</tr>
																					<tr>
																						<td height="40"/>
																					</tr>
																				</table>
																			</td>
																		</tr>
																		{/if}
																		{if $trackings}
																		<tr>
																			<td align="center" mc:edit="title1" style="font-size: 28px; font-family: Helvetica, Arial, sans-serif;" class="main-header">
																				<multiline>Tracking Informationen</multiline>
																			</td>
																		</tr>{foreach item=info key=position from=$trackings}<tr>
																			<td height="20"/>
																		</tr>
																		<tr>
																			<td align="center" mc:edit="subtitle1" style="font-size: 15px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
																				<multiline>
																					Versand mit {$info.carrier}: {$info.trackingLink}
																				</multiline>
																			</td>
																		</tr>{/foreach}<tr>
																			<td>
																				<table border="0" width="240" align="center" cellpadding="0" cellspacing="0" class="container">
																					<tr>
																						<td height="40"/>
																					</tr>
																					<tr>
																						<td align="center">
																							<img src="https://www.vapee.de/custom/plugins/MxcDropship/Resources/images/divider.png" editable="true" width="240" height="4" style="display: block;" alt="divider"/>
																						</td>
																					</tr>
																					<tr>
																						<td height="40"/>
																					</tr>
																				</table>
																			</td>
																		</tr>
																		{/if}
																	</table>
																</td>
															</tr>
														</repeater>
														<tr>
															<td align="center" mc:edit="subtitle1" style="font-size: 10px; font-family: Helvetica, Arial, sans-serif;" class="main-subheader">
																	<multiline>
																		&copy; 2019 - {$smarty.now|date_format:"%Y"} <a href="http://www.maxence.de/">maxence business consulting gmbh</a><br/>All rights reserved.
																		</multiline>
																</td>
															</tr>
															<tr>
																<td height="30"/>
															</tr>
														</table>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td height="40"/>
						</tr>
					</table>
				</body>
			</html>',
  ],
];