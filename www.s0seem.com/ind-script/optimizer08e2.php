/**
 * 추가구성 상품 라이브러리
 */
var TotalAddSale = function() {
    //추가할인액계산
    var oProductList = new Object();
    var oOlnkProductList = new Object();
    var oTotalAddSaleParam = new Object();
    var iTotalAddSalePrice = 0;
    var oTotalAddSaleData = new Object();
    var oProductOptionType = new Object();
    var bSoldOut = false;

    var oDefaultOption = {
        url : "/exec/front/shop/CalculatorProduct",
        type : "GET",
        data : oTotalAddSaleParam,
        dataType : "json",
        timeout : 5000,
        compleat : function() {
            TotalAddSale.setAsyncMode(true);
        }
    };

    var updateItemPrice = function() {

        //판매가 회원 공개인 경우 제외
        if (sIsDisplayNonmemberPrice === 'T') {
            return;
        }

        if (TotalAddSale.getIsUseSalePrice() === false) {
            return;
        }

        var oData = TotalAddSale.oTotalAddSaleData;
        var iLayer = $('#product_detail_option_layer').length;

        //1+N혜택상품은 가격이 보이지 않게 처리
        if (TotalAddSale.getIsBundleProduct() === true) {
            $('.ec-front-product-item-price[product-no="' + iProductNo + '"]').html('');
            if (iLayer > 0) {
                $(top.document).find('.ec-front-product-item-price[product-no="' + iProductNo + '"]').html('');
            }

            return;
        }

        //세트상품 제외
        var bIsSetProduct = false;
        if (typeof(set_option_data) !== 'undefined') {
            bIsSetProduct = true;
        }

        for (var sKey in oData) {
            if (oData.hasOwnProperty(sKey) === false) {
                continue;
            }

            if (typeof(oData[sKey]) !== 'object') {
                continue;
            }

            //표시항목설정 > "판매가 부가세 표시문구"이 사용함일경우 key가 object로 생김
            if (sKey === 'product_tax_type_text') {
                continue;
            }

            //본상품이 세트상품일 경우에는 본상품은 제외하고 추가구성상품만 할인가로 처리
            var oElement = $('.ec-front-product-item-price[code="' + sKey + '"]');
            if (bIsSetProduct === true && parseInt(oElement.attr('product-no')) === parseInt(iProductNo)) {
                continue;
            }

            var iProductItemPrice = 0;

            if (oData[sKey].display_vat_separately === true) {
                //부가세 별도표시일경우
                iProductItemPrice = oData[sKey].vat_sub_total_price - oData[sKey].vat_sale_price;
                iProductItemPrice = SHOP_PRICE_FORMAT.toShopPrice(iProductItemPrice);
            } else {
                //부가세 포함일 경우
                iProductItemPrice = oData[sKey].item_quantity_price - oData[sKey].add_sale;
                iProductItemPrice = SHOP_PRICE_FORMAT.toShopPrice(iProductItemPrice);
            }

            oElement.html(iProductItemPrice);
            if (iLayer > 0) {
                $(top.document).find('.ec-front-product-item-price[code="' + sKey + '"]').html(iProductItemPrice);
            }
        }
    };

    /**
     * 적립금 갱신
     * @param sOptionBoxId
     * @param sWithoutOptionId
     */
    var updateMileage = function(sOptionBoxId, sWithoutOptionId) {


        // 적립금 표시중이 아니거나, 판매가 회원 공개인 경우 제외
        if (sIsMileageDisplay !== 'T' || sIsDisplayNonmemberPrice === 'T') {
            return;
        }

        var oData = TotalAddSale.oTotalAddSaleData;
        var iQuantity = 1;
        var iLayer = $('#product_detail_option_layer').length;

        // TotalAddSale.oTotalAddSaleData 없는 경우 리턴
        if (typeof(oData) === 'undefined') {
            return;
        }

        // 이미 갱신된 현재 적립금은 추가 갱신하지 않도록 삭제
        if (typeof(sWithoutOptionId) !== 'undefined' && oData.hasOwnProperty(sWithoutOptionId) === true) {
            delete oData[sWithoutOptionId];
        }

        // 적립금 갱신
        for (var sKey in oData) {
            if (oData.hasOwnProperty(sKey) === true) {
                if (typeof(oData[sKey]) === 'object') {
                    var sMileageVal = SHOP_PRICE_FORMAT.toShopMileagePrice(TotalAddSale.getMileageGenerateCalc(sKey, typeof(oData[sKey].quantity) !== 'undefined' ? oData[sKey].quantity : iQuantity));

                    $('.mileage_price[code="' + sKey + '"]').html(sMileageVal);

                    if (iLayer > 0) {
                        $(top.document).find('.mileage_price[code="' + sKey + '"]').html(sMileageVal);
                    }
                }
            }
        }
    };

    /**
     * 추가할인액 주문api조회
     * @param fCallback 콜백함수
     * @return TotalAddSale.iTotalAddSalePrice
     */
    var getCalculatorSalePrice = function (fCallback, iPrice) {
        if (EC_FRONT_JS_CONFIG_SHOP.bDirectBuyOrderForm === true) {
            fCallback(iPrice);
            EC_SHOP_FRONT_NEW_PRODUCT_DIRECT_BUY.setDirectBuyOrderBasket();
        } else {
            var oOption = {
                success: function(oResponse) {
                    // 글로벌이면서 일체형 세트상품의 구성상품 과세비율 또는 과세타입이 다른 경우에는 구매 불가
                    if (typeof(oResponse.flag) !== 'undefined' && oResponse.flag === false && oResponse.code === 4221) {
                        // 알럿 - [$상품명] 상품은 구매할 수 있는 상품이 아닙니다.
                        ProductSet.getCompareSetAlert();
                        return;
                    }

                    TotalAddSale.oTotalAddSaleData = oResponse;
                    if (TotalAddSale.bSoldOut === false) {
                        TotalAddSale.iTotalAddSalePrice = oResponse.iTotalAddSalePrice;
                        TotalAddSale.iTotalOrderPrice = oResponse.iTotalOrderPrice;
                        TotalAddSale.oProductTaxTypeText = oResponse.product_tax_type_text;
                        TotalAddSale.sDisplayVatSeparately = oResponse.display_prd_vat_separately;
                    }

                    fCallback(iPrice);
                }, error: function () {
                    if ($('.EC-price-warning').length > 0) {
                        $('.EC-price-warning').removeClass('displaynone').show();
                    } else {
                        alert(__('할인가가 적용된 최종 결제예정금액은 주문 시 확인할 수 있습니다.'));
                    }
                    fCallback(iPrice);
                }

            };

            // 품절일 경우 할인액 계산 제외
            if ($('.soldout_option_box_id').length > 0) {
                $('.soldout_option_box_id').each(function () {
                    delete oDefaultOption.data['product'][$(this).val()];
                });
            }
            $.ajax($.extend(oDefaultOption, oOption));
        }
    };
    /* 단일 선택형인 경우 처리가 필요함. 대량 구매 할인 정책때문에 파라미터 제거 처리 */
    var setAddSaleParamRemove = function(sOptionId) {

        // 단일 선택형인지 확인
        if (oSingleSelection.isItemSelectionTypeS() === true  && sOptionId !== '') {
            var oProductListData = TotalAddSale.getProductList();
            var sUniqueProductId = '';
            var bRegexp = false;

            // 연동형의 경우 아이템코드가 조합형과 다르다.
            if (sOptionId.indexOf('||') > -1 || sOptionId.indexOf('#$%') > -1) {
                sUniqueProductId = sOptionId.replace(/[0-9]+/g, '');
                bRegexp = true;
            } else if (oProductOptionType[sOptionId] === 'F') { // 독립형의 경우 각 개별적으로 갯수가 존재함.
                sUniqueProductId = sOptionId;
            } else {
                sUniqueProductId = sOptionId.substring(0, 8);
            }

            for (var sKey in oProductListData) {
                var sOptionKey = sKey;
                if (bRegexp === true) {
                    sOptionKey = sOptionKey.replace(/[0-9]+/g, '');
                }
                if (sOptionKey.indexOf(sUniqueProductId) > -1) {
                    TotalAddSale.removeProductData(sKey);
                }
            }

        }
    };
    var getDirectBuyParam = function(){
        var aStockData = new Array();
        if (typeof(option_stock_data) !== 'undefined') {
            aStockData = $.parseJSON(option_stock_data);
        }
        var oProduct = TotalAddSale.getParam();
        var oParam = new Object();
        var oProductParam = new Object();
        oParam['product_no'] = oProduct['product_no'];

        if (typeof(oProduct['product']) !== 'undefined' && Object.keys(oProduct['product']).length > 0) {
            var i = 0;
            for (var sKey in oProduct['product']) {
                if (typeof(aStockData[sKey]) != 'undefined' && aStockData[sKey].is_auto_soldout === 'T') {
                    continue;
                }
                oProductParam[i] = {'item_code': sKey, 'quantity': oProduct['product'][sKey]};
                oParam['items'] = oProductParam;
                i++;
            }
        } else {
            oParam['items'] = null;
        }
        return oParam;
    };

    return {
        updatePrice : function(sOptionBoxId, sWithoutOptionId) {
            updateItemPrice();
            updateMileage(sOptionBoxId, sWithoutOptionId);
        },

        updateItemPrice : function() {
            updateItemPrice();
        },

        removeProductData : function(sOptionKey)
        {
            delete oProductList[sOptionKey];
        },
        // 총 추가할인액 반환
        getTotalAddSalePrice : function() {
            if (typeof(EC_SHOP_FRONT_PRODUCT_FUNDING) === 'object' && EC_SHOP_FRONT_PRODUCT_FUNDING.isFundingProduct() === true) {
                return 0;
            }
            return TotalAddSale.iTotalAddSalePrice;
        },
        // 계산할 정보 셋팅
        setParam : function(sKey, value) {
            oTotalAddSaleParam[sKey] = value;
        },
        clearAddSaleParam : function(sKey)
        {
            delete oTotalAddSaleParam[sKey];
        },
        getParam : function()
        {
            return oTotalAddSaleParam;
        },
        // 계산될 상품리스트
        getProductList : function() {
            return oProductList;
        },
        // 총 추가할인금액 리셋
        setTotalAddSalePrice : function(iSalePrice) {
            TotalAddSale.iTotalAddSalePrice = iSalePrice;

            if (EC_FRONT_JS_CONFIG_SHOP.bDirectBuyOrderForm === true) {
                EC_SHOP_FRONT_NEW_PRODUCT_DIRECT_BUY.resetDirectBuyOrderBasket();
            }
        },
        // 계산할 정보 수량 셋팅
        setQuantity : function(sItemCode, sQuantity) {
            TotalAddSale.setAddSaleParamRemove(sItemCode);
            oProductList[sItemCode] = sQuantity;
        },
        setOlnkAddProduct : function(sItemCode, iProductNo) {
            oOlnkProductList[sItemCode] = iProductNo;
        },
        getOlnkAddProductList : function() {
            return oOlnkProductList;
        },
        // api호출
        getCalculatorSalePrice : function(fCallback, iPrice) {
            getCalculatorSalePrice(fCallback, iPrice);
        },
        // 총 추가할인액 반환
        getItemAddSalePrice : function(sItemCode) {
            if ( typeof(TotalAddSale.oTotalAddSaleData) != 'undefined' ){
                return parseFloat(TotalAddSale.oTotalAddSaleData[sItemCode].unit_add_sale , 10);
            } else {
                return 0;
            }
        },
        // 총 추가할인금액 리셋
        setSoldOutFlag : function(bSoldOut) {
            if ( typeof(bSoldOut) == 'undefined' || bSoldOut === null) {
                bSoldOut = false;
            }
            TotalAddSale.bSoldOut = bSoldOut;
        },
        // 적립금 총 계산
        getMileageGenerateCalc : function(sItemCode, iQuantity) {
            if (TotalAddSale.bSoldOut === false && typeof(TotalAddSale.oTotalAddSaleData) != 'undefined') {
                if (typeof(TotalAddSale.oTotalAddSaleData[sItemCode]) !== 'undefined' && typeof(TotalAddSale.oTotalAddSaleData[sItemCode].mileage_generate_calc) !== 'undefined') {
                    return parseFloat(TotalAddSale.oTotalAddSaleData[sItemCode].mileage_generate_calc, 10);
                } else {
                    return 0;
                }
            } else {
                return (typeof(mileage_generate_calc) != 'undefined') ? mileage_generate_calc * iQuantity : 0;
            }
        },
        // 적립금 유효성 검증
        checkVaildMileageValue : function(iMileageValue) {
            if (typeof (iMileageValue) === 'undefined' && iMileageValue === 0.00 || iMileageValue <= 0) {
                return false;
            }

            return true;
        },
        /**
         * @deprecated 추가할인가 재계산 필요 여부 리턴
         * @returns true
         */
        needRecalculatorSalePrice : function() {
            /*
             * 해당 메소드 동작처리시 대량 구매 할인의 경우 product_sale_price 값이 존재하지 않으며
             * TotalAddSale.iTotalAddSalePrice 값도 대량 구매 할인의 경우 필요하지 않게 됨
             */
            return true;
        },
        // 판매가 부가세 표시문구 설정
        getProductTaxTypeText : function() {
            return TotalAddSale.oProductTaxTypeText;
        },
        // 실제 총 주문금액
        getTotalOrderPrice : function() {
            return TotalAddSale.iTotalOrderPrice;
        },
        // 부가세 별도 표시 설정
        getDisplayVatSeparately : function() {
            return TotalAddSale.sDisplayVatSeparately;
        },
        getItemSalePrice : function(sItemCode)
        {
            if (typeof(TotalAddSale.oTotalAddSaleData[sItemCode]) === 'undefined') {
                return false;
            }
            return TotalAddSale.oTotalAddSaleData[sItemCode].unit_sale_price;
        },
        // 부가세 고정세율 총상품금액
        getVatSubTotalPrice : function(sItemCode) {
            var sDisplayVatSeparately = TotalAddSale.getDisplayVatSeparately();
            if (typeof(sDisplayVatSeparately) === 'undefined') {
                return 0;
            }

            if (sDisplayVatSeparately !== true) {
                return 0;
            }

            if (typeof(TotalAddSale.oTotalAddSaleData[sItemCode]) === 'undefined') {
                return 0;
            }

            return TotalAddSale.oTotalAddSaleData[sItemCode].vat_sub_total_price;
        },
        //품목 선택시 할인가를 보여줄것인지 여부
        getIsUseSalePrice : function () {

            if (typeof (EC_FRONT_JS_CONFIG_SHOP) !== 'object') {
                return false;
            }

            //디자인에서 옵션을 설정하지 않으면 처리하지 않음
            if (EC_FRONT_JS_CONFIG_SHOP.bECUseItemSalePrice === false) {
                return false;
            }

            return true;
        },

        //1+N상품인지 여부
        getIsBundleProduct : function () {
            if (typeof(EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE) !== 'undefined' && EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig.hasOwnProperty(iProductNo) === true) {
                return true;
            }

            return false;
        } ,
        //
        setAddSaleParamRemove : function(sOptionId)
        {
            return setAddSaleParamRemove(sOptionId);
        } ,
        setProductOptionType : function (sOptionId, sOptType) {
            if (oSingleSelection.isItemSelectionTypeS() === true  && sOptionId !== '') {
                oProductOptionType[sOptionId] = sOptType;
            }

        },
        getDirectBuyParam : function()
        {
            return getDirectBuyParam();
        },
        setSubscriptionParam : function()
        {
            var isSubscription = 'F';
            if ($('.EC_regular_delivery:checked').val() === 'T') {
                isSubscription = 'T';
            }
            TotalAddSale.setParam('is_subscription',isSubscription);

        }
    };
}();
// sms 재입고 알림 모바일 레이어 팝업
var EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER = {
    bExistMobileLayerModule : false,
    sRequireSmsRestockParam : '',

    setCheckSmsRestockLayerPopup : function()
    {
        //모바일이 아니라면 사용하지 않음
        if (mobileWeb === false) {
            return;
        }

        if ($('a[id^="btn_restock"]').length < 1) {
            return;
        }

        //아이프레임 내에서는 레이어를 다시띄우지 않음
        if (CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame() === parent) {
            return;
        }

        $.ajax({
            url : '/exec/front/Product/Moduleexist?section=product&file=sms_restock_layer&module=Product_RestockSms',
            dataType : 'json',
            success : function (data) {
                if (data.result === true) {
                    EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER.bExistMobileLayerModule = true;
                }
            }
        });
    },
    createSmsRestockLayerDisplayResult : function(sParam)
    {
        //레이어 사용가능상태가 아니면 false로 바로 리턴
        if (EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER.bExistMobileLayerModule === false) {
            return false;
        }

        if ($.trim(sParam).length < 1) {
            return false;
        }

        try {
            EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER.sRequireSmsRestockParam = sParam;
            EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER.setProductSmsRestockCreateLayer();
        } catch (e) {
            return false;
        }

        return true;
    },
    setProductSmsRestockCreateLayer : function()
    {
        try {
            $('#ec-product-sms-restock-layer').remove();
        } catch ( e ) {}

        var sSmsLayerUrl = '/product/sms_restock_layer.html?' + EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER.sRequireSmsRestockParam + '&bSmsRestockLayer=T';
        var aSmsRestockLayerHtml = [];

        aSmsRestockLayerHtml.push('<div id="ec-product-sms-restock-layer" style="position:fixed; top:0; left:0; right:0; bottom:0; webkit-overflow-scrolling:touch; z-index:999;">');
        aSmsRestockLayerHtml.push('<iframe src="'+sSmsLayerUrl+'" id="smsRestockLayerIframe" frameborder="0" style="width:100%; height:100%;"></iframe>');
        aSmsRestockLayerHtml.push('</div>');

        $('body').append(aSmsRestockLayerHtml.join(''));
        $('body').addClass('eMobilePopup');
    },
    closeSmsRestockLayer : function()
    {
        if (opener) {
            self.close();
        } else {
            parent.$('body').attr('id', 'layout');
            parent.$('body').removeClass('eMobilePopup');
            parent.$('#ec-product-sms-restock-layer').remove();
        }
    }
};

/**
 * 목록 > 상품 좋아요.
 */
var EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT = {
    bIsReady    : false,   // 좋아요 클릭준비완료 여부.
    bIsSetEvent : false,   // 좋아요 버튼 이벤트 지정 여부.
    aImgSrc     : [], // 좋아요(On/Off) 아이콘 경로.
    aImgAlt     : [], // 좋아요(On/Off) 아이콘 Alt태그
    aMyLikePrdNo: [], // 유저가 이미 좋아요 선택한 상품번호
    oMyshopLikeCntNode : null, // layout_shopingInfo 좋아요 span 노드

    // 상품 좋아요 초기화
    init : function() {
        // 상품 좋아요 사용안함시
        if (EC_SHOP_FRONT_NEW_LIKE_COMMON.aConfig.bIsUseLikeProduct !== true) {
            return;
        }

        // ajax 유저가 이미 좋아요 선택한 상품번호 얻기 + 아이콘세팅
        this.setLoadData();
    },

    // 유저가 이미 좋아요 선택한 상품번호 얻기 + 아이콘세팅
    setLoadData : function() {
        if ($('.likePrdIcon').count < 1) {
            return;
        }

        var self = this;

        EC_SHOP_FRONT_NEW_LIKE_COMMON.getMyLikeProductNoInList(function(aData) {
            self.aImgSrc = aData.imgSrc;
            self.aImgAlt = aData.imgAlt;
            self.aMyLikePrdNo = aData.rows;

            // 아이콘(on) 세팅
            self.setMyLikeProductIconOn();

            // 좋아요 클릭 이벤트핸들러 지정
            if (self.bIsSetEvent === false) {
                self.setEventHandler();
                self.bIsSetEvent = true;
            }
        }, function() {
            self.bIsReady = true;
        });
    },

    // 페이지 로드시 유저가 좋아요한 상품 On.아이콘으로 변경
    setMyLikeProductIconOn : function() {
        var aData = this.aMyLikePrdNo;

        for (var i=0; i < aData.length; i++) {
            // selected 스타일 적용
            $(".likePrd_" + aData[i].product_no).each(function() {
                $(this).addClass('selected');
            });

            // 아이콘 이미지경로 변경
            $(".likePrdIcon[product_no='" + aData[i].product_no + "']").each(function() {
                $(this).attr({'src':EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.aImgSrc.on, 'icon_status':'on', 'alt' : EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.aImgAlt.on});
            });
        }
    },

    // 이벤트핸들러 지정
    setEventHandler : function() {
        // 좋아요 아이콘 클릭 이벤트
        try {
            $('.likePrd').live('click', EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.clickLikeIcon);
        } catch (e) {}

        var sContext = '';
        if (typeof(PREVIEWPRDOUCT) === 'undefined') {
            sContext = window.parent.document;
        }
        // 마이쇼핑 > 상품좋아요 페이지
        if ($(".xans-myshop-likeproductlist", sContext).length > 0) {
            // 팝업 확대보기창 닫기 이벤트
            if ($(".xans-product-zoompackage").length > 0) {
                $('.xans-product-zoompackage div.close').live('click', EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.closeZoomReload);
            }
        }
    },

    // 좋아요 아이콘 클릭 이벤트핸들러
    clickLikeIcon : function() {
        if (EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.bIsReady === false ) {
            return;
        }

        // 클릭한 상품의 좋아요수, 아이콘 정보얻기
        var iPrdNo     = $('.likePrdIcon', this).attr('product_no');
        var iCateNo    = $('.likePrdIcon', this).attr('category_no');
        var sIconStatus= $('.likePrdIcon', this).attr('icon_status');
        // 카운트 string > int 형으로 변환 (ECHOSTING-260504)
        var iLikeCount = EC_SHOP_FRONT_NEW_LIKE_COMMON.getNumericRemoveCommas($('.likePrdCount', this).text());

        // 아이콘경로 및 좋아요수 증감처리
        var sNewImgSrc = sNewIconStatus = "";
        var iNewLikeCount = 0;
        var oLikeWrapNode = $(".likePrd_" + iPrdNo);

        if (sIconStatus === 'on') {
            sNewIconStatus = 'off';
            sNewImgSrc = EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.aImgSrc.off;
            sNewImgAlt = EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.aImgAlt.off;
            if (iLikeCount > 0) {
                iNewLikeCount = --iLikeCount;
            }

            oLikeWrapNode.each(function() {
                $(this).removeClass('selected');
            });
        } else {
            sNewIconStatus = 'on';
            sNewImgSrc = EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.aImgSrc.on;
            sNewImgAlt = EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.aImgAlt.on;
            iNewLikeCount = ++iLikeCount;

            // 동일상품 selected 스타일적용
            oLikeWrapNode.each(function() {
                $(this).addClass('selected');
            });
        }
        // 좋아요 카운트 number_format (ECHOSTING-260504)
        iNewLikeCount = EC_SHOP_FRONT_NEW_LIKE_COMMON.getNumberFormat(iNewLikeCount);
        // 상단.layout_shopingInfo 좋아요수 업데이트
        EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.updateShopInfoCount(sNewIconStatus);

        // 좋아요 아이콘이미지 + 좋아요수 업데이트
        EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.updateLikeIconCount(iPrdNo, sNewImgSrc, sNewIconStatus, iNewLikeCount, sNewImgAlt);

        // ajax 호출 좋아요수(상품) + 마이쇼핑 좋아요 저장
        EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.submitMyLikeProduct(iPrdNo, iCateNo, sNewIconStatus);

        // 확대보기 팝업에서 좋아요 클릭시, 부모프레임 좋아요 업데이트
        if ($(".xans-product-zoompackage").length > 0) {
            window.parent.EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.updateShopInfoCount(sNewIconStatus);
            window.parent.EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.updateLikeIconCount(iPrdNo, sNewImgSrc, sNewIconStatus, iNewLikeCount);
        }
    },

    // 마이쇼핑 > 상품좋아요 목록 > 팝업 확대보기창 닫기 이벤트핸들러
    closeZoomReload : function() {
        var sIconsStatus = $('.xans-product-zoompackage .likePrdIcon').attr('icon_status');

        // 팝업에서 좋아요를 취소했으면 좋아요 목록 새로고침
        if (sIconsStatus === 'off') {
            window.parent.location.reload();
        }
    },

    // 좋아요 아이콘이미지 + 좋아요수 업데이트
    updateLikeIconCount : function(iPrdNo, sImgSrc, sIconStatus, iLikeCount, sNewImgAlt) {
        // 클릭한 동일상품 아이콘 변경
        $(".likePrdIcon[product_no='" + iPrdNo + "']").each(function() {
            $(this).attr({'src':sImgSrc, 'icon_status':sIconStatus, 'alt' : sNewImgAlt});
        });

        // 클릭한 동일상품 좋아요수 변경
        $('.likePrdCount_' + iPrdNo).each(function() {
            $(this).text(iLikeCount);
        });
    },

    // 상단.layout_shopingInfo 좋아요수 업데이트
    updateShopInfoCount : function(sIconStatus) {
        if (EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.oMyshopLikeCntNode === null) {
            EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.oMyshopLikeCntNode = $('#xans_myshop_like_prd_cnt');
        }

        var iMyshopLikeCnt;
        if (EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.oMyshopLikeCntNode !== null) {
            iMyshopLikeCnt = parseInt($(EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.oMyshopLikeCntNode).text() );
            iMyshopLikeCnt = (sIconStatus === 'on') ? iMyshopLikeCnt + 1  : iMyshopLikeCnt - 1;
            iMyshopLikeCnt = (iMyshopLikeCnt < 0 || isNaN(iMyshopLikeCnt)) ? 0 : iMyshopLikeCnt;
            EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.oMyshopLikeCntNode.text(iMyshopLikeCnt + '개');
        }

        if ($('#xans_myshop_main_like_prd_cnt').length > 0 && iMyshopLikeCnt >= 0) {
            $('#xans_myshop_main_like_prd_cnt').text(iMyshopLikeCnt);
        }
    },

    // 상품 좋아요수 + 마이쇼핑 좋아요 저장
    submitMyLikeProduct : function(iPrdNo, iCateNo, sIconStatus) {
        if (sIconStatus === 'on') {
            this.aMyLikePrdNo.push(iPrdNo);
        } else {
            this.aMyLikePrdNo.pop(iPrdNo);
        }

        $.ajax({
            url: '/exec/front/shop/LikeCommon',
            type: 'get',
            data: {
                'mode'    : 'saveMyLikeProduct',
                'iPrdNo'  : iPrdNo,
                'iCateNo' : iCateNo,
                'sIconStatus': sIconStatus
            },
            dataType: 'json',
            success: function(oReturn) {
                if (oReturn.bResult === true) {
                    EC_SHOP_FRONT_NEW_LIKE_COMMON.purgeMyLikeProductNoInList();
                }
            },
            complete: function() {
                EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.bIsReady = true;
            }
        });
    }
};

$(document).ready(function() {
    EC_SHOP_FRONT_NEW_LIKE_COMMON_PRODUCT.init();  // 상품 좋아요.
});

var EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE = {
    oBundleConfig : {},

    iProductQuantity : 0,

    init : function(oInit)
    {
        if (typeof(oInit) === 'object') {
            this.oBundleConfig  = oInit;
        } else {
            if (sBundlePromotion === '' || typeof(sBundlePromotion) === 'undefined') {
                return;
            }
            this.oBundleConfig = $.parseJSON(sBundlePromotion);
        }
        // 강제로 후킹
        buy_unit = 1;
        product_min = 1;
        product_max = 0;

        $.data(document,'BundlePromotion', true);
    },
    getQuantityStep : function(iProductNum)
    {
        return this.oBundleConfig[iProductNum].bundle_quantity + 1;
    },
    /**
     * 실제 UI의 수량대신 1+N 이벤트로 인해 후킹된 상품 수량을 리턴
     */
    getQuantity : function(iQuantity, iProductNum)
    {
        var iReturn = iQuantity;
        if (typeof(this.oBundleConfig[iProductNum]) === 'undefined') {
            return iReturn;
        }

        iReturn = Math.ceil(iQuantity / this.getQuantityStep(iProductNum));

        return iReturn;
    },
    /**
     * 정확한 구매 수량이 맞는지 검증
     */
    isValidQuantity : function(aQuantity, iProductNum)
    {
        var bReturn = true;
        if (typeof(this.oBundleConfig[iProductNum]) === 'undefined') {
            return bReturn;
        }

        if (this.isValidQuantityCheck(aQuantity, iProductNum) === false) {
            alert(this.getAlertMessage([iProductNum]));
            return false;
        }
        return bReturn;
    },
    isValidQuantityCheck : function(aQuantity, iProductNum)
    {
        var iQuantityStep = this.getQuantityStep(iProductNum);

        if (this.oBundleConfig[iProductNum].bundle_apply_type === 'P') {
            EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.iProductQuantity = 0;
            $.map(aQuantity, function(pv, cv) {
                EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.iProductQuantity += pv;
            });
            return (EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.iProductQuantity % iQuantityStep) === 0;
        }

        if (this.oBundleConfig[iProductNum].bundle_apply_type === 'I') {
            for (var i in aQuantity) {
                if (aQuantity.hasOwnProperty(i) === false) {
                    continue;
                }
                if (aQuantity[i] % iQuantityStep !== 0) {
                    return false;
                }
            }
        }
        return true;
    },
    getAlertMessage : function(iProductNum)
    {
        var sSubject = this.oBundleConfig[iProductNum].bundle_apply_type === 'P' ? '옵션에 상관없이' : '동일한 옵션으로';
        var sReturn = '1+%s 이벤트상품입니다.\n'+sSubject+' 수량을 %s개 단위로 구매해주세요.';
        return sprintf(__(sReturn), this.oBundleConfig[iProductNum].bundle_quantity, this.getQuantityStep(iProductNum));
    }
};

var isMobile = false;
var sInputMileBackground = '';
$(document).ready(function() {
    // 모바일접속 여부
    // mobileWeb 값이 있으면 참조하고 없으면 m. 도메인 체크
    if (mobileWeb == undefined) {
        if (window.location.hostname.substr(0, 2) == 'm.' || window.location.hostname.substr(0, 12) == 'mobile--shop' || window.location.hostname.substr(0, 11) == 'skin-mobile') {
            isMobile = true;
        }
    } else {
        isMobile = mobileWeb;
    }

    // 주문서 작성 페이지
    try {
        $('#np_use0').attr('checked', 'true');

        $('#np_use0').click(function() {
            if ($(this).attr('checked') == false) {
                initNaverMileage();
                if (isMobile == true && typeof(nbp) == 'object') {
                    nbp.mileage.inactive();
                }
            } else {
                if (isMobile == true && typeof(nbp) == 'object') {
                    nbp.mileage.active();
                }
            }
            if (isMobile == false) {
                setNaverPoint();
            }
        });
    } catch(e) {}

    // 네이버마일리지 적립금과 동시사용 불가에 따른 처리
    // 동시사용 불가인 경우 디자인 수정을 안했을때 기존꺼 노출
    try {
        if (isNMCanUseWithMileage() == false && isApplyDesignNMCanUseWithMileage() == false) {
            $('div.boardView').find('#input_mile').parents('div').first().removeClass('displaynone');
            $('div.boardView').find('#np_use0').parents('div').first().removeClass('displaynone');
        }
    } catch (e) {}

    // 적립금동시사용불가 디자인적용에 따른 처리
    try {
        if (isApplyDesignNMCanUseWithMileage()) {
            $('#either_mileage_navermileage_select0').attr('checked', true);
            if (isMobile == true) {
                $('input[name^="mileage_use_select"]').click(function() {
                    var oInputMile = getInputMileObject();
                    if ($(this).val() == 'mileage') {
                        initNaverMileage();
                        oInputMile.css('background', sInputMileBackground);
                        oInputMile.attr('readonly', false);
                        if (isApplyDesignNMCanUseWithMileage() == true) {
                            nbp.mileage.inactive();
                        }
                    } else {
                        sInputMileBackground = oInputMile.css('background');
                        oInputMile.val(0);
                        oInputMile.attr('readonly', true);
                        oInputMile.css('background', '#CCCCCC');
                        if (isApplyDesignNMCanUseWithMileage() == true) {
                            nbp.mileage.active();
                        }

                        if (bInflowParam != false){
                        } else {
                            $('#_either_mileage_acc').hide();
                        }
                    }
                    set_total_price();
                });
            } else {
                $('#navermileage_use_container').css({"display":"none"});
                $('input[id^="either_mileage_navermileage_select1"]').css("margin-left", "10px");
                $('label[for^="either_mileage_navermileage_select"]').css("padding-left", "3px");

                $('input[name^="mileage_use_select"]').click(function() {
                    var oMileageUseContainer = $('#mileage_use_container');
                    var oNavermileageUseContainer = $('#navermileage_use_container');
                    var oNavermileageGuideContainer = $('#navermileage_guide_cotainer');
                    var oInputMile = getInputMileObject();
                    oMileageUseContainer.css('display', 'none');
                    oNavermileageUseContainer.css('display', 'none');
                    oNavermileageGuideContainer.css('display', 'none');

                    if ($(this).val() == 'mileage') {
                        oMileageUseContainer.css('display', '');
                        initNaverMileage();
                    } else {
                        oNavermileageUseContainer.css('display', '');
                        oNavermileageGuideContainer.css('display', '');
                        oInputMile.val(0);

                        //네이버 ON 상태는 꼭 이렇게 비교하라고 해서 이렇게 함
                        if (bInflowParam != false) {
                        } else {
                            $('#either_divNvPointBtnAdd').hide();
                            $('#either_divNvDefaultGuide').html('네이버 통해 방문 시 적립/사용 가능');
                        }

                    }

                    if (bInflowParam != false) {
                        setNaverPoint();
                    }
                    set_total_price();
                });

                var oNavermileageGuideContainer = $('#navermileage_guide_cotainer');
                oNavermileageGuideContainer.css('display', 'none');
            }
        }
    } catch (e) {}


    // PC 쇼핑몰 > 주문서 작성페이지
    if (isMobile == false) {
        try {
            // 네이버마일리지 가이드 폭조정(동시사용 불가능 UI)
            $('.navermileage_guide').css({'text-align':'center', 'padding-top':'5px', 'padding-bottom':'5px', 'background-color':'#f7f7ff'});

            // 적립률 색상 변경 & bold처리
            $('#txt_np_save').css({'color':'#1ec228', 'font-weight':'bold'});
            $('#divNvPointOpr').css({'color':'#1ec228', 'font-weight':'bold'});
        } catch (e) {}
    }

    // 네이버 추가 적립률 네이버공통스크립트로 부터 가져오기
    try {
        var oNaverMileage = {
            'def' : 0,
            'base' : 0,
            'add' : 0
        };
        oNaverMileage.def = $('#np_save_rate_default').val();

        var oNvSaveRateBase = $('#naver_mileage_save_rate_base');
        var oNvMileageHelp  = $('#imgNaverMileageHelp');
        if ($('.naver_mileage_compare').length > 0 || mobileWeb === true) { // 상품비교, 모바일
            oNvSaveRateBase = $('.naver_mileage_save_rate_base');
            oNvMileageHelp  = $('.img_naver_mileage_help');
        }

        // get save rate of naverMileage
        if (typeof(wcs) == 'object') {
            var bInflowParam = wcs.getMileageInfo();
            if (bInflowParam != false) {
                oNaverMileage.base = wcs.getBaseAccumRate();
                oNaverMileage.add = wcs.getAddAccumRate();

                if (isMobile == false) {
                    if ($('.xans-order-form').length > 0) {//주문서
                        var oNaverStateImg = '<img src="//img.echosting.cafe24.com/design/skin/default/product/txt_naver_on1.png" style="margin:3px">';
                        $('#either_mileage_navermileage_select0').parents('tbody').find('th > label').html('적립금&<br>네이버마일리지<br>' + oNaverStateImg + '(택1)');

                        $('#naverPointStatus').html(oNaverStateImg);
                        $('#naverPointStatus img').css({'margin':'-3px 3px 0'});

                        $('#either_imgNaverMileageHelp').attr('//img.cafe24.com/images/ec_admin/btn/icon_q_green.gif');

                        if ($('#np_use0').parent().find("img").attr("src") == null || $('#np_use0').parent().find("img").attr("src") == undefined) {
                            $('#np_use0').parent().append(oNaverStateImg);
                        }
                        $('#imgNaverMileageHelp').attr('src', '//img.cafe24.com/images/ec_admin/btn/icon_q_green.gif');
                    } else {
                        $('#imgNaverMileageHelp').css({'margin-top' : '-2px'});
                    }
                }

            } else {
                oNaverMileage.base = oNaverMileage.def;

                if (isMobile == false) {
                    if ($('.xans-order-form').length > 0) {//주문서
                        var oNaverStateImg = '<img src="//img.echosting.cafe24.com/design/skin/default/product/txt_naver_off1.png" style="margin:3px">';

                        //택1 일 경우 (어차피 display none 일 때는 안 보임)
                        $('#either_mileage_navermileage_select0').parents('tbody').find('th > label').html('적립금&<br>네이버마일리지<br>' + oNaverStateImg + '(택1)');

                        $('#naverPointStatus').html(oNaverStateImg);
                        $('#naverPointStatus img').css({'margin':'-3px 3px 0'});

                        $('#np_use0').hide();
                        $('#divNvPointBtnAdd').hide();
                        $('#divNvDefaultGuide').html('네이버 통해 방문 시 적립/사용 가능');


                        $('label[for="np_use0"]').parent().html('네이버 마일리지' + oNaverStateImg);
                        $('#imgNaverMileageHelp').attr('src', '//img.cafe24.com/images/ec_admin/btn/icon_q_green.gif');
                        $('.naverInfo').hide();
                    } else {//상품상세
                        var sNaverStateImg = '//img.echosting.cafe24.com/design/skin/default/product/txt_naver_off2.png';
                        var sOnClick = "NaverMileage.openMileageIntroPopup('http://static.mileage.naver.net/static/20130708/ext/intro.html');";
                        oNvSaveRateBase.parent().html('네이버 마일리지 <a href="#none" onclick="' + sOnClick + '"><img src="' + sNaverStateImg + '" style="margin-top:-2px;"></a><br>(네이버 통해 방문 시 적립/사용 가능)');

                    }
                }

            }
        } else {
            oNaverMileage.base = $('#np_save_rate').val();
            oNaverMileage.add = $('#np_save_rate_add').val();
        }

        if (oNaverMileage.base == 0 || oNaverMileage.base == '') {
            oNaverMileage.base = oNaverMileage.def;
        }

        // casting data type
        oNaverMileage.def = castDataType(oNaverMileage.def);
        oNaverMileage.base = castDataType(oNaverMileage.base);
        oNaverMileage.add = castDataType(oNaverMileage.add);

        // true -  상품상세/상품비교 페이지, false - 주문서 작성 페이지
        if (document.getElementById('naver_mileage_save_rate_base') != undefined && document.getElementById('naver_mileage_save_rate_base') != null) {
            //ECHOSTING-73678
            oNvMileageHelp.attr('src','//img.echosting.cafe24.com/design/skin/default/product/txt_naver_on2.png');

            if (oNaverMileage.base > 0) {
                var iTotalNaverMileageRate = oNaverMileage.base + oNaverMileage.add;
                oNvSaveRateBase.html(iTotalNaverMileageRate + '%');
            } else {
                oNvSaveRateBase.html(oNaverMileage.def + '%');
            }
        } else {
            var iSaveRateSum = oNaverMileage.base;
            if (oNaverMileage.add > 0) {
                iSaveRateSum += oNaverMileage.add;
            }
            $('#divNvDefaultGuide .naver_mileage_save_rate_sum').html(castDataType(iSaveRateSum));
            $('#either_divNvDefaultGuide .naver_mileage_save_rate_sum').html(castDataType(iSaveRateSum));
        }
        // 모바일 > 주문서 작성 페이지인 경우에만 실행(마일리지 모바일버전은 ui노출부분이 다르다.)
        if (isMobile) {
            initNavermileageWithWcs();

            if ($('#frm_order_act').length > 0) {//주문서
                var bUseSelectMileage = isApplyDesignNMCanUseWithMileage();
            }

            if (bInflowParam != false) {
                if ($('.xans-product-detail').length > 0 || $('.xans-product-detaildesign').length > 0) { //상품상세
                    var sOnImg = '<img src="//img.echosting.cafe24.com/design/skin/mobile/txt_naver_on1.png" style="width:28px;margin-bottom:-1px;">';
                    $('.naver_mileage_save_rate_add').html('적립 ' + sOnImg);
                    $('.naverMileageSaveText').hide();
                } else {//주문서
                    $('#naverMileageTitle').append(' <img src="//img.echosting.cafe24.com/design/skin/default/product/txt_naver_on1.png" style="margin-bottom:-1px">');

                    if (bUseSelectMileage) {//택1
                        $('#navermileage_use_container').find('label > span').append(' <img src="//img.echosting.cafe24.com/design/skin/default/product/txt_naver_on1.png" style="margin-bottom:-1px">');
                    }
                }
            } else {
                if ($('#frm_order_act').length > 0) {//주문서
                    $('#np_use0').hide();
                    $('#naverMileageTitle').append(' <img src="//img.echosting.cafe24.com/design/skin/default/product/txt_naver_off1.png">');
                    $('#_mileage_acc').html('네이버 통해 방문 시 적립/사용 가능 ');

                    if (bUseSelectMileage) {//택1
                        $('#navermileage_use_container').find('label > span').append(' <img src="//img.echosting.cafe24.com/design/skin/default/product/txt_naver_off1.png" style="margin-bottom:-1px">');
                        $('#_mile_acc_rate').parent().hide();
                        $('#navermileage_use_container').find('.either_navermileage_use_container').append('네이버 통해 방문 시 적립/사용 가능');
                    }

                } else{//상품상세
                    $('.naver_mileage_save_rate_base').hide();
                    var sOffImg = '<img src="//img.echosting.cafe24.com/design/skin/mobile/txt_naver_off1.png" style="width:28px;margin-bottom:-1px;">';
                    $('.naver_mileage_save_rate_add').html(sOffImg+ ' (네이버 통해 방문 시 적립/사용 가능) ');
                    $('.naverMileageSaveText').hide();
                }
            }
        }

    } catch (e) {}
});

