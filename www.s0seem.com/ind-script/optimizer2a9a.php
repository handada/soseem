/**
 * 비동기식 데이터 - 최근 본 상품
 */
CAPP_ASYNC_METHODS.aDatasetList.push('recent');
CAPP_ASYNC_METHODS.recent = {
    STORAGE_KEY: 'localRecentProduct' +  EC_SDE_SHOP_NUM,

    __$target: $('.xans-layout-productrecent'),

    __aData: null,

    isUse: function()
    {
        this.__$target.hide();

        if (this.__$target.find('.xans-record-').length > 0) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        this.__aData = [];

        var iTotalCount = CAPP_ASYNC_METHODS.RecentTotalCount.getData();
        if (iTotalCount == 0) {
            // 총 갯수가 없는 경우 복구할 것이 없으므로 복구한 것으로 리턴
            return true;
        }

        var sAdultImage = '';

        if (window.sessionStorage === undefined) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null) {
            return false;
        }

        var iViewCount = EC_FRONT_JS_CONFIG_SHOP.recent_count;

        this.__aData = [];
        var aStorageData = $.parseJSON(sSessionStorageData);
        var iCount = 1;
        var bDispRecent = true;
        for (var iKey in aStorageData) {
            var sProductImgSrc = aStorageData[iKey].sImgSrc;

            if (isFinite(iKey) === false) {
                continue;
            }

            var aDataTmp = [];
            aDataTmp.recent_img = getImageUrl(sProductImgSrc);
            aDataTmp.name = aStorageData[iKey].sProductName;
            aDataTmp.disp_recent = true;
            aDataTmp.is_adult_product = aStorageData[iKey].isAdultProduct;
            aDataTmp.link_product_detail = aStorageData[iKey].link_product_detail;

            //aDataTmp.param = '?product_no=' + aStorageData[iKey].iProductNo + '&cate_no=' + aStorageData[iKey].iCateNum + '&display_group=' + aStorageData[iKey].iDisplayGroup;
            aDataTmp.param = filterXssUrlParameter(aStorageData[iKey].sParam);
            if ( iViewCount < iCount ) {
                bDispRecent = false;
            }
            aDataTmp.disp_recent = bDispRecent;

            iCount++;
            this.__aData.push(aDataTmp);
        }

        return true;

        /**
         * get SessionStorage image url
         * @param sNewImgUrl DB에 저장되어 있는 tiny값
         */
        function getImageUrl(sImgUrl)
        {
            if (typeof(sImgUrl) === 'undefined' || sImgUrl === null) {
                return;
            }
            var sNewImgUrl = '';

            if (sImgUrl.indexOf('http://') >= 0 || sImgUrl.indexOf('https://') >= 0 || sImgUrl.substr(0, 2) === '//') {
                sNewImgUrl = sImgUrl;
            } else {
                sNewImgUrl = EC_FRONT_JS_CONFIG_SHOP.cdnUrl + '/web/product/tiny/' + sImgUrl;
            }

            return sNewImgUrl;
        }

        /**
         * 파라미터 URL에서 XSS 공격 관련 파라미터를 필터링합니다. ECHOSTING-162977
         * @param string sParam 파라미터
         * @return string 필터링된 파라미터
         */
        function filterXssUrlParameter(sParam)
        {
            sParam = sParam || '';

            var sPrefix = '';
            if (sParam.substr(0, 1) === '?') {
                sPrefix = '?';
                sParam = sParam.substr(1);
            }

            var aParam = {};

            var aParamList = (sParam).split('&');
            $.each(aParamList, function() {
                var aMatch = this.match(/^([^=]+)=(.*)$/);
                if (aMatch) {
                    aParam[aMatch[1]] = aMatch[2];
                }
            });

            return sPrefix + $.param(aParam);
        }

    },

    setData: function(aData)
    {
        this.__aData = aData;

        // 쿠키엔 있지만 sessionStorage에 없는 데이터 복구
        if (window.sessionStorage) {

            var oNewStorageData = [];

            for ( var i = 0; i < aData.length; i++) {
                if (aData[i].bNewProduct !== true) {
                    continue;
                }

                var aNewStorageData = {
                    'iProductNo': aData[i].product_no,
                    'sProductName': aData[i].name,
                    'sImgSrc': aData[i].recent_img,
                    'sParam': aData[i].param,
                    'link_product_detail': aData[i].link_product_detail
                };

                oNewStorageData.push(aNewStorageData);
            }

            if ( oNewStorageData.length > 0 ) {
                sessionStorage.setItem(this.STORAGE_KEY , JSON.stringify(oNewStorageData));
            }
        }
    },

    execute: function()
    {
        var sAdult19Warning = EC_FRONT_JS_CONFIG_SHOP.adult19Warning;

        var aData = this.__aData;

        var aNodes = this.__$target.find('.xans-record-');
        var iRecordCnt = aNodes.length;
        var iAddedElementCount = 0;

        var aNodesParent = $(aNodes[0]).parent();
        for ( var i = 0; i < aData.length; i++) {
            if (!aNodes[i]) {
                $(aNodes[iRecordCnt - 1]).clone().appendTo(aNodesParent);
                iAddedElementCount++;
            }
        }

        if (iAddedElementCount > 0) {
            aNodes = this.__$target.find('.xans-record-');
        }

        if (aData.length > 0) {
            this.__$target.show();
        }

        for ( var i = 0; i < aData.length; i++) {
            assignVariables(aNodes[i], aData[i]);
        }

        // 종료 카운트 지정
        if (aData.length < aNodes.length) {
            iLength = aData.length;
            deleteNode();
        }

        recentBntInit(this.__$target);

        /**
         * 패치되지 않은 노드를 제거
         */
        function deleteNode()
        {
            for ( var i = iLength; i < aNodes.length; i++) {
                $(aNodes[i]).remove();
            }
        }

        /**
         * oTarget 엘레먼트에 aData의 변수를 어싸인합니다.
         * @param Element oTarget 변수를 어싸인할 엘레먼트
         * @param array aData 변수 데이터
         */
        function assignVariables(oTarget, aData)
        {
            var recentImage = aData.recent_img;

            if (sAdult19Warning === 'T' && CAPP_ASYNC_METHODS.member.getMemberIsAdult() === 'F' && aData.is_adult_product === 'T') {
                    recentImage = EC_FRONT_JS_CONFIG_SHOP.adult19BaseTinyImage;
            }

            var $oTarget = $(oTarget);

            var sHtml = $oTarget.html();

            sHtml = sHtml.replace('about:blank', recentImage)
                         .replace('##param##', aData.param)
                         .replace('##name##',aData.name)
                         .replace('##link_product_detail##', aData.link_product_detail);
            $oTarget.html(sHtml);

            if (aData.disp_recent === true) {
                $oTarget.removeClass('displaynone');
            }
        }

        function recentBntInit($target)
        {
            // 화면에 뿌려진 갯수
            var iDisplayCount = 0;
            // 보여지는 style
            var sDisplay = '';
            var iIdx = 0;
            //
            var iDisplayNoneIdx = 0;

            var nodes = $target.find('.xans-record-').each(function()
            {
                sDisplay = $(this).css('display');
                if (sDisplay != 'none') {
                    iDisplayCount++;
                } else {
                    if (iDisplayNoneIdx == 0) {
                        iDisplayNoneIdx = iIdx;
                    }

                }
                iIdx++;
            });

            var iRecentCount = nodes.length;
            var bBtnActive = iDisplayCount > 0;
            $('.xans-layout-productrecent .prev').unbind('click').click(function()
            {
                if (bBtnActive !== true) return;
                var iFirstNode = iDisplayNoneIdx - iDisplayCount;
                if (iFirstNode == 0 || iDisplayCount == iRecentCount) {
                    alert(__('최근 본 첫번째 상품입니다.'));
                    return;
                } else {
                    iDisplayNoneIdx--;
                    $(nodes[iDisplayNoneIdx]).hide();
                    $(nodes[iFirstNode - 1]).removeClass('displaynone');
                    $(nodes[iFirstNode - 1]).fadeIn('fast');

                }
            }).css(
            {
                cursor : 'pointer'
            });

            $('.xans-layout-productrecent .next').unbind('click').click(function()
            {
                if (bBtnActive !== true) return;
                if ( (iRecentCount ) == iDisplayNoneIdx || iDisplayCount == iRecentCount) {
                    alert(__('최근 본 마지막 상품입니다.'));
                } else {
                    $(nodes[iDisplayNoneIdx]).fadeIn('fast');
                    $(nodes[iDisplayNoneIdx]).removeClass('displaynone');
                    $(nodes[ (iDisplayNoneIdx - iDisplayCount)]).hide();
                    iDisplayNoneIdx++;
                }
            }).css(
            {
                cursor : 'pointer'
            });

        }

    }
};

