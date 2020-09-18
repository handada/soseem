/**
 * 비동기식 데이터 - 마이쇼핑 > 주문 카운트 (주문 건수 / CS건수 / 예전주문)
 */
CAPP_ASYNC_METHODS.aDatasetList.push('OrderHistoryCount');
CAPP_ASYNC_METHODS.OrderHistoryCount = {
    __sTotalOrder: null,
    __sTotalOrderCs: null,
    __sTotalOrderOld: null,

    __$target: $('#ec_myshop_total_orders'),
    __$target2: $('#ec_myshop_total_orders_cs'),
    __$target3: $('#ec_myshop_total_orders_old'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }
        }

        return false;
    },

    setData: function(aData)
    {
        this.__sTotalOrder = aData['total_orders'];
        this.__sTotalOrderCs = aData['total_orders_cs'];
        this.__sTotalOrderOld = aData['total_orders_old'];

    },

    execute: function()
    {
        this.__$target.html(this.__sTotalOrder);
        this.__$target2.html(this.__sTotalOrderCs);
        this.__$target3.html(this.__sTotalOrderOld);
    }
};
/**
 * 주문조회 > 주문내역조회 및 취소/교환/반품내역 등 탭(OrderHistoryTab) 갯수 비동기호출
 */
CAPP_ASYNC_METHODS.aDatasetList.push('OrderHistoryTab');
CAPP_ASYNC_METHODS.OrderHistoryTab = {
    __$targetTotalOrders: $('#xans_myshop_total_orders'),
    __$targetTotalOrdersCs: $('#xans_myshop_total_orders_cs'),
    __$targetTotalOrdersPast: $('#xans_myshop_total_orders_past'),
    __$targetTotalOrdersOld: $('#xans_myshop_total_orders_old'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if ($('.xans-myshop-orderhistorytab').length > 0) {
                return true;
            }
        }
        return false;
    },
    execute: function()
    {
        try {
            var mode = this.getUrlParam('mode');
            var order_id = this.getUrlParam('order_id');
            var order_status = this.getUrlParam('order_status');
            var history_start_date = this.getUrlParam('history_start_date');
            var history_end_date = this.getUrlParam('history_end_date');
            var past_year = this.getUrlParam('past_year');
            var count = this.getUrlParam('count');

            var sPathName = window.location.pathname;

            var oParameters = {
                'mode': mode == null ? '' : mode,
                'order_id': order_id == null ? '' : order_id,
                'order_status': order_status == null ? '' : order_status,
                'history_start_date': history_start_date == null ? '' : history_start_date,
                'history_end_date': history_end_date == null ? '' : history_end_date,
                'past_year': past_year == null ? '' : past_year,
                'count': count == null ? '' : count,
                'page_name': sPathName.substring(sPathName.lastIndexOf("/") + 1, sPathName.indexOf('.'))
            };

            if (typeof EC_ASYNC_ORDERHISTORYTAB_ORDER_ID !== 'undefined') {
                oParameters['encrypted_str'] = EC_ASYNC_ORDERHISTORYTAB_ORDER_ID;
            }

            var oThis = this;

            $.ajax({
                url: '/exec/front/Myshop/OrderHistoryTab',
                dataType: 'json',
                data: oParameters,
                success: function (aData) {
                    if (aData['result'] === true) {
                        oThis.__$targetTotalOrders.html(aData['total_orders']);
                        oThis.__$targetTotalOrdersCs.html(aData['total_orders_cs']);
                        oThis.__$targetTotalOrdersOld.html(aData['total_orders_old']);
                        oThis.__$targetTotalOrdersPast.html(aData['total_orders_past']);

                        var oTabATagList = {
                            'param' : $('.tab_class a'),
                            'param_cs' : $('.tab_class_cs a'),
                            'param_past' : $('.tab_class_past a'),
                            'param_old' : $('.tab_class_old a'),
                        };
                        var sHref;
                        $.each(oTabATagList, function(sKey, oTarget) {
                            if (oTarget.length > 0) {
                                sHref = oTarget.attr("href");
                                sHref = sHref.replace("$" + sKey, aData[sKey]);
                                oTarget.attr("href", sHref);
                            }
                        });

                        $("." + aData['selected_tab_class']).addClass('selected');

                        if (aData['is_past_list_display'] === false) {
                            $('.tab_class_past').addClass("displaynone");
                        } else {
                            $('.tab_class_past').removeClass("displaynone");
                        }

                        if (aData['old_list_display'] === false) {
                            $('.tab_class_old').addClass("displaynone");
                        } else {
                            $('.tab_class_old').removeClass("displaynone");
                        }
                    }
                }
            });
        } catch (oError) {
            this.errorAjaxCall(oError);
        }
    },
    getUrlParam : function(name)
    {
        var param = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
        if (param == null) {
            return null;
        } else {
            return decodeURI(param[1]) || null;
        }
    },
    errorAjaxCall : function(oError)
    {
        var sError = oError.toString();
        var aMatch = sError.match(/Error*/g);

        if ( typeof(oError) !== 'object' || aMatch == null || aMatch.length < 1 || !oError.stack ) return;

        $.ajax({
            url: '/exec/front/order/FormJserror/',
            method: 'POST',
            cache: false,
            async : false,
            data: {
                errorMessage : oError.message,
                errorStack : oError.stack,
                errorName : oError.name
            }
        });
    }
};
var PathRoleValidator = (function() {
    /**
     * Milage, Deposit 의 경우 처리되지 말아야할 페이지 확인
     * @returns {boolean}
     */
    function isInvalidPathRole()
    {
        // path role
        var sCurrentPathRole = null;

        // // euckr 환경에서 path role 획득
        if (SHOP.getProductVer() === 1) {
            // path 와 role 매핑
            var aPathRoleMap = {
                '/myshop/index.html': 'MYSHOP_MAIN',
                '/myshop/mileage/historyList.html': 'MYSHOP_MILEAGE_LIST',
                '/myshop/deposits/historyList.html': 'MYSHOP_DEPOSIT_LIST',
                '/order/orderform.html': 'ORDER_ORDERFORM'
            };

            // 페이지 경로로부터 path role 획득
            sCurrentPathRole = aPathRoleMap[document.location.pathname];

            // utf8 환경에서 path role 획득
        } else {
            // 현재 페이지 path role 획득
            sCurrentPathRole = $('meta[name="path_role"]').attr('content');
        }

        // 처리되면 안되는 경로
        var aInvalidPathRole = [
            'MYSHOP_MAIN',
            'MYSHOP_MILEAGE_LIST',
            'MYSHOP_DEPOSIT_LIST',
            'ORDER_ORDERFORM'
        ];

        return $.inArray(sCurrentPathRole, aInvalidPathRole) >= 0;
    }

    return {
        isInvalidPathRole: isInvalidPathRole
    };
})();
$(document).ready(function()
{
	CAPP_ASYNC_METHODS.init();
});
var EC_MANAGE_PRODUCT_RECENT = {
    getRecentImageUrl : function()
    {
        var sStorageKey = 'localRecentProduct' + EC_SDE_SHOP_NUM;

        if (typeof(sessionStorage[sStorageKey]) !== 'undefined') {
            var sRecentData = sessionStorage.getItem(sStorageKey);
            var oJsonData = JSON.parse(sRecentData);
            var sImageSrc = '';

            if (oJsonData[0] !== undefined) {
                sImageSrc = oJsonData[0].sImgSrc;
            }
            
            document.location.replace('recentproduct://setinfo?simg_src=' + sImageSrc);
        }
    }
};