var naver_reqTxId;
var bNvOn = false;
var NaverMileage = {
    onNvPointLayer:function(dMode)
    {
        bNvOn = true;
        var obj = document.getElementById('divNvPointInfo');
        $('#divNvPointInfo').show();

        var leftMargine = obj.offsetWidth;
        if (dMode == 1) {
            var XY = $('#imgNaverMileageHelp').position();

            obj.style.top = XY.top+14+'px';
            obj.style.left = XY.left-150+'px';

            if (obj.attachEvent) {
                obj.attachEvent('onmouseover', NaverMileage.setNvOn);
            } else {
                obj.addEventListener('mouseover', NaverMileage.setNvOn, false);
            }
        }
        return true;
    },
    setNvOn:function() {
        bNvOn = true;
    },
    offNvPointLayerTic:function(bIntval)
    {
        bNvOn = false;
        if (bIntval == true) {
            setTimeout("NaverMileage.offNvPointLayer()", 200);
        } else {
            NaverMileage.offNvPointLayer();
        }
    },
    offNvPointLayer:function()
    {
        if (bNvOn == false) $('#divNvPointInfo').hide();
    },

    openMileageIntroPopup : function(sUrl)
    {
        var iWidth = 404;
        var iHeight = 412;
        var iLeft = (screen.width - iWidth) / 2;
        var iTop = (screen.height  - iHeight) / 2;
        var sOpt = 'width='+iWidth+', height='+iHeight+', left='+iLeft+', top='+iTop+', status=no, resizable=no';

        window.open(sUrl, 'mileageIntroPopup', sOpt);
    }
};


function showNaverCashShowAccumPopup()
{
    if (isNMCanUseWithMileage() == false && isApplyDesignNMCanUseWithMileage() == false) {
        var oInputMile = getInputMileObject();
        if (parseInt(oInputMile.val()) > 0) {
            alert(__('네이버마일리지는 적립금과 동시사용할 수 없습니다.'));
            return false;
        }
    }

    if (document.getElementById('np_use0').checked == false) {
        alert(__('네이버 마일리지 사용/적립 시에는 좌측의 체크박스를 선택하셔야 합니다.'));
        return false;
    }
    var sUrl = "https://service.mileage.naver.com/service/accumulation/"+$('#np_api_id').val()+"?doneUrl="+$('#np_done_url').val();

    var sUrl = "https://service.mileage.naver.com/service/v2/accumulation/"+$('#np_api_id').val()+"?doneUrl="+$('#np_done_url').val();
    if (typeof(sIsNaverMileageSandbox) != 'undefined') {
        if (sIsNaverMileageSandbox == 'T') {
            var sUrl = "https://sandbox-service.mileage.naver.com/service/v2/accumulation/"+$('#np_api_id').val()+"?doneUrl="+$('#np_done_url').val();
        }
    }

    if (naver_reqTxId) {
        sUrl = sUrl + "&reqTxId=" + naver_reqTxId;
    }

    var sNcisy = new String();
    if (typeof(wcs) == 'object') {
        var inflowParam = wcs.getMileageInfo();
        if (inflowParam != false) {
            sNcisy = inflowParam;
        }
    } else {
        sNcisy = $('#np_ncisy').val();
    }

    sUrl = sUrl + "&Ncisy=" + sNcisy;
    sUrl = sUrl + "&sig=" + $('#np_req_sig').val();
    sUrl = sUrl + "&timestamp=" + $('#np_timestamp').val();

    try {
        if (typeof($('#r_total_price').val()) != 'undefined') {
            var iMaxUseAmount = SHOP_PRICE.toShopPrice($('#r_total_price').val());
            sUrl = sUrl + "&maxUseAmount=" + iMaxUseAmount;
        }
    } catch (e) {}

    var sWinName = document.getElementById('np_window_name').value;
    window.open(sUrl , sWinName, "width=496,height=434,status=no,resizable=no");
}

function enableNaverCashPanel(baseAccumRate, addAccumRate, useAmount, balanceAmount, reqTxId, sig, resultCode, mileageUseAmount, cashUseAmount, totalUseAmount)
{
    naver_reqTxId = reqTxId;

    if (SHOP_PRICE.toShopPrice(stringReplace(',','',$('#total_price').val())) + parseInt($('#np_use_amt').val()) < parseInt(totalUseAmount)) {
        alert(__('결제하셔야 할 금액보다 사용금액이 큽니다. 다시 사용금액을 입력해주세요'));
        return false;
    }

    if ($('#np_req_tx_id').val() != null && reqTxId != '' && reqTxId != 0 && resultCode == 'E1000') {
        $('#np_req_tx_id').val(reqTxId);
        $('#np_save_rate').val(baseAccumRate);
        $('#np_save_rate_add').val(addAccumRate);
        $('#np_use_amt').val(useAmount);
        $('#np_mileage_use_amount').val(mileageUseAmount);
        $('#np_cash_use_amount').val(cashUseAmount);
        $('#np_total_use_amount').val(totalUseAmount);
        $('#np_use_amt').val(useAmount);
        $('#np_balance_amt').val(balanceAmount);
        $('#np_sig').val(sig);
        if ($('#np_use0').attr('checked') == true) {
            $('#np_use').val('T');
        } else {
            $('#np_use').val('');
        }
    } else {
        initNaverMileage();
    }

    $('#imgNaverMileageHelp').show();

    // PC쇼핑몰인경우만 ui에 사용 마일리지&캐쉬 정보 적용
    if (isMobile == false) {
        setNaverPoint();
    }
}


function setNaverPoint()
{
    try {

        var bUseNaverMileage = false;
        if (isApplyDesignNMCanUseWithMileage()) {
            if ($('#either_mileage_navermileage_select1').attr('checked') == true) {
                bUseNaverMileage = true;
            }
        } else {
            if ($('#np_use0').attr('checked') == true) {
                bUseNaverMileage = true;
            }
        }

        if (bUseNaverMileage == false) {
            initNaverMileage();
        }

        var sNpReqTxId = document.getElementById('np_req_tx_id').value;
        var iNpUseAmt = SHOP_PRICE.toShopPrice(document.getElementById('np_use_amt').value);
        var iNpMileageUseAmt = SHOP_PRICE.toShopPrice(document.getElementById('np_mileage_use_amount').value);
        var iNpCashUseAmt = SHOP_PRICE.toShopPrice(document.getElementById('np_cash_use_amount').value);
        var iNpTotalUseAmt = SHOP_PRICE.toShopPrice(document.getElementById('np_total_use_amount').value);
        var iNpBalanceAmt = SHOP_PRICE.toShopPrice(document.getElementById('np_balance_amt').value);
        var iNpSaveRate = parseFloat(document.getElementById('np_save_rate').value);
        var iNpSaveRateAdd = parseFloat(document.getElementById('np_save_rate_add').value);
        var iNpSaveRateTotal = iNpSaveRate + iNpSaveRateAdd;

        if (isNMCanUseWithMileage() == false && isApplyDesignNMCanUseWithMileage() == true) {
            var elmNvDefaultGuide = document.getElementById('either_divNvDefaultGuide');
            var oDivNvPointUse    = document.getElementById('either_divNvPointUse');
            var oDivNvPointSave   = document.getElementById('either_divNvPointSave');
            var oDivNvPointOpr    = document.getElementById('either_divNvPointOpr');
            var oDivNvPointBtnAdd = document.getElementById('either_divNvPointBtnAdd');
            var oDivNvPointBtnMod = document.getElementById('either_divNvPointBtnMod');
            var oTxtNpUse         = document.getElementById('either_txt_np_use');
            var oTxtNpSave        = document.getElementById('either_txt_np_save');
            var oExTxNpSave       = document.getElementById('either_ex_tx_np_save');
            var oExTxNpUse        = document.getElementById('either_ex_tx_np_use');

            var bInflowParam = wcs.getMileageInfo();

        } else {
            var elmNvDefaultGuide = document.getElementById('divNvDefaultGuide');
            var oDivNvPointUse    = document.getElementById('divNvPointUse');
            var oDivNvPointSave   = document.getElementById('divNvPointSave');
            var oDivNvPointOpr    = document.getElementById('divNvPointOpr');
            var oDivNvPointBtnAdd = document.getElementById('divNvPointBtnAdd');
            var oDivNvPointBtnMod = document.getElementById('divNvPointBtnMod');
            var oTxtNpUse         = document.getElementById('txt_np_use');
            var oTxtNpSave        = document.getElementById('txt_np_save');
            var oExTxNpSave       = document.getElementById('ex_tx_np_save');
            var oExTxNpUse        = document.getElementById('ex_tx_np_use');
        }


        if (isUseNaverMileage() == false) {
            elmNvDefaultGuide.style.display = '';
        }

        oDivNvPointUse.style.display = 'none';
        oDivNvPointSave.style.display = 'none';
        oDivNvPointOpr.style.display = 'none';
        oDivNvPointBtnAdd.style.display = 'none';
        oDivNvPointBtnMod.style.display = 'none';

        if (iNpTotalUseAmt > 0 && iNpSaveRate > 0) {//& opr
            oDivNvPointOpr.style.display = 'inline';
        }
        if (iNpTotalUseAmt > 0 || iNpSaveRateTotal > 0) {
            oDivNvPointBtnMod.style.display = 'inline';
        } else {
            oDivNvPointBtnAdd.style.display = 'inline';
        }
        if (iNpSaveRateTotal > 0) {//적립
            if (elmNvDefaultGuide) {
                elmNvDefaultGuide.style.display = 'none';
            }

            oDivNvPointSave.style.display = 'inline';
            oTxtNpSave.innerHTML = oExTxNpSave.innerHTML.replace("[np_rate]", iNpSaveRateTotal);
        }

        set_total_price();

        if (iNpTotalUseAmt > 0) {
            if (elmNvDefaultGuide) {
                elmNvDefaultGuide.style.display = 'none';
            }

            oDivNvPointUse.style.display = 'inline';
            var sTmp = oExTxNpUse.innerHTML;

            var aUseNaverValue = new Array();
            if (iNpMileageUseAmt > 0) {
                aUseNaverValue.push('마일리지 ' + addCommas(iNpMileageUseAmt) + '원');
            }
            if (iNpCashUseAmt > 0) {
                aUseNaverValue.push('캐쉬 ' + addCommas(iNpCashUseAmt) + '원');
            }

            oTxtNpUse.innerHTML = aUseNaverValue.join(' + ') + ' 사용';
        }

        paymethod_display($(':input:radio[name="addr_paymethod"]:checked').val());

    } catch (e) {
        initNaverMileage();
        set_total_price();
    }

}


/**
 * 네이버 마일리지/캐쉬 reset
 * @return void
 */
function resetNaverPoint()
{
    try {
        $('#np_use0').attr('checked',false);
        setNaverPoint();
        $('#np_use0').attr('checked',true);
        paymethod_display($(':input:radio[name="addr_paymethod"]:checked').val());
    } catch (e) {}
}


/**
 * 네이버 마일리지/캐쉬 사용안함
 * @return void
 */
function initNaverMileage()
{
    // clear value
    try {
        document.getElementById('np_req_tx_id').value          = "";
        document.getElementById('np_use_amt').value            = 0;
        document.getElementById('np_mileage_use_amount').value = 0;
        document.getElementById('np_cash_use_amount').value    = 0;
        document.getElementById('np_total_use_amount').value   = 0;
        document.getElementById('np_balance_amt').value        = 0;
        document.getElementById('np_save_rate').value          = 0;
        document.getElementById('np_save_rate_add').value      = 0;
        document.getElementById('np_sig').value                = "";
    } catch (e) {}

    // init design
    try {
        if (isNMCanUseWithMileage() == false && isApplyDesignNMCanUseWithMileage() == true) {
            var oDivNvPointUse    = document.getElementById('either_divNvPointUse');
            var oDivNvPointSave   = document.getElementById('either_divNvPointSave');
            var oDivNvPointOpr    = document.getElementById('either_divNvPointOpr');
            var oDivNvPointBtnAdd = document.getElementById('either_divNvPointBtnAdd');
            var oDivNvPointBtnMod = document.getElementById('either_divNvPointBtnMod');
        } else {
            var oDivNvPointUse    = document.getElementById('divNvPointUse');
            var oDivNvPointSave   = document.getElementById('divNvPointSave');
            var oDivNvPointOpr    = document.getElementById('divNvPointOpr');
            var oDivNvPointBtnAdd = document.getElementById('divNvPointBtnAdd');
            var oDivNvPointBtnMod = document.getElementById('divNvPointBtnMod');
        }
        oDivNvPointUse.style.display    = 'none';
        oDivNvPointSave.style.display   = 'none';
        oDivNvPointOpr.style.display    = 'none';
        oDivNvPointBtnAdd.style.display = 'inline';
        oDivNvPointBtnMod.style.display = 'none';
    } catch (e) {}

    //  clear trasaction id
    try {
        naver_reqTxId = '';
    } catch (e) {}
}


/**
 * 네이버 마일리지/캐쉬 사용 여부
 * @return boolean
 */
function isUseNaverMileage()
{
    var bIsUse = false;
    try {
        if ($('#np_req_tx_id').val() != '' || $('#np_save_rate').val() > 0) {
            bIsUse = true;
        }
    } catch (e) {}
    return bIsUse;
}

/**
 * 자료형 cast
 * @param float fData 숫자
 * @return mixed
 */
function castDataType(fData)
{
    if (isNaN(fData) == false) {
        if ((fData % 1) == 0) {
            return parseInt(fData);
        } else {
            return parseFloat(fData);
        }
    } else {
        return 0;
    }
}


/**
 * 모바일 마일리지 Library 초기화
 */
function initNavermileageWithWcs()
{
    try {
        // 네이버마일리지 관련 변수가 controller에서 assign이 안되어 있으면 아래부분 실행시도를 안한다.
        if (typeof(nbp) == 'object') {

            var iMaxuseAmount = parseInt($('#total_price').val().replace(/,/g, ''));
            var iBaseAccumRate = parseFloat($('#np_save_rate_default').val());
            var iTimestamp = parseInt($('#np_timestamp').val());
            var sId = '_mileage_acc';
            if (isNMCanUseWithMileage() == false && isApplyDesignNMCanUseWithMileage() == true) {
                sId = '_either_mileage_acc';
            }

            var bResult = nbp.mileage.initWithWcs({
                'sId': sId,
                'sApiId': $('#np_api_id').val(),
                'sDoneUrl': decodeURIComponent($('#np_done_url').val()),
                'nMaxUseAmount': iMaxuseAmount,
                'sSig': $('#np_req_sig').val(),
                'nTimestamp': iTimestamp,
                'nBaseAccumRate': iBaseAccumRate,
                'bActive' : true,
                'event' : {
                    'beforeAccum' : function(oEvent) { //적립/사용페이지가 뜨기 직전 호출된다.
                        set_total_price();
                        nbp.mileage.setMaxUseAmount(getNavermileageMaxAmount());
                        if (oEvent.bActive === false) { //마일리지 모듈이 비활성화 상태에서 적립/사용 버튼 클릭 callback 구현
                            alert('네이버 마일리지를 사용/적립하려면, 먼저 \'네이버 마일리지\'를 선택해야합니다. ');
                            return false;
                        }
                    },
                    'accum' : function(aRetVal) {
                        aRetVal.resultCode = convertResultCode(aRetVal.resultCode);
                        enableNaverCashPanel(aRetVal.baseAccumRate, aRetVal.addAccumRate, aRetVal.mileageUseAmount, aRetVal.balanceAmount, aRetVal.reqTxId, aRetVal.sig, aRetVal.resultCode, aRetVal.mileageUseAmount, aRetVal.cashUseAmount, aRetVal.totalUseAmount);
                        set_total_price();
                    }
                }
            });

            if (bResult) {
                if (isNMCanUseWithMileage() == false && isApplyDesignNMCanUseWithMileage() == true) {
                    nbp.mileage.inactive();
                }
            } else {
                if ($('#np_is_use').val() == 'T' && document.getElementById('_mileage_acc') != null && document.getElementById('_mileage_acc') != undefined) {
                    alert('네이버마일리지 적립/사용 초기화가 정상적이지 않습니다. 지속발생시 운영자에게 문의 해주세요.');
                }
            }
        }
    } catch (e) {}
}

/**
 * pg모듈에서 리턴해주는 형식으로 변환
 * @param string sCode 코드
 * @return string
 */
function convertResultCode(sCode)
{
    if (sCode == 'OK') {
        return 'E1000';
    } else if (sCode == 'CANCEL') {
        return 'E1001';
    } else if (sCode == 'ERROR') {
        return 'E1002';
    } else {
        return 'E1100';
    }
}

/**
 *모바일 마일리지 최대사용가능 금액(결제금액 + 마일리지 사용금액)
 * @return int
 */
function getNavermileageMaxAmount()
{
    var iMaxAmount = SHOP_PRICE.toShopPrice($('#total_price').val().replace(/,/g, ''));
    iMaxAmount    += check_parseInt(getUseNaverMileageCash());

    return iMaxAmount;
}

var BigDataLog = {
        '_elementId'  : 'bigdata_log',
        '_cookieName' : 'bigdata_log',

        'getcookie' : function(name) {
            if (!document.cookie) return null;

            name = name || this._cookieName;
            var val = null;
            var arr = document.cookie.split((escape(name)+'='));
            if (arr.length >= 2) {
                var arrSub = arr[1].split(';');
                val = unescape(arrSub[0]);
            }

            return val;
        },

        'delcookie' : function(name) {
            name = name || this._cookieName;
            var sCookie  = escape(name) + '=; ';
                sCookie += 'expires='+ (new Date(1)).toGMTString() +'; ';
                sCookie += 'path=/; ';
                sCookie += 'domain='+ document.domain.replace(/^(www|m)\./i, '') +'; ';
            document.cookie = sCookie;
        },

        '_script' : function(src) {
            var node = document.createElement('script');
            node.setAttribute('type', 'text/javascript');
            node.setAttribute('id', this._elementId);
            node.setAttribute('src', src);
            document.body.appendChild(node);
        },

        '_iframe' : function(src) {
            var node = document.createElement('iframe');
            node.setAttribute('id', this._elementId);
            node.setAttribute('src', src);
            node.style.display = 'none';
            node.style.width = '0';
            node.style.height = '0';
            document.body.appendChild(node);
        },

        'save' : function() {
            var src  = '/exec/front/External/Save'; // 환경에 맞게 변경하여 사용
                src += '?referer='+encodeURIComponent(document.referrer);
                src += '&href='+encodeURIComponent(location.href);

            this._script(src);
            //this._iframe(src);
         }
};
if (BigDataLog.getcookie()) {
    BigDataLog.delcookie();
} else {
    if (window.attachEvent) window.attachEvent('onload', function(){BigDataLog.save();});
    else                    window.addEventListener('load', function(){BigDataLog.save();}, false);
}
var COLORCHIP_FRONT = {
    setFrontInit : function()
    {
        $('.xans-product-colorchip').find('.chips').each(function() {

            var sColor = COLORCHIP_FRONT.RGB2Color($(this).css('backgroundColor'));
            var sCursor = '';
            if (COLORCHIP_FRONT.checkValidation(sColor) === true && EC_FRONT_JS_CONFIG_SHOP.aOptionColorchip[sColor] != '') {
                if (EC_SHOP_FRONT_NEW_OPTION_EXTRA_IMAGE.isDisplayImageDesign() === false) {
                    return;
                }
                sCursor = 'pointer';
                $(this).bind('mouseover click', function() {
                    EC_SHOP_FRONT_NEW_OPTION_EXTRA_IMAGE.setImage(EC_FRONT_JS_CONFIG_SHOP.aOptionColorchip[sColor], true);
                });
            }
            $(this).css('cursor', sCursor);
        });
    },

    RGB2Color : function (sRgb)
    {
        try {
            rgb = sRgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            if (rgb === null) {
                return sRgb.toString().toUpperCase();
            } else {
                return '#' + COLORCHIP_FRONT.byte2Hex(rgb[1]) + COLORCHIP_FRONT.byte2Hex(rgb[2]) + COLORCHIP_FRONT.byte2Hex(rgb[3]);
            }
        } catch (e) {
            return '';
        }
    },

    byte2Hex : function (n)
    {
        var nybHexString = "0123456789ABCDEF";
        return String(nybHexString.substr((n >> 4) & 0x0F,1)) + nybHexString.substr(n & 0x0F,1);
    },

    checkValidation : function(sColor)
    {
        var regex = /^#?[0-9A-F]{6}$/i;
        return regex.test(sColor);
    }
};

$(document).ready(function() {
    COLORCHIP_FRONT.setFrontInit();
});

/**
 * 바로구매주문 상품모듈 라이브러리
 */