/**
 * 비동기식 데이터 - 최근본상품 총 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('RecentTotalCount');
CAPP_ASYNC_METHODS.RecentTotalCount = {
    __iRecentCount: null,

    __$target: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_recent_cnt'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'recent_plist';
        if (EC_SDE_SHOP_NUM > 1) {
            sCookieName = 'recent_plist' + EC_SDE_SHOP_NUM;
        }
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iRecentCount = decodeURI(aCookieValue[1]).split('|').length;
        } else {
            this.__iRecentCount = 0;
        }
    },

    execute: function()
    {
        this.__$target.html(this.__iRecentCount);
    },

    getData: function()
    {
        if (this.__iRecentCount === null) {
            // this.isUse값이 false라서 복구되지 않았는데 이 값이 필요한 경우 복구
            this.restoreCache();
        }

        return this.__iRecentCount;
    }
};
/**
 * 비동기식 데이터 - 주문정보
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Order');
CAPP_ASYNC_METHODS.Order = {
    __iOrderCount: null,
    __iOrderTotalPrice: null,
    __iGradeIncreaseValue: null,

    __$target: $('#xans_myshop_bankbook_order_count'),
    __$target2: $('#xans_myshop_bankbook_order_price'),
    __$target3: $('#xans_myshop_bankbook_grade_increase_value'),

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

    restoreCache: function()
    {
        var sCookieName = 'order_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            var aData = jQuery.parseJSON(decodeURIComponent(aCookieValue[1]));
            this.__iOrderCount = aData.total_order_count;
            this.__iOrderTotalPrice = aData.total_order_price;
            this.__iGradeIncreaseValue = Number(aData.grade_increase_value);
            return true;
        }

        return false;
    },

    setData: function(aData)
    {
        this.__iOrderCount = aData['total_order_count'];
        this.__iOrderTotalPrice = aData['total_order_price'];
        this.__iGradeIncreaseValue = Number(aData['grade_increase_value']);
    },

    execute: function()
    {
        this.__$target.html(this.__iOrderCount);
        this.__$target2.html(this.__iOrderTotalPrice);
        this.__$target3.html(this.__iGradeIncreaseValue);
    },

    getData: function()
    {
        return {
            total_order_count: this.__iOrderCount,
            total_order_price: this.__iOrderTotalPrice,
            grade_increase_value: this.__iGradeIncreaseValue
        };
    }
};
/**
 * 비동기식 데이터 - Benefit
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Benefit');
CAPP_ASYNC_METHODS.Benefit = {
    __aBenefit: null,
    __$target: $('.xans-myshop-asyncbenefit'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }
        }

        return false;
    },

    setData: function(aData)
    {
        this.__aBenefit = aData;
    },

    execute: function()
    {
        var aFilter = ['group_image_tag', 'group_icon_tag', 'display_no_benefit', 'display_with_all', 'display_mobile_use_dc', 'display_mobile_use_mileage'];
        var __aData = this.__aBenefit;
        
        // 그룹이미지
        $('.myshop_benefit_group_image_tag').attr({alt: __aData['group_name'], src: __aData['group_image']});

        // 그룹아이콘
        $('.myshop_benefit_group_icon_tag').attr({alt: __aData['group_name'], src: __aData['group_icon']});

        if (__aData['display_no_benefit'] === true) {
            $('.myshop_benefit_display_no_benefit').removeClass('displaynone').show();
        }
        
        if (__aData['display_with_all'] === true) {
            $('.myshop_benefit_display_with_all').removeClass('displaynone').show();
        }
        
        if (__aData['display_mobile_use_dc'] === true) {
            $('.myshop_benefit_display_mobile_use_dc').removeClass('displaynone').show();
        } 
        
        if (__aData['display_mobile_use_mileage'] === true) {
            $('.myshop_benefit_display_mobile_use_mileage').removeClass('displaynone').show();
        }

        $.each(__aData, function(key, val) {
            if ($.inArray(key, aFilter) === -1) {
                $('.myshop_benefit_' + key).html(val);
            }
        });
    }    
};
/**
 * 비동기식 데이터 - 비동기장바구니 레이어
 */