var EC_MANAGE_MEMBER = {
    // 카카오싱크 로그인
    kakaosyncLogin : function (clientSecret)
    {
        if (Kakao.isInitialized()) {
            Kakao.cleanup();
        }
        Kakao.init(clientSecret);

        Kakao.Auth.authorize({
            redirectUri: location.origin + '/Api/Member/Oauth2ClientCallback/kakao/'
        });
    }
};
var EC_EXTERNAL_FRONT_APPSCRIPT = {
    insertAppScript : function() {
        if (typeof EC_APPSCRIPT_ASSIGN_DATA !== "undefined" && $.isArray(window.EC_APPSCRIPT_ASSIGN_DATA)) {
            while (EC_APPSCRIPT_ASSIGN_DATA.length > 0) {
                EC_EXTERNAL_FRONT_APPSCRIPT.appendAppScript(EC_APPSCRIPT_ASSIGN_DATA.pop());
            }
        }
        if (typeof EC_APPSCRIPT_SOURCE_DATA !== "undefined" && $.isArray(window.EC_APPSCRIPT_SOURCE_DATA)) {
            while (EC_APPSCRIPT_SOURCE_DATA.length > 0) {
                EC_EXTERNAL_FRONT_APPSCRIPT.appendSourceTypeScript(EC_APPSCRIPT_SOURCE_DATA.pop());
            }
        }
    },
    appendAppScript : function(sSrc) {
        var js = document.createElement('script');
        js.src = sSrc;
        document.body.appendChild(js);
    },
    appendSourceTypeScript : function (sSrc) {
        var js = document.createElement('script');
        js.type = 'text/javascript';
        js.text = EC_EXTERNAL_FRONT_APPSCRIPT.base64Decode(sSrc);
        document.body.appendChild(js);
    },
    base64Decode: function (sEncoded) {
        return decodeURIComponent(atob(sEncoded).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
    }
};
if (window.addEventListener) {
    window.addEventListener('load', EC_EXTERNAL_FRONT_APPSCRIPT.insertAppScript);
} else if (window.attachEvent) {
    window.attachEvent('onload', EC_EXTERNAL_FRONT_APPSCRIPT.insertAppScript);
}
/**
 * SDK spec interface
 */
EC_EXTERNAL_UTIL_APP_SPECINTERFACE = {

    oMemberInfo : {
        member_id: null,
        group_no: null,
        guest_id: null
    },

    oCustomerIDInfo : {
        member_id: null,
        guest_id: null
    },

    oCustomerInfo: {
        member_id: null,
        name: null,
        nick_name: null,
        group_name: null,
        group_no: null,
        email: null,
        phone: null,
        cellphone: null,
        birthday: null,
        additional_information: null,
        created_date: null
    },

    // @todo deprecated
    oMileageInfo: {
        available_mileage: null,
        returned_mileage: null,
        total_mileage: null,
        unavailable_mileage: null,
        used_mileage: null
    },

    // @todo deprecated
    oDepositInfo: {
        all_deposit: null,
        member_total_deposit: null,
        refund_wait_deposit: null,
        total_deposit: null,
        used_deposit: null
    },

    oPointInfo: {
        available_point: null,
        returned_point: null,
        total_point: null,
        unavailable_point: null,
        used_point: null
    },

    oCreditInfo: {
        all_credit: null,
        member_total_credit: null,
        refund_wait_credit: null,
        total_credit: null,
        used_credit: null
    },

    oCartList: {
        shop_no: null,
        product_no: null,
        additional_option: null,
        attached_file_option: null,
        basket_product_no: null,
        product_price: null,
        quantity: null,
        selected_product: null,
        variant_code: null
    },

    oCartInfo: {
        basket_price: null
    },

    oCartItemList : {
        basket_product_no: null,
        product_no: null,
        price: null,
        option_price: null,
        quantity: null,
        discount_price: null,
        variant_code: null,
        product_weight: null,
        display_group: null,
        quantity_based_discount: null,
        non_quantity_based_discount: null
    },

    oCount: {
        count: 0
    },

    oShopInfo: {
        language_code: null,
        currency_code: null,
        timezone: null
    }
};
/**
 * 비동기식 데이터 - App Common ( 앱 공통정보 )
 */
CAPP_ASYNC_METHODS.aDatasetList.push('AppCommon');
CAPP_ASYNC_METHODS.AppCommon = {

    STORAGE_KEY: 'AppCommon_' +  EC_SDE_SHOP_NUM,

    __sGuestId: null,

    isUse: function()
    {
        if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('application', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        try {
            var aStorageData = JSON.parse(window.sessionStorage.getItem(this.STORAGE_KEY));

            // expire 체크
            if (aStorageData.exp < Date.now()) {
                throw 'cache has expired.';
            }

            // 데이터 체크
            if (typeof aStorageData.data.guest_id === 'undefined') {
                throw 'Invalid cache data.';
            }

            // 데이터 복구
            this.__sGuestId = aStorageData.data.guest_id;

            return true;

        } catch(e) {
            // 복구 실패시 캐시 삭제
            this.removeCache();
            return false;
        }
    },

    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }
        // 캐시 삭제
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    setData: function(oData)
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        this.__sGuestId = oData.guest_id || '';

        try {
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify({
                exp: Date.now() + (1000 * 60 * 10),
                data: this.getData()
            }));
        } catch (error) {
        }
    },

    execute: function()
    {
    },

    getData: function()
    {
        return {
            guest_id: this.__sGuestId
        };
    },

    setSpecData : function(oSpec, oData) {
        var aData = {};
        for (var prop in oSpec) {
            if (oData.hasOwnProperty(prop) === true) {
                aData[prop] = oData[prop];
            } else {
                aData[prop] = oSpec[prop];
            }
        }
        return aData;
    },

    setSpecDataMap : function(oSpec, oData, oMapData) {
        var aData = {};
        for (var prop in oSpec) {
            if (oData.hasOwnProperty(oMapData[prop]) === true) {
                aData[prop] = oData[oMapData[prop]];
            } else {
                aData[prop] = oSpec[prop];
            }
        }
        return aData;
    },

    // sdk function list
    getMemberID: function()
    {
        return CAPP_ASYNC_METHODS.member.getData().member_id;
    },

    getMemberInfo: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oMemberInfo, {group_no: CAPP_ASYNC_METHODS.member.getData().group_no, member_id: CAPP_ASYNC_METHODS.member.getData().member_id});
        } else {
            return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oMemberInfo, {guest_id: CAPP_ASYNC_METHODS.AppCommon.getData().guest_id});
        }
    },

    getCustomerIDInfo: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerIDInfo, {member_id: CAPP_ASYNC_METHODS.member.getData().member_id});
        } else {
            return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerIDInfo, {guest_id: CAPP_ASYNC_METHODS.AppCommon.getData().guest_id});
        }
    },

    getCustomerInfo: function()
    {
        var oMember  = CAPP_ASYNC_METHODS.member.getData();
        oMember.created_date = oMember.created_date.replace(/(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}).+/, '$1');
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerInfo, oMember);
    },

    // @todo deprecated
    getMileageInfo: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oMileageInfo, CAPP_ASYNC_METHODS.Mileage.getData());
    },

     // @todo deprecated
    getDepositInfo: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oDepositInfo, CAPP_ASYNC_METHODS.Deposit.getData());
    },

    getPointInfo: function()
    {
        var oMapData = {
            available_point : 'available_mileage',
            returned_point : 'returned_mileage',
            total_point : 'total_mileage',
            unavailable_point : 'unavailable_mileage',
            used_point : 'used_mileage'
        };

        return this.setSpecDataMap(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oPointInfo, CAPP_ASYNC_METHODS.Mileage.getData(), oMapData);
    },

    getCreditInfo: function()
    {
        var oMapData = {
            all_credit : 'all_deposit',
            member_total_credit : 'member_total_deposit',
            refund_wait_credit : 'refund_wait_deposit',
            total_credit : 'total_deposit',
            used_credit : 'used_deposit'
        };

        return this.setSpecDataMap(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCreditInfo, CAPP_ASYNC_METHODS.Deposit.getData(), oMapData);
    },

    getCartList: function()
    {
        var oData = CAPP_ASYNC_METHODS.BasketProduct.getData();
        var oCartList = EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCartList;
        var aCartList = [];

        for (var iKey in oData) {
            aCartList.push(this.setSpecData(oCartList, oData[iKey]));
        }

        return aCartList;
    },

    getCartInfo: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCartInfo, CAPP_ASYNC_METHODS.Basketprice.getData());
    },

    getCartItemList: function()
    {
        var aCartItemList = [];
        if ( typeof aBasketProductData == "undefined" && typeof aBasketProductOrderData == "undefined") {
            return aCartItemList;
        }

        var aData = (typeof aBasketProductOrderData != "undefined") ? aBasketProductOrderData : aBasketProductData;

        var oMapData = {
            basket_product_no: 'basket_prd_no',
            product_no: 'product_no',
            price: 'product_price',
            option_price: 'opt_price',
            quantity : 'product_qty',
            discount_price: 'product_sale_price',
            variant_code: 'item_code',
            product_weight: 'product_weight',
            display_group: 'main_cate_no',
            quantity_based_discount : 'add_sale_related_qty',
            non_quantity_based_discount : 'add_sale_not_related_qty'
        };

        for (var iKey in aData) {
            aCartItemList.push(this.setSpecDataMap(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCartItemList, aData[iKey], oMapData));
        }

        return aCartItemList;
    },

    getCartCount: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCount, CAPP_ASYNC_METHODS.Basketcnt.getData());
    },

    getCouponCount: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCount, CAPP_ASYNC_METHODS.Couponcnt.getData());
    },

    getWishCount: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCount, CAPP_ASYNC_METHODS.Wishcount.getData());
    },

    getShopInfo: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oShopInfo, {language_code: SHOP.getLanguage(), currency_code: SHOP.getCurrency(), timezone: SHOP.getTimezone()});
    }
};