var EC_SHOP_FRONT_NEW_PRODUCT_DIRECT_BUY = function() {
    // 장바구니 담기
    var setDirectBuyOrderBasket = function ()
    {
        // 바로구매 주문서 아님
        if (EC_FRONT_JS_CONFIG_SHOP.bDirectBuyOrderForm !== true) {
            return;
        }
        // 비회원 구매제한
        if (sIsDisplayNonmemberPrice === 'T' || is_soldout_icon === 'T') {
            return;
        }
        // 1+N 제한
        if (typeof(EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE) !== 'undefined' && EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig.hasOwnProperty(iProductNo) === true) {
            return;
        }
        product_submit('direct_buy', '/exec/front/order/basket/');
    };

    // 장바구니 리셋
    var resetDirectBuyOrderBasket = function ()
    {
        // reset basket
        EC_SHOP_FRONT_ORDERFORM_DIRECTBUY.proc.setOrderForm(TotalAddSale.getDirectBuyParam());
    };

    // 바로구매주문서 접속제한
    var setAccessRestriction = function ()
    {
        if (EC_FRONT_JS_CONFIG_SHOP.bDirectBuyOrderForm !== true) {
            return;
        }
        if (sIsDisplayNonmemberPrice === 'T' || sIsNonmemberLimit === 'T') {
            alert(__('회원만 구매 가능합니다. 비회원인 경우 회원가입 후 이용하여 주세요.'));
            btn_action_move_url('/member/login.html');
        } else if (typeof(EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE) !== 'undefined' && EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig.hasOwnProperty(iProductNo) === true) {
            // 1+N 제한
            alert(sprintf(__('EVENT.ITEM.ORDER.AT.MALL', 'SHOP.JS.FRONT.NEW.PRODUCT.DIRECTBUY'), EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig[iProductNo].bundle_quantity));
            btn_action_move_url('/');
        }
    };
    // 주문가능한 품목데이터가 있는지 확인
    var getValidOptionData = function () {
        var oParam = TotalAddSale.getDirectBuyParam();
        if (oParam['items'] === null){
            return false;
        }
        return true;

    };

    return {
        setDirectBuyOrderBasket : function (iTotalCount) {
            setDirectBuyOrderBasket(iTotalCount);
        },
        setAccessRestriction : function () {
            setAccessRestriction();
        },
        resetDirectBuyOrderBasket : function() {
            resetDirectBuyOrderBasket();
        },
        getValidOptionData : function() {
            return getValidOptionData();
        }
    };
}();
$(document).ready(function(){

});

/**
 * 상품 이미지 확대
 *
 * @package app/Shop
 * @subpackage Resource
 * @author 이장규
 * @since 2012. 1. 19.
 * @version 1.0
 *
 */
var ProductImageZoom = function()
{

    /**
     * 확대 영역 size
     * @var array 너비, 높이
     */
    var aLargeRect = {'width' : 0, 'height' : 0};

    /**
     * 상품상세에 있는 이미지 정보
     * @var array 너비, 높이
     */
    var aOriImage = {'width' : 0, 'height' : 0, 'left' : 0, 'top' : 0};


    /**
     * 초기화 여부 mouse over 하면 true, mouse out 하면 false
     * @var bool
     */
    var bInit = false;



    /**
     * 이미지 확대 준비
     */
    this.prepare = function()
    {
        init();
        bindEvent();
        out();
    };

    /**
     * 초기화
     * @returns 초기화 할 필요 없으면 return true
     */
    var init = function()
    {
        //확대를 시작하면 초기화 필요 없음
        if (bInit == true) return true;

        createLargeRect();//확대영역
        setZoomInfo();
        createSmallRect();//작은 사각형 영역
        setMouseGuide();//마우스를 올려보세요

        bInit = true;
    };

    /**
     * 확대 영역 사각형 만들기
     */
    var createLargeRect = function()
    {
        var sImageSrc = $('.BigImage').attr('src');
        var iLargeWidth = $('.BigImage').width() * 2;
        var iLargeHeight = $('.BigImage').height() * 2;

        if ($('#zoom_image').length < 1) {
            var aOriImagePosition = $('.BigImage').offset();
            var sLargeHtml = '<p class="image_zoom_large"><span class="image_zoom_large_relative"><img id="zoom_image" alt="확대 이미지" /></span></p>';
            $('#zoom_wrap').append(sLargeHtml);
        }
        $('#zoom_image').attr('src', sImageSrc);
        $('#zoom_image').css({
            'width' : iLargeWidth,
            'height' : iLargeHeight
        });
    };

    /**
     * member 변수 set
     */
    var setZoomInfo = function()
    {
        //확대 사각형
        aLargeRect = {'width' : $('.image_zoom_large').width(), 'height' : $('.image_zoom_large').height()};

        //원본 이미지
        var aOriImagePosition = $('.BigImage').offset();
        if (aOriImagePosition != null) {
            aOriImage = {'width' : $('.BigImage').width(), 'height' : $('.BigImage').height(), 'left' : aOriImagePosition.left, 'top' : aOriImagePosition.top};
        }
    };


    /**
     * 작은 사각형 만들기
     */
    var createSmallRect = function()
    {
        if ($('#image_zoom_small').length < 1) {
            $('body').append('<div id="image_zoom_small"></div>');
        }
        var iSmallWidth = (aOriImage.width * aLargeRect.width) / $('#zoom_image').width(); // 작은네모 너비 = (상품이미지 너비 * 큰이미지 너비) / 확대이미지 너비
        var iSmallHeight = (aOriImage.height * aLargeRect.height) / $('#zoom_image').height();


        $('#image_zoom_small').css({
            'width' : iSmallWidth,
            'height' : iSmallHeight
        });
    };


    /**
     * '마우스를 올려보세요' 보여주기
     */
    var setMouseGuide = function()
    {
        var sLang = SHOP.getLanguage();
        if (sLang == 'ja_JP') {
            var iImgWidth = 215;
        } else {
            var iImgWidth = 170;
        }

        var sZoomImage = '//img.echosting.cafe24.com/design/skin/admin/'+sLang+'/txt_product_zoom.gif';

        if ($('#zoomMouseGiude').length < 1) {
            var sGuideHtml = '<span id="zoomMouseGiude" style="display:block; position:relative; width:170px; margin:0 auto;"><img src="'+sZoomImage+'" id="zoomGuideImage" alt="'+__('마우스를 올려보세요.')+'" /></span>';
            $('.BigImage').parent().append(sGuideHtml);
        }

        var aGuideImageSize = {'width' : iImgWidth, 'height' : 27};

        $('#zoomGuideImage').css({
            'position' : 'absolute',
            'top' : aGuideImageSize.height * -1,
            'right' : 0
        });
    };


    /**
     * event binding
     */
    var bindEvent = function()
    {
        //브라우저 resizing 되면 위치값이 바뀜
        $(window).resize(function(){
            init();
            out();
        });

        $('.BigImage, #image_zoom_small, #zoomGuideImage').bind('mousemove mouseover', function(e){
            move(e);
        });


        $('.BigImage, #image_zoom_small').bind('mouseout', function(){
            out();
        });

    };


    /**
     * 상품 이미지 밖으로 마우스 이동
     */
    var out = function()
    {
        $('#image_zoom_small, .image_zoom_large').hide();
        $('#zoomMouseGiude').show();
        bInit = false;
    };

    /**
     * 상품 이미지 내에서 마우스 이동
     * @param e event
     */
    var move = function(e)
    {
        //썸네일 이미지에 마우스를 over 하면 이미지가 바뀌기 때문에 초기화 해야 함
        init();

        $('#zoomMouseGiude').hide();

        var aMousePosition = getMousePosition(e);


        //작은 사각형 이동
        $('#image_zoom_small').css({
            'left' : aMousePosition.left,
            'top' : aMousePosition.top,
            'display' : 'block'
        });

        $('.image_zoom_large').show();


        //확대영역 이동
        $('#zoom_image').css({
            'left' : (aMousePosition.left - aOriImage.left) * -2,
            'top' : (aMousePosition.top - aOriImage.top) * -2
        });

    };

    /**
     * 작은 네모의 좌표 구하기
     * @param e 이벤트
     * @returns array left, top
     */
    var getMousePosition = function(e)
    {
        var iSmallLeftMax = aOriImage.left + aOriImage.width - $('#image_zoom_small').outerWidth();
        var iSmallTopMax = aOriImage.top + aOriImage.height - $('#image_zoom_small').outerHeight();

        //마우스 커서가 작은 네모의 가운데로 가게 하기 위해
        var iSmallX = e.pageX - parseInt($('#image_zoom_small').outerWidth() / 2);//작은 사각형 위치 = 마우스 X좌표 - (작은 사각형 / 2)
        var iSmallY = e.pageY - parseInt($('#image_zoom_small').outerHeight() / 2);

        //max 작은 사각형 위치
        if (iSmallX > iSmallLeftMax) iSmallX = iSmallLeftMax;
        if (iSmallY > iSmallTopMax) iSmallY = iSmallTopMax;

        //min 작은 사각형 위치
        if (iSmallX < aOriImage.left) iSmallX = aOriImage.left;
        if (iSmallY < aOriImage.top) iSmallY = aOriImage.top;

        return {'left' : iSmallX, 'top' : iSmallY};
    };

};


$(document).ready(function()
{
    var imageZoom = new ProductImageZoom();
    imageZoom.prepare();
});

$(document).ready(function()
{
    // 썸네일 이미지에 대한 마우스 오버 액션 (sUseAddimageAction: 추가 이미지 액션)
    $('.ThumbImage').mouseover(function() {
        if (ImageAction.sUseAddimageAction === 'O') {
            ImageAction.setThumbImageAction($(this));
        }
    });

    // 썸네일 이미지에 대한 마우스 클릭 액션 (sUseAddimageAction: 추가 이미지 액션)
    $('.ThumbImage').click(function() {
        if (ImageAction.sUseAddimageAction === 'C') {
            ImageAction.setThumbImageAction($(this));
        }
    });

    ImagePreview.eBigImgSrc = $('.BigImage').attr('src');

    var bPreview = ($.data(document,'Preview') == 'T') ? true : false;

    // 제일 처음 로딩시 이미지값 저장해놓음..뉴상품에서는 small == big 이지만 구상품 스킨에서는
   // tiny와 big의 이미지명 틀림!!
    ImagePreview.eBigImgSrc = $('.BigImage').attr('src');

    if (bPreview === true) {
        ImagePreview.Init();
    }
});

var ImageAction = {
    // 확대 이미지
    sBigSrc: $('.BigImage').attr('src'),

    // 추가 이미지 액션 (기본값 - O: 마우스 오버)
    sUseAddimageAction: 'O',

    // 썸네일 마우스 액션 (마우스 오버 및 클릭에 대한 중복으로 인해 분기)
    setThumbImageAction: function(target)
    {
        $('#prdDetailImg').attr('rel', $(this).parent().index());

        var sSrc = target.attr('src');

        if (sSrc.indexOf('/product/tiny/') > 0) {
            if (sSrc.substring(sSrc.lastIndexOf('/')) === this.sBigSrc.substring(this.sBigSrc.lastIndexOf('/'))) {
                sSrc = sSrc.replace('/product/tiny/', '/product/big/');
            } else {
                sSrc = ImagePreview.eBigImgSrc;
            }

            $('.BigImage').attr('src', sSrc);

            // 일단 복잡한 과정은 제외하고 파일 교체만 처리
        } else if (sSrc.indexOf('/product/small/') > 0) {
            if (sSrc.substring(sSrc.lastIndexOf('/')) === this.sBigSrc.substring(this.sBigSrc.lastIndexOf('/'))) {
                sSrc = sSrc.replace('/product/small/', '/product/big/');
            } else {
                sSrc = ImagePreview.eBigImgSrc;
            }

            $('.BigImage').attr('src', sSrc);
        } else if (sSrc.indexOf('/thumb/') > 0) {
            $('.BigImage').attr('src', ImagePreview.eBigImgSrc);
        } else {
            // 추가 이미지
            sSrc = sSrc.replace('/product/extra/small/', '/product/extra/big/');

            $('.BigImage').attr('src', sSrc);

            // 단일 선택형 + 추가 이미지 액션이 C(마우스 클릭)인 경우 추가 이미지에 선택에 대한 품목 선택 처리
            if (oSingleSelection.isItemSelectionTypeS() === true && this.sUseAddimageAction === 'C') {
                // 품목 코드가 있을 경우 해당되는 UI 선택
                if (target.attr('item_code') !== '') {
                    EC_SHOP_FRONT_NEW_OPTION_COMMON.setValueByAddImage(target.attr('item_code'));
                }
            }
        }
    }
};

var ImagePreview =
{
    bNewProduct : false,
    eTarget : null,
    eBigImgSrc : null,
    Init : function()
    {
        this.eTarget = $('.xans-product-image img.BigImage');
        this.eTarget.parent().addClass('cloud-zoom');
        this.showNotice();
        ImagePreview.setZoom();

    },
    showNotice : function()
    {
        var sLang = SHOP.getLanguage();
        if (sLang == 'ja_JP') {
            var iImgWidth = 107;
        } else {
            var iImgWidth = 85;
        }

        var sZoomImage = '//img.echosting.cafe24.com/design/skin/admin/'+sLang+'/txt_product_zoom.gif';

        var sLeft = this.eTarget.width() / 2 - iImgWidth;
        $('<div id="zoomNotice"><img src="'+sZoomImage+'"></div>').css(
            {
                'height' : '0px',
                'position' : 'relative',
                'opacity' : '0.75',
                'KHTMLOpacity' : '0.75',
                'MozOpacity' : '0.75',
                'filter' : 'Alpha(opacity=75)',
                'top' : '-27px',
                'margin-left' : sLeft
            }).appendTo(this.eTarget.parent());
    },
    setZoom : function()
    {
        $('.cloud-zoom').mouseover(function()
        {
            $('.cloud-zoom').CloudZoom();
        });
    },
    //ECHOSTING-236342 preview(확대보기) 기능에서 상세페이지 연결 오류
    setIframeSrcReplaceProductNo : function(iProductNo)
    {
        if (typeof(iProductNo) === 'undefined' || iProductNo == 0) {
            return;
        }

        var oTargetIframe = $(parent.document).find('#modalContent');

        if (typeof($(oTargetIframe).attr('src')) === 'undefined') {
            return;
        }

        // 목록에서의 상품 확대 보기시 상위 iframe src의 파라미터 product_no 를 다음,이전 화면 이동시 해당 상품번호 받아와 변환
        var sUrlReplaceProductNo = $(oTargetIframe).attr('src').replace(/product_no=[\d]+/,'product_no=' + iProductNo);

        $(oTargetIframe).attr('src', sUrlReplaceProductNo);
    },
    viewProductBtnClick : function(sActionType)
    {
        if (typeof(iProductNo) === 'undefined' || $.inArray(sActionType, ['next', 'prev']) < 0) {
            return;
        }

        this.bNewProduct = true;
        var sParamUrl = ImagePreview.getViewProductUrl(iProductNo);
        var aMatchResult = ImagePreview.getLocationPathMatchResult();
        var sRefDoc = (aMatchResult !== null) ? 'product' : location.pathname;

        $.ajax({
            url : '/exec/front/Product/Detailnavi'+ sParamUrl + '&refdoc='+ sRefDoc +'&navi_action='+ sActionType,
            type : 'GET',
            async : false,
            dataType : 'json',
            success : function(data) {
                if (data.result === true) {
                    location.href = ImagePreview.getViewProductUrl(data.response.product_no, data.response.seo_url_link);
                } else {
                    if (data.response.empty_msg !== null) {
                        alert(data.response.empty_msg);
                    }
                }
            }
        });
    },
    getLocationPathMatchResult : function()
    {
        var sPath = document.location.pathname;
        var sPattern = /^\/product\/(.+)\/([0-9]+)(\/.*)/;
        return sPath.match(sPattern);
    },
    getViewProductUrl : function(iProductNo, sSeoUrl)
    {
        var aMatchResult = ImagePreview.getLocationPathMatchResult();
        var bExistSeoUrl = (sSeoUrl !== '' && typeof(sSeoUrl) !== 'undefined') ? true : false;
        var sResultUrl = '';

        ImagePreview.setIframeSrcReplaceProductNo(iProductNo);

        if (aMatchResult !== null) {
            if (bExistSeoUrl === true) {
                sResultUrl = sSeoUrl;
            } else {
                sResultUrl = (this.bNewProduct === false) ? ImagePreview.getOldProductDetailUrl(iProductNo) : '?product_no=' + iProductNo + '&cate_no='+ iCategoryNo + '&display_group=' + iDisplayGroup;
            }
        } else {
            var sSearchRelplace = location.search.replace(/product_no=[\d]+/,'product_no=' + iProductNo);
            sResultUrl = (this.bNewProduct === true) ? sSearchRelplace : location.pathname + sSearchRelplace;
        }

        return sResultUrl;
    },
    getOldProductDetailUrl : function(iProductNo)
    {
        var sSearchString = '';

        if (location.search) {
            sSearchString = '&' + location.search.replace(/\?/,'');
        }

        return '/front/php/product.php?product_no=' + iProductNo + sSearchString;
    }
};

// 이전, 다음 상품 보기
function viewProduct(iProductNo, sSeoUrl)
{
    location.href = ImagePreview.getViewProductUrl(iProductNo, sSeoUrl);
}


// 팝업
function product_popup(sLink, sName, sOption, ele)
{
    var aMatchResult = ImagePreview.getLocationPathMatchResult();
    var sSearchQuery = location.search;

    if (aMatchResult) {
        if (sSearchQuery) {
            sSearchQuery = sSearchQuery + '&product_no=' + aMatchResult[2];
        } else {
            sSearchQuery = '?product_no=' + aMatchResult[2];
        }
    }

    try {
        var sDetailUri = '';
        if (ele) {
            var iOrder = $(ele).attr('rel');
            if (window.location.href.indexOf('/surl/P/') != -1) {
                sDetailUri = '?product_no=' + parseInt(window.location.href.split('/surl/P/')[1]) + '&order=' + iOrder;
            } else {
                sDetailUri = sSearchQuery + '&order=' + iOrder;
            }
        }
        window.open('/' + sLink + sDetailUri, sName, sOption);
    } catch (e) {
        window.open('/' + sLink + sSearchQuery, sName, sOption);
    }
}