CAPP_ASYNC_METHODS.aDatasetList.push('BasketLayer');
CAPP_ASYNC_METHODS.BasketLayer = {
    __sBasketLayerHtml: null,
    __$target: document.getElementById('ec_async_basket_layer_container'),

    isUse: function()
    {
        if (this.__$target !== null) {
            return true;
        }
        return false;
    },

    execute: function()
    {
        $.ajax({
            url: '/order/async_basket_layer.html?__popupPage=T',
            async: false,
            success: function(data) {
                var sBasketLayerHtml = data;
                var sBasketLayerStyle = '';
                var sBasketLayerBody = '';

                sBasketLayerHtml = sBasketLayerHtml.replace(/<script([\s\S]*?)<\/script>/gi,''); // 스크립트 제거
                sBasketLayerHtml = sBasketLayerHtml.replace(/<link([\s\S]*?)\/>/gi,''); // 옵티마이져 제거

                var regexStyle = /<style([\s\S]*?)<\/style>/; // Style 추출
                if (regexStyle.exec(sBasketLayerHtml) != null) sBasketLayerStyle = regexStyle.exec(sBasketLayerHtml)[0];

                var regexBody = /<body[\s\S]*?>([\s\S]*?)<\/body>/; // Body 추출
                if (regexBody.exec(sBasketLayerHtml) != null) sBasketLayerBody = regexBody.exec(sBasketLayerHtml)[1];

                CAPP_ASYNC_METHODS.BasketLayer.__sBasketLayerHtml = sBasketLayerStyle + sBasketLayerBody;
            }
        });
        this.__$target.innerHTML = this.__sBasketLayerHtml;
    }
};
/**
 * 비동기식 데이터 - Benefit
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Grade');
CAPP_ASYNC_METHODS.Grade = {
    __aGrade: null,
    __$target: $('#sGradeAutoDisplayArea'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }
        }

        return false;
    },

    setData: function(aData)
    {
        this.__aGrade = aData;
    },

    execute: function()
    {
        var __aData = this.__aGrade;
        var aFilter = ['bChangeMaxTypePrice', 'bChangeMaxTypePriceAndCount', 'bChangeMaxTypePriceOrCount', 'bChangeMaxTypeCount'];

        var aMaxDisplayJson = {
            "bChangeMaxTypePrice": [
                {"sId": "sChangeMaxTypePriceArea"}
            ],
            "bChangeMaxTypePriceAndCount": [
                {"sId": "sChangeMaxTypePriceAndCountArea"}
            ],
            "bChangeMaxTypePriceOrCount": [
                {"sId": "sChangeMaxTypePriceOrCountArea"}
            ],
            "bChangeMaxTypeCount": [
                {"sId": "sChangeMaxTypeCountArea"}
            ]
        };

        if ($('.sNextGroupIconArea').length > 0) {
            if (__aData['bDisplayNextGroupIcon'] === true) {
                $('.sNextGroupIconArea').removeClass('displaynone').show();
                $('.myshop_benefit_next_group_icon_tag').attr({alt: __aData['sNextGrade'], src: __aData['sNextGroupIcon']});
            } else {
                $('.sNextGroupIconArea').addClass('displaynone');
            }
        }

        var sIsAutoGradeDisplay = "F";
        $.each(__aData, function(key, val) {
            if ($.inArray(key, aFilter) === -1) {
                return true;
            }
            if (val === true) {
                if ($('#'+aMaxDisplayJson[key][0].sId).length > 0) {
                    $('#' + aMaxDisplayJson[key][0].sId).removeClass('displaynone').show();
                }
                sIsAutoGradeDisplay = "T";
            }
        });
        if (sIsAutoGradeDisplay == "T" && $('#sGradeAutoDisplayArea .sAutoGradeDisplay').length > 0) {
            $('#sGradeAutoDisplayArea .sAutoGradeDisplay').addClass('displaynone');
        }

        $.each(__aData, function(key, val) {
            if ($.inArray(key, aFilter) === -1) {
                if ($('.xans-member-var-' + key).length > 0) {
                    $('.xans-member-var-' + key).html(val);
                }
            }
        });
    }    
};
/**
 * 비동기식 데이터 - Benefit
 */