var STOCKLAYER = (function() {

    var sUrl = '/product/stocklayer.html';

    //세트 상품 여부
    function isSetProdct()
    {
        if (typeof(set_option_data) === 'undefined') {
            return false;
        }

        return true;
    }

    //모든 재고 레이어 Element Get
    function getAllStockLayer()
    {
        return $('.ec-shop-detail-stock-layer');
    }

    return {
        init : function() {
            try {
                $('a[name="EC-stockdesign"]').live('click', function (e) {
                    e.preventDefault();
                    var iProductNo = $(this).attr('product_no');
                    var sPageType = $(this).attr('page_type');
                    STOCKLAYER.closeStockLayer();

                    if ($(this).parent().find('.ec-shop-detail-stock-layer').length == 0) {
                        var oParam = {};

                        oParam['product_no'] = iProductNo;
                        oParam['page_type'] = sPageType;


                        if (sPageType === 'detail') {
                            if (isSetProdct() === true) {
                                oParam['stockData'] = $.parseJSON(set_option_data);
                                oParam['is_set_product'] = 'T';
                            } else {
                                oParam['stockData'] = $.parseJSON(option_stock_data);
                                oParam['is_set_product'] = 'F';
                            }
                        }
                        var oHtml = $('<div>');
                        oHtml.addClass('ec-shop-detail-stock-layer');
                        $(this).parent().append(oHtml);
                        $.ajax({
                            type: 'POST',
                            url: sUrl,
                            data: oParam,
                            success: function (sHtml) {
                                sHtml = sHtml.replace(/[<]script( [^ ]+)? src=\"[^>]*>([\s\S]*?)[<]\/script>/g, "");
                                oHtml.html(sHtml);
                            },
                            error: function (e) {
                                __('오류발생');
                            }
                        });
                    } else {
                        $(this).parent().find('.ec-shop-detail-stock-layer').show();
                    }

                    e.preventDefault();
                });
            }catch(e) {}
        },

        closeStockLayer : function() {
            var $oAllStockLayer = getAllStockLayer();
            $oAllStockLayer.hide();
        }
    };
})();

$(document).ready ( function() {
    STOCKLAYER.init();
});

//상품 옵션 id
var product_option_id = 'product_option_id';
$(document).ready(function(){
    //ECHOSTING-77239 - 80113 : 배송준비중관리에서 특정된 두개의 기호가 포함된 옵션값만 깨져서 노출

    //표시된 옵션 선택박스에 대해 이벤트바인드 정리

    //추가입력 옵션 ; 제거 > ECHOSTING-77239건과 동일 이슈로 인해 역슬래시 기호 추가(ECHOSTING-182704)
    $('.input_addoption, .rel_input_addoption').blur(function(){
        var regex = /[\;\\*\|]/g;
        if (regex.test($(this).val()) === true) {
            alert(__('특수문자는 입력할 수 없습니다.'));
            $(this).val($(this).val().replace(regex, ''));
        }
    });


    //추가옵션 글자수 체크
    try {
        $('.rel_input_addoption').live('keyup', function() {
            NEWPRD_ADD_OPTION.checkProductAddOptionWord(this);
        });
    } catch (e) {}
});

// 뉴상품에 뉴상품 스킨인지 확인하는 메소드 (뉴상품인데 구상품인 경우에는 false)
function isNewProductSkin()
{
    return $('#totalProducts').length > 0;
}

// 구스킨을 사용할경우 총 금액 계산
function setOldTotalPrice()
{

    if (product_price_content == true) {
        return;
    }

    // 판매가 회원 공개인 경우 옵션 값 계산 필요없음!
    if (sIsDisplayNonmemberPrice === 'T') {
        $('#span_product_price_text').html(sNonmemberPrice);
        return;
    }

    var iQuantity = 1;
    if (typeof($(quantity_id).val()) != 'undefined' ) {
        iQuantity = parseInt($(quantity_id).val(),10);
    }

    var iOptionPrice = 0;
    if (option_type === 'T') {
        iOptionPrice = SHOP_PRICE.toShopPrice(product_price);
    }
    var aStockData = new Array();
    if (typeof(option_stock_data) != 'undefined') {
        aStockData = $.parseJSON(option_stock_data);
    }

    // 복합형
    if (option_type == 'T') {
        // 일체선택형
        if (item_listing_type == 'S') {
            sOptionId = ITEM.getOldProductItemCode();
            if (sOptionId !== false) {
                iOptionPrice += (aStockData[sOptionId].option_price - product_price);
            }
        } else {
            $('select[id^="product_option_id"][value!="*"] option:selected').each(function() {
                var sOptionId = $(this).val();
                if (typeof(aStockData[sOptionId]) != 'undefined' && aStockData[sOptionId].stock_price != 0) {
                    iOptionPrice += (aStockData[sOptionId].option_price - product_price);
                }
            });
        }
    } else if (Olnk.isLinkageType(option_type) === true) { // 저장형
        var iPrdPrice = SHOP_PRICE.toShopPrice(product_price);
        var iOptPrice = 0;
        var sPrice = '';
        $('select[id^="product_option_id"]').each(function() {
            var iValNo = parseInt($(this).val());
            if (isNaN(iValNo) === true) {
                return;
            }

            iOptPrice += SHOP_PRICE.toShopPrice(aStockData[iValNo].stock_price);
        });

        iOptionPrice = iPrdPrice + iOptPrice;
    } else {
        // 단독형일때는 구상품과 다르게 품목단위로 계산이 필요함.
        $('select[id^="product_option_id"][value!="*"] option:selected').each(function() {
            var sOptionId = $(this).val();
            if (typeof(aStockData[sOptionId]) != 'undefined' && aStockData[sOptionId].stock_price != 0) {
                    iOptionPrice += aStockData[sOptionId].option_price;
            } else {
                iOptionPrice += aStockData[sOptionId].option_price;
            }
        });
    }
    if (option_type === 'F' && iOptionPrice === 0) {
        iOptionPrice = product_price;
    }


    iPrice = getProductPrice(iQuantity, iOptionPrice, null, null, function(iPrice){
        $('#span_product_price_text').html(SHOP_PRICE_FORMAT.toShopPrice(iPrice));
    });

}

/**
 * 뉴상품 프론트 옵션을 관리하는 객체
 * 앞으로 전역으로 함수를 선언하지 말고 여기에 선언
 */
var NEWPRD_OPTION = {
    DELIMITER_SEMICOLON:';',
    DELIMITER_SLASH:'/',
    iOptionBoxSequence : 0,
    /**
     * 셀렉트 엘리먼트의 첫번째 옵션으로 변경
     * @param oSelect object 셀렉트 엘리먼트 객체
     */
    resetSelectElement: function(oSelect) {
        if (typeof(oSelect) !== 'object' || typeof(oSelect.is) !== 'function' || oSelect.is('select') !== true) {
            return false;
        }

        if (this.setOlnkOptionReset(oSelect) !== false ) {
            EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(oSelect, '*');
        }
    },

    /**
     * 옵션 셀렉트박스의 첫번째/두번째 값인지
     * @param  sOptionValue 선택값
     */
    isOptionSelectTitleOrDivider: function(sOptionValue) {
        return ($.inArray(sOptionValue, ['*', '**']) !== -1) ? true : false;
    },

    setOlnkOptionReset: function(oSelect) {
        // option code가 있으면 연동형옵션
        // 만일을 대비해서 하단
        if (oSelect.attr('option_code') != undefined && oSelect.attr('option_code') !== '' ) {

            var aOptionIdArray = oSelect.attr('id').split('_');
            var iOptionLength = aOptionIdArray.length;
            var sOptionIdTxt = 'product_option_id';
            var iOptionNum = 0;
            var sOptionButtonIdTxt = 'option_push_button';

            if (iOptionLength === 3 ) { // product_option_idX
                iOptionNum = oSelect.attr('id').replace(sOptionIdTxt,'');
            } else if (iOptionLength === 5 ) { //addproduct_option_id_product_no_x
                sOptionIdTxt = 'addproduct_option_id_' + aOptionIdArray[3] + '_';
                iOptionNum = aOptionIdArray[4];
                sOptionButtonIdTxt = 'add_option_push_button_'+aOptionIdArray[3];
            }

            // 연동형 옵션의 버튼형인 경우 리셑 처리 없이 그냥 리턴
            if (Olnk.getOptionPushbutton($('#'+sOptionButtonIdTxt)) === true) {
                return false;
            }
        }
        return true;
    }
};

/**
 * 뉴상품 프론트 추가옵션을 관리하는 객체
 * 앞으로 전역으로 함수를 선언하지 말고 여기에 선언
 */
var NEWPRD_ADD_OPTION = {
    /**
     * 추가옵션 리스트 리턴 (필수, 선택모두)
     * @returns array 추가옵션 리스트
     */
    getCurrentAddOption: function() {
        var aAddOption = [];

        $(".input_addoption").not('[name^=addproduct_add_option_name_]').each(function(){
            aAddOption.push($(this).val());
        });

        return aAddOption;
    },

    getAddOptionValue: function(sDataAddOption) {
        return (oSingleSelection.isItemSelectionTypeS() === true) ? this.getCurrentAddOption().join(NEWPRD_OPTION.DELIMITER_SEMICOLON) : unescape(sDataAddOption);
    },

        /**
         * 현재 작성되어있는 추가옵션으로 품목에 표시할 타이틀 리턴
         * @param aAddOption array 추가옵션 리스트
         * @returns string 현재 작성된 추가옵션 타이틀
         */
    getCurrentAddOptionTitle: function(aAddOption) {
        var aAddOptionTitle = [];

        $.each(aAddOption, function(iIdx, sValue){

            if (!sValue) {
                return true;
            }

            var sOptionName = add_option_name[iIdx];
            if (sOptionName !== undefined) {
                var sAddOptionTitle = sOptionName+NEWPRD_OPTION.DELIMITER_SLASH+sValue;
                aAddOptionTitle.push(sAddOptionTitle);
            }

        });

        var delimeter = ', ';
        return (aAddOptionTitle.length > 0) ? aAddOptionTitle.join(delimeter)+delimeter : '';
    },

    /**
     * 셀렉트 엘리먼트의 첫번째 옵션으로 변경
     * @param oSelect object 셀렉트 엘리먼트 객체
     */
    resetSelectElement: function(oSelect) {
        return NEWPRD_OPTION.resetSelectElement(oSelect);
    },

    /**
     * 품목별 추가옵션 처리를위한 모든 추가옵션항목을 폼에 셋팅
     */
    setItemAddOptionName: function(frm) {
        if (!add_option_name) {
            return;
        }

        frm.append(getInputHidden('item_add_option_name', add_option_name.join(NEWPRD_OPTION.DELIMITER_SEMICOLON)));
    },

    /**
     * 품목별 추가옵션을 셋팅
     * @param sItemCode string 품목코드
     * @param sItemAddOption string 품목별 추가옵션 입력값
     */
    setItemAddOption: function(sItemCode, sItemAddOption, frm) {

        if (!add_option_name || !sItemAddOption) {
            return;
        }

        var aAddOption = sItemAddOption.split(NEWPRD_OPTION.DELIMITER_SEMICOLON);
        var iLength = aAddOption.length;

        if (iLength < 1) {
            return;
        }

        for (var iIdx=0; iIdx<iLength; iIdx++) {
            frm.prepend(getInputHidden('item_option_add['+sItemCode+']['+iIdx+']', aAddOption[iIdx]));
        }
    },

    /**
     * 품목기반의 추가옵션타입을 사용해야하는지
     * @returns bool 품목기반의 추가옵션이면 true 아니면 false
     */
    isItemBasedAddOptionType: function() {
        // 옵션이 없을때
        if (has_option !== 'T') {
            return false;
        }

        // 뉴스킨이 아닐때
        if (isNewProductSkin() !== true) {
            return false;
        }

        // 연동형 옵션일때 (전역:sOptionType)
        if (Olnk.isLinkageType(sOptionType) === true) {
            return false;
        }

        return true;
    },

    isValidAddOptionSelect : function(frm, bIsSetProduct) {
        var bReturn = true;
        var iCount = 0;
        var sMsg = '';
        var oObject = null;

        $('input[class^="option_add_box_"][name="basket_add_product[]"]').each(function() {
            var sAddOptionId = $(this).attr('id').replace('_id','');
            var iAddProductNo = parseInt($(this).attr('class').substr($(this).attr('class').lastIndexOf('_')+1));
            var iQuantity = $('#'+sAddOptionId+'_quantity').val();
            var sItemCode = $(this).val();
            $('select[name="addproduct_option_name_'+iAddProductNo+'"][required="true"]:visible').each(function() {
                if ($(this).val() == '*' || $(this).val() == '**') {
                    sMsg = __('필수 옵션을 선택해주세요.');
                    oObject = $(this);
                    bReturn = false;
                    return false;
                }
            });
            if (bReturn === false) {
                return false;
            }

            frm.append(getInputHidden('selected_add_item[]', iQuantity+'||'+sItemCode));

            if (bIsSetProduct === true) {
                bResult = ProductSetAction.checkAddProductAddOption('addproduct_add_option_id_'+iAddProductNo);
            } else {
                bResult = checkAddOption('addproduct_add_option_id_'+iAddProductNo);
            }
            if (bReturn === false) {
                return false;
            }
            iCount++;
        });

        return {'result' : bReturn, 'count' : iCount, 'message' : sMsg, 'object' : oObject};
    },

    isValidRelationProductSelect : function(frm, oObj, bIsMainProductCheck) {
        var bReturn = true;
        var iCount = 0;
        var sMsg = '';
        var oObject = null;
        var sFailType = '';

        $('input[name="basket_info[]"]:checked').each(function() {
            var iRelationProductNum = $(this).val().substr(0, $(this).val().indexOf('|'));
            var eQuantity = $('#quantity_' + iRelationProductNum);
            var eOption = $('select[name="option_' + iRelationProductNum + '[]"]');

            var aValue = $(this).val().split('|');
            var sOptionType = aValue[6]; // appShopUtilNewProductFetchRelation::getCheckboxForm참조
            var sIsAddOptionName = aValue[8]; //관련상품 추가옵션 여부
            var sRelationProductName = decodeURIComponent(aValue[4]); //관련상품명
            var sIsProductPriceContent = aValue[9]; //관련상품 판매가 대체문구
            var user_option_id = 'user_option_'; //관련상품 추가옵션 id

            if (sIsProductPriceContent === 'T') {
                sMsg = sprintf(__('%s 상품은 구매할 수 있는 상품이 아닙니다.'), sRelationProductName);
                NEWPRD_ADD_OPTION.checkVaildRelationProductObject(oObj, sMsg, bIsMainProductCheck, this);
                sFailType = 'bProductPriceContent';
                oObject = $(this);
                iCount++;
                bReturn = false;
                return false;
            }

            if (NEWPRD_ADD_OPTION.checkVaildRelationProductQuantity(iRelationProductNum, this) === false) {
                sFailType = 'bRelationQuantity';
                oObject = $(this);
                iCount++;
                bReturn = false;
                return false;
            }

            if (eQuantity.attr('item_code')) {
                // 단품인가
                frm.append(getInputHidden('relation_item[' + iCount + ']', eQuantity.val()+'||'+eQuantity.attr('item_code')));
                iCount++;
            } else {
                // 품목이 있는가
                bReturn = true;
                // 조합/분리 형의 경우 value_mapper가 있어야한다. 있으면 가서 쓰고 없어서 undefined가 뜨면 catch를 실행 - 억지코드임.
                try {
                    var aOptionMapper = $.parseJSON(eval('sOptionValueMapper'+iRelationProductNum));
                    var aOptionValue = new Array();
                    eOption.each(function() {
                        if ($(this).is('[required="true"]') === true && ($(this).val() == '*' || $(this).val() == '**')) {
                            sMsg = __('필수 옵션을 선택해주세요.');
                            NEWPRD_ADD_OPTION.checkVaildRelationProductObject(oObj, sMsg, bIsMainProductCheck, this);
                            sFailType = 'sRequiredVaild';
                            oObject = $(this);
                            iCount++;
                            bReturn = false;
                            return false;
                        } else {
                            aOptionValue.push($(this).val());
                        }
                    });
                    sOptionValue = aOptionValue.join('#$%');
                    var sItemCode = aOptionMapper[sOptionValue];
                } catch(e) {
                    eOption.each(function() {
                        if ($(this).is('[required="true"]') === true && ($(this).val() == '*' || $(this).val() == '**')) {
                            sMsg = __('필수 옵션을 선택해주세요.');
                            NEWPRD_ADD_OPTION.checkVaildRelationProductObject(oObj, sMsg, bIsMainProductCheck, this);
                            sFailType = 'sRequiredVaild';
                            oObject = $(this);
                            iCount++;
                            bReturn = false;
                            return false;
                        }
                    });
                    var sItemCode = eOption.val();
                }
                if (bReturn === true) {

                    if (Olnk.isLinkageType(eQuantity.attr('option_type')) === false) {
                        if (sOptionType === 'F') {
                            // 독립형
                            eOption.each(function() {
                                frm.append(getInputHidden('relation_item[' + iCount + ']', eQuantity.val()+'||'+$(this).val()));
                                iCount++;
                            });
                        } else {
                            // 조합형
                            frm.append(getInputHidden('relation_item[' + iCount + ']', eQuantity.val()+'||'+sItemCode));
                            iCount++;
                        }
                    } else  {
                        // 연동형
                        var _sProductCode = eQuantity.attr('product_code');
                        var _iQuantity = eQuantity.val();

                        var _sItemCode = _sProductCode + '000A';
                        var _aItemValueNo = Olnk.getSelectedItemForBasket(_sProductCode, eOption, _iQuantity);

                        frm.append(getInputHidden('relation_item[' + iCount + ']', _iQuantity+'||'+_sItemCode));
                        frm.append(getInputHidden('relation_item_by_etype[' + iCount + ']', $.toJSON(_aItemValueNo)));
                        iCount++;
                    }
                } else {
                    return false;
                }
            }

            if (typeof(rel_add_option_data) !== 'undefined' && $.trim(rel_add_option_data) !== '') {
                var aRelAddOptData = $.parseJSON(rel_add_option_data);
                var sRelAddOptName = '' + aRelAddOptData[iRelationProductNum] + '';
                var aRelAddOptNameData = sRelAddOptName.split('#$%');
            }

            if (sIsAddOptionName === 'T' && $(aRelAddOptNameData).length > 0) {
                $(aRelAddOptNameData).each(function(iRelationIndex) {
                    var sAddOptionKey  = iRelationProductNum + '_' + iRelationIndex;
                    var sRelAddOptionId = '#' + user_option_id + sAddOptionKey;

                    if ($.trim($(sRelAddOptionId).val()) === '') {
                        if ($(sRelAddOptionId).attr('require') === 'T') {
                            sMsg = __('추가 옵션을 입력해주세요.');
                            NEWPRD_ADD_OPTION.checkVaildRelationProductObject(oObj, sMsg, bIsMainProductCheck, sRelAddOptionId);
                            oObject = $(sRelAddOptionId);
                            sFailType = 'sRelAddOptionValid';
                            bReturn = false;
                            return false;
                        }
                    }
                    frm.append(getInputHidden('rel_option_add[' + sAddOptionKey +']',$(sRelAddOptionId).val()));
                    frm.append(getInputHidden('rel_add_option_name[' + sAddOptionKey + ']',aRelAddOptNameData[iRelationIndex]));
                });
                if (bReturn === false) {
                    return false;
                }
             }
        });

        if ($('input[name="basket_info[]"]:checked').length >= 0) {
            frm.append(getInputHidden('relation_product', 'yes'));
        }

        return {'result' : bReturn, 'count' : iCount, 'message' : sMsg, 'object' : oObject, 'sFailType' : sFailType};
    },

    /**
     * 단독 구매 관련 유효성 검증
     */
    checkVaildIndividualMsg : function(oValidResultData, sBuyType, oObject)
    {
        var bReturn = true;
        var sBuyValidMsg = '본상품의 옵션이 선택되지 않았습니다. \n 선택한 상품만 구매하시겠습니까?';
        var sCartValidMsg = '본상품의 옵션이 선택되지 않았습니다. \n 선택한 상품만 장바구니에 담으시겠습니까?';
        var sBuyTypeMessage = (sBuyType == true) ? sBuyValidMsg : sCartValidMsg;

        if (this.checkRelationProduct(oObject) === false) {
            bReturn = false;
            return false;
        }

        if (oValidResultData.sFailType !== '') {
            bReturn = false;
            return false;
        }

        if (confirm(__('' + sBuyTypeMessage + '')) === false) {
            bReturn = false;
            return false;
        }

        return bReturn;
    },

    /**
     * 단독 구매 관련 데이터 검증
     */
    getIndividualValidCheckData : function(oValidRelationProduct, oValidAddProduct, bIsMainProductEmpty, frm)
    {
        var bIsCheckRelationProduct = (oValidRelationProduct.count > 0) ? true : false;
        var bIsCheckAddProduct = (oValidAddProduct.count > 0) ? true : false;
        var bIsIndividual = false;
        // 메인상품의 존재여부
        if (isNewProductSkin() === true && bIsMainProductEmpty === true) {
            if (is_individual_buy === 'T') {
                bIsIndividual = (bIsCheckAddProduct === true || bIsCheckRelationProduct === true) ? true : false;
            } else {
                if (bIsCheckAddProduct === false) {
                    bIsIndividual = bIsCheckRelationProduct;
                }
            }
        }
        var bIndividualBuyResult = (bIsIndividual === true) ? 'T' : 'F';
        frm.append(getInputHidden('is_individual', bIndividualBuyResult));

        return {
            'isValidInidual' : bIsIndividual,
            'isVaildRelationProduct' : bIsCheckRelationProduct,
            'isVaildAddProduct' : bIsCheckAddProduct,
            'sFailType' : oValidRelationProduct.sFailType
        };
    },

    /**
     * 관련상품 선택여부 확인
     */
    checkRelationProduct : function(oObj, sType)
    {
        var aActionType = [1, 2];

        if ($.inArray(sType, aActionType) === -1) {
            return true;
        }

        // @see ECHOSTING-358854
        // 관련상품 구매형 모듈에서 관련상품이 선택되지 않더라도 본상품이 단품이라면 구매(장바구니)가 가능해야 함
        if (typeof(oObj) === 'undefined' && $('input[name="basket_info[]"]:checkbox:checked').length <= 0 && has_option !== 'F') {
            alert(__('상품을 선택해주세요.'));
            return false;
        }

        return true;
    },

    /**
     * 관련상품 추가옵션 글자수 제한 체크
     */
    checkProductAddOptionWord : function (oObj)
    {
        var iLimit = $(oObj).attr('maxlength');
        var sId = $(oObj).attr('id');
        var sVal = $(oObj).val();
        var iStrLen = sVal.length;

        if (iStrLen > iLimit) {
            alert(sprintf(__('메세지는 %s자 이하로 입력해주세요.'), iLimit));
            $('#'+sId).val(sVal.substr(0, sVal.length-1));
            return;
        }

        $('#'+sId).parent().find('.txtLength').text(iStrLen);
    },

    /**
     * 메인상품 여부확인에 따른 얼럿메시지 노출 처리
     */
    checkVaildRelationProductObject : function(oObj, sMessage, bIsMainProductCheck, oSelected)
    {
        if (isNewProductSkin() === true && this.checkRelationProduct(oObj) === true && (bIsMainProductCheck === true || this.isSoldOutMainProduct() === true)) {
            alert(sMessage);
            $(oSelected).focus();
        }
    },

    /**
     * 본상품의 품절 아이콘이 존재하고 추가구성상품의 단독구매 여부 및 관련상품
     */
    checkSoldOutProductValid : function(oObj)
    {
        if (NEWPRD_ADD_OPTION.isSoldOutMainProduct() === true) {
            if ($('input[class^="option_add_box_"][name="basket_add_product[]"]').length > 0 || $('input[name="basket_info[]"]:checkbox:checked').length > 0) {
                return true;
            } else {
                return false;
            }
        } else if (isNewProductSkin() === true && is_soldout_icon === 'T' && this.checkRelationProduct(oObj) === true) {
            return true;
        }

        return false;
    },

    /**
     * 본상품의 품절여부 (판매가 대체문구 및 판매안함 상품)
     */
    isSoldOutMainProduct : function()
    {
        if (isNewProductSkin() === true && (is_soldout_icon === 'T' || product_price_content == true)) {
            return true;
        }

        return false;
    },

    /**
     * 관련상품 수량 체크 유효성 검증
     */
    checkVaildRelationProductQuantity : function(iRelationProductNum)
    {
        var bReturn = true;
        var aQuantityInfo = $.parseJSON(relation_product);
        var sRelationQuantityId = 'quantity_' + iRelationProductNum;
        var oProductQuantity  = $('input[id^= "'+ sRelationQuantityId +'"]');
        var iRelationQuantity = oProductQuantity.val();

        var iProductMinimum = parseInt(aQuantityInfo[iRelationProductNum].product_min, 10);
        var iProductMaximum = parseInt(aQuantityInfo[iRelationProductNum].product_max, 10);

        if (iRelationQuantity > iProductMaximum && iProductMaximum > 0) {
            alert(sprintf(__('최대 주문수량은 %s개 입니다.'), iProductMaximum));
            oProductQuantity.val(iProductMaximum);
            $(oProductQuantity).focus();
            return false;
        }

        if (iRelationQuantity < iProductMinimum) {
            alert(sprintf(__('최소 주문수량은 %s개 입니다.'), iProductMinimum));
            oProductQuantity.val(iProductMinimum);
            $(oProductQuantity).focus();
            return false;
        }

        if (bReturn === false) {
            return false;
        }

        return bReturn;
    },

    /**
     * 구스킨 > 관련상품 및 추가 구성상품용 유효성 검증 메시지
     */
    checkExistingValidMessage : function(oObj, oAddProductCount)
    {
        var sValidMsg = false;

        // 뉴스킨은 관계 없음
        if (isNewProductSkin() === true) {
            return sValidMsg;
        }

        if (typeof(oObj) === 'undefined') {
            sValidMsg = __('본상품과 함께 구매가 가능합니다. \n 본상품의 필수 옵션을 선택해 주세요.');
        } else if (oAddProductCount.count > 0) {
            //추가구성상품의 선택되어있으면서 본상품의 옵션이 선택 안되었을때
            sValidMsg = __('본상품의 필수 옵션을 선택해 주세요');
        }

        return sValidMsg;
    },

    /**
     * 관련상품 및 단독기능 사용 추가구성 상품시 유효성 검증에 해당하는 메시지의 노출여부 결정
     */
    checkIndividualValidAction : function(oRelationProductCount, oAddProductCount)
    {
        var bIsCheckValid = true;
        // 구스킨은 관계 없음
        if (isNewProductSkin() === false) {
            return bIsCheckValid;
        }

        if (is_individual_buy === 'T') {
            bIsCheckValid = (oAddProductCount.result === false || oRelationProductCount.result === false) ? false : true;
            if (bIsCheckValid === false && oAddProductCount.message !== '') {
                alert(oAddProductCount.message);
                return false;
            }
        } else {
            bIsCheckValid = (oRelationProductCount.result === false) ? false : true;
        }

        return bIsCheckValid;
    }

};

$(document).ready(function(){
    // 파일첨부옵션 초기화
    try {
        FileOptionManager.init();
    }catch (e) {}
});



/**
 * JSON.stringify
 * @param object aData JSON.stringify 할 데이터
 * @return string JSON.stringify 된 데이터 반환
 */
function JSON_stringify(aData)
{
    if (!$.stringify) {
        // https://gist.github.com/chicagoworks/754454
        jQuery.extend({
            stringify: function stringify(obj) {
                if ("JSON" in window) {
                    return JSON.stringify(obj);
                }

                var t = typeof (obj);
                if (t != "object" || obj === null) {
                    // simple data type
                    if (t == "string") obj = '"' + obj + '"';

                    return String(obj);
                } else {
                    // recurse array or object
                    var n, v, json = [], arr = (obj && obj.constructor == Array);

                    for (n in obj) {
                        v = obj[n];
                        t = typeof(v);
                        if (obj.hasOwnProperty(n)) {
                            if (t == "string") {
                                v = '"' + v + '"';
                            } else if (t == "object" && v !== null){
                                v = jQuery.stringify(v);
                            }

                            json.push((arr ? "" : '"' + n + '":') + String(v));
                        }
                    }

                    return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
                }
            }
        });
    }

    return $.stringify(aData);
}


/**
 * FileOption
 * 파일옵션 class - 파일첨부 옵션 하나당 하나씩
 * @author 백충덕 <cdbaek@simplexi.com>
 */
var FileOption = function(sInputId, aParam)
{
    this.aOpt = {
        inputId: sInputId,
        name: null,
        maxLen: null,
        maxSize: null,
        btnDel: '<a href="#none"><img src="//img.echosting.cafe24.com/skin/base_ko_KR/common/btn_attach_close.gif" /></a>',
        btnDelSelector: 'a',
        eInputFile: null
    };

    $.extend(this.aOpt, aParam);

    var self = this;

    /**
     * 초기화
     */
    this.init = function()
    {
        self.aOpt.eInputFile = $('#'+self.aOpt.inputId);

        // 지정된 id를 가진 input file이 없을 경우
        if (!self.aOpt.eInputFile) return false;

        // 파일리스트 목록 초기화
        var aFileListContainer = self._getFileListContainer(self.aOpt.inputId);
        if (aFileListContainer.length < 1) {
            self.aOpt.eInputFile.parent().append('<ul id="'+self._getFileListContainerId(self.aOpt.inputId)+'"></ul>');
            aFileListContainer = self._getFileListContainer(self.aOpt.inputId);
        }

        // 모바일의 경우 삭제버튼 변경
        if (self._isMobileBrowser()===true) {
            self.aOpt.btnDel = '<button type="button" class="btnDelete">' + __('삭제') + '</button></li>';
            self.aOpt.btnDelSelector = 'button.btnDelete';
        }

        // 삭제버튼 이벤트 핸들러 세팅
        aFileListContainer.delegate(this.aOpt.btnDelSelector, 'click', function() {
            $(this).parent().remove();
            return false;
        });
    };

    /**
     * 파일 입력폼을 초기화
     * @param jQuery eFile 파일 입력폼
     */
    this.resetFileInput = function(eFile)
    {
        // MSIE
        if (navigator.appVersion.indexOf('MSIE') > -1) {
            eFile.replaceWith(eFile = eFile.clone(true));
        } else {
            eFile.val('');
        }
    };

    /**
     * input:file change 이벤트 핸들러
     * @param object eFileInput change이벤트가 발생한 input:file
     */
    this.onChange = function(eFileInput)
    {
        var eFile = $(eFileInput);

        // 업로드 파일명
        var sFileName = this._getFileName(eFile.val());
        if (sFileName.length<1) return false;

        var eFileList = this._getFileListContainer(eFile.attr('id'));

        // 첨부파일 최대 갯수 제한
        var iCntFile = eFileList.find('li').length;
        if (iCntFile >= this.aOpt.maxLen) {
            if (eFile.val().length>0) alert(sprintf(__('첨부파일은 최대 %s개까지만 업로드 가능합니다.'), self.aOpt.maxLen));
            this.resetFileInput(eFile);
            return false;
        }

        // 업로드 파일리스트 추가
        var eFileItem = $('<li>'+sFileName+' '+this.aOpt.btnDel+'</li>');
        var sId = eFile.attr('id');
        var sRequire = eFile.attr('require');
        var sAccept = eFile.attr('accept');

        // IE8 이하에서는 display가 바뀌어도 onChange가 trigger되므로 onChange 제거
        eFile.get(0).onchange = null;

        eFile.css('display', 'none');
        eFile.attr({
            id: '',
            name: this.aOpt.inputId+'[]'
        });
        eFileItem.append(eFile);
        eFileList.append(eFileItem);

        // 새 파일업로드 input 배치
        var eFileNew = $('<input type="file" onchange="FileOptionManager.onChange(this)"/>');
        eFileNew.attr({
            id:      sId,
            name:    sId,
            require: sRequire,
            accept:  sAccept
        });
        eFileList.parent().prepend(eFileNew);

        // 업로드 가능한 파일인지를 비동기로 확인
        this.checkUpload(sFileName, eFileItem, String(sAccept));
    };

    /**
     * 파일업로드 전 체크
     * @param string sFileName 파일명
     * @param jQuery eFileItem 파일 첨부
     * @param string sAccept accept 속성값 (.jpg,.jpeg,.gif)
     */
    this.checkUpload = function(sFileName, eFileItem, sAccept)
    {
        var self = this;
        var sFileExtension = sFileName.replace(/^.+\.([^.]+)$/, '$1');
        if ($.inArray('.' + sFileExtension, sAccept.split(',')) > -1) {
            // accept 속성에 포함된 확장자인 경우 확인 안함
            return;
        }

        $.ajax({
            url: "/api/product/fileupload/",
            method: "GET",
            data: {
                cmd: "check_upload",
                file_extension: sFileExtension
            },
            dataType: "json",
            success: function(result) {
                if (result && result.err) {
                    eFileItem.find(self.aOpt.btnDelSelector).click();
                    alert(result.err);
                }
            }
        });
    };

    /**
     * 유효성 체크
     * @return bool 유효하면 true, 아니면 false
     */
    this.checkValidation = function()
    {
        // 파일첨부 옵션이 '필수'가 아닐 경우 OK
        if (self.aOpt.eInputFile.attr('require') !== 'T') return true;

        // 파일첨부 옵션이 '필수'인데 업로드 선택 파일이 없을 경우
        if (self.existsFileUpload()===false) {
            alert(self.aOpt.name+' '+__('파일을 업로드 해주세요.'));
            self.aOpt.eInputFile.focus();
            return false;
        }

        return true;
    };

    /**
     * 업로드 해야할 input:file 리스트 반환
     * @return array 업로드 해야할 input:file 리스트
     */
    this.getInputFileUpload = function()
    {
        return self._getFileListContainer(self.aOpt.inputId).find('input:file:hidden');
    };

    /**
     * 업로드 해야할 input:file이 있는지 여부 체크
     * @return bool 업로드 해야할 input:file이 있으면 true, 없으면 false
     */
    this.existsFileUpload = function()
    {
        return self.getInputFileUpload().length > 0;
    };

    /*
     * 파일업로드 리스트를 담을 노드 반환
     * @param string sSuffix
     * @return element
     */
    this._getFileListContainer = function(sSuffix)
    {
        var sFileListId = self._getFileListContainerId(sSuffix);

        return $('ul[id="'+sFileListId+'"]');
    };

    /**
     * 파일업로드 리스트를 담을 노드의 ID 반환
     * @param string sSuffix id로 사용할 suffix
     * @return string 노드의 ID
     */
    this._getFileListContainerId = function(sSuffix)
    {
        return 'ul_'+sSuffix;
    };

    /**
     * 파일 경로에서 파일명만 추출
     * @param string sFilePath 파일 경로
     * @return mixed 추출된 파일명 반환, 실패시 false 반환
     */
    this._getFileName = function(sFilePath)
    {
        sFilePath = $.trim(sFilePath);
        if (sFilePath.length<1) return false;

        return $.trim(sFilePath.split('/').pop().split('\\').pop());
    };

    /**
     * 모바일 브라우저인지 체크
     * @return bool 모바일 브라우저이면 true, 아니면 false 반환
     */
    this._isMobileBrowser = function()
    {
        // 전역 isMobile 변수가 세팅되어있을 경우 isMobile 변수값 반환
        if (typeof isMobile != 'undefined') {
            return isMobile;
        // 전역 isMobile 변수가 없을 경우 location.hostname으로 판별
        } else {
            return location.hostname.indexOf('m.')===0;
        }
    };

    /**
     * 부모창 - 자식창 파일 리스트 복사
     */
    this.sync = function(inputId, targetUl)
    {
        self.aOpt.eInputFile = $('#'+inputId);
        // 파일리스트 목록
        var aFileListContainer = self._getFileListContainer(inputId);
        // 추가된 파일 리스트 없을 경우 처리안함
        if (aFileListContainer.find('li').length < 1) return false;
        // 파일리스트 복사
        targetUl.append(aFileListContainer.find('li'));


    };
};

/**
 * FileOptionManager
 * 파일옵션 객체를 관리하는 class - 페이지 내의 파일첨부 옵션 전체를 관장
 * @author 백충덕 <cdbaek@simplexi.com>
 */
var FileOptionManager = {
    bIsInputFileSupport: null,
    /**
     * FileOption 객체 리스트
     * @var object
     */
    aList: {},

    /**
     * 초기화
     *   - FileOptionManager.add()를 통해 추가된 FileOption 객체 초기화 처리
     */
    init: function()
    {
        for (var sId in this.aList) {
            if (this.aList.hasOwnProperty(sId)===false) continue;

            // 초기화 과정에 문제가 생긴 객체는 리스트에서 제거
            if (this.aList[sId].init() === false) delete this.aList[sId];
        }
    },

    /**
     * 파일업로드용 input:file의 change 이벤트 핸들러
     * @param object eFileInput change 이벤트가 발생한 input:file
     */
    onChange: function(eFileInput)
    {
        var sId = eFileInput.id;
        this.aList[sId].onChange(eFileInput);
    },

    /**
     * 리스트에 sInputId, aOpt 파라메터로 생성한 FileOption 객체 추가
     * @param string sId 고유 ID (input:file의 id로도 쓰임)
     * @param object aOpt 생성 파라메터
     */
    add: function(sId, aOpt)
    {
        this.aList[sId] = new FileOption(sId, aOpt);
    },

    /**
     * 업로드해야 할 input:file이 있는지 체크
     * @param mixed mId 업로드 해야할 파일이 있는지 체크할 FileOption id. 없거나 하나 혹은 여러개.
     * @return bool 파일업로드가 있으면 true, 아니면 false
     */
    existsFileUpload: function(mId)
    {
        var aId = this._getList(mId);

        for (var i=0; i<aId.length; i++) {
            var sId = aId[i];

            // 업로드해야 할 파일 있음
            if (this.aList[sId].existsFileUpload() === true) return true;
        }

        return false;
    },

    /**
     * 유효성 체크
     * @param mixed mId 유효성 체크할 FileOption id. 없거나 하나 혹은 여러개.
     * @return bool 유효하면 true, 아니면 false
     */
    checkValidation: function(mId)
    {
        var aId = this._getList(mId);

        // 유효성 체크
        for (var i=0; i<aId.length; i++) {
            var sId = aId[i];

            if (this.aList[sId].checkValidation() === false) return false;
        }

        return true;
    },

    /**
     * 파일첨부 옵션 업로드 실행
     * @param mixed mId 파일업로드를 실행할 FileOption id. 없거나 하나 혹은 여러개.
     * @param function callback 파일업로드 완료 후 실행할 callback
     */
    upload: function(mId, callback)
    {
        var self = this;

        // mId 지정하지 않음
        if (typeof mId === 'function') {
            callback = mId;
            mId = null;
        }
        var aId = this._getList(mId);

        // 업로드 해야할 input:file 추출
        var aFile = [];
        var aMaxSize = {};
        for (var i=0; i<aId.length; i++) {
            var sId = aId[i];
            aMaxSize[sId] = this.aList[sId].aOpt.maxSize;

            this.aList[sId].getInputFileUpload().each(function(idx){
                var sVal = $.trim($(this).val());
                if (sVal.length < 1) return;

                aFile.push({
                    eFile: $(this),
                    eParent: $(this).parent()
                });
            });
        }

        // 업로드 할 파일이 없을 경우 중지 (업로드는 성공했다고 반환)
        if (aFile.length < 1) {
            callback(true);
            return true;
        }

        var sTargetName = 'iframe_add_option_file_upload';
        var sAction     = '/api/product/fileupload/';

        // form
        var form = $('<form action="'+sAction+'" method="post" enctype="multipart/form-data" style="display:none;" target="'+sTargetName+'"></form>');
        $('body').append(form);
        // 업로드할 input:file append
        for (var i=0; i<aFile.length; i++) {
            aFile[i].eFile.appendTo(form);
        }

        // 커맨드 지정
        $('<input type="hidden" name="cmd" value="upload" />').prependTo(form);
        // 파일 업로드 사이즈 한계
        $('<input type="hidden" name="max_size" value="'+encodeURIComponent(JSON_stringify(aMaxSize))+'" />').prependTo(form);

        // iframe
        var iframe = $('<iframe src="javascript:false;" name="'+sTargetName+'" style="display:none;"></iframe>');
        $('body').append(iframe);

        // iframe onload(form.submit response) 이벤트 핸들러
        iframe.load(function(){
            var doc = this.contentWindow ? this.contentWindow.document : (this.contentDocument ? this.contentDocument : this.document);
            var root = doc.documentElement ? doc.documentElement : doc.body;
            var sResult = root.textContent ? root.textContent : root.innerText;
            var aResult = $.parseJSON(sResult);
            var mReturn = false;

            if (typeof aResult==='object') {
                // 업로드 성공
                if (aResult.err=='') {
                    // 업로드 성공한 파일정보를 가져와 input:hidden의 value로 저장
                    for (var sId in aResult.files) {
                        var eInputHidden = $('#'+sId+'_hidden');
                        var aVal = {
                            title: self.aList[sId].aOpt.name,
                            files: []
                        };
                        for (var i=0; i<aResult.files[sId].length; i++) {
                            aVal.files.push({
                                path: aResult.files[sId][i].path,
                                name: aResult.files[sId][i].name
                            });
                        }

                        eInputHidden.val(encodeURIComponent(JSON_stringify(aVal)));

                        // 반환값 세팅
                        if (mReturn===false) mReturn = {};
                        mReturn[sId] = aVal;
                    }
                // 업로드 실패
                } else {
                    alert(aResult.err);
                }
            }

            // file element 원래 위치로 이동
            for (var i=0; i<aFile.length; i++) {
                aFile[i].eFile.appendTo(aFile[i].eParent);
            }

            // 임시 element 삭제
            form.remove();
            iframe.remove();

            callback(mReturn);
        });

        // 파일전송
        form.submit();
    },

    /**
     * 브라우저가 input file 지원여부 반환
     * @return bool input file 지원시 true, 아니면 false
     */
    isInputFileSupport: function()
    {
        if (this.bIsInputFileSupport===null) {
            this.bIsInputFileSupport = true;

            try {
                var eInputFile = document.createElement('input');
                eInputFile.type = 'file';
                eInputFile.style.display = 'none';
                document.getElementsByTagName('body')[0].appendChild(eInputFile);

                if (eInputFile.disabled) this.bIsInputFileSupport = false;
            } catch (e) {
                this.bIsInputFileSupport = false;
            } finally {
                if (eInputFile) eInputFile.parentNode.removeChild(eInputFile);
            }
        }

        return this.bIsInputFileSupport;
    },

    // 파라메터로 넘기기 위해 인코딩
    encode: function(sVal)
    {
        return encodeURIComponent(JSON_stringify(sVal)).replace(/'/g, "%27");
    },

    /**
     * 넘겨받은 id에 해당하는 유효한 FileOption id 리스트 반환
     * @param mixed mId 리스트로 추출할 FileOption id. 없거나 하나 혹은 여러개.
     * @return array 유효한 FileOption id 리스트
     */
    _getList: function(mId)
    {
        var aId = [];

        // 지정한 id가 없다면 전체대상
        if (!mId) {
            for (var sId in this.aList) {
                if (this.aList.hasOwnProperty(sId)===false) continue;

                aId.push(sId);
            }
        // 지정한 id가 문자열 하나
        } else if (typeof mId === 'string') {
            aId.push(mId);
        // 지정한 id가 Array(object)
        } else {
            aId = mId;
        }

        // 뭔가 문제가 있을 경우 빈 배열 반환
        if ($.isArray(aId)===false || aId.length<1) return [];

        // 유효한 id만 추출
        var sId = '';
        var aResult = [];
        for (var i=0; i<aId.length; i++) {
            sId = aId[i];
            if (!(sId in this.aList)) continue;

            aResult.push(sId);
        }

        return aResult;
    },

    /**
     * 부모창 - 자식창 파일 리스트 복사
     */
    sync: function(sId, target)
    {
        this.aList[sId].sync(sId, target);
    }
};

$(document).ready(function(){

    // 최근 본 상품 쿠키 세팅하기
    var sPath = document.location.pathname;
    var sPattern = /^\/product\/(.+?)\/([0-9]+)(\/.*|)/;
    var aMatchResult = sPath.match(sPattern);

    if (aMatchResult) {
        var iProductNo = aMatchResult[2];
    } else {
        var iProductNo  = NEWPRODUCT_Recent.getParameterByName('product_no');
    }

    var sCookieName = 'recent_plist' + (SHOP.isDefaultShop() ? '' : EC_SDE_SHOP_NUM);
    var sCookieVal  = $.cookie(sCookieName);

    $.cookie(sCookieName, NEWPRODUCT_Recent.getRecentUnique(iProductNo , sCookieVal), {
        'path' : '/',
        'expires' : 365
    });

    // ie하위 버젼에서는 로컬 스토리지 동작 안함으로 인해서 시도도 안함!
    // 기존 쿠키 방식 그대로 씀
    if (NEWPRODUCT_Recent.getIsLocalStorageAble() === true) {
        NEWPRODUCT_Recent.setProductRecentInfo(parseInt(iProductNo, 10));
    }


});


var NEWPRODUCT_Recent = {
        iMaxLength : 50,
        sStorageKey : 'localRecentProduct' + EC_SDE_SHOP_NUM,
        /**
         * url에서 파라미터 가져오기
         * @param string name 파라미터명
         * @return string 파라미터 값
         */
         getParameterByName : function (name) {
            name        = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
            var regexS  = "[\\?&]" + name + "=([^&#]*)";
            var regex   = new RegExp(regexS);
            var results = regex.exec(window.location.href);

            if (results == null) {
                return '';
            } else {
                return decodeURIComponent(results[1].replace(/\+/g, " "));
            }
        },

        /**
         * SEO URL 에서 name 파라메터 값 가져오기, SEO URL 이 아니면  getParameterByName 에서 요청
         * @param string name 파라미터명
         * @param string sRegexPattern seo url 에서 category 값 가져오기 패턴
         * @return string 파라미터 값
         */
         getParameterFromSeoUrl : function (name, sRegexPattern) {
            var regex   = new RegExp(sRegexPattern);
            var results = regex.exec(window.location.href);

            if (results == null) {
                return NEWPRODUCT_Recent.getParameterByName(name);
            } else {
                return decodeURIComponent(results[2].replace(/\+/g, " "));
            }
        },

        /**
         * 최근상품번호 리스트 가져오기
         * @param int iProductNo product_no
         * @return string 기존 쿠키값에 현재 상품리스트 추가한 쿠키값
         */
        getRecentUnique : function (iProductNo, sCookieVal)
        {
            var newList    = [];
            var aList      = sCookieVal ? sCookieVal.split('|') : [];

            for (var i = 0; i < aList.length; i++) {
                var sNo = $.trim(aList[i]);
                if (sNo == '' || sNo == iProductNo) {
                    continue; // 이미 있으면 skip...
                }
                newList.push(sNo);
            }
            newList.push(iProductNo);

            if (newList.length >= this.iMaxLength) {
                newList = newList.slice(newList.length - this.iMaxLength);
            }
            return newList.join('|');
        },
        /**
         * 최근상품 상품명 저장시 상품명 자르기
         * @return string 상품명
         */
         getCutProductName : function() {
            var iByte           = 0;
            var sProductNameTmp    =  product_name.replace(/(<([^>]+)>)/ig,'');
            var iStrLength      = product_name.length;
            var iMaxItem        = 10;
            var sProductName    = '';
            var iMaxLimit       = 10;

            // 상품명에 태그가 포함되어 있지 않은 경우
            if (sProductNameTmp === '') {
                sProductNameTmp = product_name;
            }

            for (var i=0; i < iStrLength; i++) {
                if (escape(sProductNameTmp.charCodeAt(i)).length > 4){
                    iByte +=2; //한글이면 2를 더한다
                    iMaxItem -= 1;
                }
                if (iByte > iMaxItem) {
                    sProductName = sProductNameTmp.slice(0,iMaxItem);
                    break;
                }
            }

            if (sProductName === '') {
                sProductName = sProductNameTmp.slice(0,iMaxLimit);
            }
            return sProductName;
        },

        /*
         * sessionStorage 사용
         */
        setProductRecentInfo : function (iProductNo) {

            var oJsonData = this.getSessionStorageData(this.sStorageKey);
            var iObjectKeyCount = 0;
            //if (this.isDulicateCheck(iProductNo ,oJsonData) === false) {
            var sRegexCategoryNumberBySeoUrl = '(\/product\/.+?\/[0-9]+\/category\/)([0-9]+)(\/.*|)';
            var sRegexDisplayNumberBySeoUrl = '(\/product\/.+?\/[0-9]+\/category\/[0-9]+\/display\/)([0-9]+)(\/.*|)';

            var iCateNum       = parseInt(NEWPRODUCT_Recent.getParameterFromSeoUrl('cate_no', sRegexCategoryNumberBySeoUrl), 10);
            var iDisplayGroup  = parseInt(NEWPRODUCT_Recent.getParameterFromSeoUrl('display_group', sRegexDisplayNumberBySeoUrl), 10);
            var sProductName   = NEWPRODUCT_Recent.getCutProductName();

            var oNewStorageData = new Object();
            var iDelProductNum = 0;

            var aParam = {
                product_no   : iProductNo,
                cate_no      : iCateNum,
                display_group: iDisplayGroup
            };
            var sParam = '?' + $.param(aParam);
            var aNewStorageData = {
                    'iProductNo'    : iProductNo,
                    'sProductName'  : sProductName,
                    'sImgSrc'       : product_image_tiny,
                    'isAdultProduct': is_adult_product,
                    'link_product_detail': link_product_detail,
                    'sParam'        : sParam
                   };

            oNewStorageData[iObjectKeyCount] = aNewStorageData;
            if (oJsonData !== null) {
                var aStorageData = $.parseJSON(oJsonData);
                for (var iKey in aStorageData) {
                    if (isFinite(iKey) === false) {
                        continue;
                    }
                    if (aStorageData[iKey].iProductNo !== iProductNo) {
                        iObjectKeyCount++;
                        oNewStorageData[iObjectKeyCount] = aStorageData[iKey];
                        iDelProductNum = aStorageData[iKey].iProductNo;
                    }
                }
            }
            this.setSessionStorageData(this.sStorageKey , oNewStorageData);

            if (iObjectKeyCount  >= this.iMaxLength) {
                this.setUpdateStorageData($.trim(iDelProductNum));
            }
            //}

        },
        /*
         * 삭제될 스토리지 범위가 벗어났을 경우 처리 필요해서
         */
        setUpdateStorageData : function (iProductNo) {
            var oJsonData = this.getSessionStorageData(this.sStorageKey);

            if (oJsonData === null) {
                return;
            }
            var iCount = 0;
            var oNewStorageData = new Object();
            var aStorageData = $.parseJSON(oJsonData);
            var iStorageLength = aStorageData.length;

            var sDeleteKey  = this.iMaxLength + '';
            // 마지막에 추가되어 있던 상품을 지운다.
            delete aStorageData[sDeleteKey];
            this.setSessionStorageData(this.sStorageKey , aStorageData);

        },
        /*
         * 중복된 상품번호가 있는가 확인 하는 메소드
         */

        isDulicateCheck : function (iProductNo , oJsonData) {
            var bDulicate = false;

            if (oJsonData === null) {
                return false;
            }
            iProductNo = $.trim(iProductNo);
            var aStorageData = $.parseJSON(oJsonData);
            for (var iKey in aStorageData) {
                if ($.trim(aStorageData[iKey].iProductNo) === iProductNo) {
                    bDulicate = true;
                    break;
                }
            }
            return bDulicate;
        },
        /**
         * get SessionStorage
         * @param sStorageKey SessionStorage에 저장되어 있는 key값
         */
        getSessionStorageData : function (sStorageKey)
        {
            return sessionStorage.getItem(sStorageKey);
        },
        /**
         * set SessionStorage
         * @param sStorageKey SessionStorage에 저장할 key값
         * @param sStorageValue SessionStorage에 저장할 value값
         */
        setSessionStorageData : function (sStorageKey , sStorageValue)
        {
            return sessionStorage.setItem(sStorageKey , $.toJSON(sStorageValue));
        },

        /**
         * 세션스토리지가 사용가능한지 확인
         */
        getIsLocalStorageAble : function() {
            var sTestKey = 'CAPP_TMP_KEY';
            try {
                window.localStorage.setItem(sTestKey, 1);
                window.localStorage.removeItem(sTestKey);
                return true;
            } catch(e) {
                return false;
            }
        }
};

var EC_FRONT_NEW_PRODUCT_LAZYLOAD = {
        resetDetailContent : function() 
        {
            var oProductDetailContent = null;
            if ($('.xans-product-additional').find('#prdDetailContent').html() !== null) {
                oProductDetailContent = $('.xans-product-additional').find('#prdDetailContent');
            } else if ($('.xans-product-additional').find('div.prdDetailView') !== null) {
                oProductDetailContent = $('.xans-product-additional').find('div.prdDetailView');
                $('div.moreBtn').hide();
            }
            if (oProductDetailContent !== null) {
                var sDetailContent = oProductDetailContent.html();
                oProductDetailContent.html('').hide();
                sDetailForm = '<div id=\"prdDetailContentLazy\">'+ sDetailContent + '</div>';
                oProductDetailContent.after(sDetailForm);
            }
        }
};

/**
 *
 */

document.oncontextmenu = function(){
    return false;
};

document.ondragstart = function(){
    return false;
};

document.onselectstart = function(event) {
    try {
        if (event.srcElement && event.srcElement.tagName == 'INPUT' && event.srcElement.value) {
            return true;
        }
    } catch (e) {
        return false;
    }
    return false;
};

/**
 * 접속통계 & 실시간접속통계
 */
$(document).ready(function(){
    // 이미 weblog.js 실행 되었을 경우 종료 
    if ($('#log_realtime').length > 0) {
        return;
    }
    /*
     * QueryString에서 디버그 표시 제거
     */
    function stripDebug(sLocation)
    {
        if (typeof sLocation != 'string') return '';

        sLocation = sLocation.replace(/^d[=]*[\d]*[&]*$/, '');
        sLocation = sLocation.replace(/^d[=]*[\d]*[&]/, '');
        sLocation = sLocation.replace(/(&d&|&d[=]*[\d]*[&]*)/, '&');

        return sLocation;
    }

    // 벤트 몰이 아닐 경우에만 V3(IFrame)을 로드합니다.
    // @date 190117
    // @date 191217 - 이벤트에도 V3 상시 적재로 변경.
    //if (EC_FRONT_JS_CONFIG_MANAGE.sWebLogEventFlag == "F")
    //{
    // T 일 경우 IFRAME 을 노출하지 않는다.
    if (EC_FRONT_JS_CONFIG_MANAGE.sWebLogOffFlag == "F")
    {
        if (window.self == window.top) {
            var rloc = escape(document.location);
            var rref = escape(document.referrer);
        } else {
            var rloc = (document.location).pathname;
            var rref = '';
        }

        // realconn & Ad aggregation
        var _aPrs = new Array();
        _sUserQs = window.location.search.substring(1);
        _sUserQs = stripDebug(_sUserQs);
        _aPrs[0] = 'rloc=' + rloc;
        _aPrs[1] = 'rref=' + rref;
        _aPrs[2] = 'udim=' + window.screen.width + '*' + window.screen.height;
        _aPrs[3] = 'rserv=' + aLogData.log_server2;
        _aPrs[4] = 'cid=' + eclog.getCid();
        _aPrs[5] = 'role_path=' + $('meta[name="path_role"]').attr('content');
        _aPrs[6] = 'stype=' + aLogData.stype;
        _aPrs[7] = 'shop_no=' + aLogData.shop_no;
        _aPrs[8] = 'lang=' + aLogData.lang;
        _aPrs[9] = 'ver=' + aLogData.ver;


        // 모바일웹일 경우 추가 파라미터 생성
        var _sMobilePrs = '';
        if (mobileWeb === true) _sMobilePrs = '&mobile=T&mobile_ver=new';

        _sUrlQs = _sUserQs + '&' + _aPrs.join('&') + _sMobilePrs;

        var _sUrlFull = '/exec/front/eclog/main/?' + _sUrlQs;

        var node = document.createElement('iframe');
        node.setAttribute('src', _sUrlFull);
        node.setAttribute('id', 'log_realtime');
        document.body.appendChild(node);

        $('#log_realtime').hide();
    }

    // eclog2.0, eclog1.9
    var sTime = new Date().getTime();//ECHOSTING-54575

    // 접속통계 서버값이 있다면 weblog.js 호출
    if (aLogData.log_server1 != null && aLogData.log_server1 != '') {
        var sScriptSrc = '//' + aLogData.log_server1 + '/weblog.js?uid=' + aLogData.mid + '&uname=' + aLogData.mid + '&r_ref=' + document.referrer + '&shop_no=' + aLogData.shop_no;
        if (mobileWeb === true) sScriptSrc += '&cafe_ec=mobile';
        sScriptSrc += '&t=' + sTime;//ECHOSTING-54575
        var node = document.createElement('script');
        node.setAttribute('type', 'text/javascript');
        node.setAttribute('src', sScriptSrc);
        node.setAttribute('id', 'log_script');
        document.body.appendChild(node);
    }
});

(function(window){
    window.htmlentities = {
        /**
         * Converts a string to its html characters completely.
         *
         * @param {String} str String with unescaped HTML characters
         **/
        encode : function(str) {
            var buf = [];

            for (var i=str.length-1; i>=0; i--) {
                buf.unshift(['&#', str[i].charCodeAt(), ';'].join(''));
            }

            return buf.join('');
        },
        /**
         * Converts an html characterSet into its original character.
         *
         * @param {String} str htmlSet entities
         **/
        decode : function(str) {
            return str.replace(/&#(\d+);/g, function(match, dec) {
                return String.fromCharCode(dec);
            });
        }
    };
})(window);
/**
 * 비동기식 데이터
 */
var CAPP_ASYNC_METHODS = {
    DEBUG: false,
    IS_LOGIN: (document.cookie.match(/(?:^| |;)iscache=F/) ? true : false),
    EC_PATH_ROLE: $('meta[name="path_role"]').attr('content') || '',
    aDatasetList: [],
    $xansMyshopMain: $('.xans-myshop-main'),
    init : function()
    {
    	var bDebug = CAPP_ASYNC_METHODS.DEBUG;

        var aUseModules = [];
        var aNoCachedModules = [];

        $(CAPP_ASYNC_METHODS.aDatasetList).each(function(){
            var sKey = this;

            var oTarget = CAPP_ASYNC_METHODS[sKey];

            if (bDebug) {
                console.log(sKey);
            }
            var bIsUse = oTarget.isUse();
            if (bDebug) {
                console.log('   isUse() : ' + bIsUse);
            }

            if (bIsUse === true) {
                aUseModules.push(sKey);

                if (oTarget.restoreCache === undefined || oTarget.restoreCache() === false) {
                    if (bDebug) {
                        console.log('   restoreCache() : true');
                    }
                    aNoCachedModules.push(sKey);
                }
            }
        });

        if (aNoCachedModules.length > 0) {
            var sEditor = '';
            try {
                if (bEditor === true) {
                    // 에디터에서 접근했을 경우 임의의 상품 지정
                    sEditor = '&PREVIEW_SDE=1';
                }
            } catch(e) { }

            var sPathRole = '&path_role=' + CAPP_ASYNC_METHODS.EC_PATH_ROLE;

            $.ajax(
            {
                url : '/exec/front/manage/async?module=' + aNoCachedModules.join(',') + sEditor + sPathRole,
                dataType : 'json',
                success : function(aData)
                {
                	CAPP_ASYNC_METHODS.setData(aData, aUseModules);
                }
            });

        } else {
        	CAPP_ASYNC_METHODS.setData({}, aUseModules);

        }
    },
    setData : function(aData, aUseModules)
    {
        aData = aData || {};

        $(aUseModules).each(function(){
            var sKey = this;

            var oTarget = CAPP_ASYNC_METHODS[sKey];

            if (oTarget.setData !== undefined && aData.hasOwnProperty(sKey) === true) {
                oTarget.setData(aData[sKey]);
            }

            if (oTarget.execute !== undefined) {
                oTarget.execute();
            }
        });
    },

    _getCookie: function(sCookieName)
    {
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        return aCookieValue ? aCookieValue[1] : null;
    }
};
/**
 * 비동기식 데이터 - 회원 정보
 */
CAPP_ASYNC_METHODS.aDatasetList.push('member');
CAPP_ASYNC_METHODS.member = {
    __sEncryptedString: null,
    __isAdult: 'F',

    // 회원 데이터
    __sMemberId: null,
    __sName: null,
    __sNickName: null,
    __sGroupName: null,
    __sEmail: null,
    __sPhone: null,
    __sCellphone: null,
    __sBirthday: null,
    __sGroupNo: null,
    __sBoardWriteName: null,
    __sAdditionalInformation: null,
    __sCreatedDate: null,

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if ($('.xans-layout-statelogon, .xans-layout-logon').length > 0) {
                return true;
            }

            if (CAPP_ASYNC_METHODS.recent.isUse() === true
                && typeof(EC_FRONT_JS_CONFIG_SHOP) !== 'undefined'
                && EC_FRONT_JS_CONFIG_SHOP.adult19Warning === 'T') {
                return true;
            }

            if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('customer', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
                return true;
            }

        } else {
            // 비 로그인 상태에서 삭제처리
            this.removeCache();
        }

        return false;
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        // 데이터 복구 유무
        var bRestored = false;

        try {
            // 데이터 복구
            var oCache = JSON.parse(window.sessionStorage.getItem('member_' + EC_SDE_SHOP_NUM));

            // expire 체크
            if (oCache.exp < Date.now()) {
                throw 'cache has expired.';
            }

            // 데이터 체크
            if (typeof oCache.data.member_id === 'undefined'
                || oCache.data.member_id === ''
                || typeof oCache.data.name === 'undefined'
                || typeof oCache.data.nick_name === 'undefined'
                || typeof oCache.data.group_name === 'undefined'
                || typeof oCache.data.group_no === 'undefined'
                || typeof oCache.data.email === 'undefined'
                || typeof oCache.data.phone === 'undefined'
                || typeof oCache.data.cellphone === 'undefined'
                || typeof oCache.data.birthday === 'undefined'
                || typeof oCache.data.board_write_name === 'undefined'
                || typeof oCache.data.additional_information === 'undefined'
                || typeof oCache.data.created_date === 'undefined'
            ) {
                throw 'Invalid cache data.';
            }

            // 데이터 복구
            this.__sMemberId = oCache.data.member_id;
            this.__sName = oCache.data.name;
            this.__sNickName = oCache.data.nick_name;
            this.__sGroupName = oCache.data.group_name;
            this.__sGroupNo   = oCache.data.group_no;
            this.__sEmail = oCache.data.email;
            this.__sPhone = oCache.data.phone;
            this.__sCellphone = oCache.data.cellphone;
            this.__sBirthday = oCache.data.birthday;
            this.__sBoardWriteName = oCache.data.board_write_name;
            this.__sAdditionalInformation = oCache.data.additional_information;
            this.__sCreatedDate = oCache.data.created_date;

            bRestored = true;
        } catch(e) {
            // 복구 실패시 캐시 삭제
            this.removeCache();
        }

        return bRestored;
    },

    cache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        // 캐시
        window.sessionStorage.setItem('member_' + EC_SDE_SHOP_NUM, JSON.stringify({
            exp: Date.now() + (1000 * 60 * 10),
            data: this.getData()
        }));
    },

    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        // 캐시 삭제
        window.sessionStorage.removeItem('member_' + EC_SDE_SHOP_NUM);
    },

    setData: function(oData)
    {
        this.__sEncryptedString = oData.memberData;
        this.__isAdult = oData.memberIsAdult;
    },

    execute: function()
    {
        if (this.__sMemberId === null) {
            AuthSSLManager.weave({
                'auth_mode'          : 'decryptClient',
                'auth_string'        : this.__sEncryptedString,
                'auth_callbackName'  : 'CAPP_ASYNC_METHODS.member.setDataCallback'
            });
        } else {
            this.render();
        }
    },

    setDataCallback: function(sData)
    {
        try {
            var sDecodedData = decodeURIComponent(sData);

            if (AuthSSLManager.isError(sDecodedData) == true) {
                console.log(sDecodedData);
                return;
            }

            var oData = AuthSSLManager.unserialize(sDecodedData);
            this.__sMemberId = oData.id || '';
            this.__sName = oData.name || '';
            this.__sNickName = oData.nick || '';
            this.__sGroupName = oData.group_name || '';
            this.__sGroupNo   = oData.group_no || '';
            this.__sEmail = oData.email || '';
            this.__sPhone = oData.phone || '';
            this.__sCellphone = oData.cellphone || '';
            this.__sBirthday = oData.birthday || 'F';
            this.__sBoardWriteName = oData.board_write_name || '';
            this.__sAdditionalInformation = oData.additional_information || '';
            this.__sCreatedDate = oData.created_date || '';

            // 데이터 랜더링
            this.render();

            // 데이터 캐시
            this.cache();
        } catch(e) {}
    },

    render: function()
    {
        // 친구초대
        if ($('.xans-myshop-asyncbenefit').length > 0) {
            $('#reco_url').attr({value: $('#reco_url').val() + this.__sMemberId});
        }

        $('.xans-member-var-id').html(this.__sMemberId);
        $('.xans-member-var-name').html(this.__sName);
        $('.xans-member-var-nick').html(this.__sNickName);
        $('.xans-member-var-group_name').html(this.__sGroupName);
        $('.xans-member-var-group_no').html(this.__sGroupNo);
        $('.xans-member-var-email').html(this.__sEmail);
        $('.xans-member-var-phone').html(this.__sPhone);

        if ($('.xans-board-commentwrite').length > 0 && typeof BOARD_COMMENT !== 'undefined') {
            BOARD_COMMENT.setCmtData();
        }
    },

    getMemberIsAdult: function()
    {
        return this.__isAdult;
    },

    getData: function()
    {
        return {
            member_id: this.__sMemberId,
            name: this.__sName,
            nick_name: this.__sNickName,
            group_name: this.__sGroupName,
            group_no: this.__sGroupNo,
            email: this.__sEmail,
            phone: this.__sPhone,
            cellphone: this.__sCellphone,
            birthday: this.__sBirthday,
            board_write_name: this.__sBoardWriteName,
            additional_information: this.__sAdditionalInformation,
            created_date: this.__sCreatedDate
        };
    }
};
/**
 * 비동기식 데이터 - 예치금
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Ordercnt');
CAPP_ASYNC_METHODS.Ordercnt = {
    __iOrderShppiedBeforeCount: null,
    __iOrderShppiedStandbyCount: null,
    __iOrderShppiedBeginCount: null,
    __iOrderShppiedComplateCount: null,
    __iOrderShppiedCancelCount: null,
    __iOrderShppiedExchangeCount: null,
    __iOrderShppiedReturnCount: null,

    __$target: $('#xans_myshop_orderstate_shppied_before_count'),
    __$target2: $('#xans_myshop_orderstate_shppied_standby_count'),
    __$target3: $('#xans_myshop_orderstate_shppied_begin_count'),
    __$target4: $('#xans_myshop_orderstate_shppied_complate_count'),
    __$target5: $('#xans_myshop_orderstate_order_cancel_count'),
    __$target6: $('#xans_myshop_orderstate_order_exchange_count'),
    __$target7: $('#xans_myshop_orderstate_order_return_count'),

    isUse: function()
    {
        if ($('.xans-myshop-orderstate').length > 0) {
            return true; 
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'ordercnt_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            var aData = jQuery.parseJSON(decodeURIComponent(aCookieValue[1]));
            this.__iOrderShppiedBeforeCount = aData.shipped_before_count;
            this.__iOrderShppiedStandbyCount = aData.shipped_standby_count;
            this.__iOrderShppiedBeginCount = aData.shipped_begin_count;
            this.__iOrderShppiedComplateCount = aData.shipped_complate_count;
            this.__iOrderShppiedCancelCount = aData.order_cancel_count;
            this.__iOrderShppiedExchangeCount = aData.order_exchange_count;
            this.__iOrderShppiedReturnCount = aData.order_return_count;
            return true;
        }

        return false;
    },

    setData: function(aData)
    {
        this.__iOrderShppiedBeforeCount = aData['shipped_before_count'];
        this.__iOrderShppiedStandbyCount = aData['shipped_standby_count'];
        this.__iOrderShppiedBeginCount = aData['shipped_begin_count'];
        this.__iOrderShppiedComplateCount = aData['shipped_complate_count'];
        this.__iOrderShppiedCancelCount = aData['order_cancel_count'];
        this.__iOrderShppiedExchangeCount = aData['order_exchange_count'];
        this.__iOrderShppiedReturnCount = aData['order_return_count'];
    },

    execute: function()
    {
        this.__$target.html(this.__iOrderShppiedBeforeCount);
        this.__$target2.html(this.__iOrderShppiedStandbyCount);
        this.__$target3.html(this.__iOrderShppiedBeginCount);
        this.__$target4.html(this.__iOrderShppiedComplateCount);
        this.__$target5.html(this.__iOrderShppiedCancelCount);
        this.__$target6.html(this.__iOrderShppiedExchangeCount);
        this.__$target7.html(this.__iOrderShppiedReturnCount);
    },

    getData: function()
    {
        return {
            shipped_before_count: this.__iOrderShppiedBeforeCount,
            shipped_standby_count: this.__iOrderShppiedStandbyCount,
            shipped_begin_count: this.__iOrderShppiedBeginCount,
            shipped_complate_count: this.__iOrderShppiedComplateCount,
            order_cancel_count: this.__iOrderShppiedCancelCount,
            order_exchange_count: this.__iOrderShppiedExchangeCount,
            order_return_count: this.__iOrderShppiedReturnCount
        };
    }
};
/**
 * 비동기식 데이터 - 장바구니 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Basketcnt');
CAPP_ASYNC_METHODS.Basketcnt = {
    __iBasketCount: null,

    __$target: $('.xans-layout-orderbasketcount span a'),
    __$target2: $('#xans_myshop_basket_cnt'),
    __$target3: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_basket_cnt'),
    __$target4: $('.EC-Layout-Basket-count'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        if (this.__$target2.length > 0) {
            return true;
        }
        if (this.__$target3.length > 0) {
            return true;
        }
        if (this.__$target4.length > 0) {
            return true;
        }

        if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('personal', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'basketcount_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iBasketCount = parseInt(aCookieValue[1], 10);
            return true;
        }
        
        return false;
    },

    setData: function(sData)
    {
        this.__iBasketCount = Number(sData);
    },

    execute: function()
    {
        this.__$target.html(this.__iBasketCount);

        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target2.html(this.__iBasketCount + '개');
        } else {
            this.__$target2.html(this.__iBasketCount);
        }

        this.__$target3.html(this.__iBasketCount);
        
        this.__$target4.html(this.__iBasketCount);
        
        if (this.__iBasketCount > 0 && this.__$target4.length > 0) {
            var $oCountDisplay = $('.EC-Layout_Basket-count-display');

            if ($oCountDisplay.length > 0) {
                $oCountDisplay.removeClass('displaynone');
            }
        }
    },

    getData: function()
    {
        return {
            count: this.__iBasketCount
        };
    }
};
/**
 * 비동기식 데이터 - 장바구니 금액
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Basketprice');
CAPP_ASYNC_METHODS.Basketprice = {
    __sBasketPrice: null,

    __$target: $('#xans_myshop_basket_price'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }

        if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('personal', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'basketprice_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__sBasketPrice = decodeURIComponent((aCookieValue[1]+ '').replace(/\+/g, '%20'));
            return true;
        }
        
        return false;
    },

    setData: function(sData)
    {
        this.__sBasketPrice = sData;
    },

    execute: function()
    {
        this.__$target.html(this.__sBasketPrice);
    },

    getData: function()
    {
        // 데이터 없는경우 0
        var sBasketPrice = (this.__sBasketPrice || 0) + '';

        return {
            basket_price: parseFloat(SHOP_PRICE_FORMAT.detachFormat(htmlentities.decode(sBasketPrice))).toFixed(2)
        };
    }
};
/*
 * 비동기식 데이터 - 장바구니 상품리스트
 */
CAPP_ASYNC_METHODS.aDatasetList.push('BasketProduct');
CAPP_ASYNC_METHODS.BasketProduct = {

    STORAGE_KEY: 'BasketProduct_' +  EC_SDE_SHOP_NUM,

    __aData: null,

    __$target: $('.xans-layout-orderbasketcount span a'),
    __$target2: $('#xans_myshop_basket_cnt'),
    __$target3: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_basket_cnt'),
    __$target4: $('.EC-Layout-Basket-count'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        if (this.__$target2.length > 0) {
            return true;
        }
        if (this.__$target3.length > 0) {
            return true;
        }
        if (this.__$target4.length > 0) {
            return true;
        }

        if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('personal', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
            return true;
        }
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null) {
            return false;
        }

        try {
            this.__aData = [];
            var aStorageData = JSON.parse(sSessionStorageData);

            for (var iKey in aStorageData) {
                this.__aData.push(aStorageData[iKey]);
            }

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
        this.__aData = oData;

        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        try {
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.getData()));
        } catch (error) {
        }
    },

    execute: function()
    {

    },

    getData: function()
    {
        var aStorageData = this.__aData;

        if (aStorageData != null) {
            var oNewStorageData = [];

            for (var iKey in aStorageData) {
                oNewStorageData.push(aStorageData[iKey]);
            }

            return oNewStorageData;
        } else {
            return false;
        }
    }
};
/**
 * 비동기식 데이터 - 쿠폰 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Couponcnt');
CAPP_ASYNC_METHODS.Couponcnt = {
    __iCouponCount: null,

    __$target: $('.xans-layout-myshopcouponcount'),
    __$target2: $('#xans_myshop_coupon_cnt'),
    __$target3: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_coupon_cnt'),
    __$target4: $('#xans_myshop_bankbook_coupon_cnt'),

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

            if (this.__$target4.length > 0) {
                return true;
            }

            if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('promotion', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
                return true;
            }
        }

        return false;
    },
    
    restoreCache: function()
    {
        var sCookieName = 'couponcount_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iCouponCount = parseInt(aCookieValue[1], 10);
            return true;
        }
        
        return false;
    },
    setData: function(sData)
    {
        this.__iCouponCount = Number(sData);
    },

    execute: function()
    {
        this.__$target.html(this.__iCouponCount);

        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target2.html(this.__iCouponCount + '개');
        } else {
            this.__$target2.html(this.__iCouponCount);
        }

        this.__$target3.html(this.__iCouponCount);
        this.__$target4.html(this.__iCouponCount);
    },

    getData: function()
    {
        return {
            count: this.__iCouponCount
        };
    }
};
/**
 * 비동기식 데이터 - 적립금
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Mileage');
CAPP_ASYNC_METHODS.Mileage = {
    __sAvailMileage: null,
    __sUsedMileage: null,
    __sTotalMileage: null,
    __sUnavailMileage: null,
    __sReturnedMileage: null,

    __$target: $('#xans_myshop_mileage'),
    __$target2: $('#xans_myshop_bankbook_avail_mileage, #xans_myshop_summary_avail_mileage'),
    __$target3: $('#xans_myshop_bankbook_used_mileage, #xans_myshop_summary_used_mileage'),
    __$target4: $('#xans_myshop_bankbook_total_mileage, #xans_myshop_summary_total_mileage'),
    __$target5: $('#xans_myshop_summary_unavail_mileage'),
    __$target6: $('#xans_myshop_summary_returned_mileage'),
    __$target7: $('#xans_myshop_avail_mileage'),

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

            if (this.__$target4.length > 0) {
                return true;
            }

            if (this.__$target5.length > 0) {
                return true;
            }

            if (this.__$target6.length > 0) {
                return true;
            }

            if (this.__$target7.length > 0) {
                return true;
            }

            if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('customer', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
                return true;
            }
        }

        return false;
    },

    restoreCache: function()
    {
        // 특정 경로 룰의 경우 복구 취소
        if (PathRoleValidator.isInvalidPathRole()) {
            return false;
        }

        // 쿠키로부터 데이터 획득
        var sAvailMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_avail_mileage_' + EC_SDE_SHOP_NUM);
        var sReturnedMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_returned_mileage_' + EC_SDE_SHOP_NUM);
        var sUnavailMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_unavail_mileage_' + EC_SDE_SHOP_NUM);
        var sUsedMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_used_mileage_' + EC_SDE_SHOP_NUM);

        // 데이터가 하나라도 없는경우 복구 실패
        if (sAvailMileage === null
            || sReturnedMileage === null
            || sUnavailMileage === null
            || sUsedMileage === null
        ) {
            return false;
        }

        // 전체 마일리지 계산
        var sTotalMileage = (parseFloat(sAvailMileage) +
            parseFloat(sUnavailMileage) +
            parseFloat(sUsedMileage)).toString();

        // 단위정보를 계산하여 필드에 셋
        this.__sAvailMileage = parseFloat(sAvailMileage).toFixed(2);
        this.__sReturnedMileage = parseFloat(sReturnedMileage).toFixed(2);
        this.__sUnavailMileage = parseFloat(sUnavailMileage).toFixed(2);
        this.__sUsedMileage = parseFloat(sUsedMileage).toFixed(2);
        this.__sTotalMileage = parseFloat(sTotalMileage).toFixed(2);

        return true;
    },

    setData: function(aData)
    {
        this.__sAvailMileage = parseFloat(aData['avail_mileage'] || 0).toFixed(2);
        this.__sUsedMileage = parseFloat(aData['used_mileage'] || 0).toFixed(2);
        this.__sTotalMileage = parseFloat(aData['total_mileage'] || 0).toFixed(2);
        this.__sUnavailMileage = parseFloat(aData['unavail_mileage'] || 0).toFixed(2);
        this.__sReturnedMileage = parseFloat(aData['returned_mileage'] || 0).toFixed(2);
    },

    execute: function()
    {
        this.__$target.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sAvailMileage));
        this.__$target2.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sAvailMileage));
        this.__$target3.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sUsedMileage));
        this.__$target4.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sTotalMileage));
        this.__$target5.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sUnavailMileage));
        this.__$target6.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sReturnedMileage));
        this.__$target7.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sAvailMileage));
    },

    getData: function()
    {
        return {
            available_mileage: this.__sAvailMileage,
            used_mileage: this.__sUsedMileage,
            total_mileage: this.__sTotalMileage,
            returned_mileage: this.__sReturnedMileage,
            unavailable_mileage: this.__sUnavailMileage
        };
    }
};
/**
 * 비동기식 데이터 - 예치금
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Deposit');
CAPP_ASYNC_METHODS.Deposit = {
    __sTotalDeposit: null,
    __sAllDeposit: null,
    __sUsedDeposit: null,
    __sRefundWaitDeposit: null,
    __sMemberTotalDeposit: null,

    __$target: $('#xans_myshop_deposit'),
    __$target2: $('#xans_myshop_bankbook_deposit'),
    __$target3: $('#xans_myshop_summary_deposit'),
    __$target4: $('#xans_myshop_summary_all_deposit'),
    __$target5: $('#xans_myshop_summary_used_deposit'),
    __$target6: $('#xans_myshop_summary_refund_wait_deposit'),
    __$target7: $('#xans_myshop_total_deposit'),

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

            if (this.__$target4.length > 0) {
                return true;
            }

            if (this.__$target5.length > 0) {
                return true;
            }

            if (this.__$target6.length > 0) {
                return true;
            }

            if (this.__$target7.length > 0) {
                return true;
            }

            if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('customer', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
                return true;
            }
        }

        return false;
    },

    restoreCache: function()
    {
        // 특정 경로 룰의 경우 복구 취소
        if (PathRoleValidator.isInvalidPathRole()) {
            return false;
        }

        // 쿠키로부터 데이터 획득
        var sAllDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_all_deposit_' + EC_SDE_SHOP_NUM);
        var sUsedDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_used_deposit_' + EC_SDE_SHOP_NUM);
        var sRefundWaitDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_deposit_refund_wait_' + EC_SDE_SHOP_NUM);
        var sMemberTotalDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_member_total_deposit_' + EC_SDE_SHOP_NUM);

        // 데이터가 하나라도 없는경우 복구 실패
        if (sAllDeposit === null
            || sUsedDeposit === null
            || sRefundWaitDeposit === null
            || sMemberTotalDeposit === null
        ) {
            return false;
        }

        // 사용 가능한 예치금 계산
        var sTotalDeposit = (parseFloat(sAllDeposit) -
            parseFloat(sUsedDeposit) -
            parseFloat(sRefundWaitDeposit)).toString();

        // 단위정보를 계산하여 필드에 셋
        this.__sTotalDeposit = parseFloat(sTotalDeposit).toFixed(2);
        this.__sAllDeposit = parseFloat(sAllDeposit).toFixed(2);
        this.__sUsedDeposit = parseFloat(sUsedDeposit).toFixed(2);
        this.__sRefundWaitDeposit = parseFloat(sRefundWaitDeposit).toFixed(2);
        this.__sMemberTotalDeposit = parseFloat(sMemberTotalDeposit).toFixed(2);

        return true;
    },

    setData: function(aData)
    {
        this.__sTotalDeposit = parseFloat(aData['total_deposit'] || 0).toFixed(2);
        this.__sAllDeposit = parseFloat(aData['all_deposit'] || 0).toFixed(2);
        this.__sUsedDeposit = parseFloat(aData['used_deposit'] || 0).toFixed(2);
        this.__sRefundWaitDeposit = parseFloat(aData['deposit_refund_wait'] || 0).toFixed(2);
        this.__sMemberTotalDeposit = parseFloat(aData['member_total_deposit'] || 0).toFixed(2);
    },

    execute: function()
    {
        this.__$target.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sTotalDeposit));
        this.__$target2.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sTotalDeposit));
        this.__$target3.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sTotalDeposit));
        this.__$target4.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sAllDeposit));
        this.__$target5.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sUsedDeposit));
        this.__$target6.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sRefundWaitDeposit));
        this.__$target7.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sMemberTotalDeposit));
    },

    getData: function()
    {
        return {
            total_deposit: this.__sTotalDeposit,
            used_deposit: this.__sUsedDeposit,
            refund_wait_deposit: this.__sRefundWaitDeposit,
            all_deposit: this.__sAllDeposit,
            member_total_deposit: this.__sMemberTotalDeposit
        };
    }
};
/**
 * 비동기식 데이터 - 위시리스트
 */