CAPP_ASYNC_METHODS.aDatasetList.push('AutomaticGradeShow');
CAPP_ASYNC_METHODS.AutomaticGradeShow = {
    __aGrade: null,
    __$target: $('#sAutomaticGradeShowArea'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }
        }
        return false;
    },

    setData: function(aData)
    {
        this.__aGrade = aData;
    },

    execute: function()
    {
        var __aData = this.__aGrade;

        /**
         * 아이콘 표기 제외
        if ($('.sNextGroupIconArea').length > 0) {
            if (__aData['bDisplayNextGroupIcon'] === true) {
                $('.sNextGroupIconArea').removeClass('displaynone').show();
                $('.myshop_benefit_next_group_icon_tag').attr({alt: __aData['sNextGrade'], src: __aData['sNextGroupIcon']});
            } else {
                $('.sNextGroupIconArea').addClass('displaynone');
            }
        }
         */

        var sIsAutoGradeDisplay = "F";
        $.each(__aData, function(key, val) {
            if (val === true) {
                sIsAutoGradeDisplay = "T";
                return false;
            }
        });
        if (sIsAutoGradeDisplay == "T" && $('#sAutomaticGradeShowArea .sAutoGradeDisplay').length > 0) {
            $('#sAutomaticGradeShowArea .sAutoGradeDisplay').addClass('displaynone');
        }

        $.each(__aData, function(key, val) {
            if ($('.xans-member-var-' + key).length > 0) {
                $('.xans-member-var-' + key).html(val);
            }
        });
    }    
};
/**
 * 비동기식 데이터 - 비동기장바구니 레이어
 */