CAPP_ASYNC_METHODS.aDatasetList.push('WishList');
CAPP_ASYNC_METHODS.WishList = {
    STORAGE_KEY: 'localWishList' +  EC_SDE_SHOP_NUM,
    __$targetWishIcon: $('.icon_img.ec-product-listwishicon'),
    __$targetWishList: $('.xans-myshop-wishlist'),
    __aWishList: null,
    __aTags_on: null,
    __aTags_off: null,

    isUse: function()
    {
        if (this.__$targetWishIcon.length > 0 || this.__$targetWishList.length > 0
        || CAPP_ASYNC_METHODS.EC_PATH_ROLE === 'PRODUCT_DETAIL') {
            return true;
        }
        return false;
    },

    restoreCache: function()
    {
        if (!window.sessionStorage) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null) {
            return false;
        }

        var aStorageData = $.parseJSON(sSessionStorageData);
        if (this.__$targetWishList.length > 0 || aStorageData['isLogin'] !== CAPP_ASYNC_METHODS.IS_LOGIN) {
            this.clearStorage();
            return false;
        }

        var aWishList = aStorageData['wishList'];
        this.__aTags_on = aStorageData['on_tags'];
        this.__aTags_off = aStorageData['off_tags'];
        this.__aWishList = [];
        for (var i = 0; i < aWishList.length; i++) {
            var aTempWishList = [];
            aTempWishList.product_no = aWishList[i];
            this.__aWishList.push(aTempWishList);
        }
        return true;
    },

    setData: function(aData)
    {
        if (aData.hasOwnProperty('wishList') === false || aData.hasOwnProperty('on_tags') === false) {
            return;
        }

        this.__aWishList = aData.wishList;
        this.__aTags_on = aData.on_tags;
        this.__aTags_off = aData.off_tags;

        if (window.sessionStorage) {
            var aWishList = [];

            for (var i = 0; i < aData.wishList.length; i++) {
                aWishList.push(aData.wishList[i].product_no);
            }

            var oNewStorageData = {
                'wishList' : aWishList,
                'on_tags' : aData.on_tags,
                'off_tags' : aData.off_tags,
                'isLogin' : CAPP_ASYNC_METHODS.IS_LOGIN
            };

            if (typeof oNewStorageData !== 'undefined') {
                sessionStorage.setItem(this.STORAGE_KEY , JSON.stringify(oNewStorageData));
            }
        }
    },

    execute: function()
    {
        var aWishList = this.__aWishList;
        var aTagsOn = this.__aTags_on;
        var aTagsOff = this.__aTags_off;

        if (aWishList === null || typeof aWishList === 'undefined') {
            aWishList = [];
        }

        var oTarget = $('.ec-product-listwishicon');
        for (var sKey in aTagsOff) {
            oTarget.attr(sKey, aTagsOff[sKey]);
        }

        for (var i = 0; i < aWishList.length; i++) {
            assignAttribute(aWishList[i]);
        }

        /**
         * oTarget 엘레먼트에 aData의 정보를 어싸인함.
         * @param array aData 위시리스트 정보
         */
        function assignAttribute(aData)
        {
            var iProductNo = aData['product_no'];
            var oTarget = $('.ec-product-listwishicon[productno="'+iProductNo+'"]');

            // oTarget의 src, alt, icon_status attribute의 값을 할당
            for (var sKey in aTagsOn) {
                oTarget.attr(sKey, aTagsOn[sKey]);
            }
        }

    },

    /**
     * 세션스토리지 삭제
     */
    clearStorage: function()
    {
        if (!window.sessionStorage) {
            return;
        }
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    /**
     * sCommand에 따른 sessionStorage Set
     * @param iProductNo
     * @param sCommand 추가(add)/삭제(del) sCommand
     */
    setSessionStorageItem: function(iProductNo, sCommand)
    {
        if (this.isUse() === false) {
            return;
        }

        var oStorageData = $.parseJSON(sessionStorage.getItem(this.STORAGE_KEY));
        var aWishList = oStorageData['wishList'];
        var iLimit = 200;

        if (aWishList === null) {
            aWishList = [];
        }

        var iProductNo = parseInt(iProductNo, 10);
        var iIndex = aWishList.indexOf(iProductNo);

        if (sCommand === 'add') {
            if (aWishList.length >= iLimit) {
                aWishList.splice(aWishList.length - 1, 1);
            }
            if (iIndex < 0) {
                aWishList.unshift(iProductNo);
            }
        } else {
            if (iIndex > -1) {
                aWishList.splice(iIndex, 1);
            }
        }

        oStorageData['wishList'] = aWishList;
        sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify(oStorageData));
    }
};

/**
 * 비동기식 데이터 - 관심상품 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Wishcount');
CAPP_ASYNC_METHODS.Wishcount = {
    __iWishCount: null,

    __$target: $('#xans_myshop_interest_prd_cnt'),
    __$target2: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_interest_prd_cnt'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        if (this.__$target2.length > 0) {
            return true;
        }

        if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('personal', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'wishcount_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iWishCount = parseInt(aCookieValue[1], 10);
            return true;
        }

        return false;
    },

    setData: function(sData)
    {
        this.__iWishCount = Number(sData);
    },

    execute: function()
    {
        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target.html(this.__iWishCount + '개');
        } else {
            this.__$target.html(this.__iWishCount);
        }

        this.__$target2.html(this.__iWishCount);
    },

    getData: function()
    {
        return {
            count: this.__iWishCount
        };
    }
};
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