CAPP_ASYNC_METHODS.aDatasetList.push('MobileMutiPopup');
CAPP_ASYNC_METHODS.MobileMutiPopup = {
    __$target: $('div[class^="ec-async-multi-popup-layer-container"]'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        return false;
    },

    execute: function()
    {
        for (var i=0; i < this.__$target.length; i++) {
            $.ajax({
                url: '/exec/front/popup/AjaxMultiPopup?index='+i,
                data : EC_ASYNC_MULTI_POPUP_OPTION[i],
                dataType: "json",
                success : function (oResult) {
                    switch (oResult.code) {
                        case '0000' :
                            if (oResult.data.length < 1) {
                                break;
                            }
                            $('.ec-async-multi-popup-layer-container-' + oResult.data.html_index).html(oResult.data.html_text);
                            if (oResult.data.type == 'P') {
                                BANNER_POPUP_OPEN.setPopupSetting();
                                BANNER_POPUP_OPEN.setPopupWidth();
                                BANNER_POPUP_OPEN.setPopupClose();
                            } else {
                                /**
                                 * 이중 스크롤 방지 클래스 추가(비동기) 
                                 *
                                 */
                                $('body').addClass('eMobilePopup');
                                $('body').width('100%');

                                BANNER_POPUP_OPEN.setFullPopupSetting();
                                BANNER_POPUP_OPEN.setFullPopupClose();
                            }
                            break;
                        default :
                            break;
                    }
                },
                error : function () {
                }
            });
        }
    }
};
/**
 * 비동기식 데이터 - 좋아요 상품 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('MyLikeProductCount');
CAPP_ASYNC_METHODS.MyLikeProductCount = {
    __iMyLikeCount: null,

    __$target: $('#xans_myshop_like_prd_cnt'),
    __$target_main: $('#xans_myshop_main_like_prd_cnt'),
    isUse: function()
    {
        if (this.__$target.length > 0 && SHOP.getLanguage() === 'ko_KR') {
            return true;
        }

        if (this.__$target_main.length > 0 && SHOP.getLanguage() === 'ko_KR') {
            return true;
        }

        return false;
    },
    restoreCache: function()
    {
        var sCookieName = 'like_product_cnt' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iMyLikeCount = parseInt(aCookieValue[1], 10);
            return true;
        }

        return false;
    },

    setData: function(sData)
    {
        this.__iMyLikeCount = Number(sData);
    },

    execute: function()
    {
        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target.html(this.__iMyLikeCount + '개');
            this.__$target_main.html(this.__iMyLikeCount);
        }
    }
};
/**
 * 라이브 링콘 on/off이미지
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Livelinkon');
CAPP_ASYNC_METHODS.Livelinkon = {
    __$target: $('#ec_livelinkon_campain_on'),
    __$target2: $('#ec_livelinkon_campain_off'),

    isUse: function()
    {
        if (this.__$target.length > 0 && this.__$target2.length > 0) {
            return true;
        }
        return false;
    },

    execute: function()
    {
        var sCampaignid = '';
        if (EC_ASYNC_LIVELINKON_ID != undefined) {
            sCampaignid = EC_ASYNC_LIVELINKON_ID;
        }
        $.ajax({
            url: '/exec/front/Livelinkon/Campaignajax?campaign_id='+sCampaignid,
            async: false,
            success: function(data) {
                if (data == 'on') {
                    CAPP_ASYNC_METHODS.Livelinkon.__$target.removeClass('displaynone').show();
                    CAPP_ASYNC_METHODS.Livelinkon.__$target2.removeClass('displaynone').hide();
                } else if (data == 'off') {
                    CAPP_ASYNC_METHODS.Livelinkon.__$target.removeClass('displaynone').hide();
                    CAPP_ASYNC_METHODS.Livelinkon.__$target2.removeClass('displaynone').show();
                } else {
                    CAPP_ASYNC_METHODS.Livelinkon.__$target.removeClass('displaynone').hide();
                    CAPP_ASYNC_METHODS.Livelinkon.__$target2.removeClass('displaynone').hide();
                }
            }
        });
    }
};
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
