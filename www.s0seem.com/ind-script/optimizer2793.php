var EC_SHOP_FRONT_NEW_OPTION_EXTRA_DIRECT_BASKET = {
    /**
     * 장바구니 담기 Ajax완료여부
     */
    bIsLoadedPriceAjax : false,

    /**
     * 옵션 선택시 장바구니 바로 담기 기능 사용 여부
     */
    bIsUseDirectBasket : false,

    /**
     * 옵션선택후 주석옵션이 선언되어있다면 바로 장바구니담기
     * @param oOptionChoose 구분할 옵션박스 object
     */
    completeCallback : function(oOptionChoose) {
        if (this.isAvailableDirectBasket(oOptionChoose) === false) {
            return;
        }

        var oInterval = setInterval(function () {
            if (EC_SHOP_FRONT_NEW_OPTION_EXTRA_DIRECT_BASKET.bIsLoadedPriceAjax === true) {
                
                //장바구니 담기
                product_submit(2, '/exec/front/order/basket/', this);
                EC_SHOP_FRONT_NEW_OPTION_EXTRA_DIRECT_BASKET.bIsLoadedPriceAjax = false;

                //옵션박스 제거
                $('.option_box_del').each(function() {
                    $(this).trigger('click');
                });

                //옵션선택값 초기화
                $('[product_option_area="' + $(oOptionChoose).attr('product_option_area') + '"]').each(function() {
                    EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(this, '*', true, true);
                });

                clearInterval(oInterval);
            }
        }, 300);
    },

    /**
     * 사용가능한 상태인지 확인
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @return boolean true : 사용가능, false : 사용불가
     */
    isAvailableDirectBasket : function (oOptionChoose) {

        if (this.bIsUseDirectBasket === false) {
            return false;
        }

        oOptionChoose = EC_SHOP_FRONT_NEW_OPTION_COMMON.setOptionBoxElement(oOptionChoose);
        if ($(oOptionChoose).attr('product_type') !== 'product_option') {
            return false;
        }

        return true;
    },

    /**
     * 옵션선택시 장바구니 바로담기 기능
     */
    setUseDirectBasket : function ()
    {
        this.bIsUseDirectBasket = true;
    }


};

var EC_SHOP_FRONT_NEW_OPTION_EXTRA_FUNDING = {
    sCurrentCompositionCode : null,
    prefetch : function(oOptionChoose)
    {
    },
    completeCallback : function(oOptionChoose)
    {
    },
    eachCallback : function(oOptionChoose)
    {
        if (typeof($(oOptionChoose).attr('composition-code')) === 'undefined') {
            return true;
        }
        var sCompositionCode = $(oOptionChoose).attr('composition-code');
        $('input.selected-funding-item[composition-code="'+sCompositionCode+'"]').remove();
        this.sCurrentCompositionCode = sCompositionCode;
        var sItemCode = EC_SHOP_FRONT_NEW_OPTION_COMMON.getItemCode(oOptionChoose);
        if (sItemCode === false) {
            return true;
        }
        /*
        var oItemCode = $('<input>').attr({
            'type' : 'hidden',
            'composition-code' : sCompositionCode
        }).addClass('selected-funding-item option_box_id').val(sItemCode);
        $('.EC-funding-checkbox[value="'+sCompositionCode+'"]').append(oItemCode);
         */
    }
};
/**
 * 뉴상품 옵션 셀렉트 공통파일
 */
var EC_SHOP_FRONT_NEW_OPTION_COMMON = {
    cons : null,

    data : null,

    bind : null,

    validation : null,

    /**
     * 페이지 로드가 완료되었는지
     */
    isLoad : false,

    initObject : function() {
        this.cons = EC_SHOP_FRONT_NEW_OPTION_CONS;
        this.data = EC_SHOP_FRONT_NEW_OPTION_DATA;
        this.bind = EC_SHOP_FRONT_NEW_OPTION_BIND;
        this.validation = EC_SHOP_FRONT_NEW_OPTION_VALIDATION;
    },

    /**
     * 페이지 로딩시 초기화
     */
    init : function() {
        var oThis = this;
        //조합분리형이지만 옵션이 1개인경우
        var bIsSolidOption = false;
        //첫 로드시에는 첫번째 옵션만 검색
        $('select[option_select_element="ec-option-select-finder"][option_sort_no="1"], ul[option_select_element="ec-option-select-finder"][option_sort_no="1"]').each(function() {
            //연동형이 아닌고 분리형일때만 실행
            bIsSolidOption = false;
            if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isSeparateOption(this) === true) {
                if (Olnk.isLinkageType($(this).attr('option_type')) === false) {
                    if (parseInt($('[product_option_area="'+oThis.getOptionSelectGroup(this)+'"]').length) < 2) {
                        bIsSolidOption = true;
                    }

                    oThis.data.initializeSoldoutFlag($(this));

                    oThis.setOptionText($(this), bIsSolidOption);
                }
            }
        });
    },

    /**
     * 옵션상품인데 모든옵션이 판매안함+진열안함일때 예외처리
     * @param sProductOptionID 옵션 Selectbox ID
     */
    isValidOptionDisplay : function(sProductOptionID)
    {
        var iOptionCount = 0;
        $('select[option_select_element="ec-option-select-finder"][id^="' + sProductOptionID + '"], ul[option_select_element="ec-option-select-finder"][ec-dev-id^="' + sProductOptionID + '"]').each(function() {

            if (EC_SHOP_FRONT_NEW_OPTION_COMMON.isOptionStyleButton(this) === true) {
                iOptionCount += $(this).find('li').length;
            } else {
                iOptionCount += $(this).find('option').length - 2;
            }
        });

        return iOptionCount > 0;
    },

    /**
     * 각 옵션에대해 전체품절인지 확인후
     */
    setOptionText : function(oOptionChoose, bIsSolidOption) {
        var bIsStyleButton = this.isOptionStyleButton(oOptionChoose);
        var oTargetOption = null;
        if (bIsStyleButton === true) {
            oTargetOption = $(oOptionChoose).find('li');
        } else {
            oTargetOption = $(oOptionChoose).find('option').filter('[value!="*"][value!="**"]');
        }

        var bIsDisplaySolout = EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isSoldoutOptionDisplay();
        var iProductNum = this.getOptionProductNum(oOptionChoose);
        var oThis = this;

        $(oTargetOption).each(function() {
            var sValue = oThis.getOptionValue(oOptionChoose, $(this));
            var isSoldout = EC_SHOP_FRONT_NEW_OPTION_DATA.getSoldoutFlag(iProductNum, sValue);
            var bIsDisplay = EC_SHOP_FRONT_NEW_OPTION_DATA.getDisplayFlag(iProductNum, sValue);
            var sOptionText = oThis.getOptionText(oOptionChoose, this);

            if (bIsDisplay === false) {
                $(this).remove();
                return;
            }

            //조합분리형인데 옵션이 1개인경우 옵션추가금액을 세팅)
            if (bIsSolidOption === true) {
                var sItemCode = oThis.data.getItemCode(iProductNum, sValue);

                var sAddText = EC_SHOP_FRONT_NEW_OPTION_BIND.setAddText(iProductNum, sItemCode, oOptionChoose);
                if (sAddText !== '') {
                    sOptionText = sOptionText + sAddText;
                }
            }

            if (isSoldout === true) {
                //품절표시안함일때 안보여주도록함(첫번째옵션이라서.. 어쩔수없이 여기서 ㅋ)
                //두번째옵션부터는 동적생성이니깐 bind에서처리
                if (bIsDisplaySolout === false) {
                    $(this).remove();
                    return;
                }
                //해당 옵션값 객첵가 넘어오면 바로 적용
                if (bIsStyleButton === true) {
                    $(this).addClass(EC_SHOP_FRONT_NEW_OPTION_CONS.BUTTON_OPTION_SOLDOUT_CLASS);
                }

                //분리형이면서 전체상품이 품절이면
                if (bIsSolidOption !== true) {
                    var sSoldoutText = EC_SHOP_FRONT_NEW_OPTION_COMMON.getSoldoutText(oOptionChoose, sValue);
                    sOptionText = sOptionText +  ' ' + sSoldoutText;

                }
            }

            oThis.setText(this, sOptionText);

        });
    },

    /**
     * 품목이 아닌 각 옵션별로 전체품절인지 황니후 품절이면 품절문구 반환
     * @param oOptionChoose
     * @param sValue
     * @returns {String}
     */
    getSoldoutText : function(oOptionChoose, sValue) {
        var sText = '';

        var iProductNum = EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionProductNum(oOptionChoose);

        if (EC_SHOP_FRONT_NEW_OPTION_DATA.getSoldoutFlag(iProductNum, sValue) === true) {
            return '[' + EC_SHOP_FRONT_NEW_OPTION_EXTRA_SOLDOUT.getSoldoutDiplayText(iProductNum) + ']';
        }

        return sText;
    },

    /**
     * 셀렉트박스형 옵션인지 버튼형 옵션이지 확인
     * @param oOptionChoose 구분할 옵션박스 object
     * @returns true => 버튼형옵션, false => 기존 select형 옵션
     */
    isOptionStyleButton : function(oOptionChoose) {
        var sOptionStyle = $(oOptionChoose).attr(this.cons.OPTION_STYLE);
        if (sOptionStyle === 'preview' || sOptionStyle === 'button' || sOptionStyle === 'radio') {
            return true;
        }

        return false;
    },

    /**
     * 해당 옵션의 옵션출력타입(분리형 : S, 일체형 : C)
     * @param oOptionChoose 구분할 옵션박스 object
     * @returns 옵션타입
     */
    getOptionListingType : function(oOptionChoose)
    {
        oOptionChoose = this.setOptionBoxElement(oOptionChoose);
        return $(oOptionChoose).attr(this.cons.OPTION_LISTING_TYPE);
    },

    /**
     * 해당 옵션의 옵션타입(조합형 : T, 연동형 : E, 독립형 : F)
     * @param oOptionChoose 구분할 옵션박스 object
     * @returns 옵션타입
     */
    getOptionType : function(oOptionChoose) {
        oOptionChoose = this.setOptionBoxElement(oOptionChoose);
        return $(oOptionChoose).attr(this.cons.OPTION_TYPE);
    },

    /**
     * 해당 옵션의 옵션그룹명을 가져온다
     * @param oOptionChoose 구분할 옵션박스 object
     * @returns 옵션그룹이름
     */
    getOptionSelectGroup : function(oOptionChoose) {
        return $(oOptionChoose).attr(this.cons.GROUP_ATTR_NAME);
    },

    /**
     * sOptionStyleConfirm 에 해당하는 옵션인지 확인
     * @param oOptionChoose 구분할 옵션박스 object
     * @param sOptionStyleConfirm 옵션스타일(EC_SHOP_FRONT_NEW_OPTION_CONS : OPTION_STYLE_PREVIEW 또는 OPTION_STYLE_BUTTON)
     * @return boolean 확인결과
     */
    isOptionStyle : function(oOptionChoose, sOptionStyleConfirm) {
        var sOptionStype = $(oOptionChoose).attr(this.cons.OPTION_STYLE);
        if (sOptionStype === sOptionStyleConfirm) {
            return true;
        }

        return false;
    },

    /**
     * 해당 옵션의 선택된 Text내용을 가져옴
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @returns 옵션 내용Text
     */
    getOptionSelectedText : function(oOptionChoose) {
        if (this.isOptionStyleButton(oOptionChoose) === true) {
            return $(oOptionChoose).find('li.' + this.cons.BUTTON_OPTION_SELECTED_CLASS).attr('title');
        } else {
            return $(oOptionChoose).find('option:selected').text();
        }
    },

    /**
     * 해당 옵션의 선택된 값을 가져옴
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @returns string 옵션값
     */
    getOptionSelectedValue : function(oOptionChoose) {
        oOptionChoose = this.setOptionBoxElement(oOptionChoose);

        if (this.isOptionStyleButton(oOptionChoose) === true) {
            var oTarget = $(oOptionChoose).find('li.' + this.cons.BUTTON_OPTION_SELECTED_CLASS);

            //버튼형옵션은 *, **값이 없기떄문에 선택된게 없다면 강제리턴
            if (oTarget.length < 1) {
                return '*';
            } else {
                return oTarget.attr('option_value');
            }
        } else {
            var sValue = $(oOptionChoose).val();
            return ($.trim(sValue) === '') ? '*' : sValue;
        }
    },

    /**
     * 해당 Element의 값을 가져옴
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @param oOptionChooseElement 값을 가져오려는 옵션 항목
     * @returns string 옵션값
     */
    getOptionValue : function(oOptionChoose, oOptionChooseElement) {
        if (this.isOptionStyleButton(oOptionChoose) === true) {
            return $(oOptionChooseElement).attr('option_value');
        } else {
            return $(oOptionChooseElement).val();
        }
    },

    /**
     * 해당 Element의 Text값을 가져옴
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @param oOptionChooseElement 값을 가져오려는 옵션 항목
     * @returns string 옵션값
     */
    getOptionText : function(oOptionChoose, oOptionChooseElement) {
        if (this.isOptionStyleButton(oOptionChoose) === true) {
            return $(oOptionChooseElement).attr('title');
        } else {
            return $(oOptionChooseElement).text();
        }
    },

    /**
     * 선택된 옵션의 Element를 가져온다
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @returns 선택옵션의 DOM Element
     */
    getOptionSelectedElement : function(oOptionChoose) {
        if (this.isOptionStyleButton(oOptionChoose) === true) {
            return $(oOptionChoose).find('li.' + this.cons.BUTTON_OPTION_SELECTED_CLASS);
        } else {
            return $(oOptionChoose).find('option:selected');
        }
    },

    getOptionLastSelectedElement : function(sOptionGroup)
    {
        var oOptionGroup = this.getGroupOptionObject(sOptionGroup);
        var aTempResult = [];
        oOptionGroup.each(function(i) {
            if (EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSelectedValue(oOptionGroup[i]) !== '*') {
                aTempResult.push(oOptionGroup[i]);
            }
        });
        return $(aTempResult[aTempResult.length - 1]);
    },

    /**
     * 해당 옵션의 상품번호를 가져옴
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @returns int 상품번호
     */
    getOptionProductNum : function(oOptionChoose) {
        return parseInt($(oOptionChoose).attr(this.cons.OPTION_PRODUCT_NUM));
    },

    /**
     * 해당 옵션의 순번을 가져옴
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @returns int 해당 옵션의 순서 번호
     */
    getOptionSortNum : function(oOptionChoose) {
        oOptionChoose = this.setOptionBoxElement(oOptionChoose);
        return parseInt($(oOptionChoose).attr(this.cons.OPTION_SORT_NUM));
    },

    /**
     * 이벤트 옵션까지에대해 현재까지 선택된 옵션값 배열
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @param bIsString 값이 true이면 선택된 옵션들을 구분자로 join해서 받아온다
     * @returns 현재까지 선택된 옵션값 배열
     */
    getAllSelectedValue : function(oOptionChoose, bIsString) {
        var iOptionSortNum = this.getOptionSortNum(oOptionChoose);

        //지금까지 선택된 옵션의 값
        var aSelectedValue = [];
        $('[product_option_area="'+$(oOptionChoose).attr(this.cons.GROUP_ATTR_NAME)+'"]').each(function() {
            if (parseInt($(this).attr('option_sort_no')) <= iOptionSortNum) {
                aSelectedValue.push(EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSelectedValue($(this)));
            }
        });

        return (bIsString === true) ? aSelectedValue.join(this.cons.OPTION_GLUE) : aSelectedValue;
    },

    /**
     * iSelectedOptionSortNum 의 하위옵션을 초기화(0일때는 모두초기화)ㅅ
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @param iSelectedOptionSortNum 하위옵션을 초기화할 대상 옵션 순번
     */
    setInitializeDefault : function(oOptionChoose, iSelectedOptionSortNum) {
        var sOptionGroup = $(oOptionChoose).attr(this.cons.GROUP_ATTR_NAME);
        var iProductNum = this.getOptionProductNum(oOptionChoose);
        this.bind.setInitializeDefault(sOptionGroup, iSelectedOptionSortNum, iProductNum);
    },

    /**
     * 외부에서 기존스크립트가 호출할때는 버튼형옵션객체가 아니라 숨겨진 셀렉트박스에서 호출하므로 버튼형옵션객체를 찾아서 리턴
     */
    setOptionBoxElement : function(oOptionChoose) {
        if (typeof($(oOptionChoose).attr('product_option_area_select')) !== 'undefined') {
            oOptionChoose = $('ul[product_option_area="'+$(oOptionChoose).attr('product_option_area_select')+'"][ec-dev-id="'+$(oOptionChoose).attr('id')+'"]');
        }

        return oOptionChoose;
    },

    /**
     * 선택한 옵션 하위옵션 모두 초기화(추가구성상품에서 연동형옵션때문에...)
     * @param oOptionChoose
     */
    setAllClear : function(oOptionChoose) {
        oOptionChoose = this.setOptionBoxElement(oOptionChoose);

        var iSortNo = parseInt(this.getOptionSortNum(oOptionChoose));
        $(this.getGroupOptionObject(this.getOptionSelectGroup(oOptionChoose))).each(function() {
            if (iSortNo < parseInt(EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSortNum($(this)))) {
                EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue($(this), '*');
            }
        });
    },

    /**
     * 멀티옵션(구스킨)에서 사용할때 해당 옵션의 id값을 바꾸는기능이 있어서 추가
     * @param oOptionChooseOrg
     * @param sId
     */
    setID : function(oOptionChooseOrg, sId) {
        if ($(oOptionChooseOrg).attr('option_style') === 'select') {
            oOptionChoose = oOptionChooseOrg;
        } else {
            oOptionChoose = $(oOptionChooseOrg).parent().find('ul[option_style="preview"], [option_style="button"], [option_style="radio"]');
        }

        if (this.isOptionStyleButton(oOptionChoose) === true) {
            $(oOptionChoose).attr('ec-dev-id', sId);
            $(oOptionChooseOrg).attr('id', sId);
        } else {
            $(oOptionChoose).attr('id', sId);
        }
    },

    /**
     * 멀티옵션(구스킨)에서 사용할때 해당 옵션의 id값을 바꾸는기능이 있어서 추가
     * @param oOptionChooseOrg
     * @param sGroupID
     */
    setGroupArea : function(oOptionChooseOrg, sGroupID) {
        var oOptionChoose = null;
        if ($(oOptionChooseOrg).attr('option_style') === 'select') {
            oOptionChoose = oOptionChooseOrg;
        } else {
            oOptionChoose = $(oOptionChooseOrg).parent().find('ul[option_style="preview"], [option_style="button"], [option_style="radio"]');
        }

        if (this.isOptionStyleButton(oOptionChoose) === true) {
            $(oOptionChoose).attr('product_option_area', sGroupID);
            $(oOptionChooseOrg).attr('product_option_area_select', sGroupID);
        } else {
            $(oOptionChoose).attr('product_option_area', sGroupID);
        }
    },

    /**
     * 해당 선택한 옵션의 text값을 세팅
     */
    setText : function(oSelectecOptionChoose, sText) {
        oOptionChoose = this.setOptionBoxElement($(oSelectecOptionChoose).parent());

        if (this.isOptionStyleButton(oOptionChoose) === true) {
            var sValue = $(oSelectecOptionChoose).attr('option_value');
            var oTarget = $(oOptionChoose).find('li[option_value="'+sValue+'"]');
            $(oTarget).attr('title', sText);

        }

        if (this.isOptionStyleButton($(oSelectecOptionChoose).parent()) !== true) {
            $(oSelectecOptionChoose).text(sText);
        }
    },

    /**
     * 추가 이미지에서 추출한 품목 코드를 바탕으로 옵션 선택
     * @param sItemCode 품목 코드
     */
    setValueByAddImage : function(sItemCode) {
        if (typeof(sItemCode) === 'undefined') {
            return;
        }

        this.selectItemCode('product_option_' + iProductNo + '_0', sItemCode);
    },

    /**
     * 외부에서 옵션을 선택하는걸 호출할 경우 해당 옵션의 product_option_area값과 품목코드를 전달
     * @param sOptionArea 옵션 element의 product_option_area값 attribute값
     * @param sItemCode 품목코드
     */
    selectItemCode : function(sOptionArea, sItemCode)
    {
        var oSelect = $('[product_option_area="' + sOptionArea + '"]');
        oSelect = this.setOptionBoxElement(oSelect);

        var sOptionListType = this.getOptionListingType(oSelect);
        var sOptionType = this.getOptionType(oSelect);

        //조합일체형이나 독립형인경우
        if (sOptionListType === 'C' || sOptionType === 'F') {
            this.setValue(oSelect, sItemCode, true, true);
        } else {
            var iProductNo = this.getOptionProductNum(oSelect);
            var oItemData = this.getProductStockData(iProductNo);

            if (oItemData === null) {
                return;
            }

            if (oItemData.hasOwnProperty(sItemCode) === false) {
                return;
            }

            var aOptionValue = oItemData[sItemCode].option_value_orginal;

            oSelect.each(function (i) {
                EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(this, aOptionValue[i], true, true);
            });
        }
    },

    /**
     * 해당 Element의 값을 강제로 지정
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @param sValue set 하려는 value
     * @param bIsInitialize false인 경우에는 클릭이벤트를 발생하지 않도록 한다
     * @param bChange change 이벤트 발생 여부
     */
    setValue : function(oOptionChoose, sValue, bIsInitialize, bChange) {
        // 값 세팅시 각 페이지에서 $(this).val()로 값을 지정할경우
        // 본래 버튼형 옵션이면 타겟을 버튼형 옵션으로 이어준다
        oOptionChoose = this.setOptionBoxElement(oOptionChoose);

        if (this.isOptionStyleButton(oOptionChoose) === true) {
            //옵션이 선택되어있는상태면 초기화후 선택
            if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isOptionSelected(oOptionChoose) === true) {
                $(oOptionChoose).find('li.' + this.cons.BUTTON_OPTION_SELECTED_CLASS).trigger('click');
            }

            var oTarget = $(oOptionChoose).find('li[option_value="' + sValue + '"]');

            if ($(oTarget).length > 0) {
                $(oTarget).trigger('click');
            } else {
                if (bIsInitialize !== false) {
                    // 선택값이 없다면 셀렉트박스 초기화
                    var iProductNum = this.getOptionProductNum(oOptionChoose);
                    var iSelectedOptionSortNum = this.getOptionSortNum(oOptionChoose);
                    var sOptionGroup = this.getOptionSelectGroup(oOptionChoose);
                    var bIsRequired = EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isRequireOption(oOptionChoose);

                    if (EC_SHOP_FRONT_NEW_OPTION_BIND.isEnabledOptionInit(oOptionChoose) === true) {
                        EC_SHOP_FRONT_NEW_OPTION_BIND.setInitializeDefault(sOptionGroup, iSelectedOptionSortNum, iProductNum, bIsRequired);
                    }

                    EC_SHOP_FRONT_NEW_OPTION_EXTRA_DISPLAYITEM.eachCallback(oOptionChoose);
                    EC_SHOP_FRONT_NEW_OPTION_BIND.setRadioButtonSelect(oTarget, oOptionChoose, false);
                }

                this.setTriggerSelectbox(oOptionChoose, sValue);
            }
        } else {
            $(oOptionChoose).val(sValue);

            if (typeof(bChange) !== 'undefined') {
                $(oOptionChoose).trigger('change');
            }
        }
    },

    /**
     * 버튼 또는 이미지형 옵션일 경우 동적 selectbox와 동기화 시킴
     * @param oOptionChoose 선택한 옵션 Object
     * @param sValue set 하려는 value
     * @param bIsTrigger 셀렉트박스의 change 이벤트를 발생시키지 않을때(ex:모바일의 옵션선택 레이어..)
     */
    setTriggerSelectbox : function(oOptionChoose, sValue, bIsTrigger)
    {
        if (this.isOptionStyleButton(oOptionChoose) === true) {
            var oTargetSelect = $('select[product_option_area_select="' + $(oOptionChoose).attr('product_option_area') + '"][id="' + $(oOptionChoose).attr('ec-dev-id') + '"]');
            var bChange = true;

            var sText = '';
            if (this.validation.isItemCode(sValue) === false) {
                sValue = '*';
                sText = 'empty';

                bChange = false;
            } else {
                sValue = this.getOptionSelectedValue(oOptionChoose);
                sText = this.getOptionSelectedText(oOptionChoose);
            }

            if (sValue !== '*') {
                $(oTargetSelect).find('option[value="' + sValue + '"]').remove('option');

                var sOptionsHtml = this.cons.OPTION_STYLE_SELECT_HTML.replace('[value]', sValue).replace('[text]', sText);

                $(oTargetSelect).append($(sOptionsHtml));
            }

            $(oTargetSelect).val(sValue);

            if (bChange === true && bIsTrigger !== false) {
                $(oTargetSelect).trigger('change');
            }
        }
    },

    /**
     * 해당 상품의 옵션 재고 관련 데이터를 리턴
     * @param iProductNum 상품번호
     * @returns option_stock_data 데이터
     */
    getProductStockData : function(iProductNum) {
        return this.data.getProductStockData(iProductNum);
    },

    /**
     * 선택상품의 아이템코드를 반환(선택이 안되어있다면 false)
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @returns 아이템 코드 OR false
     */
    getItemCode : function(oOptionChoose) {
        //분리조합형일경우
        if (this.validation.isSeparateOption(oOptionChoose) === true) {
            var sSelectedValue = this.getAllSelectedValue(oOptionChoose, true);
            var iProductNum = this.getOptionProductNum(oOptionChoose);
            return this.data.getItemCode(iProductNum, sSelectedValue);
        }

        //그외의 경우에는 현재 선택된 옵션의 value가 아이템코드
        var sItemCode = this.getOptionSelectedValue(oOptionChoose);

        return (this.validation.isItemCode(sItemCode) === true) ? sItemCode : false;
    },

    /**
     * 해당 그룹내의 모든옵션에대해 선택된 품목코드를 반환
     * @param sOptionGroup 옵션 그룹 (@see : EC_SHOP_FRONT_NEW_OPTION_GROUP_CONS)
     * @param bIsAbleSoldout 품절품목에 대한 아이템코드도 포함
     * @returns array 선택된 아이템코드 배열
     */
    getGroupItemCodes : function(sOptionGroup, bIsAbleSoldout) {
        var aItemCode = [];
        var sItemCode = '';
        var oTarget = $('[' + this.cons.GROUP_ATTR_NAME + '^="' + sOptionGroup + '"]');

        //뉴스킨인 경우에는 옵션박스 레이어에 생성된 input에서 가져온다
        if (isNewProductSkin() === true) {
            $('.' + EC_SHOP_FRONT_NEW_OPTION_GROUP_CONS.DETAIL_OPTION_BOX_PREFIX).each(function() {
                //옵션박스에 생성된 input태그이므로 val()로 가져온다
                sItemCode = $(this).val();
                if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isItemCode(sItemCode) === true) {
                    aItemCode.push(sItemCode);
                }
            });

            //품절품목에 대한 아이템코드도 포함시킨다 - 현재는 관심상품담을경우에 쓰이는것으로 보임
            if (bIsAbleSoldout === true) {
                $('.' + EC_SHOP_FRONT_NEW_OPTION_GROUP_CONS.DETAIL_OPTION_BOX_SOLDOUT_PREFIX).each(function() {
                    aItemCode.push($(this).val());

                    if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isItemCode(sItemCode) === true) {
                        aItemCode.push(sItemCode);
                    }
                });
            }
        } else {
            //구스킨인 경우에는 해당하는 옵션에 선택된 값만 가져옴
            $(oTarget).each(function() {
                sItemCode = EC_SHOP_FRONT_NEW_OPTION_COMMON.getItemCode(this);

                //이미 저장된 아이템코드이면 제와(분리형인경우 같은 값이 여러개 들어올수있음)
                //조합형을 따로 처리하기보다는 그냥 두는게 더 간단하다는 핑계임
                if ($.inArray(sItemCode, aItemCode) > -1) {
                    return true;//continue
                }

                if (sItemCode !== false) {
                    aItemCode.push(sItemCode);
                }
            });
        }

        return aItemCode;
    },

    /**
     * 해당 품목의 품절 여부
     * @param iProductNum 상품번호
     * @param sItemCode 품목코드
     * @returns boolean 품절여부
     */
    isSoldout : function(iProductNum, sItemCode) {
        var aStockData = this.getProductStockData(iProductNum);

        if (typeof(aStockData[sItemCode]) === 'undefined') {
            return false;
        }

        //재고를 사용하고 재고수량이 1개미만이면 품절
        if (aStockData[sItemCode].use_stock ===  true && parseInt(aStockData[sItemCode].stock_number) < 1) {
            return true;
        }

        //판매안함 상태이면 품절
        if (aStockData[sItemCode].is_selling === 'F') {
            return true;
        }

        return false;
    },

    /**
     * 진열여부 확인
     */
    isDisplay : function(iProductNum, sItemCode) {
        var aStockData = this.getProductStockData(iProductNum);

        if (typeof(aStockData[sItemCode]) === 'undefined') {
            return false;
        }

        if (aStockData[sItemCode].is_display !== 'T') {
            return false;
        }

        return true;
    },

    /**
     * sOptionGroup에 해당하는 옵션셀렉트박스의 Element를 가져온다
     * @param sOptionGroup sOptionGroup 옵션 그룹 (@see : EC_SHOP_FRONT_NEW_OPTION_GROUP_CONS)
     * @returns 해당 옵션셀렉트박스 Element전체
     */
    getGroupOptionObject : function(sOptionGroup) {
        return $('[' + this.cons.GROUP_ATTR_NAME + '^="' + sOptionGroup + '"]');
    },

    /**
     * 해당 옵션그룹에서 필수옵션의 갯수를 가져온다
     * @param sOptionGroup sOptionGroup 옵션 그룹 (@see : EC_SHOP_FRONT_NEW_OPTION_GROUP_CONS)
     * @returns 필수옵션 갯수
     */
    getRequiredOption : function(sOptionGroup) {
        return this.getGroupOptionObject(sOptionGroup).filter('[required="true"],[required="required"]');
    },

    /**
     * 해당 옵션의 전체 Value값을 가져옴(옵션그룹이 아니라 단일 옵션 셀렉츠박스)
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @returns {Array}
     */
    getAllOptionValues : function(oOptionChoose) {
        //일반 셀렉트박스일때
        var aOptionValue = [];
        if (this.isOptionStyleButton(oOptionChoose) === false) {
            $(oOptionChoose).find('option[value!="*"][value!="**"]').each(function() {
                aOptionValue.push($(this).val());
            });
        } else {
            //버튼형 옵션일경우
            $(oOptionChoose).find('li[option_value!="*"][option_value!="**"]').each(function() {
                aOptionValue.push($(this).attr('option_value'));
            });
        }

        return aOptionValue;
    },

    /**
     * 해당 옵션의 실제 id값을 리턴
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     * @returns {String}
     */
    getOptionChooseID : function(oOptionChoose) {
        var sID = '';
        if (this.isOptionStyleButton(oOptionChoose) === true) {
            sID = $(oOptionChoose).attr('ec-dev-id');
        } else {
            sID = $(oOptionChoose).attr('id');
        }

        return sID;
    }
};

$(document).ready(function() {
    EC_SHOP_FRONT_NEW_OPTION_COMMON.isLoad = true;

    //표시된 옵션 선택박스에 대해  디폴트 옵션데이터 정리
    EC_SHOP_FRONT_NEW_OPTION_DATA.setDefaultData();

    EC_SHOP_FRONT_NEW_OPTION_COMMON.init();
});

/**
 * 옵션에대한 Attribute 및 구분자 모음
 */
var EC_SHOP_FRONT_NEW_OPTION_CONS = {
    /**
     * 옵션 그룹 Attribute Key(각 상품 및 영역별 구분을 위한 값)
     */
    GROUP_ATTR_NAME : 'product_option_area',

    /**
     * 옵션 스타일 Attribute Key
     */
    OPTION_STYLE : 'option_style',

    /**
     * 상품번호 Attribute Key
     */
    OPTION_PRODUCT_NUM : 'option_product_no',

    /**
     * 각 옵션의 옵션순서 Attribute Key
     */
    OPTION_SORT_NUM : 'option_sort_no',

    /**
     * 옵션 타입 Attribute Key
     */
    OPTION_TYPE : 'option_type',

    /**
     * 옵션 출력 타입 Attribute Key
     */
    OPTION_LISTING_TYPE : 'item_listing_type',

    /**
     * 옵션 값 구분자
     */
    OPTION_GLUE : '#$%',

    /**
     * 미리보기형 옵션
     */
    OPTION_STYLE_PREVIEW : 'preview',

    /**
     * 버튼형 옵션
     */
    OPTION_STYLE_BUTTON : 'button',

    /**
     * 기존 셀렉트박스형 옵션
     */
    OPTION_STYLE_SELECT : 'select',

    /**
     * 라디오박스형 옵션
     */
    OPTION_STYLE_RADIO : 'radio',

    /**
     * 각 옵션마다 연결된 이미지 Attribute
     */
    OPTION_LINK_IMAGE : 'link_image',

    /**
     * 셀렉트박스형 옵션의 Template
     */
    OPTION_STYLE_SELECT_HTML : '<option value="[value]">[text]</option>',

    /**
     * 기본 품절 문구
     */
    OPTION_SOLDOUT_DEFAULT_TEXT : __("품절"),

    /**
     * 버튼형 옵션의 품절표시 class
     */
    BUTTON_OPTION_SOLDOUT_CLASS : 'ec-product-soldout',

    /**
     * 버튼형 옵션의 선택불가 class
     */
    BUTTON_OPTION_DISABLE_CLASS : 'ec-product-disabled',

    /**
     * 버튼형 옵션의 선택된 옵션값을 구분하기위한 상수
     */
    BUTTON_OPTION_SELECTED_CLASS : 'ec-product-selected'
};

/**
 * 각 옵션그룹에 대한 Key 정의
 */
var EC_SHOP_FRONT_NEW_OPTION_GROUP_CONS = {
    /**
     * 상품디테일의 메인 옵션 그룹
     */
    DETAIL_OPTION_GROUP_ID : 'product_option_',

    /**
     * 뉴스킨 상품상세의 옵션선택시 쩔어지는 옵션박스레이어 class명
     */
    DETAIL_OPTION_BOX_PREFIX : 'option_box_id',

    /**
     * 뉴스킨 상품상세의 옵션선택시 쩔어지는 옵션박스레이어 class명(품절일경우의 prefix)
     * Prefix존누 많음
     */
    DETAIL_OPTION_BOX_SOLDOUT_PREFIX : 'soldout_option_box_id'
};

var EC_SHOP_FRONT_NEW_OPTION_BIND = {

    /**
     * 선택한 옵션 그룹(product_option_상품번호 : 상품상세일반상품)
     */
    sOptionGroup : null,

    /**
     * 옵션이 모두 선택되었을때 해당하는 item_code를 Set
     */
    sItemCode : false,

    /**
     * 선택한 옵션의 상품번호
     */
    iProductNum : 0,

    /**
     * 선택한 옵션의 순번
     */
    iOptionIndex : null,

    /**
     * 선택한 옵션의 옵션 스타일(select : 셀렉트박스, preview : 미리보기, button : 버튼형)
     */
    sOptionStyle : null,

    /**
     * 해당 상품 옵션 갯수
     */
    iOptionCount : 0,

    /**
     * 품절옵션 표시여부
     */
    bIsDisplaySolout : true,

    /**
     * 선택한 옵션의 객체(셀렉트박스 또는 버튼형 옵션 박스(ul태그))
     */
    oOptionObject : null,

    /**
     * 선택한 옵션의 다음옵션 Element
     */
    oNextOptionTarget : null,

    /**
     * 선택된 옵션 값
     */
    aOptionValue : [],

    /**
     * 옵션텍스트에 추가될 항목에대한 정의
     */
    aExtraOptionText : [
        EC_SHOP_FRONT_NEW_OPTION_EXTRA_PRICE,
        EC_SHOP_FRONT_NEW_OPTION_EXTRA_SOLDOUT,
        EC_SHOP_FRONT_NEW_OPTION_EXTRA_IMAGE,
        EC_SHOP_FRONT_NEW_OPTION_EXTRA_DISPLAYITEM,
        EC_SHOP_FRONT_NEW_OPTION_EXTRA_ITEMSELECTION,
        EC_SHOP_FRONT_NEW_OPTION_EXTRA_DIRECT_BASKET,
        EC_SHOP_FRONT_NEW_OPTION_EXTRA_FUNDING
    ],

    /**
     * EC_SHOP_FRONT_NEW_OPTION_CONS 객체 Alias
     */
    cons : null,

    /**
     * EC_SHOP_FRONT_NEW_OPTION_COMMON 객체 Alias
     */
    common : null,

    /**
     * EC_SHOP_FRONT_NEW_OPTION_DATA 객체 Alias
     */
    data : null,

    /**
     * EC_SHOP_FRONT_NEW_OPTION_VALIDATION 객체 Alias
     */
    validation : null,

    isEnabledOptionInit : function(oOptionChoose)
    {
        var iProductNum = $(oOptionChoose).attr('option_product_no');
        //연동형이면서 옵션추가버튼설정이면 순차로딩제외
        if (Olnk.isLinkageType(this.common.getOptionType(oOptionChoose)) === true && (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isUseOlnkButton() === true || EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isBindUseOlnkButton(iProductNum) === true)) {
            return false;
        }

        if (this.common.getOptionType(oOptionChoose) === 'F') {
            return false;
        }

        return true;
    },

    /**
     * 각 옵션값에 대한 이벤트 처리
     * @param oThis 옵션 셀렉트박스 또는 버튼박스
     * @param oSelectedElement 선택한 옵션값
     * @param bIsUnset true 이명 deselected된상태로 초기화(setValue를 통해서 틀어왔을떄만 값이 있음)
     */
    initialize : function(oThis, oSelectedElement, bIsUnset)
    {
        this.sItemCode = false;
        this.oOptionObject = oThis;

        // 실제 옵션 처리전에 처리해야할 내용을 모아 놓는다
        this.prefetch(oThis);

        if (oSelectedElement !== null) {
            if ($(oSelectedElement).hasClass(EC_SHOP_FRONT_NEW_OPTION_CONS.BUTTON_OPTION_DISABLE_CLASS) === true) {
                this.setRadioButtonSelect(oSelectedElement, oThis, false);
                return;
            }

            //선택 옵션에대한 disable처리나 활성화 처리
            this.setSelectButton(oSelectedElement, bIsUnset);

            //필수정보 Set
            this.setSelectedOptionConf();

            //연동형이면서 옵션추가버튼설정이면 순차로딩제외..
            if (this.isEnabledOptionInit(this.oOptionObject) === true) {
                var bIsDelete = true;
                var bIsRequired = EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isRequireOption(this.oOptionObject);
                //해당 옵션이 연동형이면서 선택형 옵션이면 하위 옵션은 값만 초기화
                if (Olnk.isLinkageType(this.common.getOptionType(this.oOptionObject)) === true &&  bIsRequired=== false) {
                    bIsDelete = false;
                }

                //선택한 옵션이 옵션이 아닐경우 하위옵션 초기화
                //선택한 옵션이 옵션이 아니면 아래 로직은 타지 않고 eachCallback은 실행함
                this.setInitializeDefault(this.sOptionGroup, this.iOptionIndex, this.iProductNum, bIsRequired);

                if (bIsDelete === true && $(oSelectedElement).hasClass(this.cons.BUTTON_OPTION_DISABLE_CLASS) === false && this.validation.isOptionSelected(this.oOptionObject) === true) {
                    //선택한 옵션의 다음옵션값을 Parse
                    //연동형일경우에는 제외 / 조합분리형만 처리되도록 함
                    if (Olnk.isLinkageType(this.sOptionType) === false && this.validation.isSeparateOption(this.oOptionObject) === true) {
                        this.data.initializeOptionValue(this.oOptionObject);
                    }

                    //각 옵션을 초기화및 옵션 리스트 HTML생성
                    //조합분리형일때만 처리
                    if (this.validation.isSeparateOption(this.oOptionObject) === true) {
                        this.setOptionHTML();
                    }
                }
            }

            //해당 값이 true나 false이면 setValue를 통해서 들어온것이기때문에 다시 실행할 필요 없음
            //if (typeof(bIsUnset) === 'undefined') {
                //셀렉트박스 동기화
                this.common.setTriggerSelectbox(this.oOptionObject, this.common.getOptionSelectedValue(this.oOptionObject));
            //}

            //옵션이 모두 선택되었다면 아이템코드를 세팅
            this.setItemCode();
        }

        //옵션선택이 끝나면 각 옵션마다 처리할 프로세스(각 추가기능에서)
        this.eachCallback(oThis);

        //모든 옵션이 선택되었다면
        if (this.sItemCode !== false) {

            var sID = this.common.getOptionChooseID(this.oOptionObject);

            //상세 메인 상품에서만 실행되도록 예외처리
            if (typeof(setPrice) === 'function' && /^product_option_id+/.test(sID) === true) {
                setPrice(false, true, sID);
            }

            //모든 옵션선택이 끝나면 처리할 프로세스(각 추가기능에서)
            this.completeCallback(oThis);
        }
    },

    /**
     * 실제 옵션의 선택여부를 해제하기전 실행하는 액션
     */
    prefetch : function(oThis)
    {
        $(this.aExtraOptionText).each(function() {
            if (typeof(this.prefetech) === 'function') {
                this.prefetech(oThis);
            }
        });
    },

    /**
     * 각 옵션 선택시마다 처리할 Callback(Extra에 있는 추가기능)
     */
    eachCallback : function(oThis)
    {
        $(this.aExtraOptionText).each(function() {
            if (typeof(this.eachCallback) === 'function') {
                this.eachCallback(oThis);
            }
        });
    },

    /**
     * 옵션선택을 하고 품목이 정해졌을때 Callback(Extra에 있는 추가기능)
     */
    completeCallback : function(oThis)
    {
        $(this.aExtraOptionText).each(function() {
            if (typeof(this.completeCallback) === 'function') {
                this.completeCallback(oThis);
            }
        });
    },

    /**
     * iSelectedOptionSortNum보다 하위 옵션들을 초기상태로 변경함
     * @param sOptionGroup 옵션선택박스 그룹
     * @param iSelectedOptionSortNum 하위옵션을 초기화할 대상 옵션 순번
     * @param iProductNum 상품번호
     * @param bIsSetValue COMMON.setValue에서 호출시에는 다시 setValue를 하지 않는다
     */
    setInitializeDefault : function(sOptionGroup, iSelectedOptionSortNum, iProductNum, bSelectedOptionRequired) {
        var iSortNum = 0;
        var sHtml = '';
        var bIsDelete = null;

        $('['+this.cons.GROUP_ATTR_NAME+'="'+sOptionGroup+'"]').each(function() {

            iSortNum = parseInt(EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSortNum(this));

            //선택한 옵션의 하위옵션들을 초기화
            if (iSelectedOptionSortNum < iSortNum) {

                var bIsRequired = EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isRequireOption(this);

                //선택했던 옵션이 연동형이면서 선택형 옵션이면 값만 초기화
                //bIsDelete = (bIsDelete = null && isOlnk === true && bSelectedOptionRequired === true && bIsRequired === false) ? false : true;
                if (bIsDelete === null) {
                    //선택했던 옵션이 선택형 옵션이면 처리하지 않음
                    if (bSelectedOptionRequired === false) {
                        bIsDelete = false;
                    } else if (bSelectedOptionRequired === true) {//선택했던 옵션이 필수옵션이면 진행
                        //선택했던 옵션이 필수이면서 현재 옵션이 필수이면 초기화
                        if (bIsRequired === true) {
                            bIsDelete = true;
                        } else {
                            //선택했던 옵션이 필수이면서 현재옵션이 선택형옵션이면 다음옵션에서 체크
                            bIsDelete = null;
                        }
                    }
                }

                if (bIsDelete === true) {
                    sHtml = EC_SHOP_FRONT_NEW_OPTION_DATA.getDefaultOptionHTML(iProductNum, iSortNum);
                    $(this).html('');
                    $(this).append(sHtml);
                }

                //셀렉트박스이면서 필수옵션이라면 기본값을 제외하고 option삭제
                if (EC_SHOP_FRONT_NEW_OPTION_COMMON.isOptionStyle(this, EC_SHOP_FRONT_NEW_OPTION_CONS.OPTION_STYLE_SELECT) === true) {

                    if (bIsDelete === true && bIsRequired === true) {
                        $(this).find('option').attr('disabled', 'disable');
                        $(this).find('option[value!="*"][value!="**"]').remove('option');
                    } else {
                        EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(this, '*', false);
                    }
                }

                if (EC_SHOP_FRONT_NEW_OPTION_COMMON.isOptionStyleButton(this) === true) {
                    if (bIsDelete === true && bIsRequired === true) {
                        $(this).find('li').removeClass(EC_SHOP_FRONT_NEW_OPTION_CONS.BUTTON_OPTION_DISABLE_CLASS).addClass(EC_SHOP_FRONT_NEW_OPTION_CONS.BUTTON_OPTION_DISABLE_CLASS);
                    }

                    EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(this, '*', false);
                  //옵션 텍스트 초기화
                    EC_SHOP_FRONT_NEW_OPTION_EXTRA_DISPLAYITEM.eachCallback(this);
                }

                //첫번째 필수 옵션은 그대로 두고 두번째 필수옵션부터 remove
                if (bIsDelete !== null && bIsRequired === true) {
                    bIsDelete = true;
                }
            }
        });
    },

    /**
     * 옵션이 모두 선택되었다면 아이템코드 Set
     */
    setItemCode : function() {
        //연동형 상품 : 예외적인경우가 많아서 어쩔수가 없음...
        if (Olnk.isLinkageType(this.common.getOptionType(this.oOptionObject)) === true) {
            //선택한 값이 옵션이 아니라면 false
            if (this.validation.isItemCode(this.common.getOptionSelectedValue(this.oOptionObject)) === false) {
                return false;
            }

            //연동형 옵션
            var aSelectedValues = this.common.getAllSelectedValue(this.oOptionObject);

            //필수옵션 갯수
            var iRequiredOption = this.common.getRequiredOption(this.sOptionGroup).length;

            //선택한 옵션갯수보다 필수옵션이 많다면 false
            if (iRequiredOption > $(aSelectedValues).length) {
                return false;
            }
            //실제 필수옵션이 체크되어있는지
            var aOptionValues = [];
            var bIsExists = false;
            var iRequireSelectedOption = 0;

            //필수항목만 검사
            this.common.getRequiredOption(this.sOptionGroup).each(function() {
                bIsExists = false;
                aOptionValues = EC_SHOP_FRONT_NEW_OPTION_COMMON.getAllOptionValues(this);

                //필수 항목 옵션의 값을 실제 선택한옵션가눙데 존재하는지 일일히 확인해야한다
                $(aSelectedValues).each(function(i, iNo) {
                    //선택된 옵션중에 존재한다면 필수값이 선택된것으로 확인
                    if ($.inArray(iNo, aOptionValues) > -1) {
                        bIsExists = true;
                        return;
                    }
                });

                if (bIsExists === true) {
                    iRequireSelectedOption++;
                }
            });

            //전체 필수값 갯수가 선택된 필수옵션보다 많다면 false
            if (iRequiredOption > iRequireSelectedOption) {
                return false;
            }

            this.sItemCode = aSelectedValues;
        } else if (this.validation.isSeparateOption(this.oOptionObject) === true) {
            //조합분리형은 옵션값으로 파싱해서 가져와야함
            if (parseInt(this.iOptionCount) > parseInt(this.aOptionValue.length)) {
                return false;
            }

            this.sItemCode = this.data.getItemCode(this.iProductNum, this.aOptionValue.join(this.cons.OPTION_GLUE));
        } else {
            //조합분리형 이외에는 선택한 옵션의 value가 아이템코드
            this.sItemCode = this.common.getOptionSelectedValue(this.oOptionObject);
        }

    },

    /**
     * 각 옵션을 초기화및 옵션 리스트 HTML생성
     */
    setOptionHTML : function() {
        //하위옵션이 없다면(마지막 옵션을 선택한경우) 하위옵션이 없음으로 따로 만들지 않아도 된다
        if (parseInt(this.iOptionCount) === parseInt(this.aOptionValue.length)) {
            return;
        }

        if (this.oNextOptionTarget === null) {
            return;
        }

        var sSelectedOption = this.aOptionValue.join(this.cons.OPTION_GLUE);

        var aOptions = this.data.getOptionValueArray(this.iProductNum, sSelectedOption);

        //셀렉트박스일때 다음옵션 박스 초기화
        if (this.common.isOptionStyleButton(this.oNextOptionTarget) === false) {
            this.setOptionHtmlForSelect(aOptions, sSelectedOption);
        } else {
            this.setOptionHtmlForButton(aOptions, sSelectedOption);
        }
    },

    /**
     * 버튼형 옵션일 경우 해당 버튼 HTML초기화 및 해당 옵션값 Set
     * @param aOptions 옵션값 리스트
     * @param sSelectedOption 현재까지 선택된 옵션조합
     */
    setOptionHtmlForButton : function(aOptions, sSelectedOption) {
        //선택한값이 *sk ** 이면 다음옵션을 disable처리
        if (this.validation.isItemCode(this.common.getOptionSelectedValue(this.oOptionObject)) === false) {
            this.oNextOptionTarget.find('li').removeClass(this.cons.BUTTON_OPTION_DISABLE_CLASS).addClass(this.cons.BUTTON_OPTION_DISABLE_CLASS);
        } else {
            this.oNextOptionTarget.find('li').removeClass(this.cons.BUTTON_OPTION_DISABLE_CLASS);
        }

        //연동형일경우에는 disable /  select만 제거
        if (Olnk.isLinkageType(this.sOptionType) === true) {
            //하위옵션들만 selected클래스 삭제
            if (parseInt($(this.oOptionObject).attr('option_sort_no')) < parseInt($(this.oNextOptionTarget).attr('option_sort_no'))) {
                $(this.oNextOptionTarget).find('li').removeClass(this.cons.BUTTON_OPTION_SELECTED_CLASS);
                EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(this.oNextOptionTarget, '*', false);
            }
            return;
        }

        this.oNextOptionTarget.find('li').remove('li');

        var iNextOptionSortNum = this.common.getOptionSortNum(this.oNextOptionTarget);

        var bIsLastOption = false;
        //생성될 옵션이 마지막 옵션이면 옵션 Text에 추가 항목(옵션가 품절표시등)을 처리
        if (parseInt(iNextOptionSortNum) === this.iOptionCount) {
            bIsLastOption = true;
        }

        var oObject = this;
        var sOptionsHtml = '';

        //옵션 셀렉트박스 Text에 추가될 문구 처리
        var sAddText = '';
        var sItemCode = false;
        //품절옵션인데 품절옵션표시안함설정이면 삭제
        var bIsSoldout = false;
        var bIsDisplay = true;

        $(aOptions).each(function(i, oOption) {
            sAddText = '';
            bIsSoldout = false;
            bIsDisplay = true;
            //페이지 로딩시 저장된 해당 옵션의 HTML을 가져온다
            sOptionsHtml = oObject.data.getButonOptionHtml(oObject.iProductNum, iNextOptionSortNum, oOption.value);

            sOptionsHtml = $(sOptionsHtml).clone().removeClass(oObject.BUTTON_OPTION_DISABLE_CLASS);
            //마지막 옵션일 경우에는
            if (bIsLastOption === true) {
                sItemCode = oObject.data.getItemCode(oObject.iProductNum, sSelectedOption + oObject.cons.OPTION_GLUE + oOption.value);

                //진열안함이면 패스
                if (oObject.common.isDisplay(oObject.iProductNum, sItemCode) === false) {
                    bIsDisplay = false;
                }

                sAddText = oObject.setAddText(oObject.iProductNum, sItemCode);

                //품절상품인경우 품절class추가
                if (oObject.common.isSoldout(oObject.iProductNum, sItemCode) === true) {
                    $(sOptionsHtml).removeClass(oObject.cons.BUTTON_OPTION_SOLDOUT_CLASS).addClass(oObject.cons.BUTTON_OPTION_SOLDOUT_CLASS);
                    bIsSoldout = true;
                }
            } else {
                var sOptionText = sSelectedOption + oObject.cons.OPTION_GLUE + oOption.value;
                sAddText = oObject.common.getSoldoutText(oObject.oNextOptionTarget, sOptionText);

                if (sAddText !== '') {
                    $(sOptionsHtml).addClass(oObject.cons.BUTTON_OPTION_SOLDOUT_CLASS);
                    bIsSoldout = true;
                }

                if (oObject.data.getDisplayFlag(oObject.iProductNum, sOptionText) === false) {
                    bIsDisplay = false;
                }
            }

            if ((oObject.bIsDisplaySolout === false && bIsSoldout === true) || bIsDisplay === false) {
                $(this).remove();
                return;
            }

            oObject.oNextOptionTarget.append($(sOptionsHtml).attr('title', oOption.value + sAddText));
        });

        EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(this.oNextOptionTarget, '*', false);
    },

    /**
     * 셀렉트박스형 옵션일 경우 selectbox초기화 및 해당 옵션값 Set
     * @param aOptions 옵션값 리스트
     * @param sSelectedOption 현재까지 선택된 옵션조합 배열
     */
    setOptionHtmlForSelect : function(aOptions, sSelectedOption) {
        // 구분선 제외
        this.oNextOptionTarget.find('option[value!="**"]').removeAttr('disabled');

        //연동형일경우에는 초기화 시키고  disable제거
        //if (Olnk.isLinkageType(this.sOptionType) === true && EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isRequireOption(this.oNextOptionTarget)) {
        if (Olnk.isLinkageType(this.sOptionType) === true) {
            var sHtml = this.data.getDefaultOptionHTML(this.common.getOptionProductNum(this.oNextOptionTarget), this.common.getOptionSortNum(this.oNextOptionTarget));
            $(this.oNextOptionTarget).find('option').remove();
            $(this.oNextOptionTarget).append(sHtml);
            $(this.oNextOptionTarget).find('option[value!="**"]').removeAttr('disabled');
            $(this.oNextOptionTarget).val('*');
            return;
        }

        //옵션이 아닌 Default선택값을 제외하고 모두 삭제
        this.oNextOptionTarget.find('option[value!="*"][value!="**"]').remove();

        //선택한 옵션의 다음순서옵션항목
        var iNextOptionSortNum = this.common.getOptionSortNum(this.oNextOptionTarget);

        var bIsLastOption = false;
        //생성될 옵션이 마지막 옵션이면 옵션 Text에 추가 항목(옵션가 품절표시등)을 처리
        if (parseInt(iNextOptionSortNum) === this.iOptionCount) {
            bIsLastOption = true;
        }

        var oObject = this;
        var sOptionsHtml = '';

        var sItemCode = false;

        //옵션 셀렉트박스 Text에 추가될 문구 처리
        var sAddText = '';
        //품절옵션인데 품절옵션표시안함설정이면 삭제
        var bIsSoldout = false;
        $(aOptions).each(function(i, oOption) {
            sAddText = '';
            bIsSoldout = false;
            bIsDisplay = true;

            sOptionsHtml = oObject.data.getButonOptionHtml(oObject.iProductNum, iNextOptionSortNum, oOption.value);
            sOptionsHtml = $(sOptionsHtml).clone();

            //마지막 옵션일 경우에는 설정에따라 옵션title에 추가금액등의 text를 붙인다
            if (bIsLastOption === true) {
                sItemCode = oObject.data.getItemCode(oObject.iProductNum, sSelectedOption + oObject.cons.OPTION_GLUE + oOption.value);

                //진열안함이면 패스
                if (oObject.common.isDisplay(oObject.iProductNum, sItemCode) === false) {
                    bIsDisplay = false;
                }

                sAddText = oObject.setAddText(oObject.iProductNum, sItemCode);

                bIsSoldout = EC_SHOP_FRONT_NEW_OPTION_COMMON.isSoldout(oObject.iProductNum, sItemCode);
            } else {
                //품절문구(각 옵션마다도 보여줘야함...)
                var sOptionText = sSelectedOption + oObject.cons.OPTION_GLUE + oOption.value;
                sAddText = oObject.common.getSoldoutText(oObject.oNextOptionTarget, sOptionText);
                bIsSoldout = (sAddText === '') ? false : true;

                if (oObject.data.getDisplayFlag(oObject.iProductNum, sOptionText) === false) {
                    bIsDisplay = false;
                }
            }

            if ((oObject.bIsDisplaySolout === false && bIsSoldout === true) || bIsDisplay === false) {
                $(this).remove();
                return;
            }

            $(sOptionsHtml).val(oOption.value);
            $(sOptionsHtml).removeAttr('disabled');
            $(sOptionsHtml).text(oOption.value + sAddText);

            oObject.oNextOptionTarget.append($(sOptionsHtml));
        });
    },

    /**
     * 마지막 옵션에 추가될 추가항목들(추가금액, 품절 등)
     * @param iProductNum 상품번호
     * @param sItemCode 아이템 코드
     * @param oOptionElement 옵션셀렉트박스를 임의로 지정할경우
     */
    setAddText : function(iProductNum, sItemCode, oOptionElement) {
        var aText = [];

        if (typeof(oOptionElement) !== 'object') {
            oOptionElement = this.oOptionObject;
        }

        $(this.aExtraOptionText).each(function() {
            if (typeof(this.get) === 'function') {
                aText.push(this.get(iProductNum, sItemCode, oOptionElement));
            }
        });

        return aText.join('');
    },

    /**
     * 옵션 선택박스(셀렉트박스나 버튼)에 click 또는 change에 대한 이벤트 할당
     */
    initChooseBox : function() {
        this.cons = EC_SHOP_FRONT_NEW_OPTION_CONS;
        this.common = EC_SHOP_FRONT_NEW_OPTION_COMMON;
        this.data = EC_SHOP_FRONT_NEW_OPTION_DATA;
        this.validation = EC_SHOP_FRONT_NEW_OPTION_VALIDATION;

        var oThis = this;

        //live로 할경우에 기존 이벤트가 없어짐.
        $('select[option_select_element="ec-option-select-finder"]').unbind().change(function() {
            if (oThis.common.isOptionStyleButton(this) === true) {
                return false;
            }

            //페이지 로드가 되었는지 확인.
            if (typeof(oThis.common.isLoad) === false) {
                $(this).val('*');
                return false;
            }

            oThis.initialize(this, this);
        })
            .focus(function () {
                // select box change 이벤트 발생을 위해, selectedIndex 초기화
                if (this.selectedIndex > 0) {
                    this.selectedIndex = 0;
                }
            });

        try {
            $('ul[option_select_element="ec-option-select-finder"] > li').unbind().live('click', function (e) {
                var oOptionChoose = $(this).parent('ul');

                /*
                    ECHOSTING-194895 처리를 위해 삭제 (추가 이미지 클릭 시 해당 품목 선택 기능)
                    if (e.target.tagName === 'LI') {
                        return false;
                    }
                */

                if (EC_SHOP_FRONT_NEW_OPTION_COMMON.isOptionStyleButton(oOptionChoose) === false) {
                    return false;
                }

                //페이지 로드가 되었는지 확인.
                if (typeof(EC_SHOP_FRONT_NEW_OPTION_COMMON.isLoad) === false) {
                    return false;
                }

                //라디오버튼일경우 label태그에 상속되기때문에 click이벤트가 label input에 대해 두번 발생함
                //라디오버튼 속성이면서 발생위치가 label이면 이벤트 발생하지않고 그냥 return
                //return false이면 label클릭시 checked가 안되니깐 그냥 return
                //input 태그 자체에 이벤트를 주면 상관없지만 li태그에 이벤트를 할당하기때문에 생기는 현상같음
                if (oThis.common.isOptionStyle(oOptionChoose, oThis.cons.OPTION_STYLE_RADIO) === true && e.target.tagName.toUpperCase() === 'LABEL') {
                    return;
                }

                oThis.initialize($(this).parent('ul'), this);
            });
        } catch (e) {}
    },

    /**
     * 멀팁옵션에서 옵션추가시 이벤트 재정의(버튼형은 live로 되어있으니 상관없고 select형만)
     * @param oOptionElement
     */
    initChooseBoxMulti : function()
    {
        var oThis = this;

        //live로 할경우에 기존 이벤트가 없어짐.
        $('.xans-product-multioption select[option_select_element="ec-option-select-finder"]').unbind().change(function() {
            if (oThis.common.isOptionStyleButton(this) === true) {
                return false;
            }

            //페이지 로드가 되었는지 확인.
            if (typeof(oThis.common.isLoad) === false) {
                $(this).val('*');
                return false;
            }

            oThis.initialize(this, this);
        });
    },

    /**
     * 옵션 선택시 필요한 attribute값등을 SET
     */
    setSelectedOptionConf : function() {
        //선택한 옵션 그룹
        this.sOptionGroup = this.common.getOptionSelectGroup(this.oOptionObject);

        //선택한 옵션값 순번
        this.iOptionIndex = parseInt(this.common.getOptionSortNum(this.oOptionObject));

        //선택한 옵션 스타일
        this.sOptionStyle = $(this.oOptionObject).attr(this.cons.OPTION_STYLE);

        //현재까지 선택한 옵션의 value값을 가져온다
        this.aOptionValue = this.common.getAllSelectedValue(this.oOptionObject);

        //상풉번호
        this.iProductNum = this.common.getOptionProductNum(this.oOptionObject);

        //옵션타입
        this.sOptionType = this.common.getOptionType(this.oOptionObject);

        //품절 옵션 표시여부
        this.bIsDisplaySolout = this.validation.isSoldoutOptionDisplay();

        //선택한 옵션의 다음 옵션 Element
        //선택옵션을 제거한 다음옵션
        //1 : 필수, 2 : 선택, 3 : 필수일때 1번옵션 선택후 다음옵션을 3번(연동형)
        //[option_sort_no"'+this.iOptionIndex+'"]
        oThis = this;
        this.oNextOptionTarget = null;
        $('[product_option_area="'+this.sOptionGroup+'"][option_product_no="'+this.iProductNum+'"]').each(function() {
            //현재선택한 옵션의 하위옵션이 아니라 상위옵션이면 패스
            if (oThis.iOptionIndex >= parseInt(oThis.common.getOptionSortNum(this))) {
                return true;//continue
            }
            //선택옵션이면 패스
            if (oThis.validation.isRequireOption(this) === false) {
                return true;
            }

            oThis.oNextOptionTarget = $(this);
            return false;//break
        });

        //옵션 갯수
        this.iOptionCount = $('[product_option_area="'+this.sOptionGroup+'"]').length;
    },

    /**
     * 버튼식 옵션일 경우 선택한 옵션을 선택처리
     */
    setSelectButton : function(oSelectedOption, bIsUnset) {
        if (this.common.isOptionStyleButton(this.oOptionObject) === true) {
            //모두 선택이 안된상태로 이벤트 실행할수있도록 selected css를 지우고 리턴
            if (bIsUnset === true) {
                $(oSelectedOption).removeClass(this.cons.BUTTON_OPTION_SELECTED_CLASS);
                return;
            }

            //이미 선택한 옵션값을 다시 클릭시에는 선택해제
            if ($(oSelectedOption).hasClass(this.cons.BUTTON_OPTION_SELECTED_CLASS) === true) {
                $(oSelectedOption).removeClass(this.cons.BUTTON_OPTION_SELECTED_CLASS);
                this.common.setValue(this.oOptionObject, '*', false);
                this.setRadioButtonSelect(oSelectedOption, this.oOptionObject, false);
            } else {
                //버튼형식의  옵션일 경우 선택한 옵션을 선택처리(class 명을 추가)
                //선택불가일때는 선택된상태로 보이지 않도록 하고 클리만 가능하도록 한다
                //disable상태이면 선택CSS는 적용되지 않게 처리
                var oTargetOptionElement = $(oSelectedOption).parent('ul');
                var sDevID = $(oTargetOptionElement).attr('ec-dev-id');
                var self = this;

                //조합일체형에서 구분선이 있을경우 ul태그가 따로있지만 동일옵션이므로
                //동일 ul을 구해서 모두 unselect시킨다
                $(oTargetOptionElement).parent().find('ul[ec-dev-id="'+sDevID+'"]').each(function() {
                    $(this).find('li').removeClass(self.cons.BUTTON_OPTION_SELECTED_CLASS);
                });

                $(oSelectedOption).addClass(this.cons.BUTTON_OPTION_SELECTED_CLASS);
                this.setRadioButtonSelect(oSelectedOption, this.oOptionObject, true);
            }
        } else {
            //셀렉트박스형 옵션일 경우 **를 선택했다면 옵션초기화
            if (this.validation.isItemCode($(this.oOptionObject).val()) === false) {
                $(this.oOptionObject).val('*');
            }
        }
    },

    /**
     * Disable인 옵션일 경우 체크박스를 다시 해제함
     * @param oSelectedOption
     * @param oOptionObject
     * @param bIsCheck
     */
    setRadioButtonSelect : function(oSelectedOption, oOptionObject, bIsCheck)
    {
        if (EC_SHOP_FRONT_NEW_OPTION_COMMON.isOptionStyle(oOptionObject, EC_SHOP_FRONT_NEW_OPTION_CONS.OPTION_STYLE_RADIO) === false) {
            return;
        }

        $(oOptionObject).find('input:radio').attr('checked', '');

        //재선택시 체크해제하려면 e107c06faf31 참고
        if (bIsCheck === true) {
            $(oSelectedOption).find('input:radio').attr('checked', 'checked');
        }
    }
};

var EC_SHOP_FRONT_NEW_OPTION_DATA = {

    /**
     * EC_SHOP_FRONT_NEW_OPTION_CONS 객체 Alias
     */
    cons : EC_SHOP_FRONT_NEW_OPTION_CONS,

    /**
     * EC_SHOP_FRONT_NEW_OPTION_COMMON 객체 Alias
     */
    common : EC_SHOP_FRONT_NEW_OPTION_COMMON,

    /**
     * 옵션값관 아이템코드 매칭 데이터(option_value_mapper)
     */
    aOptioValueMapper : [],

    /**
     * 각 선택된 옵션값에대한 다음옵션값 리스트를 저장
     * aOptionValueData[상품번호][빨강#$%대형] = array(key : 1, value : 옵션값, text : 옵션 Text)
     */
    aOptionValueData : {},

    /**
     * 각 상품의 품목데이터(재고 및 추가금액 정보)
     */
    aItemStockData : {},

    /**
     * 옵션의 디폴트 HTML을 저장해둠
     */
    aOptionDefaultData : {},

    /**
     * 디폴트 옵션을 저장할떄 중복을 제거하기위해서 추가
     */
    aCacheDefaultProduct : [],

    /**
     * 버튼형 옵션 Element저장시 중복제거
     */
    aCacheButtonOption : [],

    /**
     * 버튼형 옵션의 경우 각 옵션값별 컬러칩/버튼이미지/버튼이름등을 저장해둔다
     */
    aButtonOptionDefaultData : [],

    /**
     * 추가금액 노출 설정
     */
    aOptionPriceDisplayConf : [],

    /**
     * 연동형 옵션의 옵션내용을 저장
     */
    aOlnkOptionData : [],

    /**
     * 각 옵션(품목이 아닌)마다 모두 품절이면 품절표시를 위해서 추가...
     */
    aOptionSoldoutFlag : [],

    /**
     * 각 옵션(품목이 아닌)마다 모두 진열안함이면 false로 나오지 않게 하기 위해서 추가
     */
    aOptionDisplayFlag : [],

    /**
     * 페이지 로딩시 각 옵션선택박스의 옵션정보를 Parse
     */
    initData : function() {
        var oThis = this;
        $('select[option_select_element="ec-option-select-finder"], ul[option_select_element="ec-option-select-finder"]').each(function() {
            //해당 옵션의 상품번호
            var iProductNum = oThis.common.getOptionProductNum(this);
            //해당 옵션의 옵션순서번호
            var iOptionSortNum = oThis.common.getOptionSortNum(this);

            var sCacheKey = iProductNum + oThis.cons.OPTION_GLUE + iOptionSortNum;

            EC_SHOP_FRONT_NEW_OPTION_DATA.initializeOption(this, sCacheKey);

            //버튼형 옵션일 경우 각 Element를 캐싱
            if (EC_SHOP_FRONT_NEW_OPTION_COMMON.isOptionStyleButton(this) === true) {
                EC_SHOP_FRONT_NEW_OPTION_DATA.initializeOptionForButtonOption(this, sCacheKey);
            } else {
                EC_SHOP_FRONT_NEW_OPTION_DATA.initializeOptionForSelectOption(this, sCacheKey);
                //일반 셀렉트의 경우 기본값 (*, **)을 제외하고 삭제
                //첫번째 필수값은 option들이 disable이 아니므로 disable된 옵션들만 삭제
                var bIsProcLoading = true;

                //필수옵션만 삭제
                if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isRequireOption(this) === false) {
                    bIsProcLoading = false;
                }

                //disable만 풀어준다
                //연동형이지만 옵션추가버튼 사용시에는 지우지 않음...
                //기본으로 선택된값이 있다면 지우지 않음(구스킨 관심상품, 뉴스킨 장바구니등에서는 일단 선택한 옵션을 보여주고 선택후부터 순차로딩)
                var sValue = $(this).find('option[selected="selected"]').attr('value');
                if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isItemCode(sValue) === true || (Olnk.isLinkageType(oThis.common.getOptionType(this)) === true && (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isUseOlnkButton() === true || EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isBindUseOlnkButton(iProductNum) === true))) {
                    bIsProcLoading = false;
                    $(this).find('option[value!="**"]').removeAttr('disabled');
                }

                if (bIsProcLoading === true) {
                    $(this).find('option[value!="*"][value!="**"]:disabled').remove('option');
                }
            }
        });
    },

    /**
     * 각 상품의 옵션 디폴트 옵션 HTML을 저장해둔다
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     */
    initializeOption : function(oOptionChoose, sCacheKey) {
        //이미 데이터가 있다면 패스
        if ($.inArray(sCacheKey, this.aCacheDefaultProduct) > -1) {
            return;
        }

        this.aCacheDefaultProduct.push(sCacheKey);
        this.aOptionDefaultData[sCacheKey] = $(oOptionChoose).html();
    },

    initializeOptionForSelectOption : function(oOptionChoose, sCacheKey) {
        var iProductNum = $(oOptionChoose).attr('option_product_no');
        var oThis = this;
        //같은 상품이 여러개있을수있으므로 이미 캐싱이 안된 상품만
        if ($.inArray(sCacheKey, this.aCacheButtonOption) < 0) {
            var bDisabled = false;
            if (Olnk.isLinkageType(this.common.getOptionType(oOptionChoose)) === true && (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isUseOlnkButton() === true || EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isBindUseOlnkButton(iProductNum) === true)) {
                bDisabled = true;
            }

            this.aCacheButtonOption.push(sCacheKey);
            this.aButtonOptionDefaultData[sCacheKey] = [];

            $(oOptionChoose).find('option').each(function() {
                if (bDisabled === true && this.value !== '**') {
                    $(this).removeAttr('disabled');
                }
                oThis.aButtonOptionDefaultData[sCacheKey][$(this).val()] = $('<div>').append($(this).clone()).html();
            });
        }
    },

    /**
     * 셀렉트박스 형식이 아닌 버튼이나 이미지형 옵션일 경우 HTML자체를 옵션값 별로 저장해둔다.
     * writejs쓰기싫음여
     */
    initializeOptionForButtonOption : function(oOptionChoose, sCacheKey) {
        var oThis = this;
        var iProductNum = $(oOptionChoose).attr('option_product_no');
        //같은 상품이 여러개있을수있으므로 이미 캐싱이 안된 상품만
        if ($.inArray(sCacheKey, this.aCacheButtonOption) < 0) {
            var bDisabled = false;
            if (Olnk.isLinkageType(this.common.getOptionType(oOptionChoose)) === true && (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isUseOlnkButton() === true || EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isBindUseOlnkButton(iProductNum) === true)) {
                bDisabled = true;
            }

            this.aCacheButtonOption.push(sCacheKey);
            this.aButtonOptionDefaultData[sCacheKey] = [];

            $(oOptionChoose).find('li').each(function() {
                if (bDisabled === true) {
                    $(this).removeClass(EC_SHOP_FRONT_NEW_OPTION_CONS.BUTTON_OPTION_DISABLE_CLASS);
                }
                oThis.aButtonOptionDefaultData[sCacheKey][$(this).attr('option_value')] = $('<div>').append($(this).clone()).html();
            });
        }

        var oTriggerSelect = this.getSelectClone(oOptionChoose);

        oTriggerSelect.append($('<option>').attr('value', '*').text('empty'));

        var sTitle = '';
        var sValue = '';
        for (x in this.aButtonOptionDefaultData[sCacheKey]) {
            //IE8..
            if (x !== 'indexOf') {
                sTitle = $(oThis.aButtonOptionDefaultData[sCacheKey][x]).attr('title');
                sValue = $(oThis.aButtonOptionDefaultData[sCacheKey][x]).attr('option_value');

                oTriggerSelect.append($('<option>').attr('value', sValue).text(sTitle));
            }
        }

        oTriggerSelect.val('*');
        $(oOptionChoose).parent().append(oTriggerSelect);
    },
    /**
     * 옵션 선택 UI의 미러링 객체 생성
     * @param oOptionChoose
     * @returns {jQuery}
     */
    getSelectClone : function(oOptionChoose)
    {
        var aAttribute = {
            'product_option_area_select' : $(oOptionChoose).attr('product_option_area'),
            'id' : $(oOptionChoose).attr('ec-dev-id'),
            'name' : $(oOptionChoose).attr('ec-dev-name'),
            'option_title' : $(oOptionChoose).attr('option_title'),
            'option_type' : $(oOptionChoose).attr('option_type'),
            'item_listing_type' : $(oOptionChoose).attr('item_listing_type'),
            'composition-code' : $(oOptionChoose).attr('composition-code'),
            'option_code' : $(oOptionChoose).attr('option_code')
        };
        // 셀렉트 박스의 셀렉터가 ^=라서 클래스의 순서가 중요 
        var aClass = [];
        if (typeof($(oOptionChoose).attr('ec-dev-class')) !== 'undefined') {
            aClass.push($(oOptionChoose).attr('ec-dev-class'));
        }
        aClass.push('displaynone');
        var oReturn = $('<select required="true">').attr(aAttribute).addClass(aClass.join(' '));
        if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isRequireOption(oOptionChoose) === false) {
            // true/false를 string으로 지정해야해서 먼저 string으로 지정해주고 필요없으면 제거
            oReturn.removeAttr('required');
        }
        return oReturn;
    },

    /**
     * 버튼형 옵션의 상품 옵션값에 대한 옵션 HTML을 반환
     * @param iProductNum 상품번호
     * @param iOptionSortNum 옵션순서
     * @param sOptionValue 옵션값
     * @returns boolean 해당 옵션값에 대한 버튼 HTML
     */
    getButonOptionHtml : function(iProductNum, iOptionSortNum, sOptionValue) {
        var sCacheKey = iProductNum + this.cons.OPTION_GLUE + iOptionSortNum;

        //없을경우에는 다시 초기화
        if (typeof(this.aButtonOptionDefaultData[sCacheKey]) === 'undefined') {
            this.initData();
        }

        if (typeof(this.aButtonOptionDefaultData[sCacheKey][sOptionValue]) === 'undefined') {
            return false;
        }

        return this.aButtonOptionDefaultData[sCacheKey][sOptionValue];
    },

    /**
     * 옵션을 선택하지 않았을때 하위옵션을 초기화하기위해서 디폴트 HTML을 가져옴
     * @param iProductNum 상품번호
     * @param iOptionSortNum 옵션 순서
     */
    getDefaultOptionHTML : function(iProductNum, iOptionSortNum)
    {
        var sCacheKey = iProductNum + this.cons.OPTION_GLUE + iOptionSortNum;

        if (typeof(this.aOptionDefaultData[sCacheKey]) === 'undefined') {
            return;
        }

        return this.aOptionDefaultData[sCacheKey];
    },

    /**
     * 해당 상품의 옵션 재고 관련 데이터를 리턴
     * @param iProductNum 상품번호
     */
    getProductStockData : function(iProductNum) {
        if (typeof(this.aItemStockData[iProductNum]) === 'undefined') {
            try {
                this.aItemStockData[iProductNum] = $.parseJSON(eval('option_stock_data' + iProductNum));
            } catch (e) {}
        }

        if (this.aItemStockData.hasOwnProperty(iProductNum) === false) {
            return null;
        }

        return this.aItemStockData[iProductNum];
    },

    /**
     * 옵션이 모두 선택되었다면 옵션값 리턴
     * @param iProductNum 상품번호
     * @param sSelectedOptionValue 선택된 전체 옵션값
     * @returns boolean 아이템코드
     */
    getItemCode : function(iProductNum, sSelectedOptionValue) {
        if (typeof(this.aOptioValueMapper[iProductNum]) === 'undefined') {
            return false;
        }

        if (typeof(this.aOptioValueMapper[iProductNum][sSelectedOptionValue]) === 'undefined') {
            return false;
        }

        return this.aOptioValueMapper[iProductNum][sSelectedOptionValue];
    },

    /**
     * 해당 상품의 선택된 옵션의 하위 옵션을 리턴
     * @param iProductNum 상품번호
     * @param sSelectedValue 현재까지 선택된 옵션값 String(옵션1값 + EC_SHOP_FRONT_NEW_OPTION_CONS.OPTION_GLUE + 옵션2값 형식)
     * @returns 옵션리스트
     */
    getOptionValueArray : function(iProductNum, sSelectedValue) {
        if (typeof(this.aOptionValueData[iProductNum]) === 'undefined') {
            return false;
        }

        if (typeof(this.aOptionValueData[iProductNum][sSelectedValue]) === 'undefined') {
            return false;
        }

        return this.aOptionValueData[iProductNum][sSelectedValue];
    },

    /**
     * 옵션 생성에 필요한 기본데이터 정의
     */
    setDefaultData : function() {
        if (typeof(option_stock_data) !== 'undefined') {
            this.aItemStockData[iProductNo] = $.parseJSON(option_stock_data);
        }
        if (typeof(option_value_mapper) !== 'undefined') {
            this.aOptioValueMapper[iProductNo] = $.parseJSON(option_value_mapper);
        }
        if (typeof(product_option_price_display) !== 'undefined') {
            this.aOptionPriceDisplayConf[iProductNo] = product_option_price_display;
        }

        if (typeof(add_option_data) !== 'undefined') {
            var aAddOptionJson = $.parseJSON(add_option_data);
            for (var iAddProductNo in aAddOptionJson) {
                this.aItemStockData[iAddProductNo] = $.parseJSON(aAddOptionJson[iAddProductNo].option_stock_data);
                if (typeof(aAddOptionJson[iAddProductNo].option_value_mapper) !== 'undefined') {
                    this.aOptioValueMapper[iAddProductNo] = $.parseJSON(aAddOptionJson[iAddProductNo].option_value_mapper);
                }

                this.aOptionPriceDisplayConf[iAddProductNo] = aAddOptionJson[iAddProductNo].product_option_price_display;
            }
        }

        if (typeof(set_option_data) !== 'undefined') {
            var aSetProductData = $.parseJSON(set_option_data);
            for (var iSetProductNo in aSetProductData) {
                this.aItemStockData[iSetProductNo] = $.parseJSON(aSetProductData[iSetProductNo].option_stock_data);

                if (typeof(aSetProductData[iSetProductNo].option_value_mapper) !== 'undefined') {
                    this.aOptioValueMapper[iSetProductNo] = $.parseJSON(aSetProductData[iSetProductNo].option_value_mapper);
                }

                this.aOptionPriceDisplayConf[iSetProductNo] = aSetProductData[iSetProductNo].product_option_price_display;
            }
        }
    },

    /**
     * 이벤트 옵션의 다음옵션값을 세팅
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     */
    initializeOptionValue : function(oOptionChoose) {
        //상품번호
        var iProductNum = this.common.getOptionProductNum(oOptionChoose);

        //현재까지 선택된 옵션값 배열
        var aSelectedValue = this.common.getAllSelectedValue(oOptionChoose);

        var sSelectedValue = aSelectedValue.join(this.cons.OPTION_GLUE);

        //기존 선언되지 않은 옵션에대한 처리면 뱌열로 미리 선언
        //이미 옵션값이 set되어있으면 바로 리턴
        if (typeof(this.aOptionValueData[iProductNum]) === 'undefined') {
            this.aOptionValueData[iProductNum] = {};
        }
        if (typeof(this.aOptionValueData[iProductNum][sSelectedValue]) === 'undefined') {
            this.aOptionValueData[iProductNum][sSelectedValue] = new Array();
        } else {
            return;
        }

        //선택한 옵션의 순번
        var iOptionSortNum = this.common.getOptionSortNum(oOptionChoose);

        //옵션값 순서
        var iCnt = 1;
        //중복옵션값 제거하기 위해서 저장할 옵션값
        var aCheckDuplicate = [];


        //장바구니 관심상품쪽은 데이터가 이렇게되어있어서 페이지로드시에 어떻게 할수가 없네요..
        if (typeof(this.aOptioValueMapper[iProductNum]) === 'undefined') {
            this.aOptioValueMapper[iProductNum] = $.parseJSON(eval("option_value_mapper" + iProductNum));
        }

        for (var x in this.aOptioValueMapper[iProductNum]) {

            //옵션값을 구분자에 따라 배열로 분리(옵션값 => 아이템코드 형태
            var aOptions = x.split(EC_SHOP_FRONT_NEW_OPTION_CONS.OPTION_GLUE);

            //옵션값에서 기선택된 값과 비교하기위한 옵션값
            var sOptionValue = aOptions.splice(0, iOptionSortNum).join(this.cons.OPTION_GLUE);

            //첫번째옵션부터 마지막선택한 옵션까지의 옵션값이 똑같으면서 기존처리된 옵션값이 아니라면 배열에 저장
            if (String(sOptionValue) === String(sSelectedValue) && $.inArray(aOptions[0], aCheckDuplicate) < 0) {
                this.aOptionValueData[iProductNum][sSelectedValue].push({key : iCnt, value : aOptions[0]});
                iCnt++;
                aCheckDuplicate.push(aOptions[0]);
            }
        }
    },

    /**
     * 각 옵션값의 전체품절 여부
     * @param iProductNum 상품번호
     * @param sValue 옵션값
     * @returns
     */
    getSoldoutFlag : function(iProductNum, sValue) {
        if (typeof(this.aOptionSoldoutFlag[iProductNum][sValue]) === 'undefined') {
            return false;
        }

        return this.aOptionSoldoutFlag[iProductNum][sValue];
    },

    /**
     * 각 옵션값의 진열 여부
     * @param iProductNum 상품번호
     * @param sValue 옵션값
     * @returns
     */
    getDisplayFlag : function(iProductNum, sValue) {

        if (typeof(this.aOptionDisplayFlag[iProductNum][sValue]) === 'undefined') {
            return false;
        }

        return this.aOptionDisplayFlag[iProductNum][sValue];
    },

    /**
     * 각각의 옵션값(품목말고)마다 해당 옵션전체가 품절인지 체크...
     * @param oOptionChoose
     */
    initializeSoldoutFlag : function(oOptionChoose) {
        //해당 옵션의 상품번호
        var iProductNum = this.common.getOptionProductNum(oOptionChoose);

        if (typeof(this.aOptionSoldoutFlag[iProductNum]) === 'undefined') {
            this.aOptionSoldoutFlag[iProductNum] = [];
        }

        if (typeof(this.aOptionDisplayFlag[iProductNum]) === 'undefined') {
            this.aOptionDisplayFlag[iProductNum] = [];
        }

        //장바구니 관심상품쪽은 데이터가 이렇게되어있어서 페이지로드시에 어떻게 할수가 없네요..
        if (typeof(this.aOptioValueMapper[iProductNum]) === 'undefined') {
            this.aOptioValueMapper[iProductNum] = $.parseJSON(eval("option_value_mapper" + iProductNum));
        }

        for (var x in this.aOptioValueMapper[iProductNum]) {
            //옵션값을 구분자에 따라 배열로 분리(옵션값 => 아이템코드 형태
            var aOptions = x.split(EC_SHOP_FRONT_NEW_OPTION_CONS.OPTION_GLUE);

            var bIsSoldout = EC_SHOP_FRONT_NEW_OPTION_COMMON.isSoldout(iProductNum, this.aOptioValueMapper[iProductNum][x]);

            var bIsDisplay = EC_SHOP_FRONT_NEW_OPTION_COMMON.isDisplay(iProductNum, this.aOptioValueMapper[iProductNum][x]);

            for (var i = 1; i <= $(aOptions).length; i++) {
                var sOption = aOptions.slice(0, i).join(EC_SHOP_FRONT_NEW_OPTION_CONS.OPTION_GLUE);

                //일단 품절로 세팅하고 품절이 아닌게 하나라도있다면 false로 바꿔준다
                if (typeof(this.aOptionSoldoutFlag[iProductNum][sOption]) === 'undefined') {
                    this.aOptionSoldoutFlag[iProductNum][sOption] = true;
                }

                if (bIsSoldout === false) {
                    this.aOptionSoldoutFlag[iProductNum][sOption] = false;
                }

                //일단 진열안함으로 세팅후에 한개라도 진열함이있다면 true바꿔줌다
                if (typeof(this.aOptionSoldoutFlag[iProductNum][sOption]) === 'undefined') {
                    this.aOptionDisplayFlag[iProductNum][sOption] = false;
                }

                if (bIsDisplay === true) {
                    this.aOptionDisplayFlag[iProductNum][sOption] = true;
                }
            }
        }
    }
};

var EC_SHOP_FRONT_NEW_OPTION_VALIDATION = {
    /**
     * EC_SHOP_FRONT_NEW_OPTION_COMMON Obejct Alias
     */
    common : EC_SHOP_FRONT_NEW_OPTION_COMMON,

    cons : EC_SHOP_FRONT_NEW_OPTION_CONS,

    /**
     * 해당 옵션 그룹에 필수옵션이 속해있는지 여부 확인
     * @param sOptionGroup 옵션 그룹 (@see : EC_SHOP_FRONT_NEW_OPTION_GROUP_CONS)
     * @returns 필수옵션 존재 여부
     */
    checkRequiredOption : function(sOptionGroup) {
        //해당 옵션 그룹의 필수옵션 갯수
        var iRequiredOption = $(this.common.getRequiredOption(sOptionGroup)).length;

        return (parseInt(iRequiredOption) > 0) ? true : false;
    },

    /**
     * 해당 옵션이 필수옵션인지 확인
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     */
    isRequireOption : function(oOptionChoose) {
        return (Boolean($(oOptionChoose).attr('required')) === true) ? true : false;
    },

    /**
     * 해당 값이 아이템코드인지 확인
     * @param sItemCode 선택한 아이템코드
     * @returns true이면 아이템코드
     * @todo 아이템코드 정규식을 추가..해야하나?? 그래야한다면 선택값여부를(*, **) 따로두고 실제 아이템코드인지 여부를 더 확인해야함
     */
    isItemCode : function(sItemCode) {
        return ($.inArray(sItemCode, ['*', '**']) > -1 || typeof(sItemCode) === 'undefined') ? false : true;
    },

    /**
     * 옵션값이 선택되어있는지 확인
     * @param oOptionChoose 값을 가져오려는 옵션박스 object
     */
    isOptionSelected : function(oOptionChoose) {
        return ($.inArray(this.common.getOptionSelectedValue(oOptionChoose), ['*', '**']) > -1) ? false : true;
    },
    
    /**
     * 옵션그룹에서 하나라도 선택이 되었는지 확인
     */
    isOptionGroupSelected : function(sOptionGroup)
    {
        var oThis = this;
        var bIsChoosen = false;
        $('[' + this.cons.GROUP_ATTR_NAME + '^="' + sOptionGroup + '"]').each(function() {
            if (oThis.isOptionSelected(this) === true) {
                bIsChoosen = true;
                return false;
            }
        });
        return bIsChoosen;
    },
    
    /**
     * 필수 옵션이 모두 선택된 상태인지 여부 확인
     * @param sOptionGroup 선택한 아이템코드
     * @returns boolean true이면 아이템코드
     */
    isSelectedRequiredOption : function(sOptionGroup) {
        //필수옵션이 하나도 없다면 바로 true
        if (this.checkRequiredOption(sOptionGroup) === false) {
            return true;
        }

        var oThis = this;
        var bIsComplete = true;
        $('[' + this.cons.GROUP_ATTR_NAME + '^="' + sOptionGroup + '"]').each(function() {

            //핑수옵션이지만 값이 선택되지 않았을경우 false
            if (oThis.isRequireOption(this) === true && oThis.isOptionSelected(this) === false) {
                bIsComplete = false;
                return false;
            }
        });

        return bIsComplete;
    },

    /**
     * 조합분리형만 아이템코드를 가져오는방식이 틀려서 확인용을 추가(연동형도 일단 조합분리형으로 인식하도록 함)
     * @param oOptionChoose 구분할 옵션박스 object
     * @returns true => 조합분리형, false => 기타옵션타입
     */
    isSeparateOption : function(oOptionChoose) {
        var sOptionTypeStr = $(oOptionChoose).attr('option_type');
        var sOptionListStr = $(oOptionChoose).attr('item_listing_type');
        return (Olnk.isLinkageType(sOptionTypeStr) === true || (sOptionTypeStr === 'T' && sOptionListStr === 'S')) ? true : false;
    },

    /**
     * 연동형 옵션 추가 버튼 사용설정을 사용하면 또 순차로딩 하지 않음
     * @returns
     */
    isUseOlnkButton : function() {
        return Olnk.getOptionPushbutton($('#option_push_button'));
    },
    /**
     * 세트상품에서 연동형 옵션 추가 버튼 사용설정을 사용하면 또 순차로딩 하지 않음
     * @returns
     */
    isBindUseOlnkButton : function(iProductNum) {
        return $('#add_option_push_button_'+iProductNum).length > 0;
    },
    isSoldoutOptionDisplay : function() {
        return (typeof(bIsDisplaySoldoutOption) !== 'undefined') ? bIsDisplaySoldoutOption : true;
    }
};
/**
 * 쇼핑몰 금액 라이브러리
 */
var SHOP_PRICE = {

    /**
     * iShopNo 쇼핑몰의 결제화폐에 맞게 리턴합니다.
     * @param float fPrice 금액
     * @param bool bIsNumberFormat number_format 적용 유무
     * @param int iShopNo 쇼핑몰번호
     * @return float|string
     */
    toShopPrice: function(fPrice, bIsNumberFormat, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        // 결제화폐 정보
        var aCurrencyInfo = SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo;

        return SHOP_PRICE.toPrice(fPrice, aCurrencyInfo, bIsNumberFormat);
    },

    /**
     * iShopNo 쇼핑몰의 참조화폐에 맞게 리턴합니다.
     * @param float fPrice 금액
     * @param bool bIsNumberFormat number_format 적용 유무
     * @param int iShopNo 쇼핑몰번호
     * @return float|string
     */
    toShopSubPrice: function(fPrice, bIsNumberFormat, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        // 참조화폐 정보
        var aSubCurrencyInfo = SHOP_CURRENCY_INFO[iShopNo].aShopSubCurrencyInfo;

        if ( ! aSubCurrencyInfo) {
            // 참조화폐가 없으면
            return '';

        } else {
            // 결제화폐 정보
            var aCurrencyInfo = SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo;
            if (aSubCurrencyInfo.currency_code === aCurrencyInfo.currency_code) {
                // 결제화폐와 참조화폐가 동일하면
                return '';
            } else {
                return SHOP_PRICE.toPrice(fPrice, aSubCurrencyInfo, bIsNumberFormat);
            }
        }
    },

    /**
     * 쇼핑몰의 기준화폐에 맞게 리턴합니다.
     * @param float fPrice 금액
     * @param bool bIsNumberFormat number_format 적용 유무
     * @param int iShopNo 쇼핑몰번호
     * @return float
     */
    toBasePrice: function(fPrice, bIsNumberFormat, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        // 기준화폐 정보
        var aBaseCurrencyInfo = SHOP_CURRENCY_INFO[iShopNo].aBaseCurrencyInfo;

        return SHOP_PRICE.toPrice(fPrice, aBaseCurrencyInfo, bIsNumberFormat);
    },

    /**
     * 결제화폐 금액을 참조화폐 금액으로 변환하여 리턴합니다.
     * @param float fPrice 금액
     * @param bool bIsNumberFormat number_format 적용 유무
     * @param int iShopNo 쇼핑몰번호
     * @return float 참조화폐 금액
     */
    shopPriceToSubPrice: function(fPrice, bIsNumberFormat, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        // 결제화폐 금액 => 참조화폐 금액
        fPrice = fPrice * (SHOP_CURRENCY_INFO[iShopNo].fExchangeSubRate || 0);

        return SHOP_PRICE.toShopSubPrice(fPrice, bIsNumberFormat, iShopNo);
    },

    /**
     * 결제화폐 대비 기준화폐 환율 리턴
     * @param int iShopNo 쇼핑몰번호
     * @return float 결제화폐 대비 기준화폐 환율
     */
    getRate: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        return SHOP_CURRENCY_INFO[iShopNo].fExchangeRate;
    },

    /**
     * 결제화폐 대비 참조화폐 환율 리턴
     * @param int iShopNo 쇼핑몰번호
     * @return float 결제화폐 대비 참조화폐 환율 (참조화폐가 없는 경우 null을 리턴합니다.)
     */
    getSubRate: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        return SHOP_CURRENCY_INFO[iShopNo].fExchangeSubRate;
    },

    /**
     * 금액을 원하는 화폐코드의 제약조건(소수점 절삭)에 맞춰 리턴합니다.
     * @param float fPrice 금액
     * @param string aCurrencyInfo 원하는 화폐의 화폐 정보
     * @param bool bIsNumberFormat number_format 적용 유무
     * @return float|string
     */
    toPrice: function(fPrice, aCurrencyInfo, bIsNumberFormat)
    {
        // 소수점 아래 절삭
        var iPow = Math.pow(10, aCurrencyInfo['decimal_place']);
        fPrice = fPrice * iPow;
        if (aCurrencyInfo['round_method_type'] === 'F') {
            fPrice = Math.floor(fPrice);
        } else if (aCurrencyInfo['round_method_type'] === 'C') {
            fPrice = Math.ceil(fPrice);
        } else {
            fPrice = Math.round(fPrice);
        }
        fPrice = fPrice / iPow;

        if ( ! fPrice) {
            // 가격이 없는 경우
            return 0;

        } else if (bIsNumberFormat === true) {
            // 3자리씩 ,로 끊어서 리턴
            var sPrice = fPrice.toFixed(aCurrencyInfo['decimal_place']);
            var regexp = /^(-?[0-9]+)([0-9]{3})($|\.|,)/;
            while (regexp.test(sPrice)) {
                sPrice = sPrice.replace(regexp, "$1,$2$3");
            }
            return sPrice;

        } else {
            // 숫자만 리턴
            return fPrice;

        }
    }    
};

/**
 * 화폐 포맷
 */
var SHOP_CURRENCY_FORMAT = {
    /**
     * 어드민 페이지인지
     * @var bool
     */
    _bIsAdmin: /^\/(admin\/php|disp\/admin|exec\/admin)\//.test(location.pathname) ? true : false,

    /**
     * iShopNo 쇼핑몰의 결제화폐 포맷을 리턴합니다.
     * @param int iShopNo 쇼핑몰번호
     * @return array head,tail
     */
    getShopCurrencyFormat: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        // 결제화폐 코드
        var sCurrencyCode = SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo.currency_code;

        if (SHOP_CURRENCY_FORMAT._bIsAdmin === true) {
            // 어드민

            // 기준화폐 코드
            var sBaseCurrencyCode = SHOP_CURRENCY_INFO[iShopNo].aBaseCurrencyInfo.currency_code;

            if (sCurrencyCode === sBaseCurrencyCode) {
                // 결제화폐와 기준화폐가 동일한 경우
                return {
                    'head': '',
                    'tail': ''
                };

            } else {
                return {
                    'head': sCurrencyCode + ' ',
                    'tail': ''
                };
            }

        } else {
            // 프론트
            return SHOP_CURRENCY_INFO[iShopNo].aFrontCurrencyFormat;
        }
    },

    /**
     * iShopNo 쇼핑몰의 참조화폐의 포맷을 리턴합니다.
     * @param int iShopNo 쇼핑몰번호
     * @return array head,tail
     */
    getShopSubCurrencyFormat: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        // 참조화폐 정보
        var aSubCurrencyInfo = SHOP_CURRENCY_INFO[iShopNo].aShopSubCurrencyInfo;

        if ( ! aSubCurrencyInfo) {
            // 참조화폐가 없으면
            return {
                'head': '',
                'tail': ''
            };

        } else if (SHOP_CURRENCY_FORMAT._bIsAdmin === true) {
            // 어드민
            return {
                'head': '(' + aSubCurrencyInfo.currency_code + ' ',
                'tail': ')'
            };

        } else {
            // 프론트
            return SHOP_CURRENCY_INFO[iShopNo].aFrontSubCurrencyFormat;
        }

    },

    /**
     * 쇼핑몰의 기준화폐의 포맷을 리턴합니다.
     * @param int iShopNo 쇼핑몰번호
     * @return array head,tail
     */
    getBaseCurrencyFormat: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        // 기준화폐 코드
        var sBaseCurrencyCode = SHOP_CURRENCY_INFO[iShopNo].aBaseCurrencyInfo.currency_code;

        // 결제화폐 코드
        var sCurrencyCode = SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo.currency_code;

        if (sCurrencyCode === sBaseCurrencyCode) {
            // 기준화폐와 결제화폐가 동일하면
            return {
                'head': '',
                'tail': ''
            };

        } else {
            // 어드민
            return {
                'head': '(' + sBaseCurrencyCode + ' ',
                'tail': ')'
            };

        }
    },

    /**
     * 금액 입력란 화폐 포맷용 head,tail
     * @param int iShopNo 쇼핑몰번호
     * @return array head,tail
     */
    getInputFormat: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        var sCurrencyCode = SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo;

        // 멀티쇼핑몰이 아니고 단위가 '원화'인 경우
        if (SHOP.isMultiShop() === false && sCurrencyCode === 'KRW') {
            return {
                'head': '',
                'tail': '원'
            };

        } else {
            return {
                'head': '',
                'tail': sCurrencyCode
            };
        }
    },

    /**
     * 해당몰 결제 화폐 코드 반환
     * ECHOSTING-266141 대응
     * 국문 기본몰 일 경우에는 화폐코드가 아닌 '원' 으로 반환
     *
     * @param int iShopNo 쇼핑몰번호
     * @return string currency_code
     */
    getCurrencyCode: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        var sCurrencyCode = SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo.currency_code;

        // 멀티쇼핑몰이 아니고 단위가 '원화'인 경우
        if (SHOP.isMultiShop() === false && sCurrencyCode === 'KRW') {
            return '원';
        } else {
            return sCurrencyCode;
        }
    }

};

/**
 * 금액 포맷
 */
var SHOP_PRICE_FORMAT = {
    /**
     * iShopNo 쇼핑몰의 결제화폐에 맞도록 하고 포맷팅하여 리턴합니다.
     * @param float fPrice 금액
     * @param int iShopNo 쇼핑몰번호
     * @return string
     */
    toShopPrice: function(fPrice, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        var aFormat = SHOP_CURRENCY_FORMAT.getShopCurrencyFormat(iShopNo);
        var sPrice = SHOP_PRICE.toShopPrice(fPrice, true, iShopNo);
        return aFormat.head + sPrice + aFormat.tail;
    },

    /**
     * iShopNo 쇼핑몰의 참조화폐에 맞도록 하고 포맷팅하여 리턴합니다.
     * @param float fPrice 금액
     * @param int iShopNo 쇼핑몰번호
     * @return string
     */
    toShopSubPrice: function(fPrice, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        var aFormat = SHOP_CURRENCY_FORMAT.getShopSubCurrencyFormat(iShopNo);
        var sPrice = SHOP_PRICE.toShopSubPrice(fPrice, true, iShopNo);
        return aFormat.head + sPrice + aFormat.tail;
    },

    /**
     * 쇼핑몰의 기준화폐에 맞도록 하고 포맷팅하여 리턴합니다.
     * @param float fPrice 금액
     * @param int iShopNo 쇼핑몰번호
     * @return string
     */
    toBasePrice: function(fPrice, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        var aFormat = SHOP_CURRENCY_FORMAT.getBaseCurrencyFormat(iShopNo);
        var sPrice = SHOP_PRICE.toBasePrice(fPrice, true, iShopNo);
        return aFormat.head + sPrice + aFormat.tail;
    },

    /**
     * 결제화폐 금액을 참조화폐 금액으로 변환하고 포맷팅하여 리턴합니다.
     * @param float fPrice 금액
     * @param int iShopNo 쇼핑몰번호
     * @return string
     */
    shopPriceToSubPrice: function(fPrice, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        var aFormat = SHOP_CURRENCY_FORMAT.getShopSubCurrencyFormat(iShopNo);
        var sPrice = SHOP_PRICE.shopPriceToSubPrice(fPrice, true, iShopNo);
        return aFormat.head + sPrice + aFormat.tail;
    },
    

    /**
     * 금액을 적립금 단위 명칭 설정에 따라 반환
     * @param float fPrice 금액
     * @return float|string
     */
    toShopMileagePrice: function (fPrice, iShopNo) {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;
        
        var sPrice = SHOP_PRICE.toShopPrice(fPrice, true, iShopNo);
        if (typeof sMileageUnit != 'undefined' && $.trim(sMileageUnit) != '') {
            sConvertMileageUnit = sMileageUnit.replace('[:PRICE:]', sPrice);
            return sConvertMileageUnit;
        } else {
            return SHOP_PRICE_FORMAT.toShopPrice(fPrice);
        }
    },

    /**
     * 금액을 예치금 단위 명칭 설정에 따라 반환
     * @param float fPrice 금액
     * @return float|string
     */
    toShopDepositPrice: function (fPrice, iShopNo) {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;
        
        var sPrice = SHOP_PRICE.toShopPrice(fPrice, true, iShopNo);
        if (typeof sDepositUnit != 'undefined' || $.trim(sDepositUnit) != '') {
            return sPrice + sDepositUnit;
        } else {
            return SHOP_PRICE_FORMAT.toShopPrice(fPrice);
        }
    },

    /**
     * 금액을 부가결제수단(통합포인트) 단위 명칭 설정에 따라 반환
     * @param float fPrice 금액
     * @return float|string
     */
    toShopAddpaymentPrice: function (fPrice, sAddpaymentUnit, iShopNo) {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        var sPrice = SHOP_PRICE.toShopPrice(fPrice, true, iShopNo);
        if (typeof sDepositUnit != 'undefined' || $.trim(sDepositUnit) != '') {
            return sPrice + sAddpaymentUnit;
        } else {
            return SHOP_PRICE_FORMAT.toShopPrice(fPrice);
        }
    },

    /**
     * 포맷을 제외한 금액정보만 리턴합니다.
     * @param {string} sFormattedPrice
     * @returns {string}
     */
    detachFormat: function(sFormattedPrice) {
        if (typeof sFormattedPrice === 'undefined' || sFormattedPrice === null) {
            return '0';
        }

        var sPattern = /[0-9.]/;
        var sPrice = '';
        for (var i = 0; i < sFormattedPrice.length; i++) {
            if (sPattern.test(sFormattedPrice[i])) {
                sPrice += sFormattedPrice[i];
            }
        }

        return sPrice;
    }
};

var SHOP_PRICE_UTIL = {
    /**
     * iShopNo 쇼핑몰의 결제화폐 금액 입력폼으로 만듭니다.
     * @param Element elem 입력폼
     * @param bool bUseMinus 마이너스 입력 사용 여부
     */
    toShopPriceInput: function(elem, iShopNo, bUseMinus)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        var iDecimalPlace = SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo.decimal_place;
        bUseMinus ? SHOP_PRICE_UTIL._toPriceInput(elem, iDecimalPlace, bUseMinus) : SHOP_PRICE_UTIL._toPriceInput(elem, iDecimalPlace);
    },

    /**
     * iShopNo 쇼핑몰의 참조화폐 금액 입력폼으로 만듭니다.
     * @param Element elem 입력폼
     */
    toShopSubPriceInput: function(elem, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        var iDecimalPlace = SHOP_CURRENCY_INFO[iShopNo].aShopSubCurrencyInfo.decimal_place;
        SHOP_PRICE_UTIL._toPriceInput(elem, iDecimalPlace);
    },

    /**
     * iShopNo 쇼핑몰의 기준화폐 금액 입력폼으로 만듭니다.
     * @param Element elem 입력폼
     */
    toBasePriceInput: function(elem, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || EC_SDE_SHOP_NUM;

        var iDecimalPlace = SHOP_CURRENCY_INFO[iShopNo].aBaseCurrencyInfo.decimal_place;
        SHOP_PRICE_UTIL._toPriceInput(elem, iDecimalPlace);
    },

    /**
     * 소수점 iDecimalPlace까지만 입력 가능하도록 처리
     * @param Element elem 입력폼
     * @param int iDecimalPlace 허용 소수점
     * @param bool bUseMinus 마이너스 입력 사용 여부
     */
    _toPriceInput: function(elem, iDecimalPlace, bUseMinus)
    {
        attachEvent(elem, 'keyup', function(e) {
            e = e || window.event;
            bUseMinus ? replaceToMinusPrice(e.srcElement) : replaceToPrice(e.srcElement);
        });
        attachEvent(elem, 'blur', function(e) {
            e = e || window.event;
            bUseMinus ? replaceToMinusPrice(e.srcElement) : replaceToPrice(e.srcElement);
        });

        // 추가금액에서 마이너스를 입력받기 위해 사용
        function replaceToMinusPrice(target) {
            var value = target.value;

            var regExpTest = new RegExp('^[0-9]*' + (iDecimalPlace ? '' : '\\.[0-9]{0, ' + iDecimalPlace + '}' ) + '$');

            if (regExpTest.test(value) === false) {
                value = value.replace(/[^0-9.|\-]/g, '');
                if (parseInt(iDecimalPlace)) {
                    value = value.replace(/^([0-9]+\.[0-9]+)\.+.*$/, '$1');
                    value = value.replace(new RegExp('(\\.[0-9]{' + iDecimalPlace + '})[0-9]*$'), '$1');
                } else {
                    value = value.replace(/[^(0-9|\-)]/g, '');
                }
                target.value = value;
            }
        }

        function replaceToPrice(target)
        {
            var value = target.value;

            var regExpTest = new RegExp('^[0-9]*' + (iDecimalPlace ? '' : '\\.[0-9]{0, ' + iDecimalPlace + '}' ) + '$');
            if (regExpTest.test(value) === false) {
                value = value.replace(/[^0-9.]/g, '');
                if (parseInt(iDecimalPlace)) {
                    value = value.replace(/^([0-9]+\.[0-9]+)\.+.*$/, '$1');
                    value = value.replace(new RegExp('(\\.[0-9]{' + iDecimalPlace + '})[0-9]*$'), '$1');
                } else {
                    value = value.replace(/\.+[0-9]*$/, '');
                }
                target.value = value;
            }
        }

        function attachEvent(elem, sEventName, fn)
        {
            if ( elem.addEventListener ) {
                elem.addEventListener( sEventName, fn, false );

            } else if ( elem.attachEvent ) {
                elem.attachEvent( "on" + sEventName, fn );
            }
        }

    }
};

if (window.jQuery !== undefined) {
    $.fn.extend({
        toShopPriceInput : function(iShopNo)
        {
            return this.each(function(){
                var iElementShopNo = $(this).data('shop_no') || iShopNo;
                SHOP_PRICE_UTIL.toShopPriceInput(this, iElementShopNo);
            });
        },
        toShopSubPriceInput : function(iShopNo)
        {
            return this.each(function(){
                var iElementShopNo = $(this).data('shop_no') || iShopNo;
                SHOP_PRICE_UTIL.toShopSubPriceInput(this, iElementShopNo);
            });
        },
        toBasePriceInput : function(iShopNo)
        {
            return this.each(function(){
                var iElementShopNo = $(this).data('shop_no') || iShopNo;
                SHOP_PRICE_UTIL.toBasePriceInput(this, iElementShopNo);
            });
        }
    });
}

var BASKET_CHK_ID_PREFIX = 'basket_chk_id_';
var bNaverMileageConfirm = false;
var sNaverPointExcMsg = __("주문서에 네이버마일리지 예외상품이 포함되어 있을 경우\n네이버마일리지의 적립 및 사용이 불가합니다.\n\n주문하시겠습니까?");
var bIsMobile = false; // 모바일로 접속했는지
var sLayerId = '';
window.bIsAddWishListCall = false; // 뉴상품인데 Basket.addWishList를 사용하여 세트상품을 처리할 경우 확인용

$(document).ready(function() {
    $('.shippingFee .ec-base-tooltip').hide();
    $('#ec-shop-shipfeesale-layer').click(function() {
        $('.shippingFee .ec-base-tooltip').show();
    });

    $('.shippingFee .btnClose').click(function() {
        $('.shippingFee .ec-base-tooltip').hide();
    });

    // 세션스토리지 BasketProduct 캐시 삭제
    try {
        // Basketcnt (쿠키) : 이벤트시점마다 갱신이 잘 됨.
        CAPP_ASYNC_METHODS.Basketcnt.restoreCache();
        var aBasketcnt = CAPP_ASYNC_METHODS.Basketcnt.getData();

        // count of BasketProduct (세션스토리지)
        // 장바구니, 주문서, 주문완료 페이지 외에는 갱신되지 않음.
        CAPP_ASYNC_METHODS.BasketProduct.restoreCache();
        var aBasketProduct = CAPP_ASYNC_METHODS.BasketProduct.getData();

        // aBasketcnt 와 BasketProduct의 count 를 비교하여 다를 시 BasketProduct 를 갱신가능하도록 캐시를 삭제한다.
        // aBasketProduct 가 false(boolean) 으로 return 되면 aBasketProduct.length 가 undefined
        if (aBasketcnt.count != aBasketProduct.length || aBasketProduct.length == undefined) {
            CAPP_ASYNC_METHODS.BasketProduct.removeCache();
        }
    } catch ( e ) { }


});

var Basket = {

    /**
     * '수정' 버튼 클릭
     */
    modifyQuantity : function(sId) {
        // 수량 유효성 검사 (빈값, 숫자, 숫자가 1이상인지 체크)
        if (this._checkQuantity() == false) {
            alert(__('수량은 1 이상이어야 합니다.'));
            return;
        }

        var aParam = {};
        for (var i=0,n=aBasketProductData.length; i < n; i++) {
            // index값 가져오기(변경버튼을 클릭했느냐 아니면 +/- 버튼을 클릭했는냐  판단을 위한 값임)
            // index값이 있다는것은  +/- 버튼 클릭했을경우임.
            if (bIsNewProduct == true && (typeof(sId) != "undefined" && sId != null)) {
                var iIndex = sId.split("_")[2];
                if (isNaN(iIndex) == false && iIndex != i) {
                    continue;
                }
            }

            var iProdNo = aBasketProductData[i].product_no;
            var iSetProdNo = aBasketProductData[i].set_product_no;
            var sOptId  = aBasketProductData[i].opt_id;
            var bOptAdd = aBasketProductData[i].option_add;
            var iOldQty = aBasketProductData[i].quantity;
            var iCheckQty = aBasketProductData[i].check_quantity;
            var sIsSetProduct = (iSetProdNo > 0) ? 'T' : 'F';
            var sIsBenefitEventProduct = aBasketProductData[i].sIsBenefitEventProduct;

            // 1+N 상품의 경우 수량 옵션 등 변경 불가.
            if (sIsBenefitEventProduct == 'T') {
                continue;
            }

            if (bOptAdd == "T" && $('#quantity_id_'+i).val() != iOldQty) {
                alert(__('사용자 지정 옵션 상품은 수량 변경이 불가능합니다.'));
                $('#quantity_id_'+i).val(iOldQty);
                return;
            }

            aParam['prod_id'+i]  = iProdNo + ':' + sOptId + ':'+ iSetProdNo;
            aParam['quantity'+i] = $('#quantity_id_'+i).val();

            // 뉴상품 재고체크 로직 태우기위한 파리미터(변경버튼 클릭시)
            try {
                if (isNaN(iIndex)) {
                    aParam["selected_item["+i+"]"] = aParam['quantity'+i] + "||" + aBasketProductData[i].item_code;
                }
            } catch (e) {}

            // 뉴상품일경우 구매주문단위 유효성 체크(현재수량에서 +/- 값이 구매단위로 이루어졌는지)
            // 추가 최소 구매 수량 체크 , 최대 주문수량 체크
            if (bIsNewProduct) {
                var iQuantity = parseInt($('#quantity_id_'+i).val(), 10);
                var iBuyUnit = this.getBuyUnit(i);
                if ((iQuantity % iBuyUnit) > 0 && aBasketProductData[i].check_buy_unit_type == 'O') {
                    alert(sprintf(__('구매 주문단위는 %s개 입니다.')
                            , iBuyUnit));
                    $('#quantity_id_'+i).val(iOldQty);
                    return;
                }

                // (체크해야할 수량(상품/품목) - 수량변경전 품목의 수량) + 변경한 수량
                iQuantity = (iCheckQty - iOldQty) + iQuantity;

                var iMin = parseInt(aBasketProductData[i].product_min);
                var iMax = parseInt(aBasketProductData[i].product_max);
                if (iCheckQty < iMin && iQuantity > iCheckQty) {
                    // DB에 저장된 값이 이미 최소수량보다 적게 들어갔을 때
                    // 그리고 수정하려는 값이 DB에 저장된 값보다 클 경우에만 (수량 증가할 때)
                    // 수량 변경을 처리해준다.
                    // 장바구니 구매 시도시에 잘못된 수량을 한 번 더 걸러낸다.
                } else {
                    if (iQuantity < iMin && iMin > 0 && aBasketProductData[i].check_quantity_type == 'O') {
                        alert(sprintf(__('최소 주문수량은 %s개 입니다.'), iMin));
                        $('#quantity_id_'+i).val(iOldQty);
                        return;
                    }
                }
                if (iCheckQty > iMax && iQuantity < iCheckQty) {
                    // DB에 저장된 값이 이미 최대값 보다 클때. 수량 변경을 허용한다.
                    // 그리고 수정하려는 값이 DB에 저장된 값보다 작을때 (수량 감소할 때)
                    // 장바구니 구매 시도시에 잘못된 수량을 한 번 더 걸러낸다.
                } else {
                    if (iQuantity > iMax && iMax > 0 && aBasketProductData[i].check_quantity_type == 'O') {
                        alert(sprintf(__('최대 주문수량은 %s개 입니다.'), iMax));
                        $('#quantity_id_'+i).val(iOldQty);
                        return;
                    }
                }
            }

            if (typeof ACEWrap != 'undefined') {
                ACEWrap.modQuantity(iProdNo, aParam['quantity'+i]);
            }
        }

        aParam['command'] = 'update';
        aParam['num_of_prod'] = aBasketProductData.length;

        // 최소/최대 구매수량 체크
        // 전체 체크는 이 위치에서 수행할 필요가?
        if (bIsNewProduct == false ) {
            if (this.isAbleQuantityForMaxMin(true) == false) {
                return;
            }
        }

        // for new product
        try {
            // iIndex값이 유효한 경우는 '+/-' 버튼을 클릭한 경우임.
            if (isNaN(iIndex) == false) {
                aParam["product_no"]      = aBasketProductData[iIndex].product_no;
                aParam["set_product_no"]      = aBasketProductData[iIndex].set_product_no;
                aParam["main_cate_no"]    = aBasketProductData[iIndex].main_cate_no;
                aParam["option_type"]     = aBasketProductData[iIndex].option_type;
                aParam["product_price"]   = aBasketProductData[iIndex].product_price;
                aParam["has_option"]      = aBasketProductData[iIndex].has_option;
                aParam["selected_item["+iIndex+"]"] = aParam['quantity'+iIndex] + "||" + aBasketProductData[iIndex].item_code;
            }
        } catch (e) {}

        // 배송유형
        if (sBasketDelvType != undefined && sBasketDelvType != '') {
            aParam["delvtype"] = sBasketDelvType;
        }

        this._callBasketAjax(aParam);
    },

    /**
     * ECHOSTING-14079, selee01
     * '옵션변경' 버튼 클릭시 레이어보기
     * @param sId: 변경시킬 폼 id
     */
    showLayer : function(sId) {
        $('[id^="optModify_id_"]').each(function() {
            $('[id^="optModify_id_"]').hide();
        });

        $('#'+sId).parents('.prdInfo').first().css({'zIndex':2});
        $('#'+sId).parents('.prdInfo').first().siblings('.prdInfo').css({'zIndex':1});
        $('#'+sId).show();
    },

    closeLayer : function(sId) {
        $('#'+sId).hide();
    },

    /**
     * '옵션변경'레이어에서 '적용하기' 버튼 클릭
     */
    modifyOption : function(sIdx) {

        // 구상품 모바일 스킨을 사용하는 뉴상품몰에서 옵션변경 오류 수정 - (ECHOSTING-94860, by wcchoi)
        if (mobileWeb && bIsNewProduct) {
            BasketNew.modify(sIdx, 'modify'); // 뉴상품용으로 호출
            return;
        }

        //필수옵션 체크
        if (this.checkOptionRequired() == false) return;

        //추가옵션 체크
        if (this.checkAddOption() == false) return;

        var aParam = {};
        var aOptionStr = [];
        var aAddOption = [];
        var aAdd_option_name = [];
        var aAdd_option = [];

        var iPrdNo = aBasketProductData[sIdx].product_no; //상품번호

        var old_opt_id = aBasketProductData[sIdx].opt_id; //basket_product 업데이트할때 where 조건으로 필요해서
        var aOptId = (aBasketProductData[sIdx].opt_id + '').split('-'); //옵션아이디

        var iCnt = 0;

        var aSelectedValue = [];

        $('select[id^="product_option_id"]:visible').each(function() {
            if ($(this).attr('required') == true) {
                aParam['opt_title'] = $(this).attr('option_title');
            }
            var iSelectedIndex = $(this).get(0).selectedIndex;
            if ($(this).attr('required') && iSelectedIndex > 0) iSelectedIndex -= 1;

            aOptId[iCnt] = iSelectedIndex;

            if (iSelectedIndex > 0) {
                var sValue =  $(this).find(':selected').val();
                var aVal = sValue.split("|");
                var sText = $(this).find(':selected').text();
                aOptionStr.push($(this).attr('option_title')+'='+sText);

                aSelectedValue.push(sValue); // ECHOSTING-94860, by wcchoi
            }
            iCnt++;
        });
        var sOptId  = aOptId.join('-'); //변경하려는 옵션아이디

        for (var i=0,n=aBasketProductData.length; i < n; i++) {
            var iProdNo = aBasketProductData[i].product_no;
            var bOptAdd = aBasketProductData[i].option_add;
            var iOldQty = aBasketProductData[i].quantity;
            if (bOptAdd == "T") {
                alert(__("사용자 지정 옵션 상품은 옵션변경을 하실 수 없습니다."));
                $('#quantity_id_'+i).val(iOldQty);
                return;
            }
            //aParam['quantity'+i] = aBasketProductData[i].quantity;
            if (i == sIdx) { // 옵션변경할 놈
                /*aParam['prod_id'+i] = iProdNo + ':' + sOptId;
                aParam['old_opt_id'+i] = old_opt_id;*/
                aParam['prod_id'] = iProdNo + ':' + sOptId;
                aParam['old_opt_id'] = old_opt_id;
                aParam['quantity'] = aBasketProductData[i].quantity;
            }
            /*else {
                var sOptionId  = aBasketProductData[i].opt_id;
                aParam['prod_id'+i]  = iProdNo + ':' + sOptionId;
                aParam['old_opt_id'+i] = sOptionId;
            }*/
        }
        aParam['opt_str'] = aOptionStr.join(',');

        $('input[id^="add_option"]:visible').each(function() {
            //aAddOption.push($(this).attr('name')+'='+ $(this).val()+';');
            aAdd_option_name.push($(this).attr('name'));
            aAdd_option.push($(this).val());
        });
        /*aParam['option_add'+sIdx] = aAdd_option;
        aParam['add_option_name'+sIdx] = aAdd_option_name.join(';');*/
        aParam['option_add'] = aAdd_option;
        aParam['add_option_name'] = aAdd_option_name.join(';');

        aParam['command'] = 'update';
        aParam['option_change'] = 'T';
        aParam['num_of_prod'] = 1;

        this._callBasketAjax(aParam);
    },

    /**
     * 필수옵션 체크 여부
     * @return bool true: 체크 / false: 체크안함
     */
    checkOptionRequired : function() {
        var bResult = true;
        $('select[id^="product_option_id"]:visible').each(function() {
            if ($(this).attr('required')) {
                if ($('option:selected', this).val().indexOf('*') > -1) {
                    alert(__('필수 옵션을 선택해주세요.'));
                    $(this).focus();
                    bResult = false;
                    return false;
                }
            }
        });
        return bResult;
    },


    /**
     * 추가옵션 체크
     * @return bool true: 추가옵션이 다 입력되었으면 / false: 아니면
     */
    checkAddOption : function() {
        var bResult = true;
        $('[id^="add_option"]:visible').each(function() {
            var oThis = $(this);

            // 선택항목인 경우
            if (oThis.attr('require') === 'F') {
                return;
            }

            if (oThis.val().replace(/^[\s]+[\s]+$/g, '').length == 0) {
                alert(__('추가 옵션을 입력해주세요.'));
                oThis.focus();
                bResult = false;
            }
        });
        return bResult;
    },


    /**
     * '주문하기' 버튼 클릭 (구스킨 선택한상품 주문시 사용)
     * @param sProductType normal_type, installment_type
     * @param object 클릭한 element 객체
     */
    orderBasket : function(sProductType, oElem) {
        if (this._existsChecked(true) == false) return;

        // 타입에 맞게 선택된 상품 체크
        var aCheckedProduct = [];
        var bSelected = false;
        var aProdList = this._getCheckedProduct();
        for (var i=0,n=aProdList.length; i < n; i++) {
            var iSeq = aProdList[i].seq;
            if (aBasketProductData[iSeq].product_type != sProductType) continue;

            aCheckedProduct.push(aProdList[i].val);
            bSelected = true;
        }

        if (bSelected == false) {
            alert(__('선택된 상품이 없습니다.'));
            return;
        }

        //최소/최대 구매수량 체크
        if (this.isAbleQuantityForMaxMin(false) == false) {
            return;
        }

        this._callOrderAjax({
            checked_product : aCheckedProduct.join(','),
            basket_type     : this._getBasketType(sProductType)
        }, oElem);
    },

    /**
     * '견적서 출력' 버튼 클릭
     * @param object 클릭한 element 객체
     */
    estimatePrint : function(oElem) {
        if (this._existsBasket(true) == false) return;
        var sPopupLink = $(oElem).attr('link');
        if (!sPopupLink || sPopupLink.length<1) sPopupLink = '/estimate/userform.html';

        // 구상품에서 sBasketDelvType - ECHOSTING-92787, by wcchoi
        var _delvtype = sBasketDelvType;
        if (_delvtype == '' && aBasketProductData.length > 0) {
            _delvtype = aBasketProductData[0].delvtype;
        }

        if (sPopupLink.indexOf('?') == -1) sPopupLink += '?delvtype=' + _delvtype;
        else                               sPopupLink += '&delvtype=' + _delvtype;

        var option = "toolbar=no," + "location=0," + "directories=0," +
                     "status=0," + "menubar=0," + "scrollbars=1," +
                     "resizable=1," + "width=600," + "height=500," +
                     "top=50," + "left=200";

        window.open(sPopupLink, "online_estimate_print_pop", option);
    },

    /**
     * '장바구니 비우기' 버튼 클릭
     */
    emptyBasket : function() {
        if (this._existsBasket(true) == false) return;
        if (confirm(__('장바구니를 비우시겠습니까?')) == false) return;

        if (typeof ACEWrap != 'undefined') {
            ACEWrap.delAllBasket();
        }

        this._callBasketAjax({command:'delete', delvtype: sBasketDelvType});
    },

    /**
     * '선택상품 삭제' 버튼 클릭
     */
    deleteBasket : function() {
        if (this._existsBasket(true)  == false) return;
        if (this._existsChecked(true) == false) return;
        if (confirm(__('선택하신 상품을 삭제하시겠습니까?')) == false) return;

        if (typeof ACEWrap != 'undefined') {
            ACEWrap.delCheckedBasket();
        }

        this._callBasketAjax({
            command         : 'select_delete',
            checked_product : this._getCheckedProductList().join(','),
            delvtype        : sBasketDelvType
        });
    },

    /**
     * '관심상품 담기' 버튼 클릭
     */
    addWishList : function() {
        if (this._existsBasket(true)  == false) return;
        if (this._existsChecked(true) == false) return;

        try {
            var aProdList = this._getCheckedProduct();

            if (bIsNewProduct === true) {
                window.bIsAddWishListCall = true;

                for (var i=0, n=aProdList.length; i<n; i++) {
                    BasketNew.moveWish(aProdList[i].seq);
                }

                location.href = '/myshop/wish_list.html';
                return false;
            } else {
                var sOptionType = '';
                for (var i=0,n=aProdList.length; i < n; i++) {
                    if (aProdList[i].option_type == 'F') {
                        sOptionType = aProdList[i].option_type;
                    }
                }
            }
        } catch (e) {}

        if (bIsNewProduct  === false) {
            this._callBasketAjax({
                command         : 'select_storage',
                checked_product : this._getCheckedProductList().join(","),
                delvtype        : sBasketDelvType,
                option_type : sOptionType // 단독 구성 옵션 상품/품목이 하나라도 있는 경우 'F' 를 보냄
            });
        }
    },

    /**
     * '해외배송상품 장바구니로 이동' 버튼 클릭
     */
    moveOversea : function() {
        if (this._existsBasket(true)  == false) return;
        if (this._existsChecked(true) == false) return;
        try {
            var aProdList = this._getCheckedProduct();
            var iOnlyDomestic = 0;
            var iAbleOversea = 0;
            var sOptionType = '';
            for (var i=0,n=aProdList.length; i < n; i++) {
                if (aProdList[i].is_oversea_able == true) {
                    iAbleOversea++;
                } else {
                    iOnlyDomestic++;
                }
                if (aProdList[i].option_type == 'F') {
                    sOptionType = aProdList[i].option_type;
                }
            }

            // 국내배송만 가능한경우
            if (iAbleOversea == 0) {
                alert(__('국내배송상품은 해외배송상품 장바구니로 이동이 불가능합니다.'));
                return;
            }

            // 국내배송 + 해외배송 상품이 섞여있는 경우
            if (iAbleOversea > 0 && iOnlyDomestic > 0) {
                alert(__('국내배송상품이 포함되어 있어 해외배송상품 장바구니로 이동이 불가능합니다.'));
                return;
            }

            var bConfirm = confirm(__('선택하신 상품을 해외배송상품 장바구니로 이동하시겠습니까?'));
            if (bConfirm == false) {
                return;
            }
        } catch (e) {}

        var sRedirectUrl = location.pathname + '?delvtype=B';
        this._callBasketAjax({
            command         : 'move_oversea',
            checked_product : this._getCheckedProductList().join(','),
            option_type : sOptionType // 단독 구성 옵션 상품/품목이 하나라도 있는 경우 'F' 를 보냄
        }, sRedirectUrl);
    },

    /**
     * 상품조르기
     * @param string sMemberId 회원아이디
     */
    hopeProduct : function(sMemberId) {
        if (sMemberId == '') {
            window.location.href = '/member/login.html';
            return false;
        }


        if (this._existsBasket(true)  == false) return false;
        if (this._existsChecked(true) == false) return false;

        // 선택 상품번호 추출
        var aPrdInfo = this._getCheckedProduct();
        var aProductNo = [];
        for (var i=0, length = aPrdInfo.length; i < length; i++) {
            aProductNo.push('product_no[]=' + aPrdInfo[i].product_no + ':' + aPrdInfo[i].set_product_no);
        }

        // 상품조르기 페이지 호출
        window.open('/product/request.html?' + aProductNo.join('&'), "상품조르기", "width=700,height=1000");
        return false;
    },


    /**
     * 모듈(패키징)단위로 체크박스 선택
     * @param sBoxName
     */
    setModuleCheckBasketList : function(sBoxName) {

        $('[id^="'+ BASKET_CHK_ID_PREFIX +'"]').each(function(){
            if ($(this).is(':checked')) {
                var sId = $(this).attr('id');
                var sName =  $(this).attr('name');
                if (sBoxName != sName) {
                    $(this).attr('checked', false);
                }
            }
        });

    },
    /**
     * 모듈(패키징)단위로 선택구매
     * @param sBoxName
     * @param oElem
     */
    orderSelectSuppBasket : function(sBoxName,oElem) {

        this.setModuleCheckBasketList(sBoxName);
        this.orderSelectBasket(oElem);

    },
    /**
     * 모둘(패키징)단위로 선택삭제
     * @param sBoxName
     * @param oElem
     */
    deleteSuppBasket : function(sBoxName,oElem) {

        this.setModuleCheckBasketList(sBoxName);
        this.deleteBasket();

    },
    /**
     * 모듈(패키징)단위로 전체구매
     * @param sBoxName
     * @param oElem
     */
    orderSuppAll : function(sBoxName,oElem) {

        $('[id^="'+ BASKET_CHK_ID_PREFIX +'"]').each(function(){
            sName = ($(this).attr('name'));
            if (sBoxName != sName) {
                $(this).attr('checked', false);
            } else {
                $(this).attr('checked', true);
            }
        });
        this.orderSelectBasket(oElem);

    },

    /**
     * '전체상품 주문' 버튼 클릭
     * @param object 클릭한 element 객체
     */
    orderAll : function(oElem) {
        $("input[id^='basket_chk_id']").each(function() {
            $(this).attr('checked', 'checked');
        });

        if (!this.orderSelectBasket(oElem, 'all_buy')) {
            $("input[id^='basket_chk_id']").each(function() {
                $(this).attr('checked', false);
            });
        }




        return;
        /* 국내,해외 동시활성화에 따라 디자인 하위호환성을 위해 */




        if (this._existsBasket(true) == false) return;

        // 최소,최대 주문가능 수량 체크
        if (this.isAbleQuantityForMaxMin(true) == false) {
            return;
        }

        if (this._chkInstallment() == false)
        {
            return;
        }

        this._callOrderAjax({basket_type:'all_buy'}, oElem);
    },

    /**
     * 모듈(패키징)단위로 체크박스 선택
     * @param sBoxName
     */
    setModuleCheckBasketList : function(sBoxName) {

        $('[id^="'+ BASKET_CHK_ID_PREFIX +'"]').each(function(){
            if ($(this).is(':checked')) {
                var sId = $(this).attr('id');
                var sName =  $(this).attr('name');
                if (sBoxName != sName) {
                    $(this).attr('checked', false);
                }
            }
        });

    },
    /**
     * 모듈(패키징)단위로 선택구매
     * @param sBoxName
     * @param oElem
     */
    orderSelectSuppBasket : function(sBoxName,oElem) {

        this.setModuleCheckBasketList(sBoxName);
        this.orderSelectBasket(oElem);

    },
    /**
     * 모둘(패키징)단위로 선택삭제
     * @param sBoxName
     * @param oElem
     */
    deleteSuppBasket : function(sBoxName,oElem) {

        this.setModuleCheckBasketList(sBoxName);
        this.deleteBasket();

    },
    /**
     * 모듈(패키징)단위로 전체구매
     * @param sBoxName
     * @param oElem
     */
    orderSuppAll : function(sBoxName,oElem) {

        $('[id^="'+ BASKET_CHK_ID_PREFIX +'"]').each(function(){
            sName = ($(this).attr('name'));
            if (sBoxName != sName) {
                $(this).attr('checked', false);
            } else {
                $(this).attr('checked', true);
            }
        });
        this.orderSelectBasket(oElem);

    },

    /**
     * '전체상품 주문' 버튼 클릭
     * @param object 클릭한 element 객체
     */
    orderLayerAll : function(oElem) {
        this._callOrderAjax({basket_type:'all_buy'}, oElem);
    },

    /**
     * 무이자 할부를 적용받을 수 없는경우의 컨펌
     */
    _chkInstallment : function()
    {



        return confirm(__("일반상품과 무이자 할부상품을 동시에 구매할경우 무이자 할부가 적용되지 않습니다.\n\n") +
                __("단, 카드사에서 진행하는 무이자할부 기간에는 전체주문 총 금액에 대해 무이자 할부가 적용됩니다.\n\n") +
                __("주문하시겠습니까?"));
    },

    /**
     * '선택상품 주문하기'(장바구니 타입 제한없음) 버튼 클릭
     * @param sProductType normal_type, installment_type
     * @param object 클릭한 element 객체
     * @return bool
     */
    orderSelectBasket : function(oElem, sBasketType)
    {
        if (this._existsChecked(true) == false) return false;
        if (this._chkMixedBasketType() === true) {
            if (this._chkInstallment() == false) {
                return false;
            }
        }
        var aCheckedProduct = [];
        var bSelected = false;
        var aProdList = this._getCheckedProduct();

        for (var i=0,n=aProdList.length; i < n; i++) {
            var iSeq = aProdList[i].seq;
            aCheckedProduct.push(aProdList[i].val);
            bSelected = true;
        }

        if (bSelected == false) {
            alert(__('선택된 상품이 없습니다.'));
            return false;
        }

        // 최소,최대 주문가능 수량 체크
        if (this.isAbleQuantityForMaxMin(false) == false) {
            return false;
        }

        // 상품별 결제수단 체크 - (ECHOSTING-75708, by wcchoi)
        if (! this._isValidProductPaymethod(aProdList)) {
            alert(__('주문 상품에 대하여 함께 결제할 수 없습니다. 상품의 결제수단을 확인해 주세요.'));
            return;
        }

        // 정기배송 + 1회 구매 섞여있는지 체크
        if (! this._isValidProductSubscription(aProdList)) {
            alert(__('NOT.PURCHASED.TOGETHER', 'SHOP.FRONT.BASKET.JS')); //정기배송 상품과 1회구매 상품은 함께 구매할 수 없습니다. 상품을 다시 선택해주세요.
            return;
        }

        this._callOrderAjax({
            checked_product : aCheckedProduct.join(','),
            basket_type     : sBasketType,
            delvtype : sBasketDelvType
        }, oElem);

        return true;
    },
    /**
     * 상품별 결제수단 체크 - (ECHOSTING-75708, by wcchoi)
     */
    _isValidProductPaymethod : function(aProdList)
    {
        if (bIsNewProduct == false) return true; // 뉴상품
        if (sUsePaymentMethodPerProduct != 'T') return true;

        var aList = [];
        for (var i=0,n=aProdList.length; i < n; i++) {
            var _paymethod = $.trim(aBasketProductData[aProdList[i].seq].product_paymethod);
            if (_paymethod == '') return false; // 결제수단이 없는 상품이 존재하면 주문 불가

            aList.push(_paymethod.split(','));
        }

        return (this._intersectAll(aList).length > 0);
    },

    /**
     * 정기배송 + 1회 구매 섞여있는지 체크
     */
    _isValidProductSubscription : function(aProdList)
    {
        if (bIsNewProduct == false) return true; // 뉴상품

        var aList = [];
        for (var i=0,n=aProdList.length; i < n; i++) {
            var _subscription = $.trim(aBasketProductData[aProdList[i].seq].is_subscription);
            aList.push(_subscription);
        }

        return (this._intersectAll(aList).length > 0);
    },

    /**
     * multiple arrays intersection
     */
    _intersectAll : function(aList)
    {
        if (aList.length == 0) return [];
        if (aList.length == 1) return aList[0];

        // 2 arrays intersection
        var _intersect = function(arr1, arr2) {
            var r = [], o = {}, l = arr2.length, i, v;
            for (i = 0; i < l; i++) { o[arr2[i]] = true; }
            l = arr1.length;
            for (i = 0; i < l; i++) {
                v = arr1[i];
                if (v in o) r.push(v);
            }
            return r;
        };

        var partialInt = aList[0];
        for (var i = 1; i < aList.length; i++) {
            partialInt = _intersect(partialInt, aList[i]);
        }

        return partialInt;
    },

    /**
     * 네이버 페이 주문
     */
    orderNaverCheckout : function()
    {
        if (this._existsBasket(true) == false) return;

        if (this._existsInstallmentType() == true) {
            if (!confirm (__("네이버 페이 구매시 무이자혜택을 받을 수 없습니다."))) {
                return;
            }
        }

        this._callOrderAjax({basket_type:'all_buy',navercheckout_flag:'T'});

        var sUrl = '/exec/front/order/navercheckout?sType=basket';

        // inflow param from naver common JS to Checkout Service
        try {
            if (typeof(wcs) == 'object') {
                var inflowParam = wcs.getMileageInfo();
                if (inflowParam != false) {
                    sUrl = sUrl + '&naver_inflow_param=' + inflowParam;
                }
            }
        } catch (e) {}

        if (is_order_page == 'N' && bIsMobile == false) {
            window.open(sUrl);
            return false;
        } else {
            location.href = sUrl;
            return false;
        }
    },

    /**
     * 쇼핑계속하기
     */
    continueShopping : function(oElem) {
        var sLink = $(oElem).attr('link') || '/';
        location.href = sLink;
    },

    /**
     * 수량 유효성 체크(1 이상의 수)
     * @return bool 1이상이면 ? TRUE : FALSE
     */
    _checkQuantity : function() {
        var bReturn = true;
        $('[id^="quantity_id_"]').each(function() {
            var iQnty = $.trim($(this).val());
            $(this).val(iQnty);

            if (isNaN(iQnty) == true || iQnty < 1) {
                $(this).select();
                bReturn = false;
                return false;
            }
        });

        return bReturn;
    },

    /**
     * 체크된 상품 정보 가져오기
     * @return array 체크된 상품 정보
     */
    _getCheckedProduct : function() {
        var aData = [];
        $('[id^="'+ BASKET_CHK_ID_PREFIX +'"]').each(function(){

            if ($(this).is(':checked')) {
                var iSeq = $(this).attr('id').replace(BASKET_CHK_ID_PREFIX, '');
                var iProdNo = aBasketProductData[iSeq].product_no;
                var iSetProdNo = aBasketProductData[iSeq].set_product_no;
                var iBpPrdNo = aBasketProductData[iSeq].basket_prd_no;
                var sOptId  = aBasketProductData[iSeq].opt_id;
                var sIsSubscription = aBasketProductData[iSeq].is_subscription;
                var bIsOverseaAble = true;
                var sIsSetProduct = 'F';
                var sOptionType = '';
                try {
                    if (aBasketProductData[iSeq].is_oversea_able != undefined) {
                        bIsOverseaAble = aBasketProductData[iSeq].is_oversea_able;
                    }
                } catch(e) {}
                try {
                    if (aBasketProductData[iSeq].option_type != undefined) {
                        // 단독구성 옵션일 경우 'F'
                        sOptionType =  aBasketProductData[iSeq].option_type;
                    }
                } catch(e) {}

                if (iSetProdNo > 0) {
                    sIsSetProduct = 'T';
                } else {
                    sIsSetProduct = 'F';
                }

                aData.push({
                   seq : iSeq,
                   val : iProdNo + ':' + sOptId + ':' + sIsSetProduct + ':' + iBpPrdNo,
                   product_no : iProdNo,
                   is_oversea_able : bIsOverseaAble,
                   option_type : sOptionType,
                   set_product_no : iSetProdNo,
                   is_subscription : sIsSubscription
                });
            }
        });

        return aData;
    },

    /**
     * 전체 상품 정보 가져오기
     * @return array 체크된 상품 정보
     */
    _getAllProduct : function() {
        var aData = [];
        $('[id^="'+ BASKET_CHK_ID_PREFIX +'"]').each(function(){
            var iSeq = $(this).attr('id').replace(BASKET_CHK_ID_PREFIX, '');
            var iProdNo = aBasketProductData[iSeq].product_no;
            var sOptId  = aBasketProductData[iSeq].opt_id;
            aData.push({
               seq : iSeq,
               val : iProdNo + ':' + sOptId,
            });
        });

        return aData;
    },

    /**
     * 체크된 상품 정보 Array 가져오기
     * @return array 체크된 상품 정보
     */
    _getCheckedProductList : function() {
        var aCheckedList = [];
        var aProdList = this._getCheckedProduct();
        for (var i=0,n=aProdList.length; i < n; i++) {
            aCheckedList.push(aProdList[i].val);
        }

        return aCheckedList;
    },

    /**
     * basket_type 가져오기
     * @param string product_type (normal_type, installment_type)
     * @return string basket_type(A0000, A0001)
     */
    _getBasketType : function(sProductType) {
        return (sProductType == 'installment_type') ? 'A0001' : 'A0000';
    },

    /**
     * 장바구니 상품 중 하나 이상 체크가 되어 있는지
     * @param bool bAlert 얼럿메세지 여부
     * @return bool 하나 이상 체크 ? true : false
     */
    _existsChecked : function(bAlert) {
        if (this._getCheckedProduct().length > 0) return true;

        if (bAlert) alert(__('선택된 상품이 없습니다.'));
        return false;
    },

    /**
     * 장바구니 상품이 존재하는지 체크
     * @param bool bAlert 얼럿메세지 여부
     * @return bool 상품이 1개 이상 있으면 true, 없으면 false
     */
    _existsBasket : function(bAlert) {
        if (aBasketProductData.length > 0) return true;

        if (bAlert) alert(__('상품이 없습니다.'));
        return false;
    },

    /**
     * 상품 중 '무이자할부' 타입이 있는지 체크
     * @return bool 있으면 ? TRUE : FALSE
     */
    _existsInstallmentType : function() {
        for (var i=0,n=aBasketProductData.length; i < n; i++) {
            if (aBasketProductData[i].product_type == 'installment_type') return true;
        }
        return false;
    },

    /**
     * '무이자할부' 상품과 그냥상품 섞여있는지
     * @return bool 있으면 ? TRUE : FALSE
     */
    _isMixedProductForInstallmentType : function() {
        var iNormalPrdCnt = 0;
        var iInstPrdCnt = 0;
        for (var i=0,n=aBasketProductData.length; i < n; i++) {
            if (aBasketProductData[i].product_type == 'installment_type') {
                iInstPrdCnt++;
            } else {
                iNormalPrdCnt++;
            }
        }

        // 무이자 상품과 일반상품이 섞여 있다면?
        if (iInstPrdCnt > 0 && iNormalPrdCnt > 0) {
            return true;
        }

        return false;
    },

    /**
     * 상품 중 무이자할부와 일반타입 상품이 섞인경우 체크
     * @return bool 있으면 ? TRUE : FALSE
     */
    _chkMixedBasketType : function() {
        var iInstallment = 0;
        var iNormal = 0;
        var aProdList = this._getCheckedProduct();
        for (var i=0, n=aProdList.length; i<n; i++) {
            var iSeq = aProdList[i].seq;
            if (aBasketProductData[iSeq].product_type != 'installment_type') {
                iInstallment++;
            } else if (aBasketProductData[iSeq].product_type != 'normal_type') {
                iNormal++;
            }
        }
        if (iNormal > 0 && iInstallment > 0) {
            return true;
        } else {
            return false;
        }
    },

    /**
     * 장바구니 command수행을 위한 ajax 호출
     * @param array aParam post전송할 파라미터
     * @param string sRedirectUrl redirect할 url
     */
    _callBasketAjax : function(aParam, sRedirectUrl) {
        $.post('/exec/front/order/basket/', aParam, function(data){
            Basket.isInProgressMigrationCartData(data);
            
            if (data.result < 0) {
                var msg = data.alertMSG.replace('\\n', '\n');
                try {
                    msg = decodeURIComponent(decodeURIComponent(msg));
                } catch (err) {}
                alert(msg);
                // after 수량변경
                if (aParam['command'] == 'update') {
                    location.reload();
                }

                // 관심상품
                if (aParam['command'] === 'select_storage') {
                    if (typeof(data.isLogin) != "undefined" && data.isLogin == "F") {
                        sUrl = '/member/login.html';
                        sUrl += '?returnUrl=' + encodeURIComponent("/order/basket.html?delvtype=" + aParam['delvtype']);
                        location.href = sUrl;
                        return false;
                    }
                    EC_PlusAppBridge.addWishList(EC_PlusAppBridge.getProductNo(aParam.checked_product));
                }
                if (data.result == -113) {
                    location.reload();
                }
            } else {
                if (aParam['command'] === 'select_storage') {
                    EC_PlusAppBridge.addWishList(EC_PlusAppBridge.getProductNo(aParam.checked_product));
                    if (typeof (sViewWishListBasket) != 'undefined' && sViewWishListBasket != 'T') {
                        location.href = '/myshop/wish_list.html';
                        return false;
                    }
                }

                (sRedirectUrl) ? location.href = sRedirectUrl : location.reload();
            }
        }, 'json');
    },

    /**
     * 주문하기 (is_prd 업데이트 후 orderform으로 redirect)
     * 비로그인 주문일 경우 noMember, returnUrl 파라미터 추가하여 로그인페이지로 이동
     * @param array aParam post전송할 파라미터
     * @param object 클릭한 element 객체
     */
    _callOrderAjax : function(aParam, oElem) {
        var sOrderUrl = $(oElem).attr('link-order') || '/order/orderform.html?basket_type='+ aParam.basket_type;

        if (sBasketDelvType != "") {
            sOrderUrl += '&delvtype=' + sBasketDelvType;
        }
        var sLoginUrl = $(oElem).attr('link-login') || '/member/login.html';

        $.post('/exec/front/order/order/', aParam, function(data){
            if (data.result < 0) {
                alert(data.alertMSG);
                return;
            }
            if (aParam.navercheckout_flag == 'T') {
                return true;
            }
            if (data.isLogin == 'F') { // 비로그인 주문
                // 로그인페이지로 이동
                sUrl = sLoginUrl + '?noMember=1&returnUrl=' + escape(sOrderUrl);

                if (aParam.checked_product != '') {
                   sUrl += '&checked_product=' + encodeURIComponent(aParam.checked_product);
                }
                location.href = sUrl;
            } else {
                location.href = sOrderUrl;
            }
        }, 'json');
    },

    /**
     * '△' 버튼 클릭, 수량증가
     * @param sId: 변경시킬 폼 id
     * @param int iIdx 품목정보 배열 인덱스
     */
    addQuantityShortcut : function(sId, iIdx)
    {
        //var iQuantity = parseInt($('#'+sId).val(), 10) + this.getBuyUnit(iIdx);
        var iQuantity = aBasketProductData[iIdx].quantity + this.getBuyUnit(iIdx);
        if (isNaN(iQuantity) === false) {
            $('#'+sId).val(iQuantity);
        }
        this.modifyQuantity(sId);
    },
    /**
     * '▽' 버튼 클릭, 수량감소
     * @param sId : 클릭한 id
     * @param int iIdx 품목정보 배열 인덱스
     */
    outQuantityShortcut : function(sId, iIdx)
    {
        //var iQuantity = parseInt($('#'+sId).val(), 10) - this.getBuyUnit(iIdx);
        var iQuantity = aBasketProductData[iIdx].quantity - this.getBuyUnit(iIdx);
        if (iQuantity < 1) iQuantity = 1;
        if (isNaN(iQuantity) === false) {
            $('#'+sId).val(iQuantity);
        }
        this.modifyQuantity(sId);
    },

    /**
     * 구매 주문단위값 가져오기
     * @param int iIdx 품목정보 배열 인덱스
     */
    getBuyUnit: function(iIdx)
    {
        try {
            if (bIsNewProduct) {
                return aBasketProductData[iIdx].buy_unit;
            }
        } catch (e) {}

        return 1;
    },

    /**
     * 장바구니 리스트의 '주문하기' 버튼 클릭
     * @param iIdx: 품목정보 배열 인덱스
     */
    orderBasketItem : function(iIdx)
    {
        // 각 항목별 수량체크에 성공할 경우에 주문페이지로 이동.
        if (this.isAbleQuantityForMaxMinSingle(iIdx)) {
            var aData = [];
            var iProdNo = aBasketProductData[iIdx].product_no;
            var sOptId  = aBasketProductData[iIdx].opt_id;
            var sProductType = aBasketProductData[iIdx].product_type;
            var sIsSetProduct = aBasketProductData[iIdx].is_set_product;
            var iBasketPrdNo = aBasketProductData[iIdx].basket_prd_no;

            // 장바구니 분리형세트 상품 판단을 위한 세트번호
            var iSetPrdNo = parseInt(aBasketProductData[iIdx].set_product_no);

            // 분리형세트의 선택주문시 관련세트 구성 전부 체크후 선택주문하기처리
            if (iSetPrdNo > 0 ) {
                this.setAddSingleSetItemCheckedAction(iSetPrdNo, 'orderSelectBasket');
                return false;
            }

            var sKey = iProdNo + ':' + sOptId + ':' + sIsSetProduct + ':' + iBasketPrdNo;

            aData.push(sKey);

            this._callOrderAjax({
                checked_product : aData.join(','),
                basket_type     : this._getBasketType(sProductType),
                delvtype        : sBasketDelvType
            });
        }
    },

    /**
     * 장바구니상 분리형세트 단독 처리 불가능하게 액션처리
     */
    setAddSingleSetItemCheckedAction : function(iSetPrdNo, sAction)
    {

        for (i = 0; i < aBasketProductData.length; i++) {
            if (aBasketProductData[i].set_product_no == iSetPrdNo) {
                $("#" + BASKET_CHK_ID_PREFIX + i).attr("checked", true);
            } else {
                $("#" + BASKET_CHK_ID_PREFIX + i).attr("checked", false);
            }
        }

        // 선택액션 임시 엘리먼트 생성
        var oTmpElem = document.createElement('a');
        oTmpElem.id = 'oBasketSetAction';
        oTmpElem.setAttribute("link-login","/member/login.html");
        oTmpElem.setAttribute("link-order","/order/orderform.html?basket_type=all_buy");

        switch (sAction) {
            case 'orderSelectBasket' : this.orderSelectBasket(oTmpElem); break;
            case 'deleteBasket' : this.deleteBasket(); break;
           // case 'orderSelectBasket' : this.orderSelectBasket(); break;

        }

    },

    /**
     * 장바구니 리스트의 '관심상품등록' 버튼 클릭
     * @param iIdx: 품목정보 배열 인덱스
     */
    addWishListItem : function(iIdx)
    {
        var aData = [];
        var iProdNo = aBasketProductData[iIdx].product_no;
        var sOptId  = aBasketProductData[iIdx].opt_id;
        var sProductType = aBasketProductData[iIdx].product_type;
        var sIsSetProduct = aBasketProductData[iIdx].is_set_product;
        var iBasketPrdNo = aBasketProductData[iIdx].basket_prd_no;
        var sKey = iProdNo + ':' + sOptId + ':' + sIsSetProduct + ':' + iBasketPrdNo;
        aData.push(sKey);
        this._callBasketAjax({
            command         : 'select_storage',
            checked_product : aData.join(','),
            delvtype        : sBasketDelvType
        });
    },
    /**
     * 장바구니 리스트의 '삭제' 버튼 클릭
     * @param iIdx: 품목정보 배열 인덱스
     */
    deleteBasketItem : function(iIdx)
    {
        // 장바구니 분리형세트 상품 판단을 위한 세트번호
        var iSetPrdNo = parseInt(aBasketProductData[iIdx].set_product_no);

        // 분리형세트의 선택주문시 관련세트 구성 전부 체크후 선택주문하기처리
        if (iSetPrdNo > 0) {
            this.setAddSingleSetItemCheckedAction(iSetPrdNo, 'deleteBasket');
            return false;

        }

        if (confirm(__('선택하신 상품을 삭제하시겠습니까?')) == false) return;

        if (typeof ACEWrap != 'undefined') {
            ACEWrap.delCheckedBasket();
        }
        var aData = [];
        var iProdNo = aBasketProductData[iIdx].product_no;
        var sOptId  = aBasketProductData[iIdx].opt_id;
        var sProductType = aBasketProductData[iIdx].product_type;
        var sIsSetProduct = aBasketProductData[iIdx].is_set_product;
        var iBasketPrdNo = aBasketProductData[iIdx].basket_prd_no;

        var sKey = iProdNo + ':' + sOptId + ':' + sIsSetProduct + ':' + iBasketPrdNo;
        aData.push(sKey);
        this._callBasketAjax({
            command         : 'select_delete',
            checked_product : aData.join(','),
            delvtype        : sBasketDelvType
        });
    },
    /**
     * 장바구니 리스트의 체크박스 전체선택
     * @param sBoxName: 선택할 종류이름
     * @param oElem: object 클릭한 element 객체
     */
    setCheckBasketList: function(sBoxName, oElem)
    {
        if (this._existsBasket(true) == false) return;
        $('input[name="'+ sBoxName +'"]:checkbox').each(function(){
            if ($(oElem).attr('checked')) {
                $(this).attr('checked', true);
            } else {
                $(this).attr('checked', false);
            }
        });
    },

    /**
     * 각각의 장바구니 아이템별로 객체화한다.
     * @param iIndex 장바구니인덱스.
     * @return Object 장바구니내의 개별 아이템객체
     */
    makeBasketPrdInfo: function(iIndex) {
        var iProdNo = aBasketProductData[iIndex].product_no;
        var sOptId = aBasketProductData[iIndex].opt_id;
        var sKeyProdWithOpt = iProdNo + '__' + sOptId;

        var objBasketPrdInfo = [];

      if ( objBasketPrdInfo.length == 0) {
      // [상품번호__옵션]별 객체 초기화.
          objBasketPrdInfo[sKeyProdWithOpt] = {
                "minMaxKey": sKeyProdWithOpt,
                "buyUnitKey": sKeyProdWithOpt,
                "quantity": 0,
                "min": 0,
                "max": 0,
                "maxType": "F",
                "buy_unit":1,
                "product_name_quantity": aBasketProductData[iIndex].product_name.replace(/\\(.)/mg, "$1"),
                "product_name_buy_unit": aBasketProductData[iIndex].product_name.replace(/\\(.)/mg, "$1")
            };
        }

//      폼전송이 발생하기전 화면에 입력된 값은 무시. (2015-12-11)
//      objBasketPrdInfo[sKeyProdWithOpt].quantity += parseInt($('#quantity_id_'+ iIndex).val());
        objBasketPrdInfo[sKeyProdWithOpt].quantity = aBasketProductData[iIndex].quantity;
        // ECHOSTING-336171 대응
        // 1+N 상품 일 경우, 주문수량 제한 > 최대 주문수량 체크 하지 않음
        objBasketPrdInfo[sKeyProdWithOpt].maxType  = aBasketProductData[iIndex].sIsBenefitEventProduct == 'T' ? 'F' : aBasketProductData[iIndex].product_max_type;
        //objBasketPrdInfo[sKeyProdWithOpt].maxType  = aBasketProductData[iIndex].product_max_type;
        objBasketPrdInfo[sKeyProdWithOpt].min      = aBasketProductData[iIndex].product_min;
        objBasketPrdInfo[sKeyProdWithOpt].max      = aBasketProductData[iIndex].product_max;
        objBasketPrdInfo[sKeyProdWithOpt].buy_unit      = aBasketProductData[iIndex].check_buy_unit;

        if (Olnk.isLinkageType(aBasketProductData[iIndex].option_type) === true) {
            objBasketPrdInfo[sKeyProdWithOpt].min      = aBasketProductData[iIndex].product_min;
            objBasketPrdInfo[sKeyProdWithOpt].max      = aBasketProductData[iIndex].product_max;
        }

        if (aBasketProductData[iIndex].check_quantity_type == 'P') {
            objBasketPrdInfo[sKeyProdWithOpt].minMaxKey = iProdNo;
        } else {
            objBasketPrdInfo[sKeyProdWithOpt].product_name_quantity += aBasketProductData[iIndex].opt_str.replace(/\\(.)/mg, "$1");
        }

        if (aBasketProductData[iIndex].check_buy_unit_type == 'P') {
            objBasketPrdInfo[sKeyProdWithOpt].buyUnitKey = iProdNo;
        } else {
            objBasketPrdInfo[sKeyProdWithOpt].product_name_buy_unit += aBasketProductData[iIndex].opt_str.replace(/\\(.)/mg, "$1");
        }

        return objBasketPrdInfo[sKeyProdWithOpt];
    },


    /**
     * 최소/최대 주문가능 수량 체크
     * @param boolean bIsAll 전체상품주문여부
     * @return boolean
     */
    isAbleQuantityForMaxMin: function(bIsAll)
    {
        var aBasketPrdInfo = [];
        var aBasketCheckQuantity = [];
        var aBasketCheckBuyUniyQuantity = [];
        for (var i=0,n=aBasketProductData.length; i < n; i++) {
            // 선택상품 주문인경우 선택한 상품에 대해서만
            if (bIsAll == false) {
                if ($("#" + BASKET_CHK_ID_PREFIX + i).attr("checked") == false) {
                    continue;
                }
            }

            if (aBasketProductData[i].check_quantity_type == 'P') {
                var sKey = aBasketProductData[i].product_no;
                if (typeof aBasketCheckQuantity[sKey] == 'undefined') {
                    aBasketCheckQuantity[sKey] = aBasketProductData[i].quantity;
                } else {
                    aBasketCheckQuantity[sKey] += aBasketProductData[i].quantity;
                }
            } else {
                var sKey = aBasketProductData[i].product_no + '__' + aBasketProductData[i].opt_id;
                aBasketCheckQuantity[sKey] = aBasketProductData[i].quantity;
            }

            if (aBasketProductData[i].check_buy_unit_type == 'P') {
                var sKey = aBasketProductData[i].product_no;
                if (typeof aBasketCheckBuyUniyQuantity[sKey] == 'undefined') {
                    aBasketCheckBuyUniyQuantity[sKey] = aBasketProductData[i].quantity;
                } else {
                    aBasketCheckBuyUniyQuantity[sKey] += aBasketProductData[i].quantity;
                }
            } else {
                var sKey = aBasketProductData[i].product_no + '__' + aBasketProductData[i].opt_id;
                aBasketCheckBuyUniyQuantity[sKey] = aBasketProductData[i].quantity;
            }

            aBasketPrdInfo.push(this.makeBasketPrdInfo(i));
        }

//        alert(aBasketPrdInfo.toString());
        // 유효성 체크
        var  iBasketPrdCnt = aBasketPrdInfo.length;
        for (var index = 0; index < iBasketPrdCnt; index++) {
            // 최소구매수량 체크
            var iProductMinCount = aBasketPrdInfo[index].min <= 0 ? 1 : aBasketPrdInfo[index].min;
            if (aBasketCheckQuantity[aBasketPrdInfo[index].minMaxKey] < iProductMinCount) {
                alert(aBasketPrdInfo[index].product_name_quantity+' '+sprintf(__('최소 주문수량은 %s개 입니다.'), iProductMinCount));
                this.resetQuantityFromBasket();
                return false;
            }
            // 최대구매수량 체크
            if (( aBasketPrdInfo[index].maxType == 'T' && aBasketPrdInfo[index].max > 0)
                    &&  aBasketPrdInfo[index].max <  aBasketCheckQuantity[aBasketPrdInfo[index].minMaxKey]) {
                alert(aBasketPrdInfo[index].product_name_quantity+' '+sprintf(__('최대 주문수량은 %s개 입니다.'), aBasketPrdInfo[index].max));
                this.resetQuantityFromBasket();
                return false;
            }

            if ((aBasketCheckBuyUniyQuantity[aBasketPrdInfo[index].buyUnitKey] % aBasketPrdInfo[index].buy_unit) > 0) {
                alert(aBasketPrdInfo[index].product_name_buy_unit+' '+sprintf(__('구매 주문단위는 %s개 입니다.'), aBasketPrdInfo[index].buy_unit));
                return false;
            }
        }

        return true;
    },

    /**
     * 최소/최대 주문가능 수량 체크 (단일상품)
     * @param boolean iIndex 장바구니 인덱스.
     * @return boolean
     */
    isAbleQuantityForMaxMinSingle: function(iIndex)
    {
        var aBasketPrdInfo = [];
        aBasketPrdInfo.push(this.makeBasketPrdInfo(iIndex));
        // 유효성 체크
        // 최소구매수량 체크
        var iProductMinCount = aBasketPrdInfo[0].min <= 0 ? 1 : aBasketPrdInfo[0].min; //구상품 최소 구매수량 0으로 저장 가능
        if (aBasketPrdInfo[0].quantity < iProductMinCount) {
            alert(sprintf(__('최소 주문수량은 %s개 입니다.'), iProductMinCount));
            this.resetQuantityFromBasket();
            return false;
        }
        // 최대구매수량 체크
        if (( aBasketPrdInfo[0].maxType == 'T' && aBasketPrdInfo[0].max > 0)
                &&  aBasketPrdInfo[0].max <  aBasketPrdInfo[0].quantity) {
            alert(sprintf(__('최대 주문수량은 %s개 입니다.'), aBasketPrdInfo[0].max));
            this.resetQuantityFromBasket();
            return false;
        }

        if ((aBasketPrdInfo[0].quantity % aBasketPrdInfo[0].buy_unit) > 0) {
            alert(sprintf(__('구매 주문단위는 %s개 입니다.'), aBasketPrdInfo[0].buy_unit));
            return false;
        }

        return true;
    },

    /**
     * 상품수량 장바구니 정보로 초기화
     */
    resetQuantityFromBasket: function()
    {
        try {
            for (var i=0,n=aBasketProductData.length; i < n; i++) {
                var iOldQty = parseInt(aBasketProductData[i].quantity);
                var iCurQty = parseInt($('#quantity_id_'+i).val());
                if (iOldQty != iCurQty) {
                    $('#quantity_id_'+i).val(iOldQty);
                }
            }
        } catch (e) {}
    },

    /**
     * 옵션변경 레이어 노출
     * @param string sId 옵션변경 layer id
     */
    showOptionChangeLayer: function(sId, oThis)
    {
        var aIndex = sId.split("_");
        var iIndex = aIndex[3];
        var iSetIndex = sId.split("_")[4];

        if ($("#ec-basketOptionModifyLayer").length > 0) { // 비동기 옵션 변경 레이어 사용일경우 - ECHOSTING-229719
            /** 추가/변경 버튼 클릭 이벤트 끊어주기 **/
            $(".ec-basketOptionModifyLayer-add").unbind("click");
            $(".ec-basketOptionModifyLayer-modify").unbind("click");
            /** 선택옵션, 추가옵션 템플릿 제외하고 다 지워주기 **/
            $("#ec-basketOptionModifyLayer").find(".ec-basketOptionModifyLayer-options").slice(1).remove();
            $("#ec-basketOptionModifyLayer").find(".ec-basketOptionModifyLayer-addOptions").slice(1).remove();
            
            var aParam = {
                iIndex : iIndex,
                iSetIndex : iSetIndex,
                aProductData : aBasketProductData[iIndex]
            };

           $.ajax({
                type: 'POST',
                url: '/exec/front/Product/OptionForm/',
                data: aParam,
                dataType: 'json',
                async : false,
                success: function(data) {
                  if (data.result == 0){
                    var aProductOption = data.aProductOption; 
                    $(".ec-basketOptionModifyLayer-productName").html(aProductOption.product_name);
                    $(".ec-basketOptionModifyLayer-optionStr").html(aProductOption.layer_option_str);
 
                    /** 선택 옵션 **/
                    for (var key in aProductOption.optionList) { 
                        var oOptionElement = $(".ec-basketOptionModifyLayer-options").first().clone();
                        var sOptionElement = oOptionElement.html();
                        sOptionElement = sOptionElement.replace(/{\$option_name}/g, aProductOption.optionList[key].option_name);
                        sOptionElement = sOptionElement.replace(/{\$form.option_value}/g, aProductOption.optionList[key].form_option_value);
                        oOptionElement.html(sOptionElement);
                        $(".ec-basketOptionModifyLayer-options").last().after(oOptionElement.show());
                    }

                    /** 추가입력 옵션 **/
                    for (var key in aProductOption.optionAddList) { 
                        var oOptionElement = $(".ec-basketOptionModifyLayer-addOptions").first().clone();
                        var sOptionElement = oOptionElement.html();
                        sOptionElement = sOptionElement.replace(/{\$option_name}/g, aProductOption.optionAddList[key].option_name);
                        sOptionElement = sOptionElement.replace(/{\$form.option_value}/g, aProductOption.optionAddList[key].form_option_value);
                        oOptionElement.html(sOptionElement);
                        $(".ec-basketOptionModifyLayer-addOptions").last().after(oOptionElement.show());
                    }
                    
                    /** 옵션 추가 버튼 **/
                    if (aProductOption.option_add_display == true) {
                        $(".ec-basketOptionModifyLayer-add").show();
                        $(".ec-basketOptionModifyLayer-add").click(function() {
                            BasketNew.modify(iIndex, 'add');
                        });
                    } else {
                        $(".ec-basketOptionModifyLayer-add").hide();
                    }

                    /** 옵션 변경 버튼 **/
                    $(".ec-basketOptionModifyLayer-modify").click(function() {
                        if (aBasketProductData[iIndex]['is_set_product']=='T' && aBasketProductData[iIndex]['set_product_no']==0) {
                            NewBasketSetOption.modify(iIndex, iSetIndex); // 일체세트
                        } else {
                            BasketNew.modify(iIndex, 'modify');
                        }
                    });

                    /** 옵션 폼 이벤트 초기화 **/
                    EC_SHOP_FRONT_NEW_OPTION_COMMON.initObject();
                    EC_SHOP_FRONT_NEW_OPTION_COMMON.init();
                    EC_SHOP_FRONT_NEW_OPTION_BIND.initChooseBox();
                    EC_SHOP_FRONT_NEW_OPTION_DATA.initData();
                  }
                }
            });

            /** 옵션변경 이벤트 발생시킨 엘리먼트 바로 뒤에 붙여줌 **/
            oThis.after($("#ec-basketOptionModifyLayer"));
            $("#ec-basketOptionModifyLayer").show();
            
        } else {
            $("[id^='option_modify_layer']").hide();
            $(".optionModify").hide();
            $("#" + sId).show();

            if (bIsNewProduct === true ) {
                $("#" + sId).find('[id^="product_option_id"]').eq(0).val('*').trigger('change');
            }
        }
    },
    /**
     *  상품명위에 [당일배송][퀵배송] 문구 노출
     *  @param aPrdNo : 장바구니페이지의 상품번호 array
     */
    isCustomshipAjax : function( aQuickPrdNo, aQuickItemCode )
    {
        if (!aQuickItemCode) return;
        var aParam = {};
        var sDeliveryAreaAjaxUrl = '/exec/front/order/Basketcustomship/';

        aParam['aPrdNo'] = aQuickPrdNo;
        aParam['aItemCode'] = aQuickItemCode;

        $.ajax({
            type: 'POST',
            url: sDeliveryAreaAjaxUrl,
            data: aParam,
            dataType: 'json',
            async : false,
            success: function(data) {
                if (data.result == 0){
                    var sToday = data.sDisplayToday;
                    var sQuick = data.sDisplayQuick;

                    try {
                        for (var key1 in sQuick) {
                            if (sQuick[key1] == 'T') $('[id^="custom_quick_id_show_' + key1 + '"]').removeClass('displaynone');
                            if (sQuick[key1] == 'T') $('[id^="custom_quick_id_' + key1 + '"]').html(sQuick['sc_name']);
                        }
                        for (var key in sToday) {
                            if (sToday[key] == 'T') $('[id^="custom_today_id_show_' + key + '"]').removeClass('displaynone');
                            if (sToday[key] == 'T') $('[id^="custom_today_id_' + key + '"]').html(sToday['sc_name']);
                        }
                    } catch(e) {}
                }
            }
        });
    },

    /**
     * 장바구니 스토어픽업전용상품 선택하기
     */
    orderStorePickupSelectBasket : function (oElem) {
        var aSetNoArray = new Array();

        for (i = 0; i < aBasketProductData.length; i++) {
            if (aBasketProductData[i].use_store_pickup == 'T') {
                $("#" + BASKET_CHK_ID_PREFIX + i).attr("checked", true);
                //대상상품중 분리세트가 전용설정이면 같이 선택되게 한다.
                if (parseInt(aBasketProductData[i].set_product_no) > 0) {
                    aSetNoArray.push(aBasketProductData[i].set_product_no);
                }
            } else {
                $("#" + BASKET_CHK_ID_PREFIX + i).attr("checked", false);
            }
        }

        if (aSetNoArray.length > 0) {
            this.setSetProductCheckedSync(aSetNoArray);
        }

    },

    setSetProductCheckedSync : function (aSetNo) {
        for (i = 0; i < aSetNo.length; i++) {
            for (j = 0; j < aBasketProductData.length; j++) {
                if (parseInt(aBasketProductData[j].set_product_no) == aSetNo[i]) {
                    $("#" + BASKET_CHK_ID_PREFIX + j).attr("checked", true);
                }
            }
        }
    },

    isInProgressMigrationCartData : function(aData) {
    if (aData['isInProgressMigrationCartData'] === true) {
        alert(__('SYSTEM.IS.BUSY.PLEASE.TRY', 'SHOP.FRONT.BASKET.JS'));
        window.location.reload();
    }
}

};

/**
 * 네이버 페이 주문하기
 */
function nv_add_basket_1_basket()
{
    bIsMobile = false;

    if (_isProc == 'F') {
        alert(__("네이버 페이 입점상태를 확인하십시오."));
        return;
    }
    Basket.orderNaverCheckout();
}

/**
 * 네이버 페이 찜하기
 */
function nv_add_basket_2_basket()
{
}

/**
 * 네이버 페이 주문하기(모바일)
 */
function nv_add_basket_1_m_basket()
{
    bIsMobile = true;

    if (_isProc == 'F') {
        alert(__("네이버 페이 입점상태를 확인하십시오."));
        return;
    }
    Basket.orderNaverCheckout();
}

/**
 * 네이버 페이 찜하기(모바일)
 */
function nv_add_basket_2_m_basket()
{
}

// 레이어 장바구니 페이징
function layer_basket_paging(page_no)
{
    $.get('/product/add_basket2.html?page=' + page_no + '&layerbasket=T', '', function(sHtml)
    {
        $('#confirmLayer').html(sHtml);
        $('#confirmLayer').show();

        // set delvtype to basket
        try {
            $(".xans-order-layerbasket").find("a[href='/order/basket.html']").attr("href", "/order/basket.html?delvtype=" + delvtype);
        } catch (e) {}
    });
}

/**
 * 주문관련 레이어 처리
 */
var OrderLayer = {
    /**
     * 켭니다.
     */
    onDiv : function(sId, event)
    {
        var target = event.target || event.srcElement;
        ex = $(target).position().left;
        ey = $(target).offset().top;
        if ( ex != 'undefined' && ey != 'undefined') {
            $('#'+sId).css({'top': ey - 240  + 'px'});
            $('#'+sId).css({'left':ex + 'px'});
        }
        $('#'+sId).show();
    },
    /**
     * 끕니다.
     */
    offDiv : function(sId)
    {
        $('#'+sId).hide();
    }
};

$(document).ready(function() {
    var filter = /delvtype=B/;
    if (filter.test(location.search)) {
        $("a[onclick='Basket.moveOversea()']").hide();
    }

    try {
        // 추가입력옵션 글자 길이 체크
        $("input[class^='ProductAddOption'], input[class^='SetProductAddOption']").live('keyup', function() {
            var iLimit = $(this).attr('maxlength');
            addOptionWord($(this).attr('id'), $(this).val(), iLimit);
        });
    } catch (e) {}


    // 할인가표시
    $('.discount').each(function() {
        if ($(this).next().attr('class') == 'displaynone') { // 할인가가 없을 경우
            $(this).removeClass('discount');
        }
    });

    // 추가입력 옵션 ; 제거
    $('input[class^="SetProductAddOption"]').blur(function(){
        if ($(this).val().search(/;/) > -1){
            alert(__('ENTER.SPECIAL.CHARACTER', 'SHOP.FRONT.BASKET.JS'));
            $(this).val($(this).val().replace(/;/g, ''));
        }
    });

    // 난다전용 (당일배송/퀵배송 표기) - 디자인이 추가되지 않았으면 ajax 통신 자체를 안하도록. (custom_quick_id_show_10)
    /*
    var bCustomDisplay = false;
    if (typeof(aQuickPrdNo) != "undefined" && aQuickPrdNo != null) {
        for (var key in aQuickPrdNo) {
            if ( $('#custom_quick_id_show_'+aQuickPrdNo[key]).attr('class') == 'displaynone' && $('#custom_quick_id_show_'+aQuickPrdNo[key]).attr('class') != 'undefined') {
                bCustomDisplay = true;
            }else if ( $('#custom_today_id_show_'+aQuickPrdNo[key]).attr('class') == 'displaynone' && $('#custom_today_id_show_'+aQuickPrdNo[key]).attr('class') != 'undefined') {
                bCustomDisplay = true;
            }
        }
        if ( bCustomDisplay === true ) {
            Basket.isCustomshipAjax(aQuickPrdNo);
        }
    }*/
    var bCustomDisplay = false;
    if (typeof(aQuickItemCode) != "undefined" && aQuickItemCode != null) {
        for (var key in aQuickItemCode) {
            if ( $('#custom_quick_id_show_'+aQuickItemCode[key]).attr('class') == 'displaynone' && $('#custom_quick_id_show_'+aQuickItemCode[key]).attr('class') != 'undefined') {
                bCustomDisplay = true;
            }else if ( $('#custom_today_id_show_'+aQuickItemCode[key]).attr('class') == 'displaynone' && $('#custom_today_id_show_'+aQuickItemCode[key]).attr('class') != 'undefined') {
                bCustomDisplay = true;
            }
        }
        if ( bCustomDisplay === true ) {
            Basket.isCustomshipAjax(aQuickPrdNo,aQuickItemCode);
        }
    }

    //분리형세스상품 체크 같이 처리
    $("input[id^='"+BASKET_CHK_ID_PREFIX+"']").click(function() {
        var iSeq = $(this).attr('id').replace(BASKET_CHK_ID_PREFIX, '');
        var iSetPrdNo = aBasketProductData[iSeq].set_product_no;
        var bIsChecked =  $(this).attr('checked');
        if (parseInt(iSetPrdNo) >  0) {
            for (i = 0; i < aBasketProductData.length; i++) {
                if (iSetPrdNo == aBasketProductData[i].set_product_no) {
                    $('#' + BASKET_CHK_ID_PREFIX + i ).attr('checked', bIsChecked);
                }
            }
        }
    });
});

/**
 * 뉴상품 상품옵션변경
 */
var BasketNew = {
    /**
     * '옵션변경'레이어에서 '적용하기' 버튼 클릭
     * @param int iIdx 품목정보배열 index
     * @param string sMode 액션모드(modify: 변경, add: 추가)
     */
    modify : function(iIdx, sMode)
    {
        // // 사용자지정옵션인경우 옵션변경불가(기존사양)
        // if (sMode == 'modify') {
        //     if (aBasketProductData[iIdx].option_add == "T") {
        //         alert(__("사용자 지정 옵션 상품은 옵션변경을 하실 수 없습니다."));
        //         $('#quantity_id_'+iIdx).val(aBasketProductData[iIdx].quantity);
        //         return false;
        //     }
        // }

        // 오직 추가옵션만 있는지
        var isOnlyOptionAdd = false;
        if (aBasketProductData[iIdx].has_option == "F" && aBasketProductData[iIdx].has_option_add == "T") {
            isOnlyOptionAdd = true;
        }

        //필수옵션 체크
        if (this.checkOptionRequired() == false) return;

        //추가옵션 체크
        if (this.checkAddOption() == false) return;

        // 파리미터 담을 객체
        var aParam = {};

        // 상품번호
        var iProductNo = aBasketProductData[iIdx].product_no;

        // 품목코드
        var sItemCode = aBasketProductData[iIdx].item_code;

        // 상품연동형 옵션타입인지 여부
        var isOptionEtype = Olnk.isLinkageType(aBasketProductData[iIdx].option_type);

        // 분리형세상품번호
        var iSetProductNo =  aBasketProductData[iIdx].set_product_no;

        // 선택 품목정보 추출
        var oItemInfo = {};
        if (isOptionEtype === true) {
            oItemInfo = Olnk.getMockItemInfo({
                'product_no' : aBasketProductData[iIdx].product_no,
                'product_code' : aBasketProductData[iIdx].product_code
            });
        } else {
            oItemInfo = this.getItemInfo(iIdx, iProductNo);
        }

        // 선택옵션인경우만 체크
        if (isOptionEtype == false && isOnlyOptionAdd == false) {
            // 재고정보 추출
            var sKey = "option_stock_data" + iProductNo;
            var oItemStock = $.parseJSON(window[sKey]);

            var oItem = oItemStock[oItemInfo.item_code];

            // 판매여부 체크
            if (oItem.is_selling == "F") {
                alert(sprintf(__('선택하신 %s 옵션은 판매하지 않은 옵션입니다.\n다른 옵션을 선택해 주세요.'), oItem.option_value));
                return false;
            }

            // 재고체크
            if (oItem.use_stock === true) {
                // ECHOSTING-318729 대응,
                // 상품 쪽 설정에 따라, 재고가 있을때 stock_number 가 없는 데이터가 들어오게 되므로
                // 에러방지를 위한 undefined 체크 추가
                if (oItem.is_selling == "F" || (oItem.stock_number != undefined && oItem.stock_number < 1)) {
                    alert(__('재고 수량이 부족합니다.'));
                    return false;
                }
            }
        }
        // 동일품목 추가여부 확인
        if (isOptionEtype === true && isOnlyOptionAdd === false) {
            var sOptionData = aBasketProductData[iIdx].olink_data;

            var aDulicationArray = new Array();
            $('.ProductOption'+iIdx+':visible').each(function(i) {
                if (/^\*+$/.test($(this).val()) === false ) {
                    aDulicationArray.push($(this).val());
                }
            });

            var sDulicationData = aDulicationArray.join('!@#');

            if (sDulicationData === sOptionData) {
                alert(sprintf(__('동일상품이 장바구니에 %s개 있습니다.'), aBasketProductData[iIdx].quantity));
                return false;
            }
        }
        // 수량
        var iBuyQuantity = aBasketProductData[iIdx].quantity;
        var iBuyUnit = parseInt(aBasketProductData[iIdx].buy_unit);
        var iProductMin = parseInt(aBasketProductData[iIdx].product_min);


        // 주문추가의 경우에는 입력된 수량이 아닌 초기 설정 수량이 필요함.
        // 최소 주문수량과 주문단위를 비교하는 로직 추가.
        if (sMode == "add") {
            // 주문단위 설정이 상품 단위인 경우에는 수량 1로 상품 추가
            iBuyQuantity = (aBasketProductData[iIdx].check_buy_unit_type == 'P') ? 1 : BasketNew.getInitialQuantity(iBuyUnit, iProductMin);
        }

        // 액션
        aParam["command"] = (sMode == "modify") ? "update" : "add";

        // 품목정보
        aParam["product_no"]       = oItemInfo.product_no;
        aParam["item_code"]        = oItemInfo.item_code;
        aParam["opt_id"]           = oItemInfo.opt_id;
        aParam["quantity"]         = iBuyQuantity;
        aParam["item_code_before"] = aBasketProductData[iIdx].item_code;
        aParam["opt_id_before"]    = aBasketProductData[iIdx].opt_id;
        aParam["set_product_no"]    = aBasketProductData[iIdx].set_product_no;

        // 추가입력옵션
        var aAddOptionName = [];
        $("input[id^='add_option']:visible").each(function(index) {
            aAddOptionName.push($(this).attr("name"));
            aParam["option_add[" + index + "]"] = $(this).val();
        });

        aParam["add_option_name"] = aAddOptionName.join(";");
        aParam["option_change"]   = "T";
        aParam["is_new_product"]  = "T";
        aParam["delvtype"]        = (typeof(sBasketDelvType) == "undefined") ? "A" : sBasketDelvType;

        // 유효성 체크(기존)
        aParam["selected_item[]"] = iBuyQuantity + "||" + oItemInfo.item_code;
        aParam["num_of_prod"]     = iBuyQuantity;

        // '추가' 일경우
        if (sMode == "add") {
            aParam["main_cate_no"] = aBasketProductData[iIdx].main_cate_no;
            aParam["num_of_prod"]  = 1;
        }

        aParam = Olnk.hookParamForBasket(aParam, {
           'product_code' : aBasketProductData[iIdx].product_code,
           'option_type' : aBasketProductData[iIdx].option_type,
           'quantity' : iBuyQuantity,
           'targets' : $('.ProductOption' + iIdx + ':visible')
        });


        var aBasketOlnkData = Olnk.getProductAllSelected (aBasketProductData[iIdx].product_code ,$('.ProductOption'+iIdx+':visible') , iBuyQuantity);
        if ( aBasketOlnkData !== false ) {
            aParam['selected_item_by_etype[]'] = $.toJSON(aBasketOlnkData);
        }

        // 옵션변경 레이어팝업에서 추가/변경시 필수 옵션 체크 하지 않도록 한다.
        if (sMode == "add" || sMode == "modify") {
            aParam["call_from"]    = 'option_modify';
        }

        Basket._callBasketAjax(aParam);
    },

    /**
     * 기본단위를 구하는 함수.
     * 주문단위와 최소구매수량을 비교하여 결정한다.
     */
    getInitialQuantity : function(iBuyUnit, iProductMin) {
        // 기본 최초 구매 단위는 1로 설정.
        var initialQuantity = 1;
        // 주문단위가 1 이상이면... 주문단위가 최소값.
        if (iBuyUnit > initialQuantity) {
            // 주문단위보다 최소 주문수량이 큰 경우.
            // 기본단위는 최소 주문수량보다 큰 최소 주문단위로 설정.
            initialQuantity = iBuyUnit;
            if (iBuyUnit < iProductMin) {
                while ( iProductMin % iBuyUnit != 0) {
                    iProductMin ++;
                }
                initialQuantity = iProductMin;
            }
        } else {
            // 주문단위가 없는 경우.
            // 최소주문단위가 initialQuantity 보다 큰 경우
            if ( iProductMin > initialQuantity)  {
                initialQuantity = iProductMin;
            }
        }
        return initialQuantity;
    },

    /**
     * 필수옵션 체크 여부
     * @return bool true: 체크 / false: 체크안함
     */
    checkOptionRequired : function()
    {
        var bIsPass = true;
        $('select[id^="product_option_id"]:visible').each(function() {
            if ($(this).attr('required')) {
                var sOptionValue = $('option:selected', this).val();

                if ($.inArray(sOptionValue, ['*', '**']) !== -1) {
                    alert(__('필수 옵션을 선택해주세요.'));
                    $(this).focus();
                    bIsPass = false;
                    return false;
                }
            }
        });
        return bIsPass;
    },


    /**
     * 추가옵션 체크
     * @return bool true: 추가옵션이 다 입력되었으면 / false: 아니면
     */
    checkAddOption : function()
    {
        var bIsPass = true;
        $('[id^="add_option"]:visible').each(function() {
            var oThis = $(this);

            // 선택항목인 경우
            if (oThis.attr('require') === 'F') {
                return;
            }

            if (oThis.val().replace(/^[\s]+[\s]+$/g, '').length == 0) {
                alert(__('추가 옵션을 입력해주세요.'));
                oThis.focus();
                bIsPass = false;
                return false;
            }
        });
        return bIsPass;
    },

    /**
     * 뉴상품의 경우 아이템 코드를 받아오는 로직
     * @param int iIdx 품목정보배열 index
     * @param int iProductNo 상품번호
     */
    getItemInfo : function(iIdx, iProductNo)
    {
        // 상품정보
        var oPrdData = aBasketProductData[iIdx];
        var oItemInfo = {
            "product_no": iProductNo,
            "item_code" : "",
            "opt_id"    : "",
            "opt_str"   : ""
        };

        // 오직 추가옵션만 있는지
        var isOnlyOptionAdd = false;
        if (oPrdData.has_option == "F" && oPrdData.has_option_add == "T") {
            isOnlyOptionAdd = true;
        }

        // 오직 추가옵션만 있는경우 임의 가공
        if (isOnlyOptionAdd) {
            oItemInfo.item_code = oPrdData.product_code + "000A";
            oItemInfo.opt_id = "000A";
            return oItemInfo;
        }

        // 옵션 있는경우 품목코드 추출
        if (eval("item_listing_type" + iProductNo) == "C" || eval("option_type" + iProductNo) == "F") {
            oItemInfo.item_code = $('.ProductOption' + iIdx).val();
            oItemInfo.opt_str = $('.ProductOption' + iIdx + ' :selected').text();
            oItemInfo.opt_str = oItemInfo.opt_str.replace(/\-/g, "/");
        } else {
            var aItemValue = new Array();
            $(".ProductOption" + iIdx + ":visible").each(function() {
                aItemValue.push($(this).val());
            });
            var aItemMapper = $.parseJSON(eval("option_value_mapper" + iProductNo));

            oItemInfo.item_code = aItemMapper[aItemValue.join("#$%")];
            oItemInfo.opt_str = aItemValue.join("/");
        }
        oItemInfo.opt_id = oItemInfo.item_code.substr(8);

        return oItemInfo;
    },


    /**
     * 관심상품등록
     * @param int iIdx 품목정보배열 index
     */
    moveWish: function(iIdx)
    {
        var aPrdData = aBasketProductData[iIdx];

        if (aPrdData.is_set_product == "T" && parseInt(aPrdData.set_product_no) == 0) {
            var aParam = [];
            aParam.push("command=add");
            aParam.push("from=basket");
            aParam.push("is_set_product=T");
            aParam.push("basket_prd_no=" + aPrdData.basket_prd_no);
            aParam.push("main_cate_no=" + aPrdData.main_cate_no);
            aParam.push("product_no=" + aPrdData.product_no);
            aParam.push("product_code=" + aPrdData.product_code);
            aParam.push("quantity=" + aPrdData.quantity);
            aParam.push("delvType=" + aPrdData.delvtype);
            aParam.push("product_min=" + aPrdData.product_min);
            aParam.push("selected_item[]=" + aPrdData.wish_selected_item);
            aParam.push("save_data=" + aPrdData.wish_save_data);

            var sParam = aParam.join('&');
            $.post("/exec/front/Product/Wishlist/", sParam, function(data) {
                if (window.bIsAddWishListCall === false) {
                    add_wishlist_result(data, aPrdData);
                }

                if (data.result == 'NOT_LOGIN') {
                    btn_action_move_url('/member/login.html');
                } else if (window.bIsAddWishListCall === false) {
                    location.reload();
                }
            }, 'json');
        } else if (parseInt(aPrdData.set_product_no) > 0 ) {
            //분리형세트
            var aSetData = [];
            var sSetKey = '';
            var iSetPrdNo = aBasketProductData[iIdx].set_product_no;
            for (i = 0; i < aBasketProductData.length; i++) {
                if (iSetPrdNo == aBasketProductData[i].set_product_no) {
                    sSetKey =  aBasketProductData[i].product_no + ':' +  aBasketProductData[i].opt_id + ':' + 'T' + ':' + aBasketProductData[i].basket_prd_no;
                    //sSetKey =  aBasketProductData[i].product_no + ':' +  aBasketProductData[i].opt_id+ ':' + 'T' + ':' + aBasketProductData[i].basket_prd_no + ':' + aBasketProductData[i].set_product_no + ':' + sBasketDelvType;
                    aSetData.push(sSetKey);
                }
            }
            Basket._callBasketAjax({
                command         : 'select_storage',
                checked_product : aSetData.join(','),
                delvtype        : sBasketDelvType,
                option_type : aPrdData.option_type // 단독 구성 옵션 상품/품목이 하나라도 있는 경우 'F' 를 보냄
            });
        } else {
            var aData = [];
            var sKey = aPrdData.product_no + ':' + aPrdData.opt_id + ':' + 'F' + ':' + aPrdData.basket_prd_no;
            //var sKey = aPrdData.product_no + ':' + aPrdData.opt_id + ':' + 'F' + ':' + aPrdData.basket_prd_no + ':' + parseInt(aPrdData.set_product_no) + ':' + sBasketDelvType;
            aData.push(sKey);
            Basket._callBasketAjax({
                command         : 'select_storage',
                checked_product : aData.join(','),
                delvtype        : sBasketDelvType,
                option_type : aPrdData.option_type // 단독 구성 옵션 상품/품목이 하나라도 있는 경우 'F' 를 보냄
            });
        }
    }
};
var SET_OPT_CLASS_PREFIX = "SetProductOption";
var SET_ADDOPT_CLASS_PREFIX = "SetProductAddOption";

/**
 * 뉴상품 세트상품 상품옵션변경
 */
var NewBasketSetOption = {
    /**
     * '옵션변경'레이어에서 '적용하기' 버튼 클릭
     * @param int iIdx 품목정보배열 index
     * @param int iChildIdx 세트 자식품목정보배열 index
     * @param string sMode 액션모드(modify: 변경, add: 추가)
     */
    modify : function(iIdx, iChildIdx)
    {
        // 사용자지정옵션인경우 옵션변경불가(기존사양)
        // if (aBasketProductData[iIdx].option_add == "T") {
        //     alert(__("사용자 지정 옵션 상품은 옵션변경을 하실 수 없습니다."));
        //     $('#quantity_id_'+i).val(aBasketProductData[iIdx].quantity);
        //     return false;
        // }

        //필수옵션 체크
        if (this.checkOptionRequired(iIdx, iChildIdx) == false) return;

        //추가옵션 체크
        if (this.checkAddOption(iIdx, iChildIdx) == false) return;

        // 해당옵션
        var oBasketPrdData = aBasketProductData[iIdx];
        var iBasketPrdNo = aBasketProductData[iIdx].basket_prd_no;
        var oPrdData = aBasketProductSetData[iBasketPrdNo][iChildIdx];

        // 오직 추가옵션만 있는지
        var isOnlyOptionAdd = false;
        if (oPrdData.has_option == "F" && oPrdData.has_option_add == "T") {
            isOnlyOptionAdd = true;
        }


        // 파리미터 담을 객체
        var aParam = {};

        // 상품번호
        var iProductNo = oPrdData.product_no;

        // 품목코드
        var sItemCode = oPrdData.item_code;

        // 수량
        var iQuantity = oPrdData.qty;

        // 품목코드 추출
        var oItemInfo = this.getItemInfo(iIdx, iChildIdx, oPrdData, isOnlyOptionAdd);

        // 선택옵션인경우만 체크
        if (isOnlyOptionAdd == false) {
            // 재고정보 추출
            var sKey = "option_stock_data" + iProductNo;
            var oItemStock = $.parseJSON(window[sKey]);

            // 재고체크
            var oItem = oItemStock[oItemInfo.item_code];
            if (oItem.use_stock === true) {
                // ECHOSTING-318729 대응,
                // 상품 쪽 설정에 따라, 재고가 있을때 stock_number 가 없는 데이터가 들어오게 되므로
                // 에러방지를 위한 undefined 체크 추가
                if (oItem.is_selling == "F" || (oItem.stock_number != undefined && oItem.stock_number < 1)) {
                    alert(__('재고 수량이 부족합니다.'));
                    return false;
                }
            }
        }

        // 동일품목 추가여부 확인
        if (isOnlyOptionAdd == false && (sItemCode == oItemInfo.item_code)) {
            alert(sprintf(__('동일상품이 장바구니에 %s개 있습니다.'), iQuantity));
            return false;
        }

        // 액션
        aParam["command"] = "update";

        // 품목정보
        aParam["set_product_no"]   = oBasketPrdData.product_no;
        aParam["product_no"]       = oItemInfo.product_no;
        aParam["item_code"]        = oItemInfo.item_code;
        aParam["opt_id"]           = oItemInfo.opt_id;
        aParam["quantity"]         = iQuantity;
        aParam["item_code_before"] = oPrdData.item_code;
        aParam["opt_id_before"]    = oPrdData.opt_id;

        // 추가입력 옵션 가져오기
        var aAddOptionName = [];
        var aAddOption     = [];
        var sClassName     = SET_ADDOPT_CLASS_PREFIX + iIdx;
        $("input[class^='" + sClassName + "']:visible").each(function() {
            aAddOptionName.push($(this).attr("optionname"));
            aAddOption.push($(this).val());
        });

        // 추가입력옵션
        aParam["option_add"]      = aAddOption;
        aParam["add_option_name"] = aAddOptionName.join(";");
        aParam["option_change"]   = "T";
        aParam["is_new_product"]  = "T";
        aParam["is_set_product"]  = "T";
        aParam["basket_prd_no"]   = iBasketPrdNo;
        aParam["delvtype"]        = (typeof(sBasketDelvType) == "undefined") ? "A" : sBasketDelvType;

        // 유효성 체크(기존)
        aParam["selected_item[]"] = iQuantity + "||" + oItemInfo.item_code;
        aParam["num_of_prod"]     = iQuantity;
        aParam["is_only_addoption"] = isOnlyOptionAdd ? "T" : "F";

        Basket._callBasketAjax(aParam);
    },

    /**
     * 필수옵션 체크 여부
     * @param int iIdx 품목정보배열 index
     * @param int iChildIdx 세트 자식품목정보배열 index
     * @return bool true: 체크 / false: 체크안함
     */
    checkOptionRequired : function(iIdx, iChildIdx)
    {
        var bIsChcecked = true;
        var sClassName = SET_OPT_CLASS_PREFIX + iIdx + '-' + iChildIdx;
        $("select[class*='" + sClassName + "']:visible").each(function() {
            if ($(this).attr('required')) {
                if ($('option:selected', this).val().indexOf('*') > -1) {
                    alert(__('필수 옵션을 선택해주세요.'));
                    $(this).focus();
                    bIsChcecked = false;
                    return false;
                }
            }
        });

        return bIsChcecked;
    },


    /**
     * 추가옵션 체크
     * @return bool true: 추가옵션이 다 입력되었으면 / false: 아니면
     * @param int iIdx 품목정보배열 index
     * @param int iChildIdx 세트 자식품목정보배열 index
     * @return boolean
     */
    checkAddOption: function(iIdx, iChildIdx)
    {
        var bIsChcecked = true;
        var sClassName = SET_ADDOPT_CLASS_PREFIX + iIdx;
        $("[class^='" + sClassName + "']:visible").each(function() {
            if ($(this).attr("require") == "T") {
                if ($(this).val().replace(/^[\s]+[\s]+$/g, '').length == 0) {
                    alert(__('추가 옵션을 입력해주세요.'));
                    $(this).focus();
                    bIsChcecked = false;
                    return false;
                }
            }
        });

        return bIsChcecked;
    },

    /**
     * 뉴상품의 경우 아이템 코드를 받아오는 로직
     * @param int iIdx 품목정보배열 index
     * @param int iChildIdx 세트 자식품목정보배열 index
     * @param int iProductNo 상품번호
     */
    getItemInfo : function(iIdx, iChildIdx, oPrdData, isOnlyOptionAdd)
    {
        var sClassName = SET_OPT_CLASS_PREFIX + iIdx + '-' + iChildIdx;
        var oItemInfo = {
            "product_no": oPrdData.product_no,
            "item_code": "",
            "opt_id": "",
            "opt_str": ""
        };

        // 오직 추가옵션만 있는경우 임의 가공
        if (isOnlyOptionAdd) {
            oItemInfo.item_code = oPrdData.product_code + "000A";
            oItemInfo.opt_id = "000A";
            return oItemInfo;
        }


        if (eval("item_listing_type" + oPrdData.product_no) == "C") {
            oItemInfo.item_code = $("." + sClassName + ":visible").val();
            oItemInfo.opt_str = $("." + sClassName + " :selected").text();
            oItemInfo.opt_str = oItemInfo.opt_str.replace(/\-/g, "/");
        } else {
            var aItemValue = [];
            $("select[class*='" + sClassName + "']:visible").each(function() {
                aItemValue.push($(this).val());
            });
            var aItemMapper = $.parseJSON(eval("option_value_mapper" + oPrdData.product_no));
            oItemInfo.item_code = aItemMapper[aItemValue.join("#$%")];
            oItemInfo.opt_str = aItemValue.join("/");
        }
        oItemInfo.opt_id = oItemInfo.item_code.substr(8);

        return oItemInfo;
    },

    /**
     * 관심상품등록
     * @param int iIdx 품목정보배열 index
     */
    moveWish: function(iIdx)
    {
        var aData = [];
        var iProdNo = aBasketProductData[iIdx].product_no;
        var sOptId  = aBasketProductData[iIdx].opt_id;
        var sProductType = aBasketProductData[iIdx].product_type;
        var sIsSetProduct = aBasketProductData[iIdx].is_set_product;
        var iBasketPrdNo = aBasketProductData[iIdx].basket_prd_no;
        var sKey = iProdNo + ':' + sOptId + ':' + sIsSetProduct + ':' + iBasketPrdNo;
        aData.push(sKey);
        Basket._callBasketAjax({
            command         : 'select_storage',
            checked_product : aData.join(','),
            delvtype        : sBasketDelvType
        });
    }
};
/**
 * 장바구니 앱할인 적용
 */
var BasketAppDiscount = {
    /**
     * 앱 할인 적용하여 재계산
     * @param oAppData
     */
    doAppDiscountCalculate : function(oAppData) {
        var oParam = {};
        oParam.page = 'basket';
        oParam.delv_type = sBasketDelvType;
        oParam.app = oAppData;
        $.ajax({
            url : '/exec/front/order/calculator',
            type : 'POST',
            data : oParam,
            success : function(sRes) {
                oRes = JSON.parse(sRes);
                if (oRes.code == 'success') {
                    BasketAppDiscount.setAppCalcData(oRes.app_list);
                    BasketAppDiscount.setAppDiscount(oRes);
                }
            },
            error : function(e) {}
        });
    },

    /**
     * 할인금액에 적용된 앱 데이터 셋팅
     * @param oCalcAppKey
     */
    setAppCalcData : function(oCalcAppKey) {
        for (var $i= 0; $i < oCalcAppKey.length; $i++) {
            oAppDiscountData[oCalcAppKey[$i]] = oAppRequestData[oCalcAppKey[$i]];
        }
    },

    /**
     * 앱 할인금액 적용
     * @param aAppData
     */
    setAppDiscount : function(oRes) {

        var oData = oRes.data;

        // 장바구니 상품 리스트 금액 셋팅
        this.setBasketProductListPrice(oData.product_list);

        // 상품,배송타입 별 토탈금액 셋팅
        this.setBasketProductTotalPrice(oData);

        // 하단 총 금액 셋팅
        this.setBasketTotalPrice(oData);

        // 총 할인금액 내역보기 레이어 셋팅
        this.setBasketBenefitLayer(oData.app_display_info);

        // 견적서 데이터 셋팅
        this.setEstimateData(oData.total_product_discount_price_raw, oData.total_membergroupsale_price);
    },

    /**
     * 장바구니 상품 리스트 금액 셋팅
     * @param oProduct
     */
    setBasketProductListPrice : function(oProduct)
    {
        // 장바구니 상품 리스트
        for (var data in oProduct) {
            var iKey = oProduct[data]['product_key'];

            if (oProduct[data]['product_add_sale'] > 0) {
                if ($('#product_price_div' + iKey).hasClass('discount') == false)
                    $('#product_price_div' + iKey).addClass('discount');
                if ($('#product_sale_price_div' + iKey).hasClass('displaynone') == true)
                    $('#product_sale_price_div' + iKey).removeClass('displaynone');
            }

            // 판매가 > 할인가 (결제화폐)
            $('#product_sale_price_front'+iKey).html(oProduct[data]['product_sale_price_front']);
            // 판매가 > 할인가 (참조화폐)
            if ($('#product_sale_price_back'+iKey).length > 0)
                $('#product_sale_price_back'+iKey).html(oProduct[data]['product_sale_price_back']);

            // 적립금
            $('#product_mileage'+iKey).html(oProduct[data]['product_mileage']);

            // 합계 (결제화폐)
            $('#sum_price_front'+iKey).html(oProduct[data]['sum_price_front']);
        }
    },

    /**
     * 상품,배송타입 별 토탈금액 셋팅
     * @param oData
     */
    setBasketProductTotalPrice : function(oData)
    {
        // 상품 : normal_type, 배송 : normal
        if ($('#normal_normal_ship_fee').length > 0)
            $('#normal_normal_ship_fee').html(oData.normal_normal_ship_fee);
        if ($('#normal_normal_benefit_price').length > 0)
            $('#normal_normal_benefit_price').html(oData.normal_normal_benefit_price);
        if ($('#normal_normal_ship_fee_sum').length > 0)
            $('#normal_normal_ship_fee_sum').html(oData.normal_normal_ship_fee_sum);
        if ($('#normal_normal_benefit_price_area').length > 0
            && oData.normal_normal_benefit_price_raw > 0 && $('#normal_normal_benefit_price_area').hasClass('displaynone') == true)
            $('#normal_normal_benefit_price_area').removeClass('displaynone');

        // 상품 : normal_type, 배송 : individual
        if ($('#normal_individual_ship_fee').length > 0)
            $('#normal_individual_ship_fee').html(oData.normal_individual_ship_fee);
        if ($('#normal_individual_benefit_price').length > 0)
            $('#normal_individual_benefit_price').html(oData.normal_individual_benefit_price);
        if ($('#normal_individual_ship_fee_sum').length > 0)
            $('#normal_individual_ship_fee_sum').html(oData.normal_individual_ship_fee_sum);
        if ($('#normal_individual_benefit_price_area').length > 0
            && oData.normal_individual_benefit_price_raw > 0 && $('#normal_individual_benefit_price_area').hasClass('displaynone') == true)
            $('#normal_individual_benefit_price_area').removeClass('displaynone');

        // 상품 : normal_type, 배송 : supplier
        if ($('#normal_supplier_ship_fee').length > 0)
            $('#normal_supplier_ship_fee').html(oData.normal_supplier_ship_fee);
        if ($('#normal_supplier_benefit_price').length > 0)
            $('#normal_supplier_benefit_price').html(oData.normal_supplier_benefit_price);
        if ($('#normal_supplier_ship_fee_sum').length > 0)
            $('#normal_supplier_ship_fee_sum').html(oData.normal_supplier_ship_fee_sum);
        if ($('#normal_supplier_benefit_price_area').length > 0
            && oData.normal_supplier_benefit_price_raw > 0 && $('#normal_supplier_benefit_price_area').hasClass('displaynone') == true)
            $('#normal_supplier_benefit_price_area').removeClass('displaynone');

        // 상품 : normal_type, 배송 : oversea
        if ($('#normal_oversea_ship_fee').length > 0)
            $('#normal_oversea_ship_fee').html(oData.normal_oversea_ship_fee);
        if ($('#normal_oversea_benefit_price').length > 0)
            $('#normal_oversea_benefit_price').html(oData.normal_oversea_benefit_price);
        if ($('#normal_oversea_ship_fee_sum').length > 0)
            $('#normal_oversea_ship_fee_sum').html(oData.normal_oversea_ship_fee_sum);
        if ($('#normal_oversea_benefit_price_area').length > 0
            && oData.normal_oversea_benefit_price_raw > 0 && $('#normal_oversea_benefit_price_area').hasClass('displaynone') == true)
            $('#normal_oversea_benefit_price_area').removeClass('displaynone');


        // 상품 : installment_type, 배송 : normal
        if ($('#installment_normal_ship_fee').length > 0)
            $('#installment_normal_ship_fee').html(oData.installment_normal_ship_fee);
        if ($('#installment_normal_benefit_price').length > 0)
            $('#installment_normal_benefit_price').html(oData.installment_normal_benefit_price);
        if ($('#installment_normal_ship_fee_sum').length > 0)
            $('#installment_normal_ship_fee_sum').html(oData.installment_normal_ship_fee_sum);
        if ($('#installment_normal_benefit_price_area').length > 0
            && oData.installment_normal_benefit_price_raw && $('#installment_normal_benefit_price_area').hasClass('displaynone') == true)
            $('#installment_normal_benefit_price_area').removeClass('displaynone');

        // 상품 : installment_type, 배송 : individual
        if ($('#installment_individual_ship_fee').length > 0)
            $('#installment_individual_ship_fee').html(oData.installment_individual_ship_fee);
        if ($('#installment_individual_benefit_price').length > 0)
            $('#installment_individual_benefit_price').html(oData.installment_individual_benefit_price);
        if ($('#installment_individual_ship_fee_sum').length > 0)
            $('#installment_individual_ship_fee_sum').html(oData.installment_individual_ship_fee_sum);
        if ($('#installment_individual_benefit_price_area').length > 0
            && oData.installment_individual_benefit_price_raw && $('#installment_individual_benefit_price_area').hasClass('displaynone') == true)
            $('#installment_individual_benefit_price_area').removeClass('displaynone');

        // 상품 : installment_type, 배송 : oversea
        if ($('#installment_oversea_ship_fee').length > 0)
            $('#installment_oversea_ship_fee').html(oData.installment_oversea_ship_fee);
        if ($('#installment_oversea_benefit_price').length > 0)
            $('#installment_oversea_benefit_price').html(oData.installment_oversea_benefit_price);
        if ($('#installment_oversea_ship_fee_sum').length > 0)
            $('#installment_oversea_ship_fee_sum').html(oData.installment_oversea_ship_fee_sum);
        if ($('#installment_oversea_benefit_price_area').length > 0
            && oData.installment_oversea_benefit_price_raw && $('#installment_oversea_benefit_price_area').hasClass('displaynone') == true)
            $('#installment_oversea_benefit_price_area').removeClass('displaynone');
    },

    /**
     * 하단 총 금액 셋팅
     * @param oData
     */
    setBasketTotalPrice : function(oData)
    {
        if (sBasketDelvType == 'B') {
            // 총 할인금액
            if ($('#oversea_total_product_discount_price_front').length > 0) {
                var mTotalOverseaBenefitPrice = '<strong>' + SHOP_PRICE_FORMAT.toShopPrice(oData.total_benefit_price_raw) + '</strong>';
                $('#mTotalOverseaBenefitPrice').html(mTotalOverseaBenefitPrice);
                $('#mOverseaBenefitMembergroupSale').html(SHOP_PRICE_FORMAT.toShopPrice(oData.total_membergroupsale_price_raw));
                $('#oversea_total_product_discount_price_front').html(oData.total_product_discount_price_front);
                // 참조화폐
                if ($('#oversea_total_product_discount_price_back').length > 0)
                    $('#oversea_total_product_discount_price_back').html(oData.total_product_discount_price_back);
                if ($('#oversea_total_benefit_price_title_area').hasClass('displaynone') == true)
                    $('#oversea_total_benefit_price_title_area').removeClass('displaynone');
                if ($('#oversea_total_benefit_price_area').hasClass('displaynone') == true)
                    $('#oversea_total_benefit_price_area').removeClass('displaynone');
            }
            // 총 합계
            if ($('#oversea_total_order_price_front').length > 0) $('#oversea_total_order_price_front').html(oData.total_order_price_front);
            // 총 합계 - 참조화폐
            if ($('#oversea_total_order_price_back').length > 0) $('#oversea_total_order_price_back').html(oData.total_order_price_back);
        } else {
            // 총 배송비
            if ($('#total_delv_price_front').length > 0) $('#total_delv_price_front').html(oData.total_delv_price_front);
            // 총 할인금액
            if ($('#total_product_discount_price_front').length > 0) {
                if ($('#total_benefit_price_title_area').hasClass('displaynone') == true)
                    $('#total_benefit_price_title_area').removeClass('displaynone');
                var mTotalBenefitPrice = '<strong>' + SHOP_PRICE_FORMAT.toShopPrice(oData.total_benefit_price_raw) + '</strong>';
                $('#mTotalBenefitPrice').html(mTotalBenefitPrice);
                $('#mBenefitMembergroupSale').html(SHOP_PRICE_FORMAT.toShopPrice(oData.total_membergroupsale_price_raw));
                $('#total_product_discount_price_front').html(oData.total_product_discount_price_front);
                // 참조화폐
                if ($('#total_product_discount_price_back').length > 0)
                    $('#total_product_discount_price_back').html(oData.total_product_discount_price_back);
                if ($('#total_benefit_price_title_area').hasClass('displaynone') == true)
                    $('#total_benefit_price_title_area').removeClass('displaynone');
                if ($('#total_benefit_price_area').hasClass('displaynone') == true)
                    $('#total_benefit_price_area').removeClass('displaynone');
            }
            // 결제예정금액
            if ($('#total_order_price_front').length > 0) $('#total_order_price_front').html(oData.total_order_price_front);
            // 결제예정금액 - 참조화폐
            if ($('#total_order_price_back').length > 0) $('#total_order_price_back').html(oData.total_order_price_back);
        }
    },

    /**
     * 총 할인금액 내역보기 레이어 셋팅
     * @param oAppDisplayInfo
     */
    setBasketBenefitLayer : function(oAppDisplayInfo)
    {
        this.setInitAppDisplayInfo();

        var sHtml = '';
        if (mobileWeb === true) {
            for (var i = 0; i < oAppDisplayInfo.length; i++) {
                sHtml += '<tr class="appDiscountRow">';
                sHtml += '<th scope="row">' + oAppDisplayInfo[i].name + '</th>';
                sHtml += '<td>' + SHOP_PRICE_FORMAT.toShopPrice(oAppDisplayInfo[i].price) + '</td>';
                sHtml += '</tr>';
            }
        } else {
            for (var i = 0; i < oAppDisplayInfo.length; i++) {
                sHtml += '<li class="appDiscountRow">';
                sHtml += '<strong class="term">' + oAppDisplayInfo[i].name + '</strong>';
                sHtml += '<span class="desc">' + SHOP_PRICE_FORMAT.toShopPrice(oAppDisplayInfo[i].price) + '</span>';
                sHtml += '</li>';
            }
        }

        if (sBasketDelvType == 'B') {
            $('#oversea_total_benefit_list').append(sHtml);
        } else {
            $('#total_benefit_list').append(sHtml);
        }
    },

    /**
     * 총 할인금액 내역보기 레이어에 앱할인 내역 삭제
     * (앱 여러개 일 때 중복으로 노출되지 않도록)
     */
    setInitAppDisplayInfo : function()
    {
        if ($('.appDiscountRow').length < 1) return;

        $('.appDiscountRow').each(function () {
            $(this).remove();
        });
    },

    /**
     * 견적서 데이터에 앱 할인금액 반영
     * @param iTotalDiscountPrice 총 할인금액
     * @param iTotalMemberGroupSalePrice 회원등급 할인금액
     */
    setEstimateData : function(iTotalDiscountPrice, iTotalMemberGroupSalePrice) {
        // 견적서 데이터 재셋팅
        var oBasketBenefitInfo = JSON.parse(EC_BASKET_BENEFIT_INFO);
        oBasketBenefitInfo.total_benefit_price_raw = iTotalDiscountPrice;
        oBasketBenefitInfo.aBenefit.total_membergroupsale_price = iTotalMemberGroupSalePrice;

        EC_BASKET_BENEFIT_INFO = JSON.stringify(oBasketBenefitInfo);
    }
};

/**
 * 추가입력옵션 길이 체크
 * @param oObj
 * @param limit
 */
function addOptionWord(sId, sVal, iLimit)
{
    // 영문,한글 상관없이 iLimit 글자만큼 제한하도록 수정 (ECHOSTING-78226)
    //var iStrLen = stringByteSize(sVal);
    var iStrLen = sVal.length;
    if (iStrLen > iLimit) {
        alert(sprintf(__('메시지는 %s자 이하로 입력해주세요.'), iLimit));
        $('#'+sId).val(sVal.substr(0, sVal.length-1));
        return;
    }
    $('#'+sId).parent().parent().find('.length').html(iStrLen);
}

/**
 * 문자열을 UTF-8로 변환했을 경우 차지하게 되는 byte 수를 리턴한다.
 */
function stringByteSize(str)
{
    if (str == null || str.length == 0) return 0;
    var size = 0;
    for (var i = 0; i < str.length; i++) {
      size += charByteSize(str.charAt(i));
    }
    return size;
}

/**
 * 글자수 체크
 * @param ch
 * @returns {Number}
 */
function charByteSize(ch)
{
    if ( ch == null || ch.length == 0 ) return 0;
    var charCode = ch.charCodeAt(0);
    if ( escape(charCode).length > 4 ) {
        return 2;
    } else {
        return 1;
    }
}
/**
 * 기존에 product_submit함수에 있던 내용들을 메소드 단위로 리펙토링한 객체
 */
var PRODUCTSUBMIT = {
    oConfig : {
        'sFormSelector' : '#frm_image_zoom'
    },
    /**
     * 1 : 바로 구매, 2 : 장바구니 넣기
     */
    sType : null,
    sAction : null,
    oObject : null,
    oValidate : null,
    oForm : null,
    oDebug : null,
    bIsDebugConsoleOut : false,

    /**
     * 초기화
     */
    initialize : function(sType, sAction, oObject)
    {
        this.oDebug = this.DEBUG.initialize(this);
        this.oDebug.setInfo('PRODUCTSUBMIT.initialize 시작');
        this.oDebug.setInfo('sType : ', sType);
        this.oDebug.setInfo('sAction : ', sAction);
        this.oDebug.setInfo('oObject : ', oObject);

        if (typeof(sType) === 'undefined' || ((sType !== 'sms_restock' && sType !== 'email_restock') && typeof(sAction) === 'undefined')) {
            this.oDebug.setMessage('PRODUCTSUBMIT.initialize fail');
            return false;
        }

        this.sType = sType;
        this.sAction = sAction;
        this.oObject = oObject;
        this.oValidate = this.VALIDATION.initialize(this);
        this.UTIL.initialize(this);
        this.oForm = $(this.oConfig.sFormSelector);
        this.oForm.find(':hidden').remove();
    },
    /**
     * 데이터 검증
     */
    isValidRequest : function()
    {
        try {
            this.oDebug.setInfo('PRODUCTSUBMIT.isValidRequest 시작');

            this.oDebug.setInfo('PRODUCTSUBMIT.oValidate.isValidFunding');
            if (this.oValidate.isValidFunding() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.oValidate.isValidFunding fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.oValidate.isRequireLogin');
            if (this.oValidate.isRequireLogin() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.oValidate.isRequireLogin fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.oValidate.isPriceContent');
            if (this.oValidate.isPriceContent() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.oValidate.isPriceContent fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.oValidate.isOptionDisplay');
            if (this.oValidate.isOptionDisplay() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.oValidate.isOptionDisplay fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.oValidate.isItemInStock');
            if (this.oValidate.isItemInStock() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.oValidate.isItemInStock fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.oValidate.isValidRegularDelivery');
            if (this.oValidate.isValidRegularDelivery() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.oValidate.isValidRegularDelivery fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.oValidate.isValidOption');
            if (this.oValidate.isValidOption() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.oValidate.isValidOption fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.oValidate.isValidAddproduct');
            if (this.oValidate.isValidAddproduct() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.oValidate.isValidAddproduct fail');
            }

        } catch(mError) {
            return this.DEBUG.messageOut(mError);
        }
        return true;
    },
    /**
     * 전송폼 생성
     */
    setBasketForm : function()
    {
        try {
            this.oDebug.setInfo('PRODUCTSUBMIT.setBasketForm 시작');
            // 예약 주문 체크
            STOCKTAKINGCHECKRESERVE.checkReserve();

            this.oForm.attr('method', 'POST');
            this.oForm.attr('action', '/' + this.sAction);

            this.oDebug.setInfo('PRODUCTSUBMIT.setCommonInput');
            if (this.setCommonInput() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.setCommonInput fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.setOptionId');
            if (this.setOptionId() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.setOptionId fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.setAddOption');
            if (this.setAddOption() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.setAddOption fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.setQuantityOveride');
            if (this.setQuantityOveride() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.setQuantityOveride fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.setSelectedItem');
            if (this.setSelectedItemHasOptionT() === false || this.setSelectedItemHasOptionF() === false) {
//                if (this.setSelectedItemHasOptionT() === false || this.setSelectedItemHasOptionF() === false || this.setSingleSelectedItem() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.setSelectedItem fail');
            }

            this.oDebug.setInfo('PRODUCTSUBMIT.setFundingData');
            if (this.setFundingData() === false) {
                this.oDebug.setMessage('PRODUCTSUBMIT.setFundingData fail');
            }


        } catch(mError) {
            return this.DEBUG.messageOut(mError);
        }

        return true;
    },
    setBasketAjax : function()
    {
        this.oDebug.setInfo('PRODUCTSUBMIT.setBasketAjax 시작');
        if (typeof(ACEWrap) !== 'undefined') {
            // 에이스카운터
            ACEWrap.addBasket();
        }

        // 파일첨부 옵션의 파일업로드가 없을 경우 바로 장바구니에 넣기
        if (FileOptionManager.existsFileUpload() === false) {
            action_basket(this.sType, 'detail', this.sAction, this.oForm.serialize(), this.UTIL.getData('sBasketType'));
        } else {
            // 파일첨부 옵션의 파일업로드가 있으면
            FileOptionManager.upload(function(mResult){
                // 파일업로드 실패
                if (mResult === false) {
                    PRODUCTSUBMIT.DEBUG.setMessage('PRODUCTSUBMIT.setBasketAjax fail - 파일업로드 실패');
                    return false;
                }

                // 파일업로드 성공
                for (var sId in mResult) {
                    PRODUCTSUBMIT.UTIL.appendHidden(sId, FileOptionManager.encode(mResult[sId]));
                }

                action_basket(PRODUCTSUBMIT.sType, 'detail', PRODUCTSUBMIT.sAction, PRODUCTSUBMIT.oForm.serialize(), PRODUCTSUBMIT.UTIL.getData('sBasketType'));
            });
        }
    },
    setSelectedItem : function(sItemCode, iQuantity, sParameterName, sAdditionalData)
    {
        iQuantity = parseInt(iQuantity, 10);
        if (isNaN(iQuantity) === true || iQuantity < 1) {
            this.oDebug.setMessage('PRODUCTSUBMIT.setSelectedItem fail - iQuantity Fault');
            return false;
        }

        if (typeof(sItemCode) !== 'string') {
            this.oDebug.setMessage('PRODUCTSUBMIT.setSelectedItem fail - sItemCode Fault');
            return false;
        }

        if (typeof(sParameterName) === 'undefined') {
            sParameterName = 'selected_item[]';
        }

        if (typeof(sAdditionalData) === 'undefined') {
            sAdditionalData = '';
        } else {
            sAdditionalData = '||' + sAdditionalData;
        }

        this.UTIL.prependHidden(sParameterName, iQuantity+'||'+sItemCode+sAdditionalData);
        return true;
    },
    getQuantity : function(oQuantityElement)
    {
        if (typeof(quantity_id) === 'undefined') {
            var quantity_id = '#quantity';
        }
        var $oQuantityElement = $(quantity_id);
        if (typeof(oQuantityElement) === 'object') {
            $oQuantityElement = oQuantityElement;
        }
        return parseInt($oQuantityElement.val(),10);
    },
    setSelectedItemHasOptionF : function()
    {
        if (has_option !== 'F') {
            return true;
        }

        if (item_code === undefined) {
            var sItemCode = product_code+'000A';
        } else {
            var sItemCode = item_code;
        }
        if (this.sType === 'funding') {
            EC_SHOP_FRONT_PRODUCT_FUNDING.setStandaloneProductItem(sItemCode);
        } else {
            if (NEWPRD_ADD_OPTION.checkSoldOutProductValid(this.oObject) === false && EC_SHOP_FRONT_PRODUCT_RESTOCK.isRestock(this.sType) === false) {
                this.setSelectedItem(sItemCode, this.getQuantity());
            }
        }

        return true;
    },
    setEtypeSelectedItem : function(bFormAppend)
    {
        var _sItemCode = sProductCode + '000A';
        var iQuantity = 0;
        var sSelectedItemByEtype = '';
        var _aItemValueNo = '';
        if (isNewProductSkin() === false) {
            iQuantity = this.getQuantity();

            // 수량이 없는 경우에는 최소 구매 수량으로 던진다!!
            if (iQuantity === undefined) {
                iQuantity = product_min;
            }
            var _aItemValueNo = Olnk.getSelectedItemForBasketOldSkin(sProductCode, $('[id^="product_option_id"]'), iQuantity);

            if (_aItemValueNo.bCheckNum === false ) {
                _aItemValueNo = Olnk.getProductAllSelected(sProductCode , $('[id^="product_option_id"]') , iQuantity);
                if (_aItemValueNo === false) {
                    this.oDebug.setMessage('etype error');
                }
            }
            sSelectedItemByEtype = 'selected_item_by_etype[]='+$.toJSON(_aItemValueNo) + '&';
            if (bFormAppend === true) {
                this.setSelectedItem(_sItemCode, iQuantity);
                this.UTIL.appendHidden('selected_item_by_etype[]', $.toJSON(_aItemValueNo));
            }
        } else {
            var bIsProductEmptyOption = this.UTIL.getData('bIsProductEmptyOption');
            // 메인상품 선택여부 확인 false : 선택함 || true : 선택안함
            if (bIsProductEmptyOption === false && NEWPRD_ADD_OPTION.checkSoldOutProductValid(this.oObject) === false) {
                $('.option_box_id').each(function (i) {
                    var sQuantityElement = $('#' + $(this).attr('id').replace('id', 'quantity'));
                    if (typeof(EC_SHOP_FRONT_PRODUCT_FUNDING) === 'object' && EC_SHOP_FRONT_PRODUCT_FUNDING.isFundingProduct() === true) {
                        sQuantityElement = $('#quantity_'+$(this).attr('composition-code'));
                    }
                    iQuantity = PRODUCTSUBMIT.getQuantity(sQuantityElement);
                    _aItemValueNo = Olnk.getSelectedItemForBasket(sProductCode, $(this), iQuantity);

                    if (_aItemValueNo.bCheckNum === false) { // 옵션박스는 있지만 값이 선택이 안된경우
                        _aItemValueNo = Olnk.getProductAllSelected(sProductCode, $(this), iQuantity);
                    }
                    if (bFormAppend === true) {
                        PRODUCTSUBMIT.setSelectedItem(_sItemCode, iQuantity);
                        PRODUCTSUBMIT.UTIL.prependHidden('selected_item_by_etype[]', $.toJSON(_aItemValueNo));
                    }
                    sSelectedItemByEtype += 'selected_item_by_etype[]='+$.toJSON(_aItemValueNo) + '&';
                    var oItem = $('[name="item_code[]"]').eq(i);
                    var sItemCode = oItem.val();

                    //품목별 추가옵션 셋팅
                    var sItemAddOption = NEWPRD_ADD_OPTION.getAddOptionValue(oItem.attr('data-item-add-option'));
                    NEWPRD_ADD_OPTION.setItemAddOption(_sItemCode + '_' + i, sItemAddOption, PRODUCTSUBMIT.oForm);
                });

                // 전부 선택인 경우 필요값 생성한다.
                if (_aItemValueNo === '') {
                    iQuantity = this.getQuantity();
                    _aItemValueNo = Olnk.getProductAllSelected(sProductCode, $('[id^="product_option_id"]'), iQuantity);
                    if (_aItemValueNo !== false) {
                        if (bFormAppend === true) {
                            this.setSelectedItem(_sItemCode, iQuantity);
                            this.UTIL.prependHidden('selected_item_by_etype[]', $.toJSON(_aItemValueNo));
                        }
                        sSelectedItemByEtype += 'selected_item_by_etype[]='+$.toJSON(_aItemValueNo) + '&';
                    }
                }
            }
        }
        this.UTIL.setData('sSelectedItemByEtype', sSelectedItemByEtype);
    },
    setSelectedItemHasOptionT : function()
    {
        if (has_option !== 'T') {
            return true;
        }

        if (Olnk.isLinkageType(sOptionType) === true) {
            this.setEtypeSelectedItem(true);
        } else {
            if (isNewProductSkin() === true && NEWPRD_ADD_OPTION.checkSoldOutProductValid(this.oObject) === false) {
                if (this.sType === 'funding') {
                    $('.xans-product-funding').each(function(i) {
                        if ($(this).find('.EC-funding-checkbox:checked').length !== 1) {
                            return;
                        }
                        var iQuantity = $(this).find('input.quantity').val();
                        var sItemCode = $(this).find('input.selected-funding-item').val();
                        PRODUCTSUBMIT.setSelectedItem(sItemCode, iQuantity);
                    });

                } else {
                    if ($('[name="quantity_opt[]"][id^="option_box"]').length > 0 && $('[name="quantity_opt[]"][id^="option_box"]').length == $('[name="item_code[]"]').length) {

                        $('[name="quantity_opt[]"][id^="option_box"]').each(function(i) {

                            var oItem = $('[name="item_code[]"]').eq(i);
                            var sItemCode = oItem.val();
                            PRODUCTSUBMIT.setSelectedItem(sItemCode, PRODUCTSUBMIT.getQuantity($(this)));

                            //품목별 추가옵션 셋팅
                            var sItemAddOption = NEWPRD_ADD_OPTION.getAddOptionValue(oItem.attr('data-item-add-option'));
                            NEWPRD_ADD_OPTION.setItemAddOption(sItemCode, sItemAddOption, PRODUCTSUBMIT.oForm);
                        });
                    }

                }

            } else {
                // 뉴 상품 + 구스디 스킨
                var aItemCode = ITEM.getItemCode();
                for (var i = 0; i < aItemCode.length; i++) {
                    var sItemCode = aItemCode[i];
                    this.setSelectedItem(sItemCode, this.getQuantity(i));
                }
            }
        }
        return true;
    },
    setQuantityOveride : function()
    {
        if (this.sType !== 1 && this.sType !== 'naver_checkout' && this.sType !== 'direct_buy') {
            return true;
        }

        // 전역변수임
        sIsPrdOverride = 'F';
        if (this.sType === 1) {
            var aItemParams = [];
            var aItemCode = ITEM.getItemCode();
            for (var i = 0, length = aItemCode.length; i < length; i++) {
                aItemParams.push("item_code[]=" + aItemCode[i]);
            }
            var sOptionParam = this.UTIL.getData('sOptionParam');
            sOptionParam = sOptionParam + '&delvtype=' + delvtype + '&' + aItemParams.join("&");
            if (Olnk.isLinkageType(sOptionType) === true) {
                this.setEtypeSelectedItem();
                var sSelectedItemByEtype = this.UTIL.getData('sSelectedItemByEtype', sSelectedItemByEtype);
            }
            selectbuy_action(sOptionParam, iProductNo, sSelectedItemByEtype);
        }

        if (this.sType === 'naver_checkout' || this.sType === 'direct_buy') {
            sIsPrdOverride = 'T';
        }
        this.UTIL.appendHidden('quantity_override_flag', sIsPrdOverride);
    },
    /**
     * 실제 옵션에 대한 검증이 아니라 구상품과의 호환을 위해 존재하는 파라미터들을 세팅해주는 메소드
     */
    setOptionId : function()
    {
        var count = 1;
        var sOptionParam = '';
        $('select[id^="' + product_option_id + '"]').each(function()
        {
            PRODUCTSUBMIT.UTIL.appendHidden('optionids[]', $(this).attr('name'));
            if ($(this).attr('required') == true || $(this).attr('required') == 'required') {
                PRODUCTSUBMIT.UTIL.appendHidden('needed[]', $(this).attr('name'));
            }
            var iSelectedIndex = $(this).get(0).selectedIndex;
            if ($(this).attr('required') && iSelectedIndex > 0) iSelectedIndex -= 1;

            if (iSelectedIndex > 0) {
                sOptionParam += '&option' + count + '=' + iSelectedIndex;
                var sValue = $(this).val();
                var aValue = sValue.split("|");
                PRODUCTSUBMIT.UTIL.appendHidden($(this).attr('name'), aValue[0]);
                ++count;
            }
        });
        this.UTIL.setData('sOptionParam', sOptionParam);
    },
    setAddOption : function()
    {
        if (add_option_name.length === 0) {
            return;
        }
        if (this.sType === 'funding') {
            // EC_SHOP_FRONT_PRODUCT_FUNDING.getFundingBasketData를 참조하세요.
            return;
        }

        var iAddOptionNo = 0;
        var aAddOptionName = [];
        for (var i = 0, iAddOptionNameLength = add_option_name.length; i < iAddOptionNameLength; i++) {
            if ($('#' + add_option_id + i).val() == '' || typeof($('#' + add_option_id + i).val()) == 'undefined') {
                continue;
            }
            this.UTIL.appendHidden('option_add[]', $('#' + add_option_id + i).val());
            aAddOptionName[iAddOptionNo++] = add_option_name[i];
        }
        this.UTIL.appendHidden('add_option_name', aAddOptionName.join(';'));
        NEWPRD_ADD_OPTION.setItemAddOptionName(this.oForm); // 품목별 추가옵션명인데 왜 상품단위로 도는지 확인이 필요함
    },
    setFundingData : function()
    {
        if (this.sType !== 'funding') {
            return true;
        }
        if (typeof EC_SHOP_FRONT_PRODUCT_FUNDING.getFundingBasketData !== 'function') {
            this.oDebug.setMessage('EC_SHOP_FRONT_PRODUCT_FUNDING.getFundingBasketData error');
            return false;
        }

        var oFundingBasketData = EC_SHOP_FRONT_PRODUCT_FUNDING.getFundingBasketData();
        if (typeof(oFundingBasketData) !== 'object') {
            this.oDebug.setMessage(oFundingBasketData.sMessage);
            return false;
        }

        delete oFundingBasketData.sMessage;
        delete oFundingBasketData.bIsResult;
        this.UTIL.appendHidden(oFundingBasketData);


    },
    setCommonInput : function()
    {
        var sBasketType = (typeof(basket_type) === 'undefined') ? 'A0000' : basket_type;
        this.UTIL.setData('sBasketType', sBasketType);

        var oCommon = {
            'product_no' : iProductNo,
            'product_name' : product_name,
            'main_cate_no' : iCategoryNo,
            'display_group' : iDisplayGroup,
            'option_type' : option_type,
            'product_min' : product_min,
            'command' : 'add',
            'has_option' : has_option,
            'product_price' : product_price,
            'multi_option_schema' : $('#multi_option').html(),
            'multi_option_data' : '',
            'delvType' : delvtype,
            'redirect' : this.sType,
            'product_max_type' : product_max_type,
            'product_max' : product_max,
            'basket_type' : sBasketType
        };
        this.UTIL.appendHidden(oCommon);

        if (typeof(CAPP_FRONT_OPTION_SELECT_BASKETACTION) !== 'undefined' && CAPP_FRONT_OPTION_SELECT_BASKETACTION === true) {
            this.UTIL.appendHidden('basket_page_flag', 'T');
        } else {
            this.UTIL.appendHidden('prd_detail_ship_type', $('#delivery_cost_prepaid').val());
        }
        if (this.sType !== 'funding') {
            // 수량 체크
            var iQuantity = 1;
            if (EC_SHOP_FRONT_PRODUCT_RESTOCK.isRestock(this.sType) === false) {
                iQuantity = checkQuantity();
                if (iQuantity == false) {
                    // 현재 관련상품 선택 했는지 여부 확인
                    // 관련 상품 자체가 없을때는 뒤에 저 로직을 탈 필요가 없음(basket_info 관련상품 체크박스)
                    if ($('input[name="basket_info[]"]').length <= 0 || NEWPRD_ADD_OPTION.checkRelationProduct(this.oObject, this.sType) === false) {
                        return false;
                    }
                }
            }

            // 폼 세팅
            if (iQuantity == undefined ||  isNaN(iQuantity) === true || iQuantity < 1) {
                iQuantity = 1;
            }
            this.UTIL.appendHidden('quantity', iQuantity);
        }

        // 바로구매 주문서 여부
        if (this.sType == 'direct_buy') {
            this.UTIL.appendHidden('is_direct_buy', 'T');
        } else {
            this.UTIL.appendHidden('is_direct_buy', 'F');
        }
    },
    VALIDATION : {
        initialize : function(oParent)
        {
            this.parent = oParent;
            return this;
        },
        isRequireLogin : function()
        {
            // ECHOSTING-58174
            if (sIsDisplayNonmemberPrice !== 'T') {
                return true;
            }
            switch (this.parent.sType) {
                case 1 :
                    alert(__('로그인후 상품을 구매해주세요.'));
                    break;
                case 2 :
                    alert(__('로그인후 장바구니 담기를 해주세요.'));
                     break;
                case 'direct_buy' :
                    alert(__('회원만 구매 가능합니다. 비회원인 경우 회원가입 후 이용하여 주세요.'));
                    break;
                default :
                    break;
            }
            btn_action_move_url('/member/login.html');
            return false;
        },
        isPriceContent : function()
        {
            if (typeof(product_price_content) === 'undefined') {
                return true;
            }

            var sProductcontent = product_price_content.replace(/\s/g, '').toString();
            if (sProductcontent === '1') {
                alert(sprintf(__('%s 상품은 구매할 수 있는 상품이 아닙니다.'), product_name));
                return false;
            }

            return true;
        },
        isOptionDisplay : function()
        {
            if (typeof(EC_SHOP_FRONT_NEW_OPTION_COMMON) !== 'undefined'
                && has_option === 'T'
                && Olnk.isLinkageType(sOptionType) === false
                && EC_SHOP_FRONT_NEW_OPTION_COMMON.isValidOptionDisplay(product_option_id) === false) {

                alert(sprintf(__('%s 상품은 구매할 수 있는 상품이 아닙니다.'), product_name));
                return false;
            }
            return true;
        },
        isItemInStock : function()
        {
            if (EC_SHOP_FRONT_PRODUCT_RESTOCK.isRestock(this.parent.sType) === false && ($('.option_box_id').length == 0 && $('.soldout_option_box_id').length > 0) === true) {
                alert(__('품절된 상품은 구매가 불가능합니다.'));
                return false;
            }

            return true;
        },
        isValidOption : function()
        {
            // 필수옵션 체크
            var bIsProductEmptyOption = EC_SHOP_FRONT_PRODUCT_RESTOCK.isRestock(this.parent.sType) === false && checkOptionRequired() == false;
            this.parent.UTIL.setData('bIsProductEmptyOption', bIsProductEmptyOption);

            //추가구성상품 옵션 체크
            var oValidAddProductCount = NEWPRD_ADD_OPTION.isValidAddOptionSelect(this.parent.oForm);

            //관련상품 옵션 체크
            var oValidRelationProductCount = NEWPRD_ADD_OPTION.isValidRelationProductSelect(this.parent.oForm, this.parent.oObject, bIsProductEmptyOption);

            // 개별 구매 관련 검증된 데이터
            var oIndividualValidData = NEWPRD_ADD_OPTION.getIndividualValidCheckData(oValidRelationProductCount, oValidAddProductCount, bIsProductEmptyOption, this.parent.oForm);

            // 옵션 체크
            if (bIsProductEmptyOption === true) {
                // 실패 타입 존재 할 경우
                if (oIndividualValidData.sFailType !== '') {
                    return false;
                }
                //관련상품 및 추가구성상품 단독구매시 유효성 메시지 노출여부 결정(순차 검증진행 추가 or 관련 + 본상품)
                if (NEWPRD_ADD_OPTION.checkIndividualValidAction(oValidRelationProductCount, oValidAddProductCount) === false) {
                    return false;
                }
                // 독립형 일때
                var oExistRequiredSelect = (option_type === 'F') ? $('select[id^="' + product_option_id + '"][required="true"]') : false;
                var sMsg = __('필수 옵션을 선택해주세요.');
                try {
                    // 관련상품 체크 확인 유무
                    if (NEWPRD_ADD_OPTION.checkRelationProduct(this.parent.oObject, this.parent.sType) === false) {
                        return false;
                    }

                    if (oIndividualValidData.isValidInidual === false && EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.setLayer(iProductNo, iCategoryNo, 'normal') === true) {
                        return false;
                    }

                    if (Olnk.getOptionPushbutton($('#option_push_button')) === true ) {
                        var bCheckOption = false;
                        $('select[id^="' + product_option_id + '"]').each(function() {
                            if (Boolean($(this).attr('required')) === true &&  Olnk.getCheckValue($(this).val(),'') === false) {
                                bCheckOption = true;
                                return false;
                            }
                        });
                        if (bCheckOption === false) {
                            sMsg = __('품목을 선택해 주세요.');
                        }
                    }
                } catch (e) {
                }

                // 메인상품 품목데이터 확인
                var isEmptyItemData = ITEM.getItemCode().length == false || ITEM.getItemCode() === false;
                // 추가구성상품 및 관련상품의 개별적 구매
                if (isEmptyItemData === true && oIndividualValidData.isValidInidual === true) {
                    if (NEWPRD_ADD_OPTION.checkVaildIndividualMsg(oIndividualValidData, this.parent.sType, this.parent.oObject) === false) {
                        return false;
                    }

                } else {
                    // 기존 유효성 검증 메세지
                    var sOrginalValidMsg = NEWPRD_ADD_OPTION.checkExistingValidMessage(this.parent.oObject, oValidAddProductCount);
                    //추가구성상품의 선택되어있으면서 본상품의 옵션이 선택 안되었을때
                    sMsg = (sOrginalValidMsg === false) ? sMsg : sOrginalValidMsg;

                    alert(sMsg);
                    if (oExistRequiredSelect !== false) {
                        oExistRequiredSelect.focus();
                    }
                    return false;
                }
            } else {
                // 관련상품 체크 확인
                if (NEWPRD_ADD_OPTION.checkRelationProduct(this.parent.oObject, this.parent.sType) === false) {
                    return false;
                }

                // 단독구매시 메인상품 품절된 상품일때 메시지 처리
                if (NEWPRD_ADD_OPTION.checkSoldOutProductValid(this.parent.oObject) === true) {
                    this.parent.UTIL.appendHidden('is_product_sold_out', 'T');
                    if (NEWPRD_ADD_OPTION.checkVaildIndividualMsg(oIndividualValidData, this.parent.sType, this.parent.oObject) === false) {
                        return false;
                    }
                }
                if (FileOptionManager.checkValidation() === false) {
                    return false;
                }
            }
            if (oValidAddProductCount.result === false) {
                if (oValidAddProductCount.message !== '') {
                    alert(oValidAddProductCount.message);
                    oValidAddProductCount.object.focus();
                }
                return false;
            }
            if (oValidRelationProductCount.result === false) {
                if (oValidRelationProductCount.message !== '') {
                    alert(oValidRelationProductCount.message);
                    oValidRelationProductCount.object.focus();
                }
                return false;
            }
            if (oIndividualValidData.isValidInidual === false) {
                // 추가 옵션 체크 (품목기반 추가옵션일때는 폼제출때 검증 불필요)
                var oParent = (this.parent.sType === 'funding') ? $('.EC-funding-checkbox:checked').parents('.xans-product-funding') : null;
                if (NEWPRD_ADD_OPTION.isItemBasedAddOptionType() !== true && checkAddOption(null, oParent) === false) {
                    this.parent.oDebug.setMessage('checkAddOption Fail');
                    return false;
                }
            }
            return true;
        },
        isValidAddproduct : function()
        {
            if ($('.add-product-checked:checked').length === 0) {
                return true;
            }

            var aAddProduct = $.parseJSON(add_option_data);
            var aItemCode = new Array();
            var bCheckValidate = true;
            $('.add-product-checked:checked').each(function() {
                if (bCheckValidate === false) {
                    return false;
                }
                var iProductNum = $(this).attr('product-no');
                var iQuantity = $('#add-product-quantity-'+iProductNum).val();
                var aData = aAddProduct[iProductNum];
                if (aData.item_code === undefined) {
                    if (aData.option_type === 'T') {
                        if (aData.item_listing_type === 'S') {
                            var aOptionValue = new Array();
                            $('[id^="addproduct_option_id_'+iProductNum+'"]').each(function() {
                                aOptionValue.push($(this).val());
                            });
                            if (ITEM.isOptionSelected(aOptionValue) === true) {
                                sOptionValue = aOptionValue.join('#$%');
                                aItemCode.push([$.parseJSON(aData.option_value_mapper)[sOptionValue],iQuantity]);
                            } else {
                                bCheckValidate = false;
                                alert(__('필수 옵션을 선택해주세요.'));
                                return false;
                            }
                        } else {
                            var $eItemSelectbox = $('[name="addproduct_option_name_'+iProductNum+'"]');

                            if (ITEM.isOptionSelected($eItemSelectbox.val()) === true) {
                                aItemCode.push([$eItemSelectbox.val(),iQuantity]);
                            } else {
                                bCheckValidate = false;
                                $eItemSelectbox.focus();
                                alert(__('필수 옵션을 선택해주세요.'));
                                return false;
                            }
                        }
                    } else if (Olnk.isLinkageType(sOptionType) === true) {
                        $('[id^="addproduct_option_id_'+iProductNum+'"]').each(function() {
                            alert( $(this).val());
                            if ($(this).attr('required') == true && ITEM.isOptionSelected($(this).val()) === false) {
                                bCheckValidate = false;
                                $(this).focus();
                                alert(__('필수 옵션을 선택해주세요.'));
                                return false;
                            }

                            if (ITEM.isOptionSelected($(this).val()) === true) {
                                aItemCode.push([$(this).val(),iQuantity]);
                            }
                        });
                    } else {
                        $('[id^="addproduct_option_id_'+iProductNum+'"]').each(function() {
                            if ($(this).attr('required') == true && ITEM.isOptionSelected($(this).val()) === false) {
                                bCheckValidate = false;
                                $(this).focus();
                                alert(__('필수 옵션을 선택해주세요.'));
                                return false;
                            }
                            if (ITEM.isOptionSelected($(this).val()) === true) {
                                aItemCode.push([$(this).val(),iQuantity]);
                            }
                        });
                    }
                } else {
                    aItemCode.push([aData.item_code,iQuantity]);
                }
            });
            if (bCheckValidate === false) {
                return false;
            }
            for (var x = 0; x < aItemCode.length; x++) {
                this.UTIL.appendHidden('relation_item[]', aItemCode[x][1]+'||'+aItemCode[x][0]);
            }
        },
        isValidRegularDelivery : function() // 정기 배송
        {
            if (EC_FRONT_JS_CONFIG_SHOP.bRegularConfig === false) {
                return true;
            }
            if ($('.EC_regular_delivery:checked').length === 0 || $('.EC_regular_cycle_count').length === 0) {
                return true;
            }

            if ($('.EC_regular_delivery:checked').val() === 'F') {
                return true;
            }


            if (EC_FRONT_JS_CONFIG_SHOP.bIsLogin === false) {
                alert(__('AVAILABLE.AFTER.LOGIN', 'SHOP.JS.FRONT.NEW.PRODUCT.ACTION'));
                return false;
            }

            var sSubscriptionCycleValue =  $('.EC_regular_cycle_count').val();

            if ($('.EC_regular_cycle_count').attr('type') === 'select-one') {
                sSubscriptionCycleValue =  $('.EC_regular_cycle_count > option:selected').val();
                if (sSubscriptionCycleValue === '') {
                    alert(__('REGULAR.SHIPPING.CYCLE', 'SHOP.JS.FRONT.NEW.PRODUCT.ACTION'));
                    return false;
                }
            } else if ($('.EC_regular_cycle_count').attr('type') === 'hidden') {
                if (sSubscriptionCycleValue === '') {
                    alert(__('REGULAR.SHIPPING.CYCLE', 'SHOP.JS.FRONT.NEW.PRODUCT.ACTION'));
                    return false;
                }
            } else {
                sSubscriptionCycleValue =  $('.EC_regular_cycle_count:checked').val();
                if ($('.EC_regular_cycle_count:checked').length === 0) {
                    alert(__('REGULAR.SHIPPING.CYCLE', 'SHOP.JS.FRONT.NEW.PRODUCT.ACTION'));
                    return false;
                }
            }

            // 기존 하드코딩용
            var regex = /[W|M|Y]$/g;
            if (regex.test(sSubscriptionCycleValue) === false) {
                sSubscriptionCycleValue = sSubscriptionCycleValue + 'W';
            }

            var sSubscriptionCycleCount = sSubscriptionCycleValue.substring(sSubscriptionCycleValue.length-1, -1);
            var sSubscriptionCycle = sSubscriptionCycleValue.slice(-1);

            PRODUCTSUBMIT.UTIL.appendHidden('is_subscription', $('.EC_regular_delivery:checked').val());
            PRODUCTSUBMIT.UTIL.appendHidden('subscription_cycle', sSubscriptionCycle); // 주단위 현재는 고정
            PRODUCTSUBMIT.UTIL.appendHidden('subscription_cycle_count', sSubscriptionCycleCount);

            return true;
        },
        isValidFunding : function()
        {
            if (PRODUCTSUBMIT.sType !== 'funding') {
                return true;
            }

            if (EC_SHOP_FRONT_PRODUCT_FUNDING.isSelectComposition() === false) {
                alert('펀딩 구성을 선택하세요.');
                return false;
            }

            if (EC_SHOP_FRONT_PRODUCT_FUNDING.isItemSelected() === false) {
                alert(__('필수 옵션을 선택해주세요.'));
                return false;
            }

            // 최소 주문 수량
            if (EC_SHOP_FRONT_PRODUCT_FUNDING.isValidQuantity() === false) {
                alert('남은 수량을 확인 후 다시 펀딩신청하세요.');
                return false;
            }

            return true;
        }
    },
    UTIL : {
        oData : {},
        initialize : function(oParent)
        {
            this.parent = oParent;
            return this;
        },
        appendHidden : function(mParam)
        {
            // 익스플로9 미만의 폴리필
            if (!Array.isArray) {
                Array.isArray = function(arg) {
                    return Object.prototype.toString.call(arg) === '[object Array]';
                };
            }
            if (typeof(mParam) === 'string' && arguments.length === 2) {
                this.setHidden(arguments[0], arguments[1]);
            }
            if (typeof(mParam) === 'object') {
                for (var sName in mParam) {
                    if (Array.isArray(mParam[sName]) === true) {
                        $.each(mParam[sName], function(iIndex, mValue) {
                            PRODUCTSUBMIT.UTIL.setHidden(sName+'[]', mValue);
                        });
                        continue;
                    }
                    this.setHidden(sName, mParam[sName]);
                }
            }
        },
        prependHidden : function(mParam)
        {
            // 익스플로9 미만의 폴리필
            if (!Array.isArray) {
                Array.isArray = function(arg) {
                    return Object.prototype.toString.call(arg) === '[object Array]';
                };
            }
            if (typeof(mParam) === 'string' && arguments.length === 2) {
                this.setHidden(arguments[0], arguments[1], 'prepend');
            }
            if (typeof(mParam) === 'object') {
                for (var sName in mParam) {
                    if (Array.isArray(mParam[sName]) === true) {
                        $.each(mParam[sName], function(iIndex, mValue) {
                            PRODUCTSUBMIT.UTIL.setHidden(sName+'[]', mValue, 'prepend');
                        });
                        continue;
                    }
                    this.setHidden(sName, mParam[sName], 'prepend');
                }
            }
        },
        setHidden : function(sName, sValue, sAppendType)
        {
            //ECHOSTING-9736
            if (typeof(sValue) == "string" && (sName == "option_add[]" || sName.indexOf("item_option_add") === 0)) {
                 sValue = sValue.replace(/'/g,  '\\&#039;');
            }

            // 타입이 string 일때 연산시 단일 따움표 " ' " 문자를 " ` " 액센트 문자로 치환하여 깨짐을 방지
            var oAttribute = {
                'name': sName,
                'type': 'hidden',
                'class' : 'basket-hidden'
            };
            if (sAppendType === 'prepend') {
                this.parent.oForm.prepend($('<input>').attr(oAttribute).val(sValue));

            } else {
                this.parent.oForm.append($('<input>').attr(oAttribute).val(sValue));

            }
        },
        setData : function(sKey, mValue)
        {
            this.oData[sKey] = mValue;
            return true;
        },
        getData : function(sKey)
        {
            return this.oData[sKey];
        }
    },
    DEBUG : {
        aMessage : [],
        initialize : function(oParent)
        {
            this.aMessage = [];
            this.parent = oParent;
            this.bIsDebugConsoleOut = this.parent.bIsDebugConsoleOut;
            return this;
        },
        setInfo : function()
        {
            if (this.bIsDebugConsoleOut === false) {
                return;
            }
            if (window.console) {
                var aMessage = [];
                for (var i = 0; i < arguments.length; i++) {
                    aMessage.push(arguments[i]);
                }
                console.info(aMessage.join(''));
            }
        },
        setMessage : function(sMessage)
        {
            this.aMessage.push(sMessage);
            this.setConsoleDebug();
            throw 'USER_DEFINED_ERROR';
        },
        setConsoleDebug : function()
        {
            if (this.bIsDebugConsoleOut === false) {
                return;
            }
            if (window.console) {
                console.warn(this.aMessage.join('\n'));
            }
        },
        messageOut : function(mError)
        {
            if (this.bIsDebugConsoleOut === true && mError !== 'USER_DEFINED_ERROR') {
                console.error(mError);
            }
            return false;
        }
    }
};


// 상품 옵션 id
var product_option_id = 'product_option_id';

// 추가옵션 id
var add_option_id = 'add_option_';

// 선택된 상품만 주문하기
var sIsPrdOverride = 'F';

//모바일로 접속했는지
var bIsMobile = false;

//분리형 세트상품의 구성상품(품절)에서 SMS 재입고 알림 팝업 호출
function set_sms_restock(iProductNo) {
    if (typeof(iProductNo) === 'undefined') {
        return;
    }

    // 모바일 접속 및 레이어 팝업 여부 확인
    if (typeof(EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER) !== 'undefined') {
        var sParam = 'product_no=' + iProductNo;
        if (EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER.createSmsRestockLayerDisplayResult(sParam) === true) {
            return;
        }
    }

    window.open('/product/sms_restock.html?product_no=' + iProductNo, 'sms_restock', 200, 100, 459, 490);
}

// 예약 주문 체크
var STOCKTAKINGCHECKRESERVE = {
    checkReserve : function()
    {
        var bIsReserveStatus = $('.option_box_id').filter('[data-item-reserved="R"]').length > 0;
        // 예약 주문이 있는경우
        if (bIsReserveStatus === true) {
            alert(__('ITEMS.MAY.SHIPPED', 'SHOP.JS.FRONT.NEW.PRODUCT.ACTION'));
        }
        return false;
    }
};


/**
 * sType - 1:바로구매, 2:장바구니,naver_checkout:네이버 페이 form.submit - 바로구매, 장바구니, 관심상품
 * TODO 바로구매 - 장바구니에 넣으면서 주문한 상품 하나만 주문하기
 *
 * @param string sAction action url
 */
function product_submit(sType, sAction, oObj)
{
    PRODUCTSUBMIT.initialize(sType, sAction, oObj);
    if (PRODUCTSUBMIT.isValidRequest() === true && PRODUCTSUBMIT.setBasketForm() === true) {
        PRODUCTSUBMIT.setBasketAjax();
    }
    return;
}

/**
 * 선택한상품만 주문하기
 *
 * @param string sOptionParam 옵션 파람값
 * @param int iProductNo 상품번호
 * @param string sSelectedItemByEtype 상품연동형의 경우 입력되는 선택된옵션 json 데이터
 */
function selectbuy_action(sOptionParam, iProductNo, sSelectedItemByEtype)
{
    var sAddParam = '';
    if (typeof sSelectedItemByEtype != 'undefined' && sSelectedItemByEtype != '') {
        sAddParam = '&' + sSelectedItemByEtype;
    }

    var sUrl = '/exec/front/order/basket/?command=select_prdcnt&product_no=' + iProductNo + '&option_type=' + (window['option_type'] || '') + sOptionParam + sAddParam;

    $.ajax(
    {
        url : sUrl,
        dataType : 'json',
        async : false,
        success : function(data)
        {
            if (data.result > 0) {
                //1+N상품이라면
                if (typeof(EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE) !== 'undefined' && EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig.hasOwnProperty(iProductNo) === true) {
                    sIsPrdOverride = 'F';
                } else {
                    if (!confirm(sprintf(__('동일상품이 장바구니에 %s개 있습니다.'), data.result) +'\n'+ __('함께 구매하시겠습니까?'))) {
                        sIsPrdOverride = 'T';
                    }
                }
            }
        }
    });
}

/**
 * 장바구니 담기(카테고리)
 *
 * @param int iProductNo 상품번호
 * @param int iCategoryNo 카테고리 번호
 * @param int iDisplayGroup display_group
 * @param string sBasketType 무이자 설정(A0000:일반, A0001:무이자)
 * @param string iQuantity 주문수량
 * @param string sItemCode 아이템코드
 * @param string sDelvType 배송타입
 */
function category_add_basket(iProductNo, iCategoryNo, iDisplayGroup, sBasketType, bList, iQuantity, sItemCode, sDelvType, sProductMaxType, sProductMax)
{
    if (iQuantity == undefined) {
        iQuantity = 1;
    }

    if (bList == true) {
        try {
            if ($.type(EC_ListAction) == 'object') {
                EC_ListAction.getOptionSelect(iProductNo, iCategoryNo, iDisplayGroup, sBasketType);
            }
        } catch (e) {
            alert(__('장바구니에 담을 수 없습니다.'));
            return false;
        }
    } else {
        var sAction = '/exec/front/order/basket/';
        var sData = 'command=add&quantity=' + iQuantity + '&product_no=' + iProductNo + '&main_cate_no=' + iCategoryNo + '&display_group='
                + iDisplayGroup + '&basket_type=' + sBasketType + '&delvtype=' + sDelvType + '&product_max_type=' + sProductMaxType + '&product_max=' + sProductMax;
        // 장바구니 위시리스트인지 여부
        if (typeof (basket_page_flag) != 'undefined' && basket_page_flag == 'T') {
            sData = sData + '&basket_page_flag=' + basket_page_flag;
        }

        // 뉴상품 옵션 선택 구매
        sData = sData + '&selected_item[]='+iQuantity+'||' + sItemCode + '000A';

        action_basket(2, 'category', sAction, sData, sBasketType);
    }
}

/**
 * 구매하기
 *
 * @param int iProductNo 상품번호
 * @param int iCategoryNo 카테고리 번호
 * @param int iDisplayGroup display_group
 * @param string sBasketType 무이자 설정(A0000:일반, A0001:무이자)
 * @param string iQuantity 주문수량
 */
function add_order(iProductNo, iCategoryNo, iDisplayGroup, sBasketType, iQuantity)
{
    if (iQuantity == undefined) {
        iQuantity = 1;
    }

    var sAction = '/exec/front/order/basket/';
    var sData = 'command=add&quantity=' + iQuantity + '&product_no=' + iProductNo + '&main_cate_no=' + iCategoryNo + '&display_group='
            + iDisplayGroup + '&basket_type=' + sBasketType;

    action_basket(1, 'wishlist', sAction, sData, sBasketType);
}

/**
 * 레이어 생성
 *
 * @param layerId
 * @param sHtml
 */
function create_layer(layerId, sHtml, oTarget)
{
    //아이프레임일때만 상위객체에 레이어생성
    if (oTarget === parent) {
        oTarget.$('#' + layerId).remove();
        oTarget.$('body').append($('<div id="' + layerId + '" style="position:absolute; z-index:10001;"></div>'));
        oTarget.$('#' + layerId).html(sHtml);
        oTarget.$('#' + layerId).show();

        //옵션선택 레이어 프레임일 경우 그대로 둘경우 영역에대해 클릭이 안되는부분때문에 삭제처리
        if (typeof(bIsOptionSelectFrame) !== 'undefined' && bIsOptionSelectFrame === true) {
            parent.CAPP_SHOP_NEW_PRODUCT_OPTIONSELECT.closeOptionCommon();
        }
    } else {
        $('#' + layerId).remove();
        $('<div id="' + layerId + '"></div>').appendTo('body');
        $('#' + layerId).html(sHtml);
        $('#' + layerId).show();
    }
    // set delvtype to basket
    try {
        $(".xans-product-basketadd").find("a[href='/order/basket.html']").attr("href", "/order/basket.html?delvtype=" + delvtype);
    } catch (e) {}
    try {
        $(".xans-order-layerbasket").find("a[href='/order/basket.html']").attr("href", "/order/basket.html?delvtype=" + delvtype);
    } catch (e) {}
}

/**
 * 레이어 위치 조정
 *
 * @param layerId
 */
function position_layer(layerId)
{
    var obj = $('#' + layerId);

    var x = 0;
    var y = 0;
    try {
        var hWd = parseInt(document.body.clientWidth / 2 + $(window).scrollLeft());
        var hHt = parseInt(document.body.clientHeight / 2 + $(window).scrollTop() / 2);
        var hBW = parseInt(obj.width()) / 2;
        var hBH = parseInt(hHt - $(window).scrollTop());

        x = hWd - hBW;
        if (x < 0) x = 0;
        y = hHt - hBH;
        if (y < 0) y = 0;

    } catch (e) {}

    obj.css(
    {
        position : 'absolute',
        display : 'block',
        top : y + "px",
        left : x + "px"
    });

}


// 장바구니 담기 처리중인지 체크 - (ECHOSTING-85853, 2013.05.21 by wcchoi)
var bIsRunningAddBasket = false;

/**
 * 장바구니/구매 호출
 *
 * @param sType
 * @param sGroup
 * @param sAction
 * @param sParam
 * @param aBasketType
 * @param bNonDuplicateChk
 */
function action_basket(sType, sGroup, sAction, sParam, sBasketType, bNonDuplicateChk)
{
    // 장바구니 담기에 대해서만 처리
    // 중복 체크 안함 이 true가 아닐경우(false나 null)에만 중복체크
    if (sType == 2 && bNonDuplicateChk != true) {
        if (bIsRunningAddBasket) {
            alert(__('처리중입니다. 잠시만 기다려주세요.'));
            return;
        } else {
            bIsRunningAddBasket = true;
        }
    }

    if (sType == 'sms_restock') {
        action_sms_restock(sParam);
        return;
    }

    if (sType == 'email_restock') {
        action_email_restock();
        return;
    }

    if (sType == 2 && EC_SHOP_FRONT_BASKET_VALIID.isBasketProductDuplicateValid(sParam) === false) {
        bIsRunningAddBasket = false;
        return false;
    }

    $.post(sAction, sParam, function(data)
    {
        Basket.isInProgressMigrationCartData(data);

        basket_result_action(sType, sGroup, data, sBasketType, sParam);

        bIsRunningAddBasket = false; // 장바구니 담기 처리 완료

    }, 'json');

    // 관신상품 > 전체상품 주문 ==> 장바구니에 들어가기도 전에 /exec/front/order/order/ 호출하게 되어 오류남
    // async : false - by wcchoi
    // 다시 async모드로 원복하기로 함 - ECQAINT-7857
    /*
    $.ajax({
        type: "POST",
        url: sAction,
        data: sParam,
        async: false,
        success: function(data) {
            basket_result_action(sType, sGroup, data, sBasketType);
            bIsRunningAddBasket = false; // 장바구니 담기 처리 완료
        },
        dataType: 'json'
    });
    */
}

/**
 * 리스트나 상세에서 장바구니 이후의 액션을 처리하고 싶을 경우 이변수를 파라미터로 지정해줌
 */
var sProductLink = null;
/**
 * 장바구니 결과 처리
 *
 * @param sType
 * @param sGroup
 * @param aData
 * @param sBasketType
 * @param sParam
 */
function basket_result_action(sType, sGroup, aData, sBasketType, sParam)
{
    if (aData == null) {
        return;
    }

    var sHtml = '';
    var bOpener = false;
    var oTarget = CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame();
    var bIsProgressLink = true;

    var oCheckZoomPopUp = {
        isPopUp : function()
        {
            var bIsPopup = false;
            if (bIsProgressLink === true || (typeof(sIsPopUpWindow) !== "undefined" && sIsPopUpWindow === "T")) {
                if (CAPP_SHOP_FRONT_COMMON_UTIL.isPopupFromThisShopFront() === true) {
                    bIsPopup = true;
                }
            }
            return bIsPopup;
        }
    };

    //var oOpener = findMainFrame();
    //var sLocation = location;
    var bBuyLayer = false;

    // 쿠폰적용 가능상품 팝업 -> 상품명 클릭하여 상품상세 진입 -> 바로 구매 시,
    // 쿠폰적용 가능상품 팝업이 열려있으면 주문서 페이지로 이동되지 않고, 창이 닫이는 이슈 처리(ECHOSTING-266906)
    if (sType == 1 && window.opener !== null && oTarget.couponPopupClose !== undefined) {
        bOpener = true;
    }

    if (aData.result >= 0) {
        try {
            bBuyLayer = ITEM.setBodyOverFlow(true);
        } catch (e) {}

        // 네이버 페이
        if (sType == 'naver_checkout') {
            var sUrl = '/exec/front/order/navercheckout';

            // inflow param from naver common JS to Checkout Service
            try {
                if (typeof(wcs) == 'object') {
                    var inflowParam = wcs.getMileageInfo();
                    if (inflowParam != false) {
                        sUrl = sUrl + '?naver_inflow_param=' + inflowParam;
                    }
                }
            } catch (e) {}

            if (is_order_page == 'N' && bIsMobile == false) {
                window.open(sUrl);
                return false;
            } else {
                oTarget.location.href = sUrl;
                return false;
            }
        }

        // 배송유형
        var sDelvType = '';
        if (typeof(delvtype) != 'undefined') {
            if (typeof(delvtype) == 'object') {
                sDelvType = $(delvtype).val();
            } else {
                sDelvType = delvtype;
            }
        } else if (aData.sDelvType != null) {
            sDelvType = aData.sDelvType;
        }

        if (sType == 1 || sType === 'funding') { // 바로구매하기
            if (aData.isLogin == 'T') { // 회원
                if (bOpener === true) {
                    // 쿠폰적용 가능상품 팝업이 열려있을 때, 팝업이 아닌 현재 페이지(상품상세)가 주문서 페이지로 이동되도록 처리(ECHOSTING-266906)
                    self.location.href = "/order/orderform.html?basket_type=" + sBasketType + "&delvtype=" + sDelvType;
                } else {
                    oTarget.location.href = "/order/orderform.html?basket_type=" + sBasketType + "&delvtype=" + sDelvType;
                }
            } else { // 비회원
                sUrl = '/member/login.html?noMember=1&returnUrl=' + encodeURIComponent('/order/orderform.html?basket_type=' + sBasketType + "&delvtype=" + sDelvType);
                sUrl += '&delvtype=' + sDelvType;

                oTarget.location.href = sUrl;
            }
        } else if (sType === 'direct_buy') {
            EC_SHOP_FRONT_ORDERFORM_DIRECTBUY.proc.setOrderForm(TotalAddSale.getDirectBuyParam());
            return;
        } else { // 장바구니담기
            var oData = EC_PlusAppBridge.unserialize(sParam);
            EC_PlusAppBridge.addBasket(oData);

            if (sGroup == 'detail') {
                if (mobileWeb === true) {
                    if (typeof (basket_page_flag) != 'undefined' && basket_page_flag == 'T') {
                        oTarget.reload();
                        return;
                    }
                }

                var oSearch = /basket.html/g;
                //레이어가 뜨는 설정이라면 페이지이동을 하지 않지만
                //레이어가 뜨어라고 확대보기팝업이라면 페이지 이동

                if (typeof(aData.isDisplayBasket) != "undefined" && aData.isDisplayBasket == 'T' && oSearch.test(window.location.pathname) == false) {
                    if ((typeof(aData.isDisplayLayerBasket) != "undefined" && aData.isDisplayLayerBasket == 'T') && (typeof(aData.isBasketPopup) != "undefined" && aData.isBasketPopup == 'T')) {
                        layer_basket2(sDelvType, oTarget);
                    } else {
                        //ECQAINT-14010 Merge이슈 : oTarget이 정상
                        layer_basket(sDelvType, oTarget);
                    }

                    bIsProgressLink = false;
                }

                //확인 레이어설정이 아니거나 확대보기 팝업페이지라면 페이지이동
                if (oCheckZoomPopUp.isPopUp() === true || bIsProgressLink === true) {
                    oTarget.location.href = "/order/basket.html?"  + "&delvtype=" + sDelvType;
                }
            } else {
                // from으로 위시리스트에서 요청한건지 판단.
                var bIsFromWishlist = false;
                if (typeof(aData.from) != "undefined" && aData.from == "wishlist") {
                    bIsFromWishlist = true;
                }

                // 장바구니 위시리스트인지 여부
                if (typeof (basket_page_flag) != 'undefined' && basket_page_flag == 'T' || bIsFromWishlist == true) {
                    oTarget.reload();
                    return;
                }
                if (typeof(aData.isDisplayBasket) != "undefined" && aData.isDisplayBasket === 'T' ) {
                    if ((typeof(aData.isDisplayLayerBasket) != "undefined" && aData.isDisplayLayerBasket == 'T') && (typeof(aData.isBasketPopup) != "undefined" && aData.isBasketPopup == 'T')) {
                        layer_basket2(sDelvType, oTarget);
                    } else {
                        layer_basket(sDelvType, oTarget);
                    }
                } else {
                    location.href = "/order/basket.html?"  + "&delvtype=" + sDelvType;
                }
            }
        }
    } else {
        var msg = aData.alertMSG.replace('\\n', '\n');

        // 디코딩 하기전에 이미 인코딩 된 '\n' 문자를 실제 개행문자로 변환
        // 목록에서 호출될 경우에는 인코딩 되지 않은 '\n' 문자 그대로 넘어오므로 추가 처리
        msg = msg.replace(/%5Cn|\\n/g, '%0A');

        try {
            msg = decodeURIComponent(msg);
        } catch (err) {
            msg = unescape(msg);
        }

        alert(msg);

        if (aData.result == -111 && sProductLink !== null) {
            oTarget.href = '/product/detail.html?' + sProductLink;
        }
        if (aData.result == -101 || aData.result == -103) {
            sUrl = '/member/login.html?noMember=1&returnUrl=' + encodeURIComponent(oTarget.location.href);
            oTarget.location.href = sUrl;
        }

        if (aData.result == -113) {
            if (typeof(delvtype) != 'undefined') {
                if (typeof(delvtype) == 'object') {
                    sDelvTypeForMove = $(delvtype).val();
                } else {
                    sDelvTypeForMove = delvtype;
                }
                oTarget.location.href = "/order/basket.html?"  + "&delvtype=" + sDelvTypeForMove;
            } else {
                oTarget.location.href = "/order/basket.html";
            }
        }
    }

    // ECHOSTING-130826 대응, 쿠폰적용상품 리스트에서 옵션상품(뉴옵션)담기 처리시, 화면이 자동으로 닫히지 않아 예외처리 추가
    if (oTarget.couponPopupClose !== undefined) {
        oTarget.couponPopupClose();
    }
    if (oCheckZoomPopUp.isPopUp() === true && bOpener === false) {
        self.close();
    } else {
        // ECHOSTING-130826 대응, 특정 화면에서 장바구니에 상품 담기 시 async 가 동작하지 않아,
        // 장바구니 담기처리 후처리 구간에 async 강제 실행추가
        // 쿠폰 적용 가능상품 리스트 에서 장바구니 담기시, 여기서 실행할 경우 js 오류가 발생하여, 함수 상단에 별도 처리 추가
        if (typeof(oTarget) !== 'undefined' && typeof(oTarget.CAPP_ASYNC_METHODS) !== 'undefined') {
            oTarget.CAPP_ASYNC_METHODS.init();
        } else {
            CAPP_ASYNC_METHODS.init();
        }
    }
}

function layer_basket(sDelvType, oTarget)
{
    var oProductName = null;
    if (typeof(product_name) !== 'undefined') {
        oProductName = {'product_name' : product_name};
    }
    $('.xans-product-basketoption').remove();
    $.get('/product/add_basket.html?delvtype='+sDelvType, oProductName, function(sHtml)
        {
            sHtml = sHtml.replace(/<script.*?ind-script\/optimizer.php.*?<\/script>/g, '');
            // scirpt를 제거하면서 document.ready의 Async 모듈이 실행안되서 강제로 실행함
            CAPP_ASYNC_METHODS.init();
            create_layer('confirmLayer', sHtml, oTarget);
        });
}

function layer_basket2(sDelvType, oTarget)
{
    $('.xans-order-layerbasket').remove();
    var oTarget = CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame();
    $.get('/product/add_basket2.html?delvtype=' + sDelvType + '&layerbasket=T', '', function(sHtml)
    {
        sHtml = sHtml.replace(/<script.*?ind-script\/optimizer.php.*?<\/script>/g, '');

        //scirpt를 제거하면서 document.ready의 Async 모듈이 실행안되서 강제로 실행함
        CAPP_ASYNC_METHODS.init();
        create_layer('confirmLayer', sHtml, oTarget);
    });
}

function layer_wishlist(oTarget)
{
    $('.layerWish').remove();
    $.get('/product/layer_wish.html','' ,function(sHtml)
    {
        sHtml = sHtml.replace(/<script.*?ind-script\/optimizer.php.*?<\/script>/g, '');
        create_layer('confirmLayer', sHtml, oTarget);
    });
}

function go_basket()
{
    var oTarget = CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame();
    oTarget.location.href = '/order/basket.html';
    if (CAPP_SHOP_FRONT_COMMON_UTIL.isPopupFromThisShopFront() === true) {
        self.close();
    }
}

function move_basket_page()
{
    var sLocation = location;
    try {

        sLocation = ITEM.setBodyOverFlow(location);
    } catch (e) {}

    sLocation.href = '/order/basket.html';
}

/**
 * 이미지 확대보기 (상품상세 버튼)
 */
function go_detail()
{
    var sUrl = '/product/detail.html?product_no=' + iProductNo;
    var oTarget = CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame();

    if (typeof(iCategoryNo) != 'undefined') {
        sUrl += '&cate_no='+iCategoryNo;
    }

    if (typeof(iDisplayGroup) != 'undefined') {
        sUrl += '&display_group='+iDisplayGroup;
    }

    oTarget.location.href = sUrl;
    if (CAPP_SHOP_FRONT_COMMON_UTIL.isPopupFromThisShopFront() === true) {
        self.close();
    }
}

/**
 * 바로구매하기/장바구니담기 Action  - 로그인하지 않았을 경우
 */
function check_action_nologin()
{
    alert(__('회원만 구매 가능합니다. 비회원인 경우 회원가입 후 이용하여 주세요.'));

    var oTarget = CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame();

    ITEM.setBodyOverFlow(location);

    sUrl = '/member/login.html?returnUrl=' + encodeURIComponent(oTarget.location.href);
    oTarget.location.href = sUrl;
}

/**
 * 바로구매하기 Action  - 불량회원 구매제한
 */
function check_action_block(sMsg)
{
    if (sMsg == '' ) {
        sMsg = __('쇼핑몰 관리자가 구매 제한을 설정하여 구매하실 수 없습니다.');
    }
    alert(sMsg);
}

/**
 * 관심상품 등록 - 로그인하지 않았을 경우
 */
function add_wishlist_nologin(sUrl)
{

    alert(__('로그인 후 관심상품 등록을 해주세요.'));

    btn_action_move_url(sUrl);
}

/**
 * 바로구매하기 / 장바구니 담기 / 관심상품 등록 시 url 이동에 사용하는 메소드
 * @param sUrl 이동할 주소
 */
function btn_action_move_url(sUrl)
{
    var oTarget = CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame();

    sLocation = ITEM.setBodyOverFlow(location);

    sUrl += '?returnUrl=' + encodeURIComponent(oTarget.location.pathname + oTarget.location.search);
    oTarget.location.replace(sUrl);
}

/**
 * return_url 없이 url 이동에 사용하는 메소드
 * @param sUrl 이동할 주소
 */
function btn_action_move_no_return_url(sUrl)
{
    var oTarget = CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame();
    oTarget.location.replace(sUrl);
}

/**
 * 관심상품 등록 - 파라미터 생성
 * @param bIsUseOptionSelect 장바구니옵션선택 새모듈 사용여부(basket_option.html, Product_OptionSelectLayer)
 */
function add_wishlist(sMode, bIsUseOptionSelect)
{
    var sUrl = '//' + location.hostname;
    sUrl += '/exec/front/Product/Wishlist/';
    var param = location.search.substring(location.search.indexOf('?') + 1);
    sParam = param + '&command=add';
    sParam += '&referer=' + encodeURIComponent('//' + location.hostname + location.pathname + location.search);

    add_wishlist_action(sUrl, sParam, sMode, bIsUseOptionSelect);
}

var bWishlistSave = false;
/**
 * @param bIsUseOptionSelect 장바구니옵션선택 새모듈 사용여부(basket_option.html, Product_OptionSelectLayer)
 */
function add_wishlist_action(sAction, sParam, sMode, bIsUseOptionSelect)
{
    //연동형 옵션 여부
    var bIsOlinkOption = Olnk.isLinkageType(sOptionType);
    if (bWishlistSave === true) {
        return false;
    }
    var required_msg = __('품목을 선택해 주세요.');
    if (sOptionType !== 'F') {
        var aItemCode = ITEM.getWishItemCode();
    } else {
        var aItemCode = null;
    }
    var sSelectedItemByEtype   = '';

    var frm = $('#frm_image_zoom');
    frm.find(":hidden").remove();
    frm.attr('method', 'POST');
    frm.attr('action', '/' + sAction);

    if (bIsOlinkOption === true) {
        if (isNewProductSkin() === false) {
            sItemCode = Olnk.getSelectedItemForWishOldSkin(sProductCode, $('[id^="product_option_id"]'));

            if (sItemCode !== false) {
                frm.append(getInputHidden('selected_item_by_etype[]', $.toJSON(sItemCode)));
                //sSelectedItemByEtype += 'selected_item_by_etype[]='+$.toJSON(sItemCode) + '&';
                aItemCode.push (sItemCode);
            }

        } else {
            $('.soldout_option_box_id,.option_box_id').each(function(i) {
                sItemCode = Olnk.getSelectedItemForWish(sProductCode, $(this));
                if (sItemCode.bCheckNum === false) {
                    sItemCode = Olnk.getProductAllSelected(sProductCode ,  $(this) , 1);
                }
                frm.append(getInputHidden('selected_item_by_etype[]', $.toJSON(sItemCode)));
                //sSelectedItemByEtype += 'selected_item_by_etype[]='+$.toJSON(sItemCode) + '&';
                aItemCode.push (sItemCode);
            });

            // 전부 선택인 경우 필요값 생성한다.
            if ( sSelectedItemByEtype === '') {
                iQuantity = (buy_unit >= product_min ? buy_unit : product_min);
                aItemValueNo = Olnk.getProductAllSelected(sProductCode , $('[id^="product_option_id"]') , 1);
                if ( aItemValueNo !== false ) {
                    frm.append(getInputHidden('selected_item_by_etype[]', $.toJSON(aItemValueNo)));
                    //sSelectedItemByEtype += 'selected_item_by_etype[]='+$.toJSON(aItemValueNo) + '&';
                    aItemCode.push (aItemValueNo);
                }
            }

            NEWPRD_ADD_OPTION.setItemAddOptionName(frm);
            $('.option_box_id').each(function(i) {

                iQuantity = $('#' + $(this).attr('id').replace('id','quantity')).val();
                _aItemValueNo = Olnk.getSelectedItemForBasket(sProductCode, $(this), iQuantity);

                if (_aItemValueNo.bCheckNum === false) { // 옵션박스는 있지만 값이 선택이 안된경우
                    _aItemValueNo = Olnk.getProductAllSelected(sProductCode , $(this) , iQuantity);
                }

                var oItem = $('[name="item_code[]"]:eq('+i+')');
                var sItemCode = oItem.val();

                //품목별 추가옵션 셋팅
                var sItemAddOption = NEWPRD_ADD_OPTION.getAddOptionValue(oItem.attr('data-item-add-option'));
                NEWPRD_ADD_OPTION.setItemAddOption(sProductCode + '000A_' + i , sItemAddOption, frm);
            });


        }

        if (bIsUseOptionSelect !== true && (/^\*+$/.test(aItemCode) === true  || aItemCode == '')) {
            alert(required_msg);
            return false;
        }
    } else {
        if (isNewProductSkin() === true) {
            //품목별 추가옵션 이름 셋팅
            NEWPRD_ADD_OPTION.setItemAddOptionName(frm);

            $('[name="quantity_opt[]"][id^="option_box"]').each(function(i) {

                var oItem = $('[name="item_code[]"]:eq('+i+')');
                var sItemCode = oItem.val();

                //품목별 추가옵션 셋팅
                var sItemAddOption = NEWPRD_ADD_OPTION.getAddOptionValue(oItem.attr('data-item-add-option'));
                NEWPRD_ADD_OPTION.setItemAddOption(sItemCode, sItemAddOption, frm);
            });
        }
    }

    if (aItemCode === false && bIsUseOptionSelect !== true) {
        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.setLayer(iProductNo, iCategoryNo, 'normal') === true) {
            return;
        }
        alert(required_msg);
        return false;
    }


    if (aItemCode !== null) {
        var sItemCode = '';
        var aTemp = [];

        if (Olnk.isLinkageType(sOptionType) === true) {
            frm.append(getInputHidden('selected_item[]', '000A'));
            //sParam = sParam + '&' + 'selected_item[]=000A&' + sSelectedItemByEtype;
        } else {
            for (var x in aItemCode) {
                try {
                    var opt_id = aItemCode[x].substr(aItemCode[x].length-4, aItemCode[x].length);
                    frm.append(getInputHidden('selected_item[]', opt_id));
                    //aTemp.push('selected_item[]='+opt_id);
                }catch(e) {}
            }
        }
    }

    if (typeof(iProductNo) !== undefined && iProductNo !== '' && iProductNo !== null) {
        frm.append(getInputHidden('product_no', iProductNo));
    }
    frm.append(getInputHidden('option_type', sOptionType));
    //sParam = sParam + '&product_no='+iProductNo;


    // 추가 옵션 체크 (품목기반 추가옵션일때는 폼제출때 검증 불필요)
    //뉴모듈사용시에는 체크안함
    if (bIsUseOptionSelect !== true && (NEWPRD_ADD_OPTION.isItemBasedAddOptionType() !== true && checkAddOption() === false)) {
        return false;
    }

    // 추가옵션
    var aAddOptionStr = new Array();
    var aAddOptionRow = new Array();
    if (add_option_name) {
        for (var i=0; i<add_option_name.length; i++) {
            if (add_option_name[i] != '') {
                aAddOptionRow.push(add_option_name[i] + '*' + $('#' + add_option_id + i).val());
            }
        }
    }
    aAddOptionStr.push(aAddOptionRow);

    frm.append(getInputHidden('add_option', aAddOptionStr.join('|')));
    //sParam += '&add_option=' + encodeURIComponent(aAddOptionStr.join('|'));

    // 파일첨부 옵션 유효성 체크
    if (bIsUseOptionSelect !== true && FileOptionManager.checkValidation() === false) return;

    bWishlistSave = true;

    // 파일첨부 옵션의 파일업로드가 없을 경우 바로 관심상품 넣기
    if (FileOptionManager.existsFileUpload() === false) {
        sParam = sParam + '&' + frm.serialize();
        add_wishlist_request(sParam, sMode);
    // 파일첨부 옵션의 파일업로드가 있으면
    } else{
        FileOptionManager.upload(function(mResult){
            // 파일업로드 실패
            if (mResult===false) {
                bWishlistSave = false;
                return false;
            }

            // 파일업로드 성공
            for (var sId in mResult) {
                frm.append(getInputHidden(sId, FileOptionManager.encode(mResult[sId])));
                //sParam += '&'+sId+'='+FileOptionManager.encode(mResult[sId]);
            }

            sParam = sParam + '&' + frm.serialize();
            add_wishlist_request(sParam, sMode);
        });
    }
}

function add_wishlist_request(sParam, sMode)
{
    var sUrl = '/exec/front/Product/Wishlist/';

    $.post(
        sUrl,
        sParam,
        function(data) {
            if (sMode != 'back') {
                add_wishlist_result(data);
            }
            bWishlistSave = false;
        },
        'json');
}

function add_wishlist_result(aData, aPrdData)
{
    var oTarget = CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame();
    var agent = navigator.userAgent.toLowerCase();

    if (aData == null) return;
    //새로운 모듈 사용시에는 중복되어있어도 처리된것으로 간주함.. 왜 그렇게하는지는 이해불가
    if (aData.result == 'SUCCESS' || (aData.bIsUseOptionSelect === true && aData.result === 'NO_TARGET')) {

        bBuyLayer = ITEM.setBodyOverFlow(true);

        if (typeof iProductNo !== 'undefined') {
            var iSendProductNo = iProductNo;
        } else if (typeof aPrdData !== 'undefined') {
            var iSendProductNo = aPrdData.product_no;
        }

        if (iSendProductNo) {
            EC_PlusAppBridge.addWishList(iSendProductNo);
        }

        if (CAPP_ASYNC_METHODS.hasOwnProperty('WishList') === true && typeof iProductNo !== 'undefined') {
            // 관심상품 추가시 sessionStorage 추가
            CAPP_ASYNC_METHODS.WishList.setSessionStorageItem(iProductNo, aData.command);
        }

        if (CAPP_ASYNC_METHODS.hasOwnProperty('Wishcount') === true) {
            CAPP_ASYNC_METHODS.Wishcount.restoreCache();
            CAPP_ASYNC_METHODS.Wishcount.execute();
        }

        if (aData.confirm == 'T') {
            layer_wishlist(CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame());
            return;
        }

        alert(__('관심상품으로 등록되었습니다.'));
    } else if (aData.result == 'ERROR') {
        alert(__('실패하였습니다.'));
    } else if (aData.result == 'NOT_LOGIN') {
        alert(__('회원 로그인 후 이용하실 수 있습니다.'));
    } else if (aData.result == 'INVALID_REQUEST') {
        alert(__('파라미터가 잘못되었습니다.'));
    } else if (aData.result == 'NO_TARGET') {
        alert(__('이미 등록되어 있습니다.'));
    } else if (aData.result == 'INVALID_PRODUCT') {
        alert(__('파라미터가 잘못되었습니다.'));
    }
}

/**
* 추가된 함수
* 해당 value값을 받아 replace 처리
* @param string sValue value
* @return string replace된 sValue
*/
function replaceCheck(sName,sValue)
{
   //ECHOSTING-9736
   if (typeof(sValue) == "string" && (sName == "option_add[]" || sName.indexOf("item_option_add") === 0)) {
        sValue = sValue.replace(/'/g,  '\\&#039;');
   }
   // 타입이 string 일때 연산시 단일 따움표 " ' " 문자를 " ` " 액센트 문자로 치환하여 깨짐을 방지
   return sValue;
}


/**
 * name, value값을 받아 input hidden 태그 반환
 *
 * @param string sName name
 * @param string sValue value
 * @return string input hidden 태그
 */
function getInputHidden(sName, sValue)
{
    sValue = replaceCheck(sName,sValue); // 추가된 부분 (replaceCheck 함수 호출)
    return $('<input>').attr({'type':'hidden', 'name':sName}).val(sValue);
}


/**
 * 필수옵션이 선택되었는지 체크
 *
 * @return bool 필수옵션이 선택되었다면 true, 아니면 false 반환
 */
function checkOptionRequired(sReq)
{
    var bResult = true;
    // 옵션이 없다면 필수값 체크는 필요없음.
    if (has_option === 'F') {
        return bResult;
    }
    var sTargetOptionId = product_option_id;
    if (sReq != null) {
        sTargetOptionId = sReq;
    }

    if (option_type === 'F') {
        // 단독구성
        var iOptionCount = $('select[id^="' + sTargetOptionId + '"][required="true"]').length;
        if (iOptionCount > 0) {
            if (ITEM.getItemCode() === false) {
                bResult = false;
                return false;
            }

            var aRequiredOption = new Object();
            var aItemCodeList = ITEM.getItemCode();
            // 필수 옵션정보와 선택한 옵션 정보 비교
            for (var i=0; i<aItemCodeList.length; i++) {
                var sTargetItemCode =  aItemCodeList[i];
                $('select[id^="' + sTargetOptionId + '"][required="true"] option').each(function() {
                    if ($(this).val() == sTargetItemCode) {
                        var sProductOptionId = $(this).parent().attr('id');
                        aRequiredOption[sProductOptionId] = true;
                    }
                });

            }
            // 필수옵션별 개수보다 선택한 옵션개수가 적을경우 리턴
            if (iOptionCount > Object.size(aRequiredOption)) {
                bResult = false;
                return bResult;
            }
        }
    } else {
        if (Olnk.isLinkageType(sOptionType) === true) {
            if (isNewProductSkin() === false) {
                $('select[id^="' + product_option_id + '"][required="true"]').each(function() {
                    var sel = parseInt($(this).val());

                    if (isNaN(sel) === true) {
                        $(this).focus();
                        bResult = false;
                        return false;
                    }
                });
                // 추가 구매 check
                $('.' + $.data(document, 'multiple_option_select_class')).each(function(i)
                {
                    if (Boolean($(this).attr('required')) === true) {
                        var sel = parseInt($(this).val());

                        if (isNaN(sel) === true) {
                            $(this).focus();
                            bResult = false;
                            return false;
                        }
                    }
                });
            } else { // 연동형 사용중이면서 뉴스킨
                var aItemCodeList = ITEM.getItemCode();
                if (aItemCodeList === false) {
                    bResult = false;
                    return false;
                }
                // 연동형 옵션의 버튼 사용중이지만 선택된 품목이 없는 경우 , 뉴스킨에서만 동작해야 함.
                if ( Olnk.getOptionPushbutton($('#option_push_button')) === true  && $('.option_box_id').length === 0 ) {
                    bResult = false;
                    return false;
                }
            }
            return bResult;
        }
        if (ITEM.getItemCode() === false) {
            bResult = false;
            return false;
        }
        // 조합구성
        if (item_listing_type == 'S') {
            // 분리선택형
            var eTarget = $.parseJSON(option_value_mapper);
            for (var x in eTarget) {
                if (ITEM.getItemCode().indexOf(eTarget[x]) > -1) {
                    bResult = true;
                    break;
                } else {
                    bResult = false;
                }
            }
            if (bResult === false) {
                bResult = false;
                return false;
            }
        } else {
            $('select[id^="' + product_option_id + '"][required="true"]').each(function() {
                var eTarget = $(this).find('option[value!="*"][value!="**"]');
                bResult = false;
                eTarget.each(function() {
                    if (ITEM.getItemCode().indexOf($(this).val()) > -1) {
                        bResult = true;
                        return false;
                    }
                });
                if (bResult === false) {
                    return false;
                }
            });
        }
    }

    return bResult;
}

/**
 * 추가 옵션 입력값 체크
 *
 * @return bool 모든 추가옵션에 값이 입력되었다면 true, 아니면 false
 *
 */
/**
 * 추가 입력 옵션의 값 체크
 * @param string sReq 셀렉터를 기본값 이외로 사용할 경우
 * @param object oParent 전체 인풋이 아닌 특정 객체 하위의 엘리먼트만 검사할경우
 * @returns {boolean}
 */
function checkAddOption(sReq, oParent)
{
    var sAddOptionField = add_option_id;

    var sAddOptionSelector = '[id^="' + sAddOptionField + '"]';
    if (sReq != null) {
        sAddOptionField = sReq;
        sAddOptionSelector = '[id="' + sAddOptionField + '"]';
    }
    var oTargetElement = $(sAddOptionSelector);
    if (oParent !== null && typeof(oParent) !== 'undefined') {
        oTargetElement = oParent.find(sAddOptionSelector);
    }

    var bResult = true;
    oTargetElement.filter(':visible').each(function()
    {
        if ($(this).attr('require') !== false && $(this).attr('require') == 'T') {
            if ($(this).val().replace(/^[\s]+|[\s]+$/g, '').length == 0) {
                alert(__('추가 옵션을 입력해주세요.'));
                $(this).focus();
                bResult = false;
                return false;
            }
        }
    });

    return bResult;
}

/**
 * 수량 가져오기
 *
 * @return mixed 정상적인 수량이면 수량(integer) 반환, 아니면 false 반환
 */
function getQuantity()
{
    // 뉴상품인데 디자인이 수정안됐을 수 있다.
    if (isNewProductSkin() === false) {
        iQuantity = parseInt($(quantity_id).val(),10);
    } else {
        if (has_option == 'T') {
            var iQuantity = 0;

            if (Olnk.isLinkageType(sOptionType) === true) {
                iQuantity = parseInt($(quantity_id).val(),10);
                return iQuantity;
            }

            $('[name="quantity_opt[]"]').each(function() {
                iQuantity = iQuantity + parseInt($(this).val(),10);
            });
        } else {
            var iQuantity = parseInt($(quantity_id).val().replace(/^[\s]+|[\s]+$/g,'').match(/[\d\-]+/),10);
            if (isNaN(iQuantity) === true || $(quantity_id).val() == '' || $(quantity_id).val().indexOf('.') > 0) {
                return false;
            }
        }

    }

    return iQuantity;
}

/**
 * 수량 체크
 *
 * @return mixed 올바른 수량이면 수량을, 아니면 false
 */
function checkQuantity()
{
    // 수량 가져오기
    var iQuantity = getQuantity();

    if (isNewProductSkin() === false) {
        if (iQuantity === false) return false;

        // 구스킨의 옵션 추가인 경우 수량을 모두 합쳐야 함..하는수 없이 each추가
        // 재고 관련도 여기서 하나?
        if (Olnk.isLinkageType(option_type) === true) {
            var sOptionIdTmp = '';
            $('select[id^="' + product_option_id + '"]').each(function() {
                if (/^\*+$/.test($(this).val()) === false ) {
                    sOptionIdTmp = $(this).val();
                    return false;
                }
            });

            $('.EC_MultipleOption').each(function(i){
                iQuantity +=  parseInt($(this).find('.' + $.data(document,'multiple_option_quantity_class')).val(),10);
            });

            if ( Olnk.getStockValidate(sOptionIdTmp , iQuantity) === true ) {
                alert(__('상품의 수량이 재고수량 보다 많습니다.'));
                $(quantity_id).focus();
                return false;
            }
        }

        if (iQuantity < product_min) {
            alert(sprintf(__('최소 주문수량은 %s개 입니다.'), product_min));
            $(quantity_id).focus();
            return false;
        }
        if (iQuantity > product_max && product_max > 0) {
            alert(sprintf(__('최대 주문수량은 %s개 입니다.'), product_max));
            $(quantity_id).focus();
            return false;
        }

    } else {
        var bResult = true;
        var bSaleMainProduct = false;
        var aQuantity = new Array();
        var iTotalOuantity = 0;
        var iProductMin = product_min;
        var iProductMax = product_max;
        $('#totalProducts > table > tbody').not('.add_products').find('[name="quantity_opt[]"]').each(function() {
            // 본상품 구매여부
            bSaleMainProduct = true;
            iQuantity = parseInt($(this).val());

            var iProductNum = iProductNo;
            // 추가 구성상품인 경우 product_min ,  product_max 값은 다른값을 비교해야 함..
            if ($(this).attr('id').indexOf('add_') > -1) {
                iProductMin = $('#'+$(this).attr('id').replace('quantity','productmin')).val();
                iProductMax = $('#'+$(this).attr('id').replace('quantity','productmax')).val();
                var iProductNum = $('#'+$(this).attr('id').replace('quantity','id')).attr('class').replace('option_add_box_','');
            }
            if (typeof(aQuantity[iProductNum]) === 'undefined') {
                aQuantity[iProductNum] = new Array();
            }
            aQuantity[iProductNum].push(iQuantity);

            // 상품기준의 경우 품목 총합으로 판단
            if (order_limit_type !== 'P') {
                if (iQuantity < iProductMin) {
                    alert(sprintf(__('상품별 최소 주문수량은 %s 입니다.'), iProductMin));
                    $(quantity_id).focus();
                    bResult = false;
                    return false;
                }
                if (iQuantity > iProductMax && iProductMax > 0) {
                    alert(sprintf(__('상품별 최대 주문수량은 %s 입니다.'), iProductMax));
                    $(quantity_id).focus();
                    bResult = false;
                    return false;
                }
            }
            iTotalOuantity = iTotalOuantity + iQuantity;
        });

        if (bResult == false) {
            return bResult;
        }
        if (typeof(EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE) === 'object') {
            for (var iProductNum in aQuantity) {
                if (aQuantity.hasOwnProperty(iProductNum) === false) {
                    continue;
                }
                if (EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig.hasOwnProperty(iProductNum) === false) {
                    continue;
                }

                if (EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.isValidQuantity(aQuantity[iProductNum], iProductNum) === false) {
                    return false;
                }
            }
        }
        // 본상품 없이 구매가능하기때문에 본상품있을떄만 체크
        if (bSaleMainProduct === true) {
            if (order_limit_type === 'P') {
                if (iTotalOuantity < iProductMin) {
                    alert(sprintf(__('최소 주문수량은 %s개 입니다.'), iProductMin));
                    bResult = false;
                    return false;
                }
                if (iTotalOuantity > iProductMax && iProductMax > 0) {
                    alert(sprintf(__('최대 주문수량은 %s개 입니다.'), iProductMax));
                    bResult = false;
                    return false;
                }
            }
            if (buy_unit_type === 'P') {
                if (iTotalOuantity % parseInt(buy_unit, 10) !== 0) {
                    alert(sprintf(__('구매 주문단위는 %s개 입니다.'), parseInt(buy_unit, 10)));
                    bResult = false;
                    return false;
                }
            }
        }
        if ($('.add_products').find('[name="quantity_opt[]"]').length > 0) {
            var aTotalQuantity = {};
            $('.add_products').find('[name="quantity_opt[]"]').each(function () {
                    iQuantity = parseInt($(this).val());
                    if (typeof(aTotalQuantity[$(this).attr('product-no')]) === 'undefined' || aTotalQuantity[$(this).attr('product-no')] < 1) {
                        aTotalQuantity[$(this).attr('product-no')] = 0;
                    }
                    aTotalQuantity[$(this).attr('product-no')] += parseInt($(this).val(), 10);

                }
            );

            for (var iProductNo in aTotalQuantity) {
                var aProductQuantityInfo = ProductAdd.getProductQuantityInfo(iProductNo);

                if (aProductQuantityInfo.order_limit_type === 'P') {
                    if (aTotalQuantity[iProductNo] < aProductQuantityInfo.product_min) {
                        alert(sprintf(__('최소 주문수량은 %s개 입니다.'), aProductQuantityInfo.product_min));
                        bResult = false;
                        return false;
                    }
                    if (aTotalQuantity[iProductNo] > aProductQuantityInfo.product_max && aProductQuantityInfo.product_max > 0) {
                        alert(sprintf(__('최대 주문수량은 %s개 입니다.'), aProductQuantityInfo.product_max));
                        bResult = false;
                        return false;
                    }
                }
                if (aProductQuantityInfo.buy_unit_type === 'P') {
                    if (aTotalQuantity[iProductNo] % parseInt(aProductQuantityInfo.buy_unit, 10) !== 0) {
                        alert(sprintf(__('구매주문단위는 %s개 입니다.'), parseInt(aProductQuantityInfo.buy_unit, 10)));
                        bResult = false;
                        return false;
                    }
                }
            }
        }
        if (bResult == false) {
            return bResult;
        }
    }

    return iQuantity;
}

function commify(n)
{
    var reg = /(^[+-]?\d+)(\d{3})/; // 정규식
    n += ''; // 숫자를 문자열로 변환
    while (reg.test(n)) {
        n = n.replace(reg, '$1' + ',' + '$2');
    }
    return n;
}

var isClose = 'T';
function optionPreview(obj, sAction, sProductNo, closeType)
{
    var sPreviewId = 'btn_preview_';
    var sUrl = '/product/option_preview.html';
    var layerId = $('#opt_preview_' + sAction + '_' + sProductNo);

    // layerId = action명 + product_no 로 이루어짐 (한 페이지에 다른 종류의 상품리스트가 노출될때 구분 필요)
    if ($(layerId).length > 0) {
        $(layerId).show();
    } else if (sProductNo != '') {
        $.post(sUrl, 'product_no=' + sProductNo + '&action=' + sAction, function(result)
        {
            $(obj).after(result.replace(/[<]script( [^ ]+)? src=\"[^>]*>([\s\S]*?)[<]\/script>/g,""));
        });
    }
}

function closeOptionPreview(sAction, sProductNo)
{
    isClose = 'T';
    setTimeout("checkOptionPreview('" + sAction + "','" + sProductNo + "')", 150);
}

function checkOptionPreview(sAction, sProductNo)
{
    var layerId = $('#opt_preview_' + sAction + '_' + sProductNo);
    if (isClose == 'T') $(layerId).hide();
}

function openOptionPreview(sAction, sProductNo)
{
    isClose = 'F';
    var layerId = $('#opt_preview_' + sAction + '_' + sProductNo);
    $(layerId).show();

    $(layerId).mousemouseenter(function()
    {
        $(layerId).show();
    }).mouseleave(function()
    {
        $(layerId).hide();
    });

}

/**
 * 네이버 페이 주문하기
 */
function nv_add_basket_1_product()
{
    bIsMobile = false;

    if (_isProc == 'F') {
        alert(__("네이버 페이 입점상태를 확인하십시오."));
        return;
    }

    if (typeof(set_option_data) != 'undefined') {
        alert(__('세트상품은 네이버 페이 구매가 불가하오니, 쇼핑몰 바로구매를 이용해주세요. 감사합니다.'));
        return;
    }

    product_submit('naver_checkout', '/exec/front/order/basket/');
}

/**
 * 네이버 페이 찜하기
 */
function nv_add_basket_2_product()
{
    if (_isProc == 'F') {
        alert(__("네이버 페이 입점상태를 확인하십시오."));
        return;
    }

    window.open("/exec/front/order/navercheckoutwish?product_no=" + iProductNo, "navercheckout_basket",
            'scrollbars=yes,status=no,toolbar=no,width=450,height=300');
}

/**
 * 네이버 페이 주문하기
 */
function nv_add_basket_1_m_product()
{
    bIsMobile = true;

    if (_isProc == 'F') {
        alert(__("네이버 페이 입점상태를 확인하십시오."));
        return;
    }

    if (typeof(set_option_data) != 'undefined') {
        alert(__('세트상품은 네이버 페이 구매가 불가하오니, 쇼핑몰 바로구매를 이용해주세요. 감사합니다.'));
        return;
    }

    product_submit('naver_checkout', '/exec/front/order/basket/');
}

/**
 * 네이버 페이 찜하기
 */
function nv_add_basket_2_m_product()
{
    if (_isProc == 'F') {
        alert(__("네이버 페이 입점상태를 확인하십시오."));
        return;
    }

    window.location.href = "/exec/front/order/navercheckoutwish?product_no=" + iProductNo;
    //window.open("/exec/front/order/navercheckoutwish?product_no=" + iProductNo, "navercheckout_basket", 'scrollbars=yes,status=no,toolbar=no,width=450,height=300');
}

/**
 * 옵션 추가 구매시에 같은 옵션을 검사하는 함수
 *
 * @returns Boolean
 */
function duplicateOptionCheck()
{
    var bOptionDuplicate = getOptionDuplicate();
    //var bAddOptionDuplicate = getAddOptionDuplicate();

    if (bOptionDuplicate !== true  ){ //}&& bAddOptionDuplicate !== true) {
        alert(__('동일한 옵션의 상품이 있습니다.'));
        return false;
    }

    return true;
}

/**
 * 텍스트 인풋 옵션 중복 체크
 *
 * @returns {Boolean}
 */
function getAddOptionDuplicate()
{
    var aOptionRow = new Array();
    var iOptionLength = 0;
    var aOptionValue = new Array();
    var bReturn = true;
    // 기본 옵션
    $('[id^="' + add_option_id + '"]').each(function()
    {
        aOptionRow.push($(this).val());
    });
    aOptionValue.push(aOptionRow.join(',@,'));
    $('.EC_MultipleOption').each(function()
    {
        aOptionRow = new Array();
        $($(this).find('.' + $.data(document, 'multiple_option_input_class'))).each(function()
        {
            aOptionRow.push($(this).val());
        });
        var sOptionRow = aOptionRow.join(',@,');
        if ($.inArray(sOptionRow, aOptionValue) > -1) {
            bReturn = false;
            return false;
        } else {
            aOptionValue.push(sOptionRow);
        }
    });
    return bReturn;
}
/**
 * 일반 셀렉트박스형 옵션 체크 함수
 *
 * @returns {Boolean}
 */
function getOptionDuplicate() {
    // 선택여부는 이미 선택이 되어 있음
    var aOptionId = new Array();
    var aOptionValue = new Array();
    var aOptionRow = new Array();
    var iOptionLength = 0;
    // 기본 옵션
    $('select[id^="' + product_option_id + '"]').each(function (i) {
        aOptionValue.push($(this).val());
        iOptionLength++;
    });
    // 추가 구매
    $('.' + $.data(document, 'multiple_option_select_class')).each(function (i) {
        aOptionValue.push($(this).val());
    });

    var aOptionRow = new Array();
    for (var x in aOptionValue) {
        var sOptionValue = aOptionValue[x];
        aOptionRow.push(sOptionValue);
        if (x % iOptionLength == iOptionLength - 1) {
            var sOptionId = aOptionRow.join('-');

            if ($.inArray(sOptionId, aOptionId) > -1) {
                return false;
            }
            aOptionId.push(sOptionId);
            aOptionRow = new Array();
        }
    }

    return true;
}

//sms 재입고
function action_sms_restock(sParam)
{
    // 모바일 접속 및 레이어 팝업 여부 확인
    if (typeof(EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER) !== 'undefined') {
        if (EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER.createSmsRestockLayerDisplayResult(sParam) === true) {
            return;
        }
    }

    window.open('#none', 'sms_restock' ,'width=459, height=490, scrollbars=yes');
    $('#frm_image_zoom').attr('target', 'sms_restock');
    $('#frm_image_zoom').attr('action', '/product/sms_restock.html');
    $('#frm_image_zoom').submit();
}

//email 재입고
function action_email_restock(iProductNo)
{
    if (typeof(iProductNo) === 'undefined') {
        iProductNo = '';
    }
    if ((window.navigator.standalone || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)) === true) {
        window.open('/product/email_restock.html?' + $('#frm_image_zoom').serialize(), 'email_restock' ,'width=459, height=490, scrollbars=yes');
    } else {
        window.open('#none', 'email_restock' ,'width=459, height=490, scrollbars=yes');
        $('#frm_image_zoom').attr('target', 'email_restock');
        $('#frm_image_zoom').attr('action', '/product/email_restock.html?product_no' + iProductNo);
        $('#frm_image_zoom').submit();
    }
}

// 최대 할인쿠폰 다운받기 팝업
function popupDcCoupon(product_no, coupon_no, cate_no, opener_url, location)
{
    var Url = '/';
    if ( location === 'Front' || typeof location === 'undefined') {
        Url += 'product/';
    }
    Url += '/coupon_popup.html';
    window.open(Url + "?product_no=" + product_no + "&coupon_no=" + coupon_no + "&cate_no=" + cate_no + "&opener_url=" + opener_url, "popupDcCoupon", "toolbar=no,scrollbars=no,resizable=yes,width=800,height=640,left=0,top=0");
}

/**
 * 관련상품 열고 닫기
 */
function ShowAndHideRelation()
{
    try {
        var sRelation = $('ul.mSetPrd').parent();
        var sRelationDisp = sRelation.css('display');
        if (sRelationDisp === 'none') {
            $('#setTitle').removeClass('show');
            sRelation.show();
        } else {
            $('#setTitle').addClass('show');
            sRelation.hide();
        }
    } catch(e) { }
 }

var ITEM = {
    getItemCode : function()
    {
        var chk_has_opt = '';
        try {
            chk_has_opt = has_option;
        }catch(e) {chk_has_opt = 'T';}

        if (chk_has_opt == 'F') {
            return [item_code];
        } else {
            // 필수값 체크
            var bRequire = false;
            // 옵션이 없음
            if ($('[id^="product_option_id"]').size() < 1) {
                return false;
            }
            $('[id^="product_option_id"]').each(function() {
                if (Boolean($(this).attr('required')) === true || $(this).attr('required') == 'required') {
                    bRequire = true;
                    return false;
                }
            });

            var aItemCode = new Array();
            if (bRequire === true) {
                if ($('#totalProducts').length === 0 || (typeof(EC_SHOP_FRONT_PRODUCT_FUNDING) === 'object' && EC_SHOP_FRONT_PRODUCT_FUNDING.isFundingProduct() === false)) {
                    sItemCode = this.getOldProductItemCode();
                    if (sItemCode !== false) {
                        if (typeof(sItemCode) === 'string') {
                            aItemCode.push(sItemCode);
                        } else {
                            aItemCode = sItemCode;
                        }
                    } else {
                        // 옵션이 선택되지 않음
                        return false;
                    }
                } else {
                    if ($('.option_box_id').length == 0) {
                        // 옵션이 선택되지 않음
                        return false;
                    }
                    $('.option_box_id').each(function() {
                        aItemCode.push($(this).val());
                    });
                }
            }


            return aItemCode;
        }
    },
    getWishItemCode : function()
    {
        var chk_has_opt = '';
        try {
            chk_has_opt = has_option;
        }catch(e) {chk_has_opt = 'T';}

        if (chk_has_opt == 'F') {
            return [item_code];
        } else {
            // 필수값 체크
            var bRequire = false;
            $('[id^="product_option_id"]').each(function() {
                if (Boolean($(this).attr('required')) === true || $(this).attr('required') == 'required') {
                    bRequire = true;
                    return false;
                }
            });

            var aItemCode = new Array();
            if (bRequire === true) {
                if ($('#totalProducts').length === 0) {
                    sItemCode = this.getOldProductItemCode();
                    if (sItemCode !== false) {
                        if (typeof(sItemCode) === 'string') {
                            aItemCode.push(sItemCode);
                        } else {
                            aItemCode = sItemCode;
                        }
                    } else {
                        // 옵션이 선택되지 않음
                        return false;
                    }
                } else {
                    if ($('.soldout_option_box_id,.option_box_id').length == 0) {
                        // 옵션이 선택되지 않음
                        return false;
                    }
                    $('.soldout_option_box_id,.option_box_id').each(function() {
                        aItemCode.push($(this).val());
                    });
                }
            }

            return aItemCode;
        }
    },
    getOldProductItemCode : function(sSelector)
    {
        if (sSelector === undefined) {
            sSelector = '[id^="product_option_id"]';
        }
        var sItemCode = null;
        // 뉴상품 옵션 선택 구매
        if (has_option === 'F') {
            // 화면에 있음
            sItemCode = item_code;
        } else {
            if (item_listing_type == 'S') {
                var aOptionValue = new Array();
                $(sSelector).each(function() {
                    if (ITEM.isOptionSelected($(this).val()) === true) {
                        aOptionValue.push($(this).val());
                    }
                });

                if (option_type === 'T') {
                    var aCodeMap = $.parseJSON(option_value_mapper);
                    sItemCode = aCodeMap[aOptionValue.join('#$%')];
                } else {
                    sItemCode = aOptionValue;
                }
            } else {
                sItemCode = $(sSelector).val();
            }
        }

        if (sItemCode === undefined) {
            return false;
        }

        return sItemCode;
    },
    isOptionSelected : function(aOption)
    {
        var sOptionValue = null;
        if (typeof aOption === 'string') {
            sOptionValue = aOption;
        } else {
            if (aOption.length === 0) return false;
            sOptionValue = aOption.join('-|');
        }

        sOptionValue = '-|'+sOptionValue+'-|';
        return !(/-\|\*{1,2}-\|/g).test(sOptionValue);
    },
    setBodyOverFlow : function(sType)
    {
        var sLocation =  location;
        var bBuyLayer = false;

        //var oReturnData = new Object();
        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isExistLayer(true) === true) {
            //parent.$('html, body').css('overflowY', 'auto');
            closeBuyLayer(false);
            sLocation =  parent.location;
            bBuyLayer = true;
        }

        //프레임으로 선언된 페이지일경우
        if (typeof(bIsOptionSelectFrame) !== 'undefined' && bIsOptionSelectFrame === true) {
            sLocation =  parent.location;
            bBuyLayer = true;
        }
        /*
        oReturnData['sLocation'] = sLocation;
        oReturnData['bBuyLayer'] = bBuyLayer;
        */

        oReturnData = sLocation;

        if (typeof(sType) === 'boolean') {
            oReturnData = bBuyLayer;
        }
        return oReturnData;
    }
};

var EC_SHOP_FRONT_PRODUCT_RESTOCK = (function() {

    return {
        isRestock : function(sType) {

            if (sType === 'sms_restock') {
                return true;
            }

            if (sType === 'email_restock') {
                return true;
            }

            return false;
        },
        openRestockEmailPopup : function()
        {
            product_submit('email_restock');
        },
        bindOpenRestockEmailPopup : function(product_no)
        {
            action_email_restock(product_no);

        }
    };
})();

//상세 장바구니 담기확인창에서 스크립트를 중목으로 볼러오는부분을 제거하기위해서 추가
//사용자 디자인에서도 basket.js에 있는 함수에 의존적이라서 추가가 안되어있다면 아래 함수들을 실행하도록 함
if (typeof(layer_basket_paging) !== 'function') {
  //레이어 장바구니 페이징
  function layer_basket_paging(page_no)
  {
      var sUrl = '/product/add_basket2.html?page=' + page_no + '&layerbasket=T';
      if (typeof(sBasketDelvType) !== 'undefined') {
          sUrl += sUrl + '&delvtype=' + sBasketDelvType;
      }
      $.get(sUrl, '', function(sHtml)
      {
          sHtml = sHtml.replace(/<script.*?ind-script\/optimizer.php.*?<\/script>/g, '');
          $('#confirmLayer').html(sHtml);
          $('#confirmLayer').show();

          // set delvtype to basket
          try {
              $(".xans-order-layerbasket").find("a[href='/order/basket.html']").attr("href", "/order/basket.html?delvtype=" + delvtype);
          } catch (e) {}
      });
  }
}

if (typeof(Basket) === 'undefined') {
  var Basket = {
      orderLayerAll : function(oElem) {
          var aParam = {basket_type:'all_buy'};
          var sOrderUrl = $(oElem).attr('link-order') || '/order/orderform.html?basket_type='+ aParam.basket_type;

          if (sBasketDelvType != "") {
              sOrderUrl += '&delvtype=' + sBasketDelvType;
          }
          var sLoginUrl = $(oElem).attr('link-login') || '/member/login.html';

          $.post('/exec/front/order/order/', aParam, function(data){
              if (data.result < 0) {
                  alert(data.alertMSG);
                  return;
              }

              if (data.isLogin == 'F') { // 비로그인 주문 > 로그인페이지로 이동
                  location.href = sLoginUrl + '?noMember=1&returnUrl=' + escape(sOrderUrl);
              } else {
                  location.href = sOrderUrl;
              }
          }, 'json');
      },

      isInProgressMigrationCartData : function(aData) {
          if (aData['isInProgressMigrationCartData'] === true) {
              alert(__('SYSTEM.IS.BUSY.PLEASE.TRY', 'SHOP.FRONT.BASKET.JS'));
              window.location.reload();
          }
      }

  };
}

/**
 * 장바구니 유효성 검증 validation
 */
var EC_SHOP_FRONT_BASKET_VALIID = {
    // 장바구니 상품 중복여부 확인
    isBasketProductDuplicateValid : function (sParam)
    {
        var bReturn = true;

        $.ajax({
            url:  '/exec/front/order/Basketduplicate/',
            type: 'post',
            data: sParam,
            async: false,
            dataType: 'json',
            success: function(data) {
                if (data.result === true) {
                    if (confirm(__('장바구니에 동일한 상품이 있습니다. ' + '\n' + '장바구니에 추가하시겠습니까?')) === false) {
                        bReturn = false;
                        return false;
                    }
                }
            }
        });

        return (bReturn === false) ? false : true;
    }
};

$(document).ready(function() {
    // 모바일 할인 적용 상품일 경우
    if (typeof(isMobileDcStatus) !== 'undefined' && isMobileDcStatus == 'F' ) {
        // 모바일 할인이 적용 되지 않는 상품일 경우 가려준다.
        try{ $('#span_product_price_mobile_p_line').hide(); $('#span_product_price_mobile_d_line').hide(); }catch(e){}
    }
    EC_SHOP_FRONT_QRCODE.init();

    EC_SHOP_FRONT_REGULAR_DELIVERY.init();
});

var EC_SHOP_FRONT_QRCODE = {
    init : function()
    {
        if (typeof(qrcode_class) !== 'string' || qrcode_class.length < 1) {
            return;
        }
        $('.'+qrcode_class).click(EC_SHOP_FRONT_QRCODE.bindUrlCopyButton);
    },
    bindUrlCopyButton : function()
    {
        var sTargetUrl = $('img.EC_QRCODE_URL_BUTTON-'+qrcode_class).attr('target-url');
        if (typeof(sTargetUrl) === 'undefined') {
            return;
        }
        if (typeof(window.clipboardData) === 'object') {
            window.clipboardData.setData('text', sTargetUrl);
        } else {
            $('<textarea id="qrcode_dummy">').css({'position':'absolute','top':'-1000px'}).appendTo('body').text(sTargetUrl).select();
            document.execCommand('copy');
        }
        alert(__('URL.ADDRESS.COPIED.CTRL', 'SHOP.JS.FRONT.NEW.PRODUCT.INFO'));
        $('textarea#qrcode_dummy').remove();
    }
};
/**
 * SNS 링크 정보
 * @param sMedia
 * @param iProductNo
 */
function SnsLinkAction(sMedia, iProductNo)
{
    EC_PlusAppBridge.shareSocialLink(sMedia, iProductNo);
    window.open(sSocialUrl + '?product_no=' + iProductNo + '&type=' + sMedia,sMedia);
}

/**
 * 상품 상세 페이지 이동
 * @param iProductNo 상품번호
 * @param iCategoryNo 카테고리 번호
 * @param iDisplayGroup 진열그룹
 * @param sLink URL정보
 */
function product_detail(iProductNo, iCategoryNo, iDisplayGroup, sLink)
{
    var sLink = sLink ? sLink : '/product/detail.html';
    sLink += '?product_no=' + iProductNo + '&cate_no=' + iCategoryNo + '&display_group=' + iDisplayGroup;

    try {
        opener.location.href = sLink;
    } catch (e) {
        location.href = sLink;
    }

    self.close();
}

/**
 * 추천메일보내기
 * @param product_no 상품번호
 * @param category_no 카테고리번호
 * @param display_group 진열그룹
 */
function recommend_mail_pop(product_no, category_no, display_group)
{
    option = "'toolbar=no," + "location=no," + "directories=no," + "status=no," + "menubar=no," + "scrollbars=yes," + "resizable=yes," + "width=576," + "height=568," + "top=300," + "left=200";

    filename = "/product/recommend_mail.html?product_no=" + product_no + "&category_no=" + category_no;
    filename += "&display_group=" + display_group;

    window.open(filename,"recommend_mail_pop",option);
}

/**
 * 상품조르기 팝업 호출
 * @param product_no 상품번호
 */
function request_pop(product_no)
{
    option = "'toolbar=no," + "location=no," + "directories=no," + "status=no," + "menubar=no," + "scrollbars=yes," + "resizable=yes," + "width=576," + "height=568," + "top=300," + "left=200";
    filename = "/product/request.html?product_no[]=" + product_no;

    window.open(filename,"request_pop",option);
}

//모바일 옵션선택레이어(옵션미선택후 구매하기/장바구니/관심상품버튼 클릭시) 후처리 모음...
var EC_SHOP_FRONT_PRODUCT_OPTIONLAYER = {
    bIsUseOptionLayer : false,
    bIsUseRegularDelivery : 'F',
    /**
     * 설정값 Set
     * @param bIsExec 강제실행여부
     * @param oCallBack 콜백함수(관심상품에서는 따로 fixedActionButton아이디값을 확인하지않고 실행되기떄문에 디자인확인하지 않고 바로 실행)
     */
    init : function(oCallBack)
    {
        //레이어가 사용가능한 상태인지 확인..

        //모바일이 아니라면 사용하지 않음
        if (EC_MOBILE !== true && EC_MOBILE_DEVICE !== true) {
            return;
        }

        //아이프레임 내에서는 레이어를 다시띄우지 않음
        if (CAPP_SHOP_FRONT_COMMON_UTIL.findTargetFrame() === parent) {
            return;
        }

        $.ajax({
            url : '/exec/front/Product/Moduleexist?section=product&file=layer_option&module=product_detail',
            dataType : 'json',
            success : function (data) {
                if (data.result === true) {
                    EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.bIsUseOptionLayer = true;
                    if (typeof oCallBack === 'function') {
                        oCallBack();
                    }
                }
            }
        });
    },

    /**
     * 레이어띄우기(기존 로직때문에 영향이 있어 레이어를 띄우지 못하는 상황이면 false로 리턴하는 로직도 같이..)
     * @param iProductNo 상품번호
     * @param iCategoryNo 카테고리 번호
     * @param sType 각 액션별 정의(일반상품-normal / 세트상품-set / 관심상품에서 호출-wishlist)
     */
    setLayer : function(iProductNo, iCategoryNo, sType)
    {
        var iCategoryNo = iCategoryNo || '';
        var iProductNo = iProductNo || '';

        //상품번호는 필수
        if (iProductNo === '') return false;

        //레이어 사용가능상태가 아니면 false로 바로 리턴
        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.bIsUseOptionLayer === false) {
            return false;
        }

        try {
            EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.createLayer(iProductNo, iCategoryNo, sType);
        } catch (e) {
            return false;
        }

        return true;
    },

    /**
     * 모듈이 존재하는지 확인후에 레이어 아이프레임 생성
     * @param iProductNo 상품번호
     * @param iCategoryNo 카테고리 번호
     * @param sType 각 액션별 정의(일반상품-normal / 세트상품-set / 관심상품에서 호출-wishlist)
     */
    createLayer : function(iProductNo, iCategoryNo, sType)
    {
        try {
            $('#opt_layer_window').remove();
        } catch (e) {}

        EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.setHTML(iProductNo, iCategoryNo, sType);

        // @see ECHOSTING-354154
        // 아이프레임으로 페이지를 로드할 때 스크립트의 실행 시간과 페이지 로드 완료 시점의 시간차가 발생하여
        // 옵션 및 금액 계산 관련 스크립트가 정상적으로 동작하지 않기 때문에 (display 속성 등)
        // 로드 전에 무조건 해당 페이지를 뿌려주고 opacity만 0으로 변경하여 정상 동작하도록 처리
        $('#opt_layer_window').show().css('opacity', 0);

        // 아이프레임이 로드된후에 parent 상세페이지의 옵션정보와 동기화
        $('#productOptionIframe').load(function() {
            $('#opt_layer_window').css('opacity', 100);

            // 구매버튼 높이
            var iActionHeight = $(this).contents().find('.xans-product-action').outerHeight();
            // 레이어 전체 높이
            var iTotalHeight = $(this).contents().find('#product_detail_option_layer').outerHeight();
            // 닫기버튼 높이
            var iCloseButtonHeight = $(this).contents().find('#product_detail_option_layer .btnClose').outerHeight();
            // 네이버체크아웃 버튼 높이
            var iNaverCheckOuterHeight = $(this).contents().find('#product_detail_option_layer #NaverChk_Button').outerHeight();

            // 구매버튼 + 닫기버튼을 제외한 영역의 높이가 200이 안된다면  최소높이값에 구매버튼 높이를 더해서 지정
            if (iTotalHeight - iActionHeight - iCloseButtonHeight < 200 + iCloseButtonHeight) {
                iTotalHeight = 200 + iCloseButtonHeight + iActionHeight;
            }

            // 체크아웃버튼이 있을경우 해당버튼 높이도 더함
            iTotalHeight += iNaverCheckOuterHeight;
            $(this).css('height', iTotalHeight);

            if (sType === 'normal') {
                // 일반상품 상세페이지와 레이어 동기화
                EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.setNormalInit();
            } else if (sType === 'set') {
                // 세트상품 상세페이지와 레이어 동기화
                EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.setSetInit();
            }

            if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.bIsUseRegularDelivery === 'T') {
                $(this).contents().find("#product_detail_option_layer #actionBuy").hide();
                $(this).contents().find("#product_detail_option_layer #btnRegularDelivery").removeClass('displaynone').show();
                $(this).contents().find("#product_detail_option_layer #btnRegularDelivery").append(
                    '<input id="is_subscriptionT" style="display: none" checked="checked" class="EC_regular_delivery" name="is_subscription" value="T" type="radio">'
                );
            }

            EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.showLayer();
        });
    },

    /**
     * 레이어노출시키기
     */
    showLayer : function()
    {
        //기존 고정된 위치에 나오던것을 스크롤에 따라 움직이도록 디자인변경 - setHTML() 참조
        var iTop = parseInt(($(window).height() - $("#productOptionIframe").height()) / 2);
        $("#opt_layer_iframe_parent").css({"top": iTop, "left": 0});
        $('#opt_layer_window').show();
    },

    /**
     * 레이어 HTML생성
     * @param iProductNo 상품번호
     * @param iCategoryNo 카테고리 번호
     * @param sType 각 액션별 정의(일반상품-normal / 세트상품-set / 관심상품에서 호출-wishlist)
     */
    setHTML : function(iProductNo, iCategoryNo, sType)
    {
        var sPrdOptUrl = "/product/layer_option.html?product_no="+iProductNo+'&cate_no='+iCategoryNo+'&bPrdOptLayer=T&bIsUseRegularDelivery=F';// + this.bIsUseRegularDelivery;
        if (sType === 'wishlist') {
            sPrdOptUrl += '&sActionType=' + sType;
        }
        var aPrdOptLayerHtml = [];

        aPrdOptLayerHtml.push('<div id="opt_layer_window">');
        aPrdOptLayerHtml.push('<div id="opt_layer_background" style="position:fixed; top:0; left:0; width:100%; height:100%; background:#000; opacity:0.3; filter:alpha(opacity=30); z-index:9994;"></div>');
        aPrdOptLayerHtml.push('<div id="opt_layer_iframe_parent" style="position:fixed; top:0; left:0; width:100%; z-index:9995;">');
        aPrdOptLayerHtml.push('<iframe src="'+sPrdOptUrl+'" id="productOptionIframe" style="width:100%; height:100%; border:0;"></iframe>');
        aPrdOptLayerHtml.push('</div>');
        aPrdOptLayerHtml.push('</div>');

        $('body').append(aPrdOptLayerHtml.join(''));
    },

    /**
     * 일반상품 담기시 레이어 동기화
     * 옵션선택레이어가 뜬후에 상세페이지에있던 옵션선택정보와 동기화하는듯
     */
    setNormalInit : function()
    {
        var sValue = '*';
        var oTarget = null;
        var oOptionIframe = '';

        if (Olnk.isLinkageType(option_type) === true) {
            $('select[id^="' + product_option_id + '"]').each(function() {
                sValue = EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSelectedValue(this);
                if (Olnk.getCheckValue(sValue,'') === true ) {
                    oTarget = $("#productOptionIframe")[0].contentWindow.$('#product_detail_option_layer #'+ $(this).attr('id')+'').val($(this).val()).trigger('change');
                    $("#productOptionIframe")[0].contentWindow.EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(oTarget, sValue);
                }
            });
        } else {
            $('select[id^="' + product_option_id + '"]').each(function() {
                var sSelectOptionId = $(this).attr('id');
                sValue = EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSelectedValue(this);
                oTarget = $("#productOptionIframe")[0].contentWindow.$('#product_detail_option_layer #'+sSelectOptionId+'');
                oOptionIframe = $("#productOptionIframe")[0].contentWindow.EC_SHOP_FRONT_NEW_OPTION_COMMON;

                if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isSeparateOption(oTarget) === true) {
                    oOptionIframe.setValue(oTarget, sValue, true, true);
                } else {
                    oOptionIframe.setValue(oTarget, sValue);
                }
            });
        }

        // 파일첨부 리스트 복사
        if ($('[name^="file_option"]').length > 0) {
            FileOptionManager.sync($('[name^="file_option"]').attr('id'), $("#productOptionIframe")[0].contentWindow.$('ul#ul_file_option'));
        }
    },

    /**
     * 세트상품 담기시 레이어 동기화
     * 옵션선택레이어가 뜬후에 상세페이지에있던 옵션선택정보와 동기화하는듯
     */
    setSetInit : function()
    {
        var iTotalOptCnt = $('[class*='+set_option.setproduct_require+']').length;
        var iOptionSeq = 0;
        $('[class*='+set_option.setproduct_require+']').each(function(i){
            if ($(this)[0].tagName == 'INPUT') {
                return;
            }
            var sSelectOptionId = $(this).attr('id');
            var sParentVal = EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSelectedValue(this);

            if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isOptionSelected(this) === true) {
                iOptionSeq = i + 2;
            }
            if (iTotalOptCnt >= iOptionSeq) {
                $("#productOptionIframe").contents().find('.'+set_option.setproduct_require+'_'+iOptionSeq).attr('disabled', false);
            }

            var oTarget = $("#productOptionIframe")[0].contentWindow.$('#product_detail_option_layer #'+sSelectOptionId+'');//.val(sParentVal).trigger('change');
            if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isSeparateOption(oTarget) === true) {
                $("#productOptionIframe")[0].contentWindow.EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(oTarget, sParentVal, true, true);
            } else {
                $("#productOptionIframe")[0].contentWindow.EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(oTarget, sParentVal);
            }

        });
    },

    /**
     * 옵션선택레이어가 존재하는지 여부(기존 비교 그대로)
     * @param bIsParent 부모Element에서 옵션레이어를 찾을 경우
     */
    isExistLayer : function(bIsParent)
    {
        if (EC_MOBILE !== true && EC_MOBILE_DEVICE !== true) {
            return false;
        }

        if (bIsParent === true) {
            return typeof(window.parent) == 'object' && parseInt(parent.$('#opt_layer_window').length) > 0;
        } else {
            return typeof($('#opt_layer_window')) == 'object' && parseInt($('#opt_layer_window').length) > 0;
        }
    },

    /**
     * 옵션선택 레이어가 display상태인지 여부
     * @param bIsParent 부모Element에서 옵션레이어를 찾을 경우
     */
    isDisplayLayer : function(bIsParent)
    {
        if (EC_MOBILE !== true && EC_MOBILE_DEVICE !== true) {
            return false;
        }

        if (bIsParent === true) {
            return typeof(bPrdOptLayer) !== 'undefined' && bPrdOptLayer === 'T' && parent.$('#opt_layer_window').css('display') === 'block';
        } else {
            return ($('#opt_layer_window').css('display') === 'none') ? false : true;
        }
    }
};

/**
 * 프론트 옵션 정보 관리
 */
var EC_SHOP_FRONT_PRODUCT_OPTION_INFO = {
    /**
     * 옵션 타입 리턴
     * @param int iProductNo 상품 번호
     * @return string 옵션 타입
     */
    getOptionType: function (oOptionChoose) {
        return EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionType(oOptionChoose);
    },

    /**
     * 옵션 리스팅 타입 리턴
     * @param int iProductNo 상품 번호
     * @return string 옵션 리스팅 타입
     */
    getItemListingType: function (oOptionChoose) {
        return EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionListingType(oOptionChoose);
    },

    /**
     * 전체 품목 재고 정보
     * @param int iProductNo 상품 번호
     * @return object 품목별 재고 정보 리스트
     */
    getAllItemStorkInfo: function (iProductNo) {
        return EC_SHOP_FRONT_NEW_OPTION_COMMON.getProductStockData(iProductNo);
    },

    /**
     * 옵션값으로 품목코드 구하여 리턴
     * @param int iProductNo 상품 번호
     * @param array aOptionValue 옵션값
     * @return string 품목코드
     */
    getItemCodeByOptionValue: function (iProductNo, aOptionValue) {
        var sOptionValue = aOptionValue.join("#$%");

        return EC_SHOP_FRONT_NEW_OPTION_DATA.getItemCode(iProductNo, sOptionValue);
    }
};

var EC_FRONT_NEW_PRODUCT_QUANTITY_VALID = {
    setBuyUnitQuantity : function(iBuyUnit, iProductMin, sBuyUnitType, sOrderLimitType, iItemCount, sType)
    {
        // 구매주문단위가 상품별의 경우 1씩 증가
        if (sBuyUnitType === 'P') {
            iBuyUnit = (iItemCount > 1) ? 1 : iBuyUnit;
            // 최초 셋팅되는 수량은 "상품"기준 구매단위 에서 "품목"기준 최소/최대 수량 =? 최소수량이 기본수량
            if (sType === 'base' && sOrderLimitType === 'O') {
                iBuyUnit = iProductMin;
            }
        }
        return iBuyUnit;
    },
    getBuyUnitQuantity : function(sType)
    {
        return this.setBuyUnitQuantity(parseInt(buy_unit,10), parseInt(product_min,10), buy_unit_type, order_limit_type, item_count, sType);
    },
    getSetBuyUnitQuantity : function(aProductInfo, sType) {

        return this.setBuyUnitQuantity(parseInt(aProductInfo.buy_unit,10), parseInt(aProductInfo.product_min,10), aProductInfo.buy_unit_type, aProductInfo.order_limit_type, aProductInfo.item_count, sType);
    },
    setProductMinQuantity : function(iBuyUnit, iProductMin, sBuyUnitType, sOrderLimitType, iItemCount)
    {
        if (isNewProductSkin() === true) {
            var iItemCount = typeof(iItemCount) === "undefined" ? 1: parseInt(iItemCount, 10);
            // 단품 or 품목이 1개인경우 품목-품목 기준으로 동작
            if (iItemCount > 1) {
                // 상품기준 단위 증차감 단위는 1
                if (sBuyUnitType === 'P' && sOrderLimitType === 'P') {
                    iProductMin = 1;
                    // "품목"기준 단위 이면서 최소/최대 "상품"기준의 경우 "품목"구매단위가 최소수량
                } else if (sOrderLimitType === 'P') {
                    iProductMin = iBuyUnit;
                }
            }
        } else {
            var iBuyUnit = parseInt(buy_unit, 10);
            iBuyUnit = iBuyUnit < 1 ? 1 : iBuyUnit;
            var iFactor = Math.ceil(iProductMin / iBuyUnit);
            iProductMin = iBuyUnit * iFactor;
        }

        return iProductMin;
    },
    getProductMinQuantity : function()
    {
        return this.setProductMinQuantity(parseInt(buy_unit,10), parseInt(product_min,10), buy_unit_type, order_limit_type, item_count);
    },
    getSetProductMinQuantity : function(aProductInfo)
    {
        return this.setProductMinQuantity(parseInt(aProductInfo.buy_unit,10), parseInt(aProductInfo.product_min,10), aProductInfo.buy_unit_type, aProductInfo.order_limit_type, aProductInfo.item_count);
    },
    getNumberValidate : function(e)
    {
        var keyCode = e.which;
        // Tab, Enter, Delete키 포함
        var isNumberPress = ((keyCode >= 48 && keyCode <= 57 && !e.shiftKey) // 숫자키
        || (keyCode >= 96 && keyCode <= 105) // 키패드
        || keyCode == 8 // BackSpace
        || keyCode == 9 // Tab
        || keyCode == 46); // Delete

        if (!isNumberPress) {
            e.preventDefault();
        }
    }
};

var EC_SHOP_FRONT_REGULAR_DELIVERY = {
    init : function()
    {
        $('#EC_cycle_count option:eq(0)').attr('disabled','disabled');

        $('.EC_regular_delivery').live('click', function() {
            EC_SHOP_FRONT_REGULAR_DELIVERY.changeBuyButton($(this).val());
            if (has_option === 'F') {
                setPrice(false, false, '');
            } else {
                if ($('#option_box1_quantity').length > 0) {
                    // 옵션선택 이후에는 option_id를 특정할 수 없음 수량선택박스 있으면 재계산
                    setOptionBoxQuantity('change', $('#option_box1_quantity'));
                }
            }
        });

        this.changeBuyButton($('.EC_regular_delivery:checked').val());

    },
    changeBuyButton : function(sUsedRegularDelivery)
    {

        if (typeof(EC_FRONT_JS_CONFIG_SHOP) === 'undefined') {
            return;
        }

        if (typeof(sUsedRegularDelivery) === 'undefined') {
            return;
        }

        if (EC_FRONT_JS_CONFIG_SHOP.bRegularConfig === false) {
            return;
        }

        if ($('#btnReserve').is(':visible') === true || $('#actionReserve').is(':visible') === true) {
            if (sUsedRegularDelivery === 'T') {
                $('#regular_cycle_period').removeClass('displaynone').show();
            } else {
                $('#regular_cycle_period').hide();
            }
            return;
        }

        var sActionButtonSelector = '#btnBuy, #actionBuy, #actionBuyClone, #actionBuyCloneFixed';
        var sActionButtonRegular = '#btnRegularDeliveryCloneFixed, #btnRegularDelivery, #regular_cycle_period';

        if (sUsedRegularDelivery === 'T') {
            $(sActionButtonSelector).hide();
            $(sActionButtonRegular).removeClass('displaynone').show();
            EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.bIsUseRegularDelivery = 'T';
        } else {
            $(sActionButtonSelector).show();
            $(sActionButtonRegular).hide();
            EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.bIsUseRegularDelivery = 'F';
        }
    }
};

if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function(elt /*, from*/) {
        var len  = this.length >>> 0;
        var from = Number(arguments[1]) || 0;

        from = (from < 0) ? Math.ceil(from) : Math.floor(from);
        if (from < 0) {
            from += len;
        }

        for (from; from < len; from++) {
            if (from in this && this[from] === elt) {
                return from;
            }
        }
        return -1;
    };
}

if (!Object.size) {
    Object.size = function(obj) {
        var size = 0, key;
        for (key in obj) {
            if (obj.hasOwnProperty(key)) size++;
        }
        return size;
    };
}

if (!Object.keys) Object.keys = function(o) {
    if (o !== Object(o))
    throw new TypeError('Object.keys called on a non-object');
    var k=[],p;
    for (p in o) if (Object.prototype.hasOwnProperty.call(o,p)) k.push(p);
    return k;
};

/**
 * 앱 할인
 * (앱에서 계산한 금액을 AppDiscount.setAppDiscountPrice() 호출
 */
// 요청 데이터
var oAppRequestData = {};
// 앱할인 적용 데이터
var oAppDiscountData = {};
var AppDiscount = {
    /**
     * Ajax Calc 호출
     */
    setAppDiscountPrice : function(aData)
    {
        // 앱할인 데이터
        if (aData !== undefined && aData !== null && aData !== {}) {
            var aAppData = JSON.parse(aData);
            oAppRequestData[aAppData['app_key']] = aAppData;
        }

        // 장바구니이고, 디스플레이모드가 3 (장바구니 할인금액 표시함) 일 때
        if (sPage == 'ORDER_BASKET' && sBasketDisplayMode == '3') {
            BasketAppDiscount.doAppDiscountCalculate(oAppRequestData);
        }
        // 주문서 일 때
        if (sPage == 'ORDER_ORDERFORM') {
            EC_SHOP_FRONT_ORDERFORM_APP.exec.setAppDiscount(oAppRequestData);
        }
    }
};
/**
 * 앱 콜백용 js(front)
 */
// 요청 데이터
var oAppRequestData = {};
// 응답 데이터
var oAppResponseData = {};

// 앱할인 적용 데이터
var oAppDiscountData = {};


var AppCallback = {
    /**
     * 주문서 > app으로부터 받은 주소를 배송지주소로 세팅
     *
     * country_code : 국가코드
     * zipcode : 우편번호
     * state : 주도
     * city : 시군도시
     * detail : 상세
     * disable_flag : 주소비활성화처리여부(T:비활성화처리 함, T가 아니면 활성화처리 안함)
     */
    setShippingAddress : function(aData)
    {
        // app으로부터 받은 배송지 주소정보
        var aShippingAddressInfo = JSON.parse(aData);

        //배송지 주소 세팅
        EC_SHOP_FRONT_ORDERFORM_DISPLAY.form.setShippingAddressDisplay(aShippingAddressInfo);

        // app으로부터 받은 배송지 주소정보 - 로그기록
        var sLogName = 'AppCallback.setShippingAddress()';
        EC_SHOP_FRONT_ORDERFORM_COMMON.logAjaxCall(aData, sLogName);
    },

    /**
     * EC_ORDER_ORDERFORM_CHANGE - shipping_company 이벤트 콜백
     * response
     * shipping_available_flag : 선택된 배송사로 배송가능여부 ( true / false)
     * message : 배송불가능한 경우 사유
     */
    setCustomShippingCompany : function(response)
    {
        //배송가능여부
        var shipping_available_flag = response.shipping_available_flag;

        //메세지
        var message = response.message;

        //이용가능 여부
        EC_SHOP_FRONT_ORDERFORM_DATA.shipping.aShippingCompanyEventResultByApp = response;

        // 우선순위
        // 1. EC체크
        // 2. app 체크
        if (EC_SHOP_FRONT_ORDERFORM_DATA.shipping.bIsAvailAreaViewInitFlag === false) { // 우선 체크된 EC 결과가 배송가능한 조건일 경우 실행 - EC 가 배송불가능한 경우에는 EC 체크로만 끝내도록

            // 앱에 의해 배송가능한 배송사로 판단 되었는지 여부를 저장하기 위한 값.
            EC_SHOP_FRONT_ORDERFORM_DATA.form.sIsAvailableShippingCompanyByApp = shipping_available_flag;
            //메세지
            if (shipping_available_flag === false) {
                if (message) {
                    //이용불가 사유
                    alert(message);
                }
                EC_SHOP_FRONT_ORDERFORM_DISPLAY.shipping.setCustomShippingFirst();
            }
        }

        return;
    },
   /**
     * 배송비 앱 callback 함수
     * 앱에서 전달받은 배송비 정보를 세팅
     * @param aResponse
     */
    setDeliveryAppInfo : function (aResponse)
    {
        if (typeof (aResponse.message) !== 'undefined') {
            EC_SHOP_FRONT_ORDERFORM_APP_DELIVERY.proc.setDeliveryAppInfo(aResponse);
        }
    },
    /**
     * 주문 가능여부 적용
     * EC_SHOP_FRONT_ORDERFORM_APP.exec.setAppOrderEnable (App.js)
     * @param oAppData : 앱사에서 보낸 주문 가능여부 데이터
     */
    setOrderEnable : function(oAppData) {
        try {
            $('#order_enable').val(JSON.stringify(oAppData));
        } catch (e) { }
    },
    /**
     * Ajax Calc 호출
     * AppDiscount.setAppDiscountPrice (AppDiscount.js)
     */
    setDiscountPrice : function(aData) {
        // 앱할인 데이터
        if (aData !== undefined && aData !== null && aData !== {}) {
            var aAppData = JSON.parse(aData);
            oAppRequestData[aAppData['app_key']] = aAppData;
        }

        // 장바구니이고, 디스플레이모드가 3 (장바구니 할인금액 표시함) 일 때
        if (sPage == 'ORDER_BASKET' && sBasketDisplayMode == '3') {
            BasketAppDiscount.doAppDiscountCalculate(oAppRequestData);
        }
        // 주문서 일 때
        if (sPage == 'ORDER_ORDERFORM') {
            EC_SHOP_FRONT_ORDERFORM_APP.exec.setAppDiscount(oAppRequestData);
        }
    }
};

$(document).ready(function()
{
    /**
     * JS 로딩후 셀렉트 박스 활성화
     * Jira : ECHOSTING-69324
     */
    $('select[class^="ProductOption"]').removeAttr('disabled');


    $('.btn_wishlist_add').click(function()
    {
        if (!check_wishlist())
            return;

        var str_param = 'command=add';
        $('input[name="basket_info[]"]:checkbox:checked').each(function()
        {
            str_param += '&product_no[]=' + $(this).val().split('|')[0];
        });

        action_wishlist(str_param);
    });

    $('.btn_wishlist_del').click(function()
    {
        if (confirm(__('정말 삭제하시겠습니까?'))) {
            var str_param = 'command=del';
            str_param += '&product_no[]=' + $(this).attr('rel');


            action_wishlist(str_param);
        }
    });

    // 추가입력옵션 글자 길이 체크
    try {
        $("input[class^='ProductAddOption'], input[class^='SetProductAddOption']").live('keyup', function () {
            var iLimit = $(this).attr('maxlength');
            addOptionWord($(this).attr('id'), $(this).val(), iLimit);
        });
    } catch (e) {}

    // 추가입력 옵션 ; 제거
    $('input[class^="SetProductAddOption"]').blur(function(){
        if ($(this).val().search(/;/) > -1){
            alert('특수문자 ;는 입력할 수 없습니다.');
            $(this).val($(this).val().replace(/;/g, ''));
        }
    });

    $('input[name="wish_idx[]"]:checkbox').click(function() {
        if ($(this).attr('is-set-product') !== 'F') {
            $('input[name="wish_idx[]"][is-set-product='+$(this).attr('is-set-product')+']:checkbox').attr('checked', $(this).attr('checked'));
        }
    });
});

var EC_Wishlist = {
    PrepareBasket : function(iProductNo, iCategoryNo, iDisplayGroup, sBasketType, iOrder, iQuantity, bBasket, sOptType, sDefaultOptId, sItemCode, sProductCode, sDelvtype)
    {
        aProductLink = new Array();
        aProductLink.push('product_no='+iProductNo);
        aProductLink.push('cate_no='+iCategoryNo);
        aProductLink.push('display_group='+iDisplayGroup);
        sProductLink = aProductLink.join('&');
        if (parseInt(iQuantity,10) < 1) {
            iQuantity = 1;
        }
        var aData = new Array();
        var bResult = true;

        this.iProductNo = iProductNo;
        var sOptionStr = new Array();

        $("select[id^='" + this.getOptionIdentifier() + "'].ProductOption" + iOrder).each(function(i)
        {
            if ($(this).attr('required')) {
                if ($.inArray($('option:selected',this).val(), ['*','**']) > -1) {
                    alert(__('필수 옵션을 선택해주세요.'));
                    $(this).focus();
                    bResult = false;
                    return false;
                }
            }
            var sOptionVal = $('option:selected',this).val().split('|')[0];
            aData.push('optionids[]='+$(this).attr('name'));
            if ($(this).attr('required') == true) {
                aData.push('needed[]='+$(this).attr('name'));
            }
            var iSelectedIndex = $(this).get(0).selectedIndex;
            if ($(this).attr('required') && iSelectedIndex > 0) iSelectedIndex -= 1;

            if (iSelectedIndex > 0) {
                aData.push($(this).attr('name')+'='+sOptionVal);
            }

        });
        if (bResult === false) return false;

        // 품절여부 확인
        if (this.isSoldout(iOrder)) {
            return false;
        }

        // 옵션 타입 받아오기
        var sOptionType = eval('sOptionType'+iProductNo);
        if (sOptionType !== '') {
            aData.push('option_type='+sOptionType);
        }

        var aProductMax =  eval('aProductMax'+iProductNo);
        if (aProductMax !== '') {
            aData.push('product_max='+aProductMax.product_max);
            aData.push('product_max_type='+aProductMax.product_max_type);
        }
        if (aData.length > 0) {
            var sOptionStr = aData.join('&');
        }

        var aAddOption = new Array();
        $('input.ProductAddOption' + iOrder).each(function()
        {
            var regex = /[\;\\]/g;
            if (regex.test($(this).val()) === true) {
                alert(__('특수문자는 입력할 수 없습니다.'));
                $(this).val($(this).val().replace(regex, ''));
                bResult = false;
                return false;
            }

            if ( $(this).attr('require') == 'T' ) {
                if ($(this).val().replace(/^[\s]+|[\s]+$/g,'').length == 0) {
                    alert(__('추가 옵션을 입력해주세요.'));
                    $(this).focus();
                    bResult = false;
                    return false;
                }
            }
            aData.push('option_add[]=' + $(this).val());
            aAddOption.push($(this).attr('optionname'));
        });

        if (bResult === false) return false;

        if (sItemCode == "" || typeof(sItemCode) == "undefined") {
            if (Olnk.isLinkageType(sOptType) === false) {
                var sItemCode = (EC_Wishlist.getItemCode(iOrder, iProductNo) == null) ? sDefaultOptId : EC_Wishlist.getItemCode(iOrder, iProductNo);
            } else {
                var sItemCode = sProductCode + '000A';
            }
        }

        aData.push('add_option_name=' + aAddOption.join(';'));
        aData.push('command=add');
        aData.push('quantity=' + iQuantity);
        aData.push('product_no=' + iProductNo);
        aData.push('main_cate_no=' + iCategoryNo);
        aData.push('display_group=' + iDisplayGroup);
        aData.push('basket_type=' + sBasketType);
        aData.push('delvtype=' + sDelvtype);

        if (sItemCode != null && $.isArray(sItemCode) === true) {
            for (var iKey=0; iKey<sItemCode.length; iKey++) {
                aData.push('selected_item[]=' + iQuantity + '||' + sItemCode[iKey]);
            }
        } else {
            aData.push('selected_item[]=' + iQuantity + '||' + sItemCode);
        }
        if (typeof (basket_page_flag) != 'undefined' && basket_page_flag == 'T') {
            aData.push('basket_page_flag=' + basket_page_flag);
        }

        if (Olnk.isLinkageType(sOptType) === true) {
            var aItemCode = Olnk.getSelectedItemForBasket(sProductCode, $("select[id^='" + this.getOptionIdentifier() + "'].ProductOption" + iOrder), iQuantity);

            if ( aItemCode.bCheckNum === false ) {
                var _aItemValueNo = Olnk.getProductAllSelected(sProductCode , $("select[id^='" + this.getOptionIdentifier() + "'].ProductOption" + iOrder), iQuantity);
                if ( _aItemValueNo !== false ) {
                    aData.push('selected_item_by_etype[]=' + $.toJSON(_aItemValueNo));
                }
            } else {
                aData.push('selected_item_by_etype[]=' + $.toJSON(aItemCode));
            }


        }

        var sData = aData.join('&');
        sAction = '/exec/front/order/basket/';

        var sType  = null;
        if (bBasket === 'T') {
            sType = 2;
            } else {
                if (typeof(sItemCode) != "undefined" && sItemCode != "") {
                    sOptionStr = sOptionStr + "&item_code[]=" + sItemCode;
                }
                selectbuy_action('&'+sOptionStr, iProductNo);
                sData = sData + '&quantity_override_flag='+sIsPrdOverride;

            sType = 1;
            sData = sData + '&redirect=' + sType;
        }

        action_basket(sType, 'category', sAction, sData, sBasketType);
    },

    /**
     * 뉴상품의 경우 아이템 코드를 받아오는 로직
     */
    getItemCode : function(iOrder, iProductNo)
    {
        var sHasOption = eval("sOptionType" + iProductNo);
        if (sHasOption == "") {
            return eval("sItemCode" + iProductNo);
        }

        var sTargetClass = "[id^='" + this.getOptionIdentifier() + "'].ProductOption" + iOrder + ":visible";
        if ($(sTargetClass).length == 0) {
            return null;
        }

        var bRequire = false;
        $(sTargetClass).each(function() {
            if ($(this).attr('required') === true) {
                if ($(this).val() == '*' || $(this).val() == '**') {
                    bRequire = true;
                    $(this).focus();
                    alert(__('필수 옵션을 선택해주세요.'));
                    return false;
                }
            }
        });
        if (bRequire === true) return null;

        var aItemMapper = null;
        if (eval('item_listing_type'+iProductNo) == 'S') {
            aItemMapper =  $.parseJSON(eval('option_value_mapper'+iProductNo));
        }

        var aItemValue = new Array();
        var aIgnoreValue = ['*', '**'];

        if (aItemMapper == null || eval('item_listing_type'+iProductNo) == 'C') {
            $("[id^='" + EC_Wishlist.getOptionIdentifier() + "'].ProductOption"+iOrder + ":visible").each(function() {
               if ($.inArray(this.value, aIgnoreValue) === -1) {
                   aItemValue.push(this.value);
               }
            });
            return aItemValue;
        } else {
            $("[id^='" + this.getOptionIdentifier() + "'].ProductOption" + iOrder + ":visible").each(function() {
               if ($.inArray(this.value, aIgnoreValue) === -1) {
                   aItemValue.push(this.value);
               }
            });
            return aItemMapper[aItemValue.join('#$%')];
        }
    },

    /**
     * 옵션ID 식별자명
     * @return string
     */
    getOptionIdentifier: function()
    {
        // 최근본 상품 호환을 위해
        var sIdentifierId = "wishlist_product_option";
        if ($("select[id^='wishlist_product_option']").length == 0 && $("select[id^='recent_product_option_id']").length > 0) {
            sIdentifierId = "recent_product_option";
        }

        return sIdentifierId;
    },

    isSoldout: function(iIndex)
    {
        if (typeof(aWishlistProductData) != "undefined") {
            if (aWishlistProductData[iIndex].is_soldout == "T") {
                alert(sprintf(__("이 상품은 현재 재고가 부족하여 판매가 잠시 중단되고 있습니다.\n\n제품명 : %s"), aWishlistProductData[iIndex].product_name));
                return true;
            }
        }

        return false;
    },

    /**
     * 선택상품 삭제하기
     */
    deleteSelect: function()
    {
        if (this.isChecked() == false) {
            alert(__('상품을 선택해주세요.'));
            return false;
        }

        if (confirm(__('선택하신 상품을 삭제하시겠습니까?'))) {
            var str_param = 'command=del';
            str_param += '&key=idx&' + $("input[name='wish_idx[]']:checked").serialize();

            var sParamIsSetProductInfo = NewWishlist.getWishlistDeleteIsSetProduct(true);
            if (sParamIsSetProductInfo !== false) {
                str_param += sParamIsSetProductInfo;
            }

            action_wishlist(str_param);
        }

        return false;
    },

    /**
     * 관심상품 비우기
     */
    deleteAll: function()
    {
        if (aWishlistProductData.length == 0) {
            alert(__('관심상품이 없습니다.'));
            return false;
        }

        if (confirm(__('관심상품을 비우시겠습니까?'))) {
            var aWishIdx = [];
            for (var i = 0, length = aWishlistProductData.length; i < length; i++) {
                aWishIdx.push("wish_idx[]=" + aWishlistProductData[i].wish_list_idx);
            }
            var str_param = 'command=del';
            str_param += '&key=idx&' + aWishIdx.join("&");
            var sParamIsSetProductInfo = NewWishlist.getWishlistDeleteIsSetProduct(false);
            if (sParamIsSetProductInfo !== false) {
                str_param += sParamIsSetProductInfo;
            }
            str_param += '&clear=t';
            action_wishlist(str_param);
        }
    },

    /**
     * 관심상품 전체상품 주문
     */
    orderAll: function()
    {
        // 관심상품수 체크
        if (aWishlistProductData.length == 0) {
            alert(__('관심상품이 없습니다.'));
            return false;
        }

        if (this.isDifferentDelvType(true) && EC_GLOBAL_INFO.isGlobal() === false) {
            alert(__('국내배송상품과 해외배송상품은 함께 주문할 수 없습니다.\n같은 배송 상품만 선택해 주세요.'));
            return false;
        }

        // 장바구니에 담고 주문서 작성
        var aCheckedProduct = [];
        var sBasketType = 'A0000';
        var sDelvType = 'A';
        for (var i = 0, length = aWishlistProductData.length; i < length; i++) {
            // 품절여부 확인
            if (this.isSoldout(i)) {
                return false;
            }


            var aData = [];
            var aPrdData = aWishlistProductData[i];

            var sOptionType = eval('sOptionType'+aPrdData.product_no);

            if (intval(aPrdData.set_product_no) > 0 ) {
                continue;
            }
            var aProductMax =  eval('aProductMax'+aPrdData.product_no);
            if (aProductMax !== '') {
                aData.push('product_max='+aProductMax.product_max);
                aData.push('product_max_type='+aProductMax.product_max_type);
            }
            aData.push('command=add');
            aData.push('option_type=' + sOptionType);
            aData.push('option_add[]=' + aPrdData.opt_add);
            aData.push('quantity=1');
            aData.push('product_no=' + aPrdData.product_no);
            aData.push('main_cate_no=' + aPrdData.main_cate_no);
            aData.push('display_group=' + aPrdData.display_group);
            aData.push('basket_type=' + aPrdData.basket_type);
            aData.push('delvtype=' + aPrdData.delvtype);
            aData.push('selected_item[]=' + aPrdData.quantity + '||' + aPrdData.item_code);
            aData.push('is_set_product=' + aPrdData.is_set_product);
            aData.push('set_product_no=' + aPrdData.set_product_no);
            aData.push('basket_prd_no=' + aPrdData.basket_prd_no);

            var sData = aData.join('&');
            sAction = '/exec/front/order/basket/';
            action_basket(2, 'detail', sAction, sData, aPrdData.basket_type, true);

            // for order
            aCheckedProduct[i] = aPrdData.product_no + ":" + aPrdData.opt_id;
            sDelvType = aPrdData.delvtype;
        }

        return false;

        //무이자 할부 상품만 있는지 체크
        if (this.isAllInstallmentType(aWishlistProductData) == true) {
            sBasketType = 'A0001';
        }

        aParam = {
            checked_product : aCheckedProduct.join(','),
            basket_type     : sBasketType,
            delvtype        : sDelvType
        };


        var sOrderUrl = '/order/orderform.html?basket_type='+ sBasketType;
        sOrderUrl += '&delvtype=' + sDelvType;

        $.post('/exec/front/order/order/', aParam, function(data) {
            if (data.result < 0) {
                alert(data.alertMSG);
                return;
            }

            if (data.isLogin == 'F') { // 비로그인 주문
                location.href = sLoginUrl + '?noMember=1&returnUrl=' + escape(sOrderUrl);
            } else {
                location.href = sOrderUrl;
            }
        }, 'json');
    },

    /**
     * 선택상품 장바구니 담기
     */
    basket: function()
    {
        if (this.isChecked()) {
            if (this.isDifferentDelvType() && EC_GLOBAL_INFO.isGlobal() === false) {
                alert(__('국내배송상품과 해외배송상품은 함께 장바구니에 담을 수 없습니다.\n같은 배송 상품만 선택해 주세요.'));
                return false;
            }

            var sDelvType = 'A';
            for (var i = 0, length = aWishlistProductData.length; i < length; i++) {
                // 품절여부 확인
                if (this.isSoldout(i)) {
                    return false;
                }

                var aData = [];
                var aPrdData = aWishlistProductData[i];
                var sOptionType = eval('sOptionType'+aPrdData.product_no);

                var aProductMax =  eval('aProductMax'+aPrdData.product_no);
                if (aProductMax !== '') {
                    aData.push('product_max='+aProductMax.product_max);
                    aData.push('product_max_type='+aProductMax.product_max_type);
                }
                aData.push('command=add');
                aData.push('wish_list_idx='+aPrdData.wish_list_idx);
                aData.push('option_type=' + sOptionType);
                aData.push('option_add[]=' + aPrdData.opt_add);
                aData.push('quantity=1');
                aData.push('product_no=' + aPrdData.product_no);
                aData.push('main_cate_no=' + aPrdData.main_cate_no);
                aData.push('display_group=' + aPrdData.display_group);
                aData.push('basket_type=' + aPrdData.basket_type);
                aData.push('delvtype=' + aPrdData.delvtype);
                aData.push('selected_item[]=' + aPrdData.quantity + '||' + aPrdData.item_code);


                var sData = aData.join('&');
                sAction = '/exec/front/order/basket/';
                action_basket(2, 'detail', sAction, sData, aPrdData.basket_type, true);

                sDelvType = aPrdData.delvtype;
            }

        } else {
            alert(__('상품을 선택해주세요.'));
        }
    },

    /**
     * 상품선택여부
     */
    isChecked: function()
    {
        if ($("input[name='wish_idx[]']:checked").length > 0) {
            return true;
        }

        return false;
    },

    /**
     * 관심상품 배송유형 섞였는지 체크
     * @param boolean bIsAll 전체상품인지 선택상품인지(true : 전체, false : 선택)\
     * @return boolean false:오직하나,true:해외/국내 석임
     */
    isDifferentDelvType: function(bIsAll)
    {
        var aDelvType = [];
        if (bIsAll) {
            // delvtype 값 모으기
            for (var i = 0, length = aWishlistProductData.length; i < length; i++) {
                if ($.inArray(aWishlistProductData[i].delvtype, aDelvType) < 0) {
                    aDelvType.push(aWishlistProductData[i].delvtype);
                }
            }
        } else {
            var aWishIdx = [];
            $("input[name='wish_idx[]']:checked").each(function() {
                aWishIdx.push(parseInt($(this).val()));
            });

            // delvtype 값 모으기
            for (var i = 0, length = aWishlistProductData.length; i < length; i++) {
                if ($.inArray(aWishlistProductData[i].wish_list_idx, aWishIdx) >= 0) {
                    if ($.inArray(aWishlistProductData[i].delvtype, aDelvType) < 0) {
                        aDelvType.push(aWishlistProductData[i].delvtype);
                    }
                }
            }
        }

        if (aDelvType.length > 1) {
            return true;
        }

        return false;
    }
};

function check_wishlist()
{
    if ($('input[name="basket_info[]"]:checkbox:checked').length <= 0) {
        alert(__('상품을 선택해주세요.'));
        return false;
    }

    return true;
}

/**
 * 관심상품 Action
 * @param string sQueryString 파라미터
 * @param string sFrom 요청위치
 */
function action_wishlist(sQueryString, sFrom)
{
    var sFrom = typeof(sFrom) == "undefined" ? "" : sFrom;
    if (sQueryString != "") {
        $.post(sWishUrl, sQueryString, function(req) {
            //새로운 모듈 사용시에는 중복되어있어도 처리된것으로 간주함.. 왜 그렇게하는지는 이해불가
            if (req.command == 'add' && (req.result == 'SUCCESS' || (req.bIsUseOptionSelect === true && req.result === 'NO_TARGET'))) {
                alert(__('관심상품에 등록되었습니다'));
                if (sFrom == "wishlist") {
                    location.reload();
                }
            } else if (req.command == 'del' && req.result == 'SUCCESS') {
                alert(__('관심상품이 삭제되었습니다.'));
                location.reload();
            } else if (req.command == 'update' && req.result == 'SUCCESS') {
                alert(__('관심상품의 옵션이 변경되었습니다.'));
                location.reload();
            } else if (req.result == 'NOT_LOGIN') {
                alert(__('로그인이 필요합니다'));
            } else if (req.result == 'INVALID_REQUEST') {
                alert(__('잘못된 요청입니다'));
            } else if (req.result == 'NO_TARGET') {
                alert(__('이미 등록된 상품입니다'));
            } else if (req.command == 'add' && req.result == 'ERROR') {
                alert(__('등록이 실패하였습니다.'));
            } else if (req.command == 'del' && req.result == 'ERROR') {
                alert(__('삭제가 실패하였습니다.'));
            } else if (req.command == 'update' && req.result == 'ERROR') {
                alert(__('옵션변경을 실패하였습니다.'));
            } else {
                alert(__('잘못된 요청입니다'));
            }

        },'json');
    }
}

/**
 * 관심상품
 */
var NewWishlist = {
    sLayerBoxId : '' ,
    sIsPrdOverride : 'F',
    bIsOrderProc : false,//구매불가 상품이 있을떄 구매진행여부, 기본값은 구매안함
    oForm : null,
    /**
     * 관심상품옵션 변경 or 추가
     * @param string sMode 액션모드(update:옵션변경, add:옵션추가)
     * @param int iIndex 품목정보 배열index
     * @param int iProductNo 상품번호
     */
    modify: function(sMode, iIndex, iProductNo)
    {
        var sReg = /^\*/;
        var sOptionStr = $("#wishlist_product_option_id" + iIndex).val();

        // 필수옵션선택여부 체크
        if (this.isValidate(iIndex, sMode) == false) {
            return false;
        }

        iIndex = parseInt(iIndex, 10);

        // 관심상품 idx
        var iWishIdx = aWishlistProductData[iIndex].wish_list_idx;

        // 상품연동형 옵션타입인지 여부
        var isOptionEtype = Olnk.isLinkageType(aWishlistProductData[iIndex].option_type);

        // 품목코드 추출
        var oItemInfo = {};
        if (isOptionEtype === true) {
            oItemInfo = Olnk.getMockItemInfo({
                'product_no' : aWishlistProductData[iIndex].product_no,
                'product_code' : aWishlistProductData[iIndex].product_code
            });
        } else {
            oItemInfo = this.getItemInfo(iIndex, iProductNo);
        }

        // 재고정보 추출
        var sKey = "option_stock_data" + iProductNo;
        var oItemStock = $.parseJSON(window[sKey]);

        var oItemEtype;

        // 이미 담겨져 있는지 체크
        if (isOptionEtype == true) {
            var oData;
            var sItemEtype = '';
            var aItemEtype = new Array();
            var sSelectedItemEtype = '';
            var aSelectedItemEtype = new Array();
            var j = 0;
            $('.ProductOption' + iIndex + ':visible').each(function() {
                if (/^\*+$/.test($(this).val()) === false ) {
                    aSelectedItemEtype.push($(this).val());
                }
            });

            sSelectedItemEtype = aSelectedItemEtype.join('!@#');

            for (var i=0; i < aWishlistProductData.length; i++) {
                // 지금 수정 중인 상품은 중복체크에서 제외
                if (sMode === 'update' && i === iIndex) {
                    continue;
                }

                if ( iProductNo == aWishlistProductData[i].product_no ) {
                    sItemEtype = '';
                    aItemEtype = [];
                    oData = aWishlistProductData[i].selected_item_by_etype.options;
                    for (j in oData) {
                        aItemEtype.push(oData[j]);
                    }

                    sItemEtype = aItemEtype.join('!@#');
                    if ( sSelectedItemEtype ==  sItemEtype) {
                        alert(__('이미 등록된 상품입니다'));
                        return false;
                    }
                }
            }
        } else {
            for (var i=0, length = aWishlistProductData.length; i < length; i++) {
                // 지금 수정 중인 상품은 중복체크에서 제외
                if (sMode === 'update' && i === iIndex) {
                    continue;
                }

                if (oItemInfo.item_code == aWishlistProductData[i].item_code && parseInt(oItemInfo.set_product_no, 10) === parseInt(aWishlistProductData[i].set_product_no, 10)) {
                    alert(__('이미 등록된 상품입니다'));
                    return false;
                }
            }
        }

        // 옵션추가
        var aParams = [];
        if (sMode == "add") {
            aParams.push("command=add");
            var aRow = $('.xans-myshop-wishlistitem>.xans-record-').eq(iIndex).children(0);
            var bIndividualSetProduct = false;
            var oIndividualSetProductRow = null;
            for (var x in aRow) {
                if (aRow.hasOwnProperty(x) === false) {
                    continue;
                }
                if ($(aRow[x]).hasClass('individual-set-child') === true) {
                    bIndividualSetProduct = true;
                    oIndividualSetProductRow = $(aRow[x]);
                    break;
                }
            }
            if (bIndividualSetProduct === true) {
                // 분리구매형 세트상품인지 확인
                var aIndividualSetProductClass = oIndividualSetProductRow.attr('class').split(' ');
                for (var x in  aIndividualSetProductClass) {
                    if (aIndividualSetProductClass[x].substring(0, 15) === 'set-product-no-') {
                        var iSetProductNum = aIndividualSetProductClass[x].substring(15);
                        break;
                    }
                }
                var sItemCode = oItemInfo.item_code;
                aParams.push('selected_item[]=1||' + sItemCode);
                aParams.push('set_product_no=' + iSetProductNum);
                aParams.push('product_no=' + iProductNo);
                aParams.push('is_set_product=T');
                aParams.push('is_individual_set_product=T');
            } else {
                aParams.push("product_no=" + iProductNo);
                aParams.push("selected_item[]=" + oItemInfo.opt_id);
            }
        } else {
            aParams.push("command=update");
            aParams.push("wish_idx=" + iWishIdx);
            aParams.push("item_code=" + oItemInfo.item_code);
            aParams.push("opt_str=" + oItemInfo.opt_str);
        }

        aParams = Olnk.hookParamForWish(aParams, {
            'product_code' : aWishlistProductData[iIndex].product_code,
            'quantity' : 1,
            'option_type' : aWishlistProductData[iIndex].option_type,
            'targets' : $('.ProductOption' + iIndex + ':visible')
        });

        // 추가옵션 - (ECHOSTING-92287, by wcchoi)
        var aAddOpt = [];
        $("input.ProductAddOption" + iIndex + ":visible").each(function() {
            var _name = $(this).attr('OptionName');
            var _val = $(this).val();
            aAddOpt.push(_name + '*' + _val);
        });

        if (aAddOpt.length > 0) {
            aParams.push('add_option=' + encodeURIComponent(aAddOpt.join(',')));
        }

        // action
        action_wishlist(aParams.join("&"), "wishlist");
    },

    /**
     * 뉴상품의 경우 아이템 코드를 받아오는 로직
     * @param int iIndexiIndexiIndexiIndex 품목정보배열 index
     * @param int iProductNo 상품번호
     */
    getItemInfo : function(iIndex, iProductNo)
    {
        var oItemInfo = {
            "product_no": iProductNo,
            "item_code": "",
            "opt_id": "",
            "opt_str": "",
            "set_product_no":""
        };

        oItemInfo.set_product_no = aWishlistProductData[iIndex].set_product_no;
        // 옵션이없는 경우의 처리
        if (aWishlistProductData[iIndex].has_option === 'F') {
            oItemInfo.item_code = aWishlistProductData[iIndex].item_code;
            oItemInfo.opt_id = aWishlistProductData[iIndex].item_code.substr(8);
            return oItemInfo;
        }

        // 분리옵션 여부(T:조합옵션, F:분리옵션 in has_option is 'T')
        var oWishlistData = aWishlistProductData[iIndex];
        var sOptionType = oWishlistData.option_type;
        if (oWishlistData.has_option == 'F' || $(".ProductOption" + iIndex + ":visible").length == 0) {
            oItemInfo.item_code = oWishlistData.item_code;
        } else {
            // ECHOSTING-64737 bug fix
            if ($('.ProductOption' + iIndex + ":visible").length == 0) {
                oItemInfo.item_code = oWishlistData.item_code;
            } else {
                if (eval("item_listing_type" + iProductNo) == "C" || sOptionType == "F") {
                    oItemInfo.item_code = $("[id^='" + this.getOptionIdentifier() + "'].ProductOption" + iIndex + ":visible").val();
                    oItemInfo.opt_str = $("[id^='" + this.getOptionIdentifier() + "'].ProductOption" + iIndex + ' :selected').text();

                    oItemInfo.opt_str = oItemInfo.opt_str.replace(/\-/g, "/");
                } else {
                    var aItemValue = new Array();
                    $("[id^='" + this.getOptionIdentifier() + "'].ProductOption" + iIndex + ":visible").each(function() {
                        aItemValue.push($(this).val());
                    });
                    var aItemMapper = $.parseJSON(eval("option_value_mapper" + iProductNo));

            oItemInfo.item_code = aItemMapper[aItemValue.join("#$%")];
                    oItemInfo.opt_str = aItemValue.join("/");
                }
            }
        }
        oItemInfo.opt_id = oItemInfo.item_code.substr(8);

        return oItemInfo;
    },

    /**
     * 주문관련 액션(한개씩 액션이 일어날때마다)
     * @param string sMode 액션모드
     * @param int iIndex 배열index키
     */
    actionOrder: function(sMode, iIndex)
    {
        // 필수 추가옵션값만 가져오기 - (ECHOSTING-92287, by wcchoi)
        var aRequiredOptAdd = this.getRequiredAddOption(iIndex);
        for (var i in aRequiredOptAdd) {
            if (aRequiredOptAdd[i] == '') {
                alert(__('옵션 정보에 문제가 있어 진행할 수 없습니다. \n확인 후 다시 시도해 주세요.'));
                return;
            }
        }

        if (sMode == "basket") {
            if (typeof(iIndex) != "undefined" && iIndex != null) {
                this.basketOne(iIndex);
            } else {
                this.basket();
            }
        } else {
            this.order(iIndex);
        }
    },


    /**
     * 필수 추가옵션값만 가져오기 - (ECHOSTING-92287, by wcchoi)
     * @param int iIndex 배열index키
     * @return array 필수 추가옵션정보
     */
    getRequiredAddOption : function(iIndex)
    {
        var aRequiredOptAdd = [];

        // 뉴상품 && 구스킨의 경우
        if ($("[id^='wishlist_option_modify_layer_']").length <= 0) {
            $("input.ProductAddOption" + iIndex).each(function() {
                if ($(this).attr('require') == 'F') return;

                aRequiredOptAdd.push($(this).val());
            });

            return aRequiredOptAdd;
        }

        // aWishlistProductData.opt_add 값 파싱
        var aOptAdd = {};
        var aTmp = decodeURIComponent(aWishlistProductData[iIndex].opt_add).split(';');
        for (var i = 0; i < aTmp.length; i++) {
            var tok = aTmp[i].split('=');
            aOptAdd[i] = tok[1];
        }

        // 필수 추가옵션값만 뽑아내서...
        var i = 0;
        //장바구니페이지에서 장바구니의 옵션영역과 관심상품의 옵션영역을 분리하기위해 추가
        $(".xans-myshop-optionaddlist>.xans-record->.ProductAddOption" + iIndex).each(function() {
            if ($(this).attr('require') == 'F') {
                i++;
                return;
            }

            aRequiredOptAdd.push(aOptAdd[i] || '');
            i++;
        });
        return aRequiredOptAdd;
    },


    isValidate: function(iIndex, sMode)
    {
        var sOptionStr = [];
        var bIsPass = true;
        $("select[id^='" + this.getOptionIdentifier() + "'].ProductOption" + iIndex + ":visible").each(function(i) {
            if ($(this).attr('required')) {
                if ($('option:selected',this).val().indexOf('*') > -1) {
                    alert(__('필수 옵션을 선택해주세요.'));
                    $(this).focus();
                    bIsPass = false;
                    return false;
                }
            }
        });
        if (bIsPass === false) {
            return false;
        }

        var iCount = 0;
        $("input.ProductAddOption" + iIndex + ":visible").each(function() {
            iCount++;
            if ($(this).attr('require') == 'T') {
                if ($(this).val().replace(/^[\s]+|[\s]+$/g,'').length == 0) {
                    $(this).focus();
                    bIsPass = false;
                    return false;
                }
            }
        });

        // // 사용자지정옵션인경우 옵션변경불가(기존사양)
        // if (sMode == 'update' && iCount > 0) {
        //     alert(__("사용자 지정 옵션 상품은 옵션변경을 하실 수 없습니다."));
        //     bIsPass = false;
        //     return false;
        // }

        if (bIsPass === false) {
            alert(__('추가 옵션을 입력해주세요.'));
        }

        return bIsPass;
    },

    isSoldout: function(iIndex)
    {
        if (aWishlistProductData[iIndex].set_product_no > 0) {
            return false;
        }
        if (aWishlistProductData[iIndex].is_soldout == "T") {
            alert(sprintf(__("이 상품은 현재 재고가 부족하여 판매가 잠시 중단되고 있습니다.\n\n제품명 : %s"), aWishlistProductData[iIndex].product_name));
            return true;
        }

        return false;
    },
    isPurchase : function(iIndex, bAlert)
    {
        if (typeof(bAlert) === 'undefined') {
            bAlert = true;
        }

        if (aWishlistProductData[iIndex].enable_purchase === "F") {
            if (bAlert === true) {
                alert(__('로그인 하셔야 주문 가능한 상품이 있습니다.\n비회원인 경우 회원가입 후 이용하여 주세요.'));
                location.href = '/member/login.html?noMember=1&returnUrl=' + escape(window.location.href);
            }
            return false;
        }

        return true;
    },

    isReserve: function(iIndex)
    {
        if (aWishlistProductData[iIndex].is_reserve_stat === "R") {
            alert(__('ITEMS.MAY.SHIPPED', 'SHOP.JS.FRONT.NEW.WISH.LIST'));
            return true;
        }

        return false;
    },

    /**
     * all check or uncheck
     */
    checkAll: function(obj)
    {
        var bIsChecked = $(obj).attr("checked");
        $("input[name='wish_idx[]']").each(function() {
            $(this).attr("checked", bIsChecked);
        });
    },
    getWishlistDeleteIsSetProduct : function(bChecked)
    {
        if ($(aWishlistProductData).length < 1) {
            return false;
        }

        var aWishListIndex = [];
        var aWishListDeleteSetProductInfo = [];
        var sChecked = (bChecked === true) ? ':checked' : '';

        $("input[name='wish_idx[]']" + sChecked).each(function(){
            aWishListIndex.push($(this).val());
        });

        $.each(aWishListIndex, function (i, iWishlistIndex) {
            if (iWishlistIndex != aWishlistProductData[i].wish_list_idx) {
                return true;
            }

            aWishListDeleteSetProductInfo.push("wishlist_delete_set_product_info[" + aWishlistProductData[i].product_no + "]=" + aWishlistProductData[i].is_set_product);
        });

        return '&' + aWishListDeleteSetProductInfo.join("&");
    },
    /**
     * 선택상품 삭제하기
     */
    deleteSelect: function()
    {
        if (this.isChecked() == false) {
            alert(__('상품을 선택해주세요.'));
            return false;
        }

        if (confirm(__('선택하신 상품을 삭제하시겠습니까?'))) {
            var str_param = 'command=del';
            str_param += '&key=idx&' + $("input[name='wish_idx[]']:checked").serialize();

            var sParamIsSetProductInfo = NewWishlist.getWishlistDeleteIsSetProduct(true);
            if (sParamIsSetProductInfo !== false) {
                str_param += sParamIsSetProductInfo;
            }

            action_wishlist(str_param);
        }

        return false;
    },

    /**
     * 관심상품 비우기
     */
    deleteAll: function()
    {
        if (aWishlistProductData.length == 0) {
            alert(__('관심상품이 없습니다.'));
            return false;
        }

        if (confirm(__('관심상품을 비우시겠습니까?'))) {
            var aWishIdx = [];
            for (var i = 0, length = aWishlistProductData.length; i < length; i++) {
                aWishIdx.push("wish_idx[]=" + aWishlistProductData[i].wish_list_idx);
            }
            var str_param = 'command=del';
            str_param += '&key=idx&' + aWishIdx.join("&");
            var sParamIsSetProductInfo = NewWishlist.getWishlistDeleteIsSetProduct(false);
            if (sParamIsSetProductInfo !== false) {
                str_param += sParamIsSetProductInfo;
            }
            str_param += '&clear=t';
            action_wishlist(str_param);
        }
    },

    /**
     * 폼객체 생성
     * @param bIsTruncate 폼내용을 초기화할것인지 여부
     */
    getForm : function(bIsTruncate) {
        if (this.oForm === null) {
            this.oForm = $('<form>');
            this.oForm.attr('method', 'post');
            this.oForm.css('display', 'none');
            $('body').append(this.oForm);
        }

        if (bIsTruncate === true) {
            this.oForm.find(":hidden").remove();
        }
    },

    /**
     * name, value값을 받아 input hidden 태그 반환
     * @param string sName name
     * @param string sValue value
     * @return string input hidden 태그
     */
    getInputHidden : function(sName, sValue) {
        return "<input type='hidden' name='" + sName + "' value='" + sValue + "' />";
    },

    /**
     * 관심상품 전체상품 주문
     */
    orderAll: function()
    {
        // 필수 추가옵션값 체크 - (ECHOSTING-92287, by wcchoi)
        var aIndex = [];
        $("input[name='wish_idx[]']").each(function() {
            aIndex.push($(this).attr('id').replace('wish_idx_', ''));
        });

        // 불량회원 체크
        if (sBlackType == 'P') {
            var sMsg = __('쇼핑몰 관리자가 구매 제한을 설정하여 구매하실 수 없습니다.');
            if (sBlackMsg  != 'undefined' || sBlackMsg != '') {
                var sMsg = sBlackMsg;
            }
            alert(sMsg);
            return false;
        }

        // 관심상품수 체크
        if (aWishlistProductData.length == 0) {
            alert(__('관심상품이 없습니다.'));
            return false;
        }

        if (this.isDifferentDelvType(true) && EC_GLOBAL_INFO.isGlobal() === false) {
            alert(__('국내배송상품과 해외배송상품은 함께 주문할 수 없습니다.\n같은 배송 상품만 선택해 주세요.'));
            return false;
        }

        NewWishlist.bIsOrderProc = false;
        //구매불가상품이 있고 불가상품제외하고 confirm창에서 구매하기를 안했을경우에만 중단
        var bIsCheckEnable = this.checkEnableProduct(false, 'orderAll');
        if (bIsCheckEnable === false && NewWishlist.bIsOrderProc === false) {
            return false;
        }

        // 장바구니에 담고 주문서 작성
        var aCheckedProduct = [];
        var sBasketType = 'A0000';
        if (this.isAllInstallmentType(aWishlistProductData) == true) {
            sBasketType = 'A0001';
        }
        var sDelvType = 'A';

        //폼객체 생서
        var oThis = this;
        this.getForm(true);
        this.oForm.append(this.getInputHidden('command', 'add'));
        this.oForm.append(this.getInputHidden('basket_page_flag', 'T'));
        this.oForm.attr('action', '/exec/front/Order/Wishconvert');
        var iCnt = 0;
        var aIndividualSetProduct = {};
        var save_data = '';
        var oJsonSaveData;

        for (var i = 0; i < aWishlistProductData.length; i++) {
            var aData = [];
            var aPrdData = aWishlistProductData[i];
            var sOptionType = eval('sOptionType'+aPrdData.product_no);

            var aProductMax =  eval('aProductMax'+aPrdData.product_no);
            if (aProductMax !== '') {
                this.oForm.append(this.getInputHidden('product_max['+i+']', aProductMax.product_max));
                this.oForm.append(this.getInputHidden('product_max_type['+i+']', aProductMax.product_max_type));
            }
            //옵션이 선택안된 상품은 패스
            if (aPrdData.opt_id === null || (aPrdData.option_type === 'E' && aPrdData.opt_id.length === 4) || parseInt($('#wish_idx_' + i).attr('enable-order')) !== 1) {
                continue;
            }

            if (aWishlistProductData[i].product_price_type === 'T') {
                continue;
            }

            var iKey = i;
            if (aPrdData.is_set_product === 'T' && aPrdData.set_product_no > 0) {
                var iSetProductNum = aPrdData.set_product_no;
                if (typeof(aIndividualSetProduct[iSetProductNum]) === 'undefined') {
                    aIndividualSetProduct[iSetProductNum] = iKey;
                } else {
                    iKey = aIndividualSetProduct[iSetProductNum];
                }
            }

            var iProductNum =  aPrdData.product_no;

            this.oForm.append(this.getInputHidden('option_type['+iKey+']', sOptionType));
            this.oForm.append(this.getInputHidden('quantity['+iKey+']', aPrdData.quantity));
            this.oForm.append(this.getInputHidden('main_cate_no['+iKey+']', aPrdData.main_cate_no));
            this.oForm.append(this.getInputHidden('display_group['+iKey+']', aPrdData.display_group));
            this.oForm.append(this.getInputHidden('basket_type['+iKey+']', aPrdData.basket_type));
            this.oForm.append(this.getInputHidden('delvtype['+iKey+']', aPrdData.delvtype));
            this.oForm.append(this.getInputHidden('is_set_product['+iKey+']', aPrdData.is_set_product));
            this.oForm.append(this.getInputHidden('product_no['+iKey+']', iProductNum));

            if (aPrdData.is_set_product === 'T') {
                if (aPrdData.set_product_no > 0 ) {
                    this.oForm.append(this.getInputHidden('save_data['+iKey+']', JSON.stringify(eval('aSetIndividaulSaveData' + aPrdData.set_product_no))));

                    this.oForm.append(this.getInputHidden('set_product_no['+iKey+']', aPrdData.set_product_no));
                    this.oForm.append(this.getInputHidden('set_product_type['+iKey+']', 'S'));
                    this.oForm.append(this.getInputHidden('product_code['+iKey+']', aPrdData.set_product_code));
                    this.oForm.append(this.getInputHidden('selected_item['+iKey+'][]', aWishlistProductData[i].quantity + '||' +aWishlistProductData[i].item_code));
                    this.oForm.append(this.getInputHidden('default_opt_id['+iKey+'][]', aWishlistProductData[i].opt_id));
                    this.oForm.append(this.getInputHidden('unit_product_no['+iKey+'][]', aWishlistProductData[i].product_no));
                    this.oForm.append(this.getInputHidden('product_no['+iKey+']', aPrdData.set_product_no));
                } else {
                    this.oForm.append(this.getInputHidden('product_code['+iKey+']', aPrdData.product_code));
                    this.oForm.append(this.getInputHidden('save_data['+iKey+']', aPrdData.basket_save_data));
                    this.oForm.append(this.getInputHidden('selected_item['+iKey+'][]', aPrdData.basket_selected_item));

                    this.oForm.append(this.getInputHidden('default_opt_id['+iKey+']', aPrdData.opt_id));
                }

            } else {
                this.oForm.append(this.getInputHidden('product_code['+iKey+']', aPrdData.product_code));
                this.oForm.append(this.getInputHidden('selected_item['+iKey+'][]', aPrdData.quantity + '||' + aPrdData.item_code));
                this.oForm.append(this.getInputHidden('default_opt_id['+iKey+']', aPrdData.opt_id));
            }


            // 추가옵션 오류 수정 - (ECHOSTING-92287, by wcchoi)
            this.oForm.append(this.getInputHidden('add_option_name['+iKey+']', aPrdData.add_option_name_org));

            $(aPrdData.opt_add_params_org).each(function(iIndex, sValue) {
                oThis.oForm.append(oThis.getInputHidden('option_add['+iKey+'][]', sValue));
            });
            //aData.push('add_option_name=' + aPrdData.add_option_name);

            //쓰이지 않음
            //aData.push(aPrdData.opt_add_params);

            // 파일첨부 옵션
            this.oForm.append(this.getInputHidden('file_option['+iKey+']', FileOptionManager.encode($.parseJSON(aPrdData.option_attached_file_info_json))));

            if (Olnk.isLinkageType(sOptionType) === true) {
                this.oForm.append(this.getInputHidden('selected_item_by_etype['+iKey+'][]', $.toJSON(aPrdData.selected_item_by_etype)));
            }

            iCnt++;
        }

        if (iCnt < 1) {
            return;
        }

        this.oForm.submit();
    },
    /**
     * 선택한상품만 주문하기
     *
     * @param string sOptionParam 옵션 파람값
     * @param int iProductNo 상품번호
     * @param string sSelectedItemByEtype 상품연동형의 경우 입력되는 선택된옵션 json 데이터
     */
    selectbuy_action : function(sOptionParam, iProductNo, sOptionType)
    {

        var sUrl = '/exec/front/order/basket/?command=select_prdcnt&product_no=' + iProductNo + '&option_type=' + sOptionType + '&' + sOptionParam;
        $.ajax(
        {
            url : sUrl,
            dataType : 'json',
            async : false,
            success : function(data)
            {
                if (data.result > 0 && !confirm(sprintf(__('동일상품이 장바구니에 %s개 있습니다.'), data.result) +'\n'+ __('함께 구매하시겠습니까?'))) {
                    NewWishlist.sIsPrdOverride = 'T';
                }
            }
        });
    },


    /**
     * 관심상품 주문(한개만)
     * @param int iIndex 배열index키
     */
    order: function(iIndex)
    {
        //장바구니에 동일상품이있을경우 동시구매 여부 초기화
        NewWishlist.sIsPrdOverride = 'F';
        // 불량회원 체크
        if (sBlackType == 'P') {
            var sMsg = __('쇼핑몰 관리자가 구매 제한을 설정하여 구매하실 수 없습니다.');
            if (sBlackMsg  != 'undefined' || sBlackMsg != '') {
                var sMsg = sBlackMsg;
            }
            alert(sMsg);
            return false;
        }

        // 장바구니에 담고 주문서 작성
        var sBasketType = 'A0000';
        var sDelvType = 'A';

        // 품절여부 확인
        if (this.isSoldout(iIndex)) {
            return false;
        }

        // 구매제한확인
        if (this.isPurchase(iIndex) === false) {
            return false;
        }

        // ECHOSTING-162635 예약주문 Alert
        this.isReserve(iIndex);

        if (this.isPriceContentProduct(iIndex) === true) {
            alert(__('판매가가 없는 상품은 주문하실 수 없습니다. 판매가가 있는 상품으로만 주문해주세요.'));
            return false;
        }


        var aData = [];

        var oPrdData = aWishlistProductData[iIndex];
        var sOptionType = eval('sOptionType'+oPrdData.product_no);
        var aProductMax =  eval('aProductMax'+oPrdData.product_no);
        if (aProductMax !== '') {
            aData.push('product_max='+aProductMax.product_max);
            aData.push('product_max_type='+aProductMax.product_max_type);
        }
        var iProductNum =  oPrdData.product_no;
        aData.push('from=wishlist');
        aData.push('option_type=' + sOptionType);

        aData.push('wish_list_idx='+oPrdData.wish_list_idx);
        aData.push('quantity=' + oPrdData.quantity);
        aData.push('main_cate_no=' + oPrdData.main_cate_no);
        aData.push('display_group=' + oPrdData.display_group);
        aData.push('basket_type=' + oPrdData.basket_type);
        aData.push('delvtype=' + oPrdData.delvtype);
        aData.push("is_set_product=" + oPrdData.is_set_product);
        aData.push("basket_page_flag=T");

        var sItemcode = '';

        if (oPrdData.is_set_product === "T") {
            if (oPrdData.set_product_no > 0 ) {
                iProductNum = oPrdData.set_product_no;
                var aIndividualSetProduct = this.getIndividualSetProductOrderData(iIndex);
                if (aIndividualSetProduct === false) {
                    return false;
                }
                $.merge(aData, aIndividualSetProduct);
            } else {
                aData.push('product_code=' + oPrdData.product_code);
                aData.push("selected_item[]=" + oPrdData.basket_selected_item);
                aData.push("save_data=" + oPrdData.basket_save_data);

                aData.push('default_opt_id=' + oPrdData.opt_id); // ECHOSTING-92287, by wcchoi
            }
        } else {
            // 뉴상품 & 구스킨을 사용하는 경우 예외처리
            if ($("[id^='wishlist_option_modify_layer_']").length <= 0) {
                var oItemInfo = {};

                if (Olnk.isLinkageType(sOptionType) === true) {
                    oItemInfo = Olnk.getMockItemInfo({
                        'product_no' : oPrdData.product_no,
                        'product_code' : oPrdData.product_code
                    });
                } else {
                    oItemInfo = this.getItemInfo(iIndex, oPrdData.product_no);
                }

                aData.push('selected_item[]=' + oPrdData.quantity + '||' + oItemInfo.item_code);
            } else {
                aData.push('selected_item[]=' + oPrdData.quantity + '||' + oPrdData.item_code);
            }

            aData.push('default_opt_id=' + oPrdData.opt_id); // ECHOSTING-92287, by wcchoi
        }
        aData.push('product_no=' + iProductNum);

        // 추가입력옵션 가공
        aData.push('add_option_name=' + oPrdData.add_option_name);

        // 뉴상품 & 구스킨을 사용하는 경우 예외처리
        if ($("[id^='wishlist_option_modify_layer_']").length <= 0) {
            $("input.ProductAddOption" + iIndex).each(function() {
                aData.push('option_add[]=' + encodeURIComponent($(this).val()));
            });
        } else {
            aData.push(oPrdData.opt_add_params);
        }

        // 파일첨부 옵션
        aData.push('file_option='+FileOptionManager.encode($.parseJSON(oPrdData.option_attached_file_info_json)));

        if (Olnk.isLinkageType(sOptionType) === true) {
            if ($("[id^='wishlist_option_modify_layer_']").length <= 0) {
                aData = Olnk.hookParamForWish(aData, {
                    'product_code' : oPrdData.product_code,
                    'quantity' : 1,
                    'option_type' : oPrdData.option_type,
                    'targets' : $('.ProductOption' + iIndex + ':visible'),
                    'selected_item_by_etype' : oPrdData.selected_item_by_etype
                });
            } else {
                aData.push('selected_item_by_etype[]=' + $.toJSON(oPrdData.selected_item_by_etype));
            }
        }

        var sParams = aData.join('&');

        sBasketType = oPrdData.basket_type == '' ? sBasketType : oPrdData.basket_type;
        sDelvType   = oPrdData.delvtype == '' ? 'A' : oPrdData.delvtype;

        //동일상품 구매여부
        NewWishlist.selectbuy_action(sParams, oPrdData.product_no, sOptionType);

        //기존에 이미 담겨있는지 먼저 확인해야하므로 command파라미터를 여기서 추가
        sParams += '&command=add&quantity_override_flag=' + NewWishlist.sIsPrdOverride;

        sAction = '/exec/front/order/basket/';
        action_basket(1, 'detail', sAction, sParams, sBasketType, true);

        // 이미 action_basket에서 주문서로 이동하는데 왜 아래 코드가 중복으로 들어갔는지 알 수 없음
        // 또한 아래 코드는 data가 undefined이므로 스크립트 에러가 발생함
        // 일단 삭제하지 않고 주석 처리함
        /*
        var sCheckedProductParams = oPrdData.product_no + ":" + oPrdData.opt_id;

        // 뉴상품 & 구스킨을 사용하는 경우 예외처리
        if ($("[id^='wishlist_option_modify_layer_']").length <= 0) {
            var oItemInfo = {};
            if (Olnk.isLinkageType(sOptionType) === true) {
                oItemInfo =  aWishlistProductData[iIndex];
                oItemInfo.opt_id = data.opt_id_by_etype;
            } else {
                oItemInfo = NewWishlist.getItemInfo(iIndex, oPrdData.product_no);
            }
            sCheckedProductParams = oPrdData.product_no + ":" + oItemInfo.opt_id;
        }

        // 세트상품인경우 basket_prd_no 붙여주기
        if (typeof(data.isSetProduct) != "undefined" && data.isSetProduct == "T") {
            sCheckedProductParams = oPrdData.product_no + ":" + oPrdData.opt_id + data.aBasketPrdNo[0];
            sCheckedProductParams = sCheckedProductParams + ":T";
            sCheckedProductParams = sCheckedProductParams + ":" + data.aBasketPrdNo[0];
        }

        // 주문진행
        var aParam = {
            checked_product : sCheckedProductParams,
            basket_type     : sBasketType,
            delvtype        : sDelvType
        };
        $.post('/exec/front/order/order/', aParam, function(data) {
            if (data.result < 0) {
                alert(data.alertMSG);
                return;
            }

            var sOrderUrl = '/order/orderform.html?basket_type='+ sBasketType + '&delvtype=' + sDelvType;
            if (data.isLogin == 'F') { // 비로그인 주문
                location.href = sLoginUrl + '?noMember=1&returnUrl=' + escape(sOrderUrl);
            } else {
                location.href = sOrderUrl;
            }
        }, 'json');
        */
    },

    /**
     * 선택상품 장바구니 담기
     * @param int iIndex ㅅㅓㄴ택\
     * @todo 이쪽 정리 해야함. 일단 급해서 작업하고, 이후 여러개 담을때 로직부분은 수정하도록 한다.
     */
    basket: function(iIndex)
    {
        // 선택상품 장바구니 담기 필수 추가옵션값 체크 - (ECHOSTING-92287, by wcchoi)
        var aIndex = [];
        if (typeof(iIndex) == 'undefined' || iIndex == null) {
            $("input[name='wish_idx[]']:checked").each(function() {
                aIndex.push($(this).attr('id').replace('wish_idx_', ''));
            });
        } else {
            aIndex.push(iIndex);
        }

        // 상품에서 직접
        var bIsOne = false;
        if (typeof(iIndex) != "undefined" && iIndex != null) {
            bIsOne = true;
        }

        if (bIsOne || this.isChecked()) {
            if (bIsOne == false && this.isDifferentDelvType() && EC_GLOBAL_INFO.isGlobal() === false) {
                alert(__('국내배송상품과 해외배송상품은 함께 장바구니에 담을 수 없습니다.\n같은 배송 상품만 선택해 주세요.'));
                return false;
            }

            NewWishlist.bIsOrderProc = false;
            //구매불가상품이 있고 불가상품제외하고 confirm창에서 구매하기를 안했을경우에만 중단
            var bIsCheckEnable = this.checkEnableProduct(true);
            if (bIsCheckEnable === false && NewWishlist.bIsOrderProc === false) {
                return false;
            }

            var sDelvType = 'A';
            var iCnt = 0;
            var aIndividualSetProductNum = [];
            for (var i = 0; i < aWishlistProductData.length; i++) {

                // 선택안된상품 패스
                if (bIsOne) {
                    if (iIndex != i) {
                        continue;
                    }
                } else {
                    if ($("#wish_idx_" + i).attr("checked") == false) {
                        continue;
                    }
                }

                if (parseInt($("#wish_idx_" + i).attr("enable-order")) !== 1) {
                    continue;
                }

                // 품절여부 확인
                if (this.isSoldout(i)) {
                    continue;
                }

                // 구매제한확인
                if (this.isPurchase(i) === false) {
                    return false;
                }


                // 상품정보
                var aPrdData = aWishlistProductData[i];

                if (aPrdData.is_set_product === 'T' && aPrdData.set_product_no > 0 && aIndividualSetProductNum.indexOf(aPrdData.set_product_no) > -1) {
                    continue;
                }
                //옵션이 선택안된 상품은 패스
                if (aPrdData.opt_id === null || (aPrdData.option_type === 'E' && aPrdData.opt_id.length === 4)) {
                    continue;
                }

                // 장바구니에서 장바구니담기 액션시
                var sDelvType = aPrdData.delvtype;
                if (typeof(sBasketDelvType) != "undefined" && sBasketDelvType != null) {
                    if (sBasketDelvType == "B") {
                        if (aPrdData.delvtype == "A" && aPrdData.is_able_oversea == "F") {
                            alert(__('국내배송상품은 해외배송상품 장바구니로 이동이 불가능합니다.'));
                            return false;
                        }
                    } else {
                        if (aPrdData.delvtype == "B") {
                            alert(__('해외배송상품은 국내배송상품 장바구니로 이동이 불가능합니다.'));
                            return false;
                        }
                    }
                    sDelvType = sBasketDelvType;
                }

                var aData = [];
                var sOptionType = eval('sOptionType'+aPrdData.product_no);
                var iProductNum  = aPrdData.product_no;
                aData.push('command=add');
                aData.push('option_type=' + sOptionType);
                //aData.push('option_add[]=' + aPrdData.opt_add);
                aData.push('quantity=' + aPrdData.quantity);

                aData.push('main_cate_no=' + aPrdData.main_cate_no);
                aData.push('display_group=' + aPrdData.display_group);
                aData.push('basket_type=' + aPrdData.basket_type);
                aData.push('delvtype=' + sDelvType);
                aData.push('basket_page_flag=T');

                // 추가옵션 오류 수정 - (ECHOSTING-92287, by wcchoi)
                aData.push('add_option_name=' + aPrdData.add_option_name);
                aData.push(aPrdData.opt_add_params);

                if (aPrdData.is_set_product === "T") {

                    aData.push('wish_list_idx='+aPrdData.wish_list_idx);
                    aData.push('basket_page_flag=T');
                    aData.push('is_set_product=T');
                    if (aPrdData.set_product_no > 0) {
                        var iSetProductNum = aPrdData.set_product_no;
                        var iProductNum = aPrdData.set_product_no;
                        if (typeof(aIndividualSetProductNum[iSetProductNum]) === 'undefined') {
                            var aIndividualSetProduct = this.getIndividualSetProductOrderData(i);
                            if (aIndividualSetProduct === false) {
                                return false;
                            }

                            $.merge(aData, aIndividualSetProduct);
                            aIndividualSetProductNum.push(iSetProductNum);
                        }
                    } else {
                        aData.push('product_code=' + aPrdData.product_code);
                        aData.push("selected_item[]=" + aPrdData.basket_selected_item);
                        aData.push("save_data=" + aPrdData.basket_save_data);

                        aData.push('default_opt_id=' + aPrdData.opt_id); // ECHOSTING-92287, by wcchoi
                    }
                } else {
                // 뉴상품 & 구스킨을 사용하는 경우 예외처리
                    if ($("[id^='wishlist_option_modify_layer_']").length <= 0) {
                        var oItemInfo = {};

                        if (Olnk.isLinkageType(sOptionType) === true) {
                            oItemInfo = Olnk.getMockItemInfo({
                                'product_no' : aPrdData.product_no,
                                'product_code' : aPrdData.product_code
                            });
                        } else {
                            oItemInfo = this.getItemInfo(i, aPrdData.product_no);
                        }

                        aData.push('selected_item[]=' + aPrdData.quantity + '||' + oItemInfo.item_code);
                    } else {
                        aData.push('selected_item[]=' + aPrdData.quantity + '||' + aPrdData.item_code);
                    }

                    aData.push('default_opt_id=' + aPrdData.opt_id);
                }
                aData.push('product_no=' + iProductNum);


                aData.push('from=wishlist');

                // 파일첨부 옵션
                aData.push('file_option='+FileOptionManager.encode($.parseJSON(aPrdData.option_attached_file_info_json)));

                if (Olnk.isLinkageType(aPrdData.option_type) === true) {
                    if ($("[id^='wishlist_option_modify_layer_']").length <= 0) {
                        aData = Olnk.hookParamForWish(aData, {
                            'product_code' : aPrdData.product_code,
                            'quantity' : 1,
                            'option_type' : aPrdData.option_type,
                            'targets' : $('.ProductOption' + i + ':visible'),
                            'selected_item_by_etype' : aPrdData.selected_item_by_etype
                        });
                    } else {
                        aData.push('selected_item_by_etype[]=' + $.toJSON(aPrdData.selected_item_by_etype));
                    }
                }
                var sData = aData.join('&');

                sAction = '/exec/front/order/basket/';
                action_basket(2, 'detail', sAction, sData, aPrdData.basket_type, true);

                if (bIsOne) {
                    if (iIndex == i) {
                        break;
                    }
                }
            }
        } else {
            alert(__('상품을 선택해주세요.'));
        }

    },

    /**
     * 장바구니 하나 담기
     */
    basketOne: function(iIndex)
    {
        // 품절여부 확인
        if (this.isSoldout(iIndex)) {
            return false;
        }

        // 구매제한확인
        if (this.isPurchase(iIndex) === false) {
            return false;
        }

        // ECHOSTING-162635 예약주문 Alert
        this.isReserve(iIndex);

        if (this.isPriceContentProduct(iIndex) === true) {
            alert(__('판매가가 없는 상품은 장바구니 담기가 불가능합니다.'));
            return false;
        }


        // 상품정보
        var oPrdData = aWishlistProductData[iIndex];

        // 장바구니에서 장바구니담기 액션시
        var sDelvType = oPrdData.delvtype;
        if (typeof(sBasketDelvType) != "undefined" && sBasketDelvType != null) {
            if (sBasketDelvType == "B") {
                if (oPrdData.delvtype == "A" && oPrdData.is_able_oversea == "F") {
                    alert(__('국내배송상품은 해외배송상품 장바구니로 이동이 불가능합니다.'));
                    return false;
                }
            } else {
                if (oPrdData.delvtype == "B") {
                    alert(__('해외배송상품은 국내배송상품 장바구니로 이동이 불가능합니다.'));
                    return false;
                }
            }
            sDelvType = sBasketDelvType;
        }

        var aData = [];
        var sOptionType = eval('sOptionType'+oPrdData.product_no);

        var aProductMax =  eval('aProductMax'+oPrdData.product_no);
        if (aProductMax !== '') {
            aData.push('product_max='+aProductMax.product_max);
            aData.push('product_max_type='+aProductMax.product_max_type);
        }
        var iProductNum =  oPrdData.product_no;
        aData.push('wish_list_idx='+oPrdData.wish_list_idx);
        aData.push('command=add');
        aData.push('option_type=' + sOptionType);
        aData.push('basket_page_flag=T');
        aData.push('is_set_product=' + oPrdData.is_set_product);
        aData.push('quantity=' + oPrdData.quantity);

        aData.push('main_cate_no=' + oPrdData.main_cate_no);
        aData.push('display_group=' + oPrdData.display_group);
        aData.push('basket_type=' + oPrdData.basket_type);
        aData.push('delvtype=' + sDelvType);
        aData.push('from=wishlist');

        if (oPrdData.is_set_product === 'T') {
            if (oPrdData.set_product_no > 0 ) {
                iProductNum = oPrdData.set_product_no;
                var aIndividualSetProduct = this.getIndividualSetProductOrderData(iIndex);
                if (aIndividualSetProduct === false) {
                    return false;
                }

                $.merge(aData, aIndividualSetProduct);
            } else {
                aData.push('product_code=' + oPrdData.product_code);
                aData.push("selected_item[]=" + oPrdData.basket_selected_item);
                aData.push("save_data=" + oPrdData.basket_save_data);

                aData.push('default_opt_id=' + oPrdData.opt_id); // ECHOSTING-92287, by wcchoi
            }
        } else {
            // 뉴상품 & 구스킨을 사용하는 경우 예외처리
            if ($("[id^='wishlist_option_modify_layer_']").length <= 0) {
                var oItemInfo = {};

                if (Olnk.isLinkageType(sOptionType) === true) {
                    oItemInfo = Olnk.getMockItemInfo({
                        'product_no' : oPrdData.product_no,
                        'product_code' : oPrdData.product_code
                    });
                } else {
                    oItemInfo = this.getItemInfo(iIndex, oPrdData.product_no);
                }

                aData.push('selected_item[]=' + oPrdData.quantity + '||' + oItemInfo.item_code);
            } else {
                aData.push('selected_item[]=' + oPrdData.quantity + '||' + oPrdData.item_code);
            }

            aData.push('default_opt_id=' + oPrdData.opt_id); // ECHOSTING-92287, by wcchoi
        }


        aData.push('product_no=' + iProductNum);


        // 추가입력옵션 가공
        aData.push('add_option_name=' + oPrdData.add_option_name);

        // 뉴상품 & 구스킨을 사용하는 경우 예외처리
        if ($("[id^='wishlist_option_modify_layer_']").length <= 0) {
            $("input.ProductAddOption" + iIndex).each(function() {
                aData.push('option_add[]=' + encodeURIComponent($(this).val()));
            });
        } else {
            aData.push(oPrdData.opt_add_params);
        }

        // 파일첨부 옵션
        aData.push('file_option='+FileOptionManager.encode($.parseJSON(oPrdData.option_attached_file_info_json)));

        if (Olnk.isLinkageType(sOptionType) === true) {
            if ($("[id^='wishlist_option_modify_layer_']").length <= 0) {
                aData = Olnk.hookParamForWish(aData, {
                    'product_code' : oPrdData.product_code,
                    'quantity' : 1,
                    'option_type' : oPrdData.option_type,
                    'targets' : $('.ProductOption' + iIndex + ':visible'),
                    'selected_item_by_etype' : oPrdData.selected_item_by_etype
                });
            } else {
                aData.push('selected_item_by_etype[]=' + $.toJSON(oPrdData.selected_item_by_etype));
            }
        }

        // 장바구니에 담은뒤 탭유지가 되게 하기위해 through basket_result_action()
        delvtype = sDelvType;

        var sData = aData.join('&');

        sAction = '/exec/front/order/basket/';
        action_basket(2, 'detail', sAction, sData, oPrdData.basket_type);
    },

    /**
     * 세트상품의 동일데이터 처리 부분
     */
    getIndividualSetProductOrderData : function(iIndex)
    {
        var aReturn = [];
        var oPrdData = aWishlistProductData[iIndex];
        if (oPrdData.set_product_no === 0 ) {
            return aReturn;
        }
        var save_data = '';
        var oJsonSaveData;
        var iIndividualSetProduct = 0;
        for (i = iIndex; i < aWishlistProductData.length; i++) {
            if (oPrdData.set_product_no === aWishlistProductData[i].set_product_no && aWishlistProductData[i].set_product_no != aWishlistProductData[i].product_no) {
                // 품절여부 확인
                if (aWishlistProductData[i].is_soldout === 'T') {
                    continue;
                }

                if (aWishlistProductData[i].product_price_type === 'T') {
                    continue;
                }

                if (aWishlistProductData[i].enable_order !== true) {
                    continue;
                }

                aReturn.push('selected_item[]=' + aWishlistProductData[i].quantity + '||' +aWishlistProductData[i].item_code);
                aReturn.push('default_opt_id[]=' + aWishlistProductData[i].opt_id);
                aReturn.push('unit_product_no[]=' + aWishlistProductData[i].product_no);
                aReturn.push('add_option_name['+iIndividualSetProduct+']=' + aWishlistProductData[i].add_option_name_org);

                $(aWishlistProductData[i].opt_add_params_org).each(function(iIndex, sValue) {
                    aReturn.push('option_add['+iIndividualSetProduct+'][]=' + sValue);
                });

                iIndividualSetProduct++;
            }
        }
        if (iIndividualSetProduct === 0) {
            return false;
        }

        aReturn.push("save_data=" + JSON.stringify(eval('aSetIndividaulSaveData' + oPrdData.set_product_no)));
        aReturn.push("set_product_no=" + oPrdData.set_product_no);
        aReturn.push("product_code=" + oPrdData.set_product_code);
        aReturn.push("is_set_product=" + oPrdData.is_set_product);
        aReturn.push("set_product_type=S");

        return aReturn;
    },

    /**
     * 상품선택여부
     */
    isChecked: function()
    {
        if ($("input[name='wish_idx[]']:checked").length > 0) {
            return true;
        }

        return false;
    },

    /**
     * 옵션ID 식별자명
     * @return string
     */
    getOptionIdentifier: function()
    {
        // 최근본 상품 호환을 위해
        var sIdentifierId = "wishlist_product_option";
        if ($("select[id^='wishlist_product_option']").length == 0 && $("select[id^='product_option']").length > 0) {
            sIdentifierId = "product_option";
        }

        return sIdentifierId;
    },

    checkEnableProduct : function(bIsChecked, sMode)
    {
        //ECHOSTING-159244 bIsChecked 값이 false 일때는 회원 전용상품은 로그인페이지로 이동
        var bResult = true;
        var bReserve = false;
        var sErrorMsg = '';

        //ECHOSTING-159244 회원 전용 상품 목록용 배열
        var aPurschaseExceptProductName = new Array();
        var iCheckedProduct = 0;
        var oWishlistElement = $("input[name='wish_idx[]']");

        //구매가 불가능한 상품이름(품절 또는 옵션이 없는상품 또는 독립형
        var aExceptProductName = [];
        var oSetProductName = {};

        oWishlistElement.each(function(iIndex) {
            //선택구매인데 체크가 안되어있다면 패스
            if (bIsChecked === true && $(this).attr('checked') !== true) {
                return;
            }

            //ECHOSTING-159244
            if (NewWishlist.isPurchase(iIndex, false) === false) {
                aPurschaseExceptProductName.push(aWishlistProductData[iIndex].product_name);
                return;
            }

            //ECHOSTING-162635
            if ($(this).attr('reserve-order') === 'R') {
                bReserve = true;
            }

            var sProductNameKey = aWishlistProductData[iIndex].product_no;
            if (aWishlistProductData[iIndex].is_set_product === 'T' && aWishlistProductData[iIndex].set_product_no > 0) {
                sProductNameKey = aWishlistProductData[iIndex].set_product_no;
                if (aWishlistProductData[iIndex].product_no === aWishlistProductData[iIndex].set_product_no) {
                    sProductNameKey = aWishlistProductData[iIndex].set_product_no+'-1';
                }
            }

            if (oSetProductName.hasOwnProperty(sProductNameKey) === false) {
                oSetProductName[sProductNameKey] = {'bind':[], 'product_no':[], 'set_product_no':[], 'set_product_name':[]};
            }
            if (parseInt($(this).attr('enable-order')) !== 1 || NewWishlist.isCheckAddOptionEnable(iIndex) === false || NewWishlist.isPriceContentProduct(iIndex) === true) {
                bResult = false;
                var sProductName = aWishlistProductData[iIndex].product_name;
                oSetProductName[sProductNameKey]['bind'].push(sProductName);
                oSetProductName[sProductNameKey]['product_no'].push(aWishlistProductData[iIndex].product_no);
                oSetProductName[sProductNameKey]['set_product_no'].push(aWishlistProductData[iIndex].set_product_no);
                oSetProductName[sProductNameKey]['set_product_name'].push(aWishlistProductData[iIndex].set_product_name);
                return;
            }
        });

        if (sMode === 'orderAll') {
            iCheckedProduct = oWishlistElement.length;
        } else {
            iCheckedProduct = $("input[name='wish_idx[]']:checked").length;
        }

        //ECHOSTING-159244 회원 전용 구매 상품이 있다면
        if (aPurschaseExceptProductName.length > 0) {

            if (iCheckedProduct === aPurschaseExceptProductName.length) {
              //ECHOSTING-159244 비회원으로 구매 가능한 상품이 0 개면 로그인페이지로 무조건 이동
              check_action_nologin();
              return false;
            }

            sErrMsg = __('로그인 하셔야 주문 가능한 상품이 있습니다.\n비회원인 경우 회원가입 후 이용하여 주세요.');

            if (confirm(sErrMsg) === false) {
                return false;
            } else {
                location.href = '/member/login.html?noMember=1&returnUrl=' + escape(window.location.href);
                return false;
            }

            NewWishlist.bIsOrderProc = true;
        }

        var sErrMsg = '';
        if (bResult === false) {
            NewWishlist.bIsOrderProc = false;

            sErrMsg = __('품절 또는 옵션을 직접 선택해야만 주문 가능한 상품이 있습니다. 해당 상품을 제외해 주세요. \n상품명 :');
            for (var sProductNameKey in oSetProductName) {
                var oRow = oSetProductName[sProductNameKey];
                if (oRow.bind.length === 0) {
                    continue;
                }
                var iSetProductNum = oRow.set_product_no[0];
                if (iSetProductNum === 0) {
                    aExceptProductName.push(oRow.bind[0]);
                } else {
                    if ($('input[is-set-product='+iSetProductNum+']').length === oRow.bind.length) {
                        aExceptProductName.push(oRow.set_product_name[0]);
                    } else {
                        aExceptProductName = $.merge(aExceptProductName, oSetProductName[sProductNameKey].bind);
                    }
                }
            }
            sErrMsg = this.getExceptProductNameListAlertMsg(sErrMsg, aExceptProductName);
            alert(sErrMsg);
        }

        //ECHOSTING-162635
        if (bReserve === true && bResult === true) {
            alert(__('ITEMS.MAY.SHIPPED', 'SHOP.JS.FRONT.NEW.WISH.LIST'));
        }


        return bResult;
    },

    getExceptProductNameListAlertMsg : function(sErrMsg, aExceptProduct)
    {
        var aUniqueName = [];
        var sErrAlertMsg = sErrMsg;

        $(aExceptProduct).each(function(iIndex) {
            if (aUniqueName.indexOf(aExceptProduct[iIndex]) === -1) {
                sErrAlertMsg += aExceptProduct[iIndex] + '\n';
                aUniqueName.push(aExceptProduct[iIndex]);
            }
        });
        return sErrAlertMsg;
    },

    isCheckAddOptionEnable : function(iIndex)
    {
        var bResult = true;
        var aRequiredOptAdd = NewWishlist.getRequiredAddOption(iIndex); // 필수 추가입력 옵션값만 가져오기
        for (var j in aRequiredOptAdd) {
            if (aRequiredOptAdd[j] == '') {
                //alert(__('옵션 정보에 문제가 있는 상품이 포함되어 있습니다. \n확인 후 다시 시도해 주세요.'));
                bResult = false;
                break;
            }
        }

        return bResult;
    },

    /**
     * 관심상품 배송유형 섞였는지 체크
     * @param boolean bIsAll 전체상품인지 선택상품인지(true : 전체, false : 선택)\
     * @return boolean false:오직하나,true:해외/국내 석임
     */
    isDifferentDelvType: function(bIsAll)
    {
        var aDelvType = [];
        if (bIsAll) {
            // delvtype 값 모으기
            for (var i = 0, length = aWishlistProductData.length; i < length; i++) {
                if ($.inArray(aWishlistProductData[i].delvtype, aDelvType) < 0) {
                    aDelvType.push(aWishlistProductData[i].delvtype);
                }
            }
        } else {
            var aWishIdx = [];
            $("input[name='wish_idx[]']:checked").each(function() {
                aWishIdx.push(parseInt($(this).val()));
            });

            // delvtype 값 모으기
            for (var i = 0, length = aWishlistProductData.length; i < length; i++) {
                if ($.inArray(aWishlistProductData[i].wish_list_idx, aWishIdx) >= 0) {
                    if ($.inArray(aWishlistProductData[i].delvtype, aDelvType) < 0) {
                        aDelvType.push(aWishlistProductData[i].delvtype);
                    }
                }
            }
        }

        if (aDelvType.length > 1) {
            return true;
        }

        return false;
    },
    isPriceContentProduct : function(iIndex)
    {
        var sTargetData = null;
        if (iIndex === undefined) {
            for (var x in aWishlistProductData) {
                sTargetData = aWishlistProductData[x].product_price_type;
                if (sTargetData === 'T') {
                    return true;
                }
            }
        } else {
            sTargetData = aWishlistProductData[iIndex].product_price_type;
            return sTargetData === 'T';
        }

        return false;
    },
    /*
     *  관심상품 리스트에서 옵션 변경 레이어 창
     */
    showOptionChangeLayer : function(sLayerId)
    {
        $('.optionModify').hide();
        $('#'+sLayerId).show();

        if (bIsNewProduct === true ) {
            $("#" + sLayerId).find('[id^="wishlist_product_option_id"]').eq(0).val('*').trigger('change');
        }
    },
    /**
     * 상품중 무이자 할부 타입의 상품만 있는지 체크
     */
    isAllInstallmentType : function(aWhishListProduct) {
        var iTotalBasketCount = aWhishListProduct.length;
        var bIsAllInstallmentType = true;
        for (var i=0; i < iTotalBasketCount; i++) {
            if (aWhishListProduct[i].basket_type != 'A0001') {
                bIsAllInstallmentType = false;
            }
        }
        return bIsAllInstallmentType;
    },

    /**
     * 모바일 관심상품페이지 옵션선택 레이어
     */
    setOptionSelectMobile : function(iProductNo) {

        //구매옵션레이어 사용가능 여부 세팅
        EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.init(function() {
            EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.setLayer(iProductNo, 1, 'wishlist');
        });
        //EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.setLayer(iProductNo, 1, 'wishlist');
    },

    /**
     * 옵션선택 레이어 위치지정
     */
    setVisibleOptionLayer : function() {
        var iTop = parseInt(( $(window).height() - $("#productOptionIframe").height() ) / 2);
        $("#opt_layer_iframe_parent").css({"top": iTop, "left": 0});
        $('html, body').css({'overflow-y': 'hidden', width: '100%', height: '100%'});
        $('#opt_layer_window').show();
    },

    /**
     * 분리 세트 동시 체크
     */
    setIndividualProductCheck : function(aWishList, bIsChecked) {
        if (typeof(aWishList) != "undefined") {
            var iSetPrdNo = aWishList.set_product_no;

            for (i = 0; i < aWishlistProductData.length; i++) {
                if (iSetPrdNo == aWishlistProductData[i].set_product_no) {
                    $('#wish_idx_' + i ).attr('checked', bIsChecked);
                }
            }

        }
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

var SET_OPT_CLASS_PREFIX = "SetProductOption";
var SET_ADDOPT_CLASS_PREFIX = "SetProductAddOption";

/**
 * 뉴상품 세트상품 상품옵션변경
 */
var NewWishSetOption = {
    /**
     * '옵션변경'레이어에서 '적용하기' 버튼 클릭
     * @param int iIdx 품목정보배열 index
     * @param int iChildIdx 세트 자식품목정보배열 index
     * @param string sMode 액션모드(modify: 변경, add: 추가)
     */
    modify : function(iIdx, iChildIdx)
    {
        // 사용자지정옵션인경우 옵션변경불가(기존사양)
        // if (aWishlistProductData[iIdx].option_add == "T") {
        //     alert(__("사용자 지정 옵션 상품은 옵션변경을 하실 수 없습니다."));
        //     $('#quantity_id_'+i).val(aWishlistProductData[iIdx].quantity);
        //     return false;
        // }

        //필수옵션 체크
        if (this.checkOptionRequired(iIdx, iChildIdx) == false) return;

        //추가옵션 체크
        if (this.checkAddOption(iIdx, iChildIdx) == false) return;

        // 해당옵션
        var iWishIdx = aWishlistProductData[iIdx].wish_list_idx;
        var oPrdData = aWishProductSetData[iWishIdx][iChildIdx];

        // 오직 추가옵션만 있는지
        var isOnlyOptionAdd = false;
        if (oPrdData.has_option == "F" && oPrdData.has_option_add == "T") {
            isOnlyOptionAdd = true;
        }
        // 상품번호
        var iProductNo = oPrdData.product_no;

        // 품목코드
        var sItemCode = oPrdData.item_code;

        // 수량
        var iQuantity = oPrdData.qty;

        // 품목코드 추출
        var oItemInfo = this.getItemInfo(iIdx, iChildIdx, oPrdData, isOnlyOptionAdd);

        // 선택옵션인경우만 체크
        if (isOnlyOptionAdd == false) {
            // 재고정보 추출
            var sKey = "option_stock_data" + iProductNo;
            var oItemStock = $.parseJSON(window[sKey]);

            // 재고체크
            var oItem = oItemStock[oItemInfo.item_code];
            if (oItem.use_stock === true) {
                if (oItem.is_selling == "F") {
                    alert(__('재고 수량이 부족합니다.'));
                    return false;
                }
            }
        }

        // 동일품목 추가여부 확인
        var sClassName    = SET_ADDOPT_CLASS_PREFIX + iIdx;
        var iCntAddOption = $("input[class^='" + sClassName + "']:visible").length;
        if ((sItemCode == oItemInfo.item_code) && iCntAddOption == 0) {
            alert(__('이미 등록되어 있습니다.'));
            return false;
        }

        // 추가입력 옵션 가져오기
        var aAddOptionName = [];
        var aAddOption     = [];
        $("input[class^='" + sClassName + "']:visible").each(function() {
            aAddOptionName.push($(this).attr("optionname"));
            aAddOption.push($(this).val());
        });

        // 옵션변경
        var aParams = [];
        aParams.push("command=update");
        aParams.push("is_set_product=" + "T");
        aParams.push("wish_idx=" + iWishIdx);
        aParams.push("product_no=" + oItemInfo.product_no);
        aParams.push("item_code=" + oItemInfo.item_code);
        aParams.push("item_code_before=" + oPrdData.item_code);
        aParams.push("quantity=" + iQuantity);
        aParams.push("opt_str=" + oItemInfo.opt_str);
        aParams.push("add_option_name=" + aAddOptionName.join(";"));

        for (var i = 0, length = aAddOption.length; i < length; i++) {
            aParams.push("option_add[]=" + aAddOption[i]);
        }

        // action
        action_wishlist(aParams.join("&"), "wishlist");
    },

    /**
     * 필수옵션 체크 여부
     * @param int iIdx 품목정보배열 index
     * @param int iChildIdx 세트 자식품목정보배열 index
     * @return bool true: 체크 / false: 체크안함
     */
    checkOptionRequired : function(iIdx, iChildIdx)
    {
        var bIsChcecked = true;
        var sClassName = SET_OPT_CLASS_PREFIX + iIdx + '-' + iChildIdx;
        $("select[class*='" + sClassName + "']:visible").each(function() {
            if ($(this).attr('required')) {
                if ($('option:selected', this).val().indexOf('*') > -1) {
                    alert(__('필수 옵션을 선택해주세요.'));
                    $(this).focus();
                    bIsChcecked = false;
                    return false;
                }
            }
        });

        return bIsChcecked;
    },


    /**
     * 추가옵션 체크
     * @return bool true: 추가옵션이 다 입력되었으면 / false: 아니면
     * @param int iIdx 품목정보배열 index
     * @param int iChildIdx 세트 자식품목정보배열 index
     * @return boolean
     */
    checkAddOption: function(iIdx, iChildIdx)
    {
        var bIsChcecked = true;
        var sClassName = SET_ADDOPT_CLASS_PREFIX + iIdx;
        $("[class^='" + sClassName + "']:visible").each(function() {
            if ($(this).attr("require") == "T") {
                if ($(this).val().replace(/^[\s]+[\s]+$/g, '').length == 0) {
                    alert(__('추가 옵션을 입력해주세요.'));
                    $(this).focus();
                    bIsChcecked = false;
                    return false;
                }
            }
        });

        return bIsChcecked;
    },

    /**
     * 뉴상품의 경우 아이템 코드를 받아오는 로직
     * @param int iIdx 품목정보배열 index
     * @param int iChildIdx 세트 자식품목정보배열 index
     * @param int iProductNo 상품번호
     */
    getItemInfo : function(iIdx, iChildIdx, oPrdData, isOnlyOptionAdd)
    {
        var sClassName = SET_OPT_CLASS_PREFIX + iIdx + '-' + iChildIdx;
        var oItemInfo = {
            "product_no": oPrdData.product_no,
            "item_code": "",
            "opt_id": "",
            "opt_str": ""
        };

        // 오직 추가옵션만 있는경우 임의 가공
        if (isOnlyOptionAdd) {
            oItemInfo.item_code = oPrdData.product_code + "000A";
            oItemInfo.opt_id = "000A";
            return oItemInfo;
        }


        if (eval("item_listing_type" + oPrdData.product_no) == "C") {
            oItemInfo.item_code = $("." + sClassName + ":visible").val();
            oItemInfo.opt_str = $("." + sClassName + " :selected").text();
            oItemInfo.opt_str = oItemInfo.opt_str.replace(/\-/g, "/");
        } else {
            var aItemValue = [];
            $("select[class*='" + sClassName + "']:visible").each(function() {
                aItemValue.push($(this).val());
            });
            var aItemMapper = $.parseJSON(eval("option_value_mapper" + oPrdData.product_no));
            oItemInfo.item_code = aItemMapper[aItemValue.join("#$%")];
            oItemInfo.opt_str = aItemValue.join("/");
        }
        oItemInfo.opt_id = oItemInfo.item_code.substr(8);

        return oItemInfo;
    }
};
var categoryOddColor = new Object();
var categoryEvenColor = new Object();

$(document).ready(function()
{
    // 카테고리타입
    var aCategoryType = new Array('normal', 'reco', 'new', 'project', 'main');
    // 상품 ID prefix
    var sProductIdPrefix = 'product_';
    // 옵션 미리보기 아이콘 ID prefix
    var sOptPreviewIconId = 'opt_prv_id_';
    // 옵션 미리보기 레이어 ID prefix
    var sOptPreviewLayerId = 'opt_prv_layer_id_';
    // 옵션 미리보기 닫기 버튼 ID prefix
    var sOptPreviewCloseId = 'opt_prv_close_id_';

    // 상품요약정보 (툴팁)
    if ($('.tooltip').length > 0 && $.fn.Tooltip) {
        $('.tooltip').Tooltip({
            'name' : 'toolTipStyle',
            'delay' : '0',
            'top' : '-200',
            'left' : '10',
            'fade' : false,
            'opacity' : 1
        });
    }

    /**
     * 카테고리 타입별로 홀짝수 라인색상 설정
     */
    var iCategoryTypeLen = aCategoryType.length;
    for ( var i = 0; i < iCategoryTypeLen; i++) {
        var iBeforeOffsetTop = -1;
        var sCategoryType = aCategoryType[i];
        var sBgColor = categoryOddColor[sCategoryType];
        $('[id^="' + sProductIdPrefix + aCategoryType[i] + '_"]').each(function(idx)
        {
            if ((idx > 0) && $(this).attr('offsetTop') != iBeforeOffsetTop) {
                sBgColor = (sBgColor == categoryOddColor[sCategoryType]) ? categoryEvenColor[sCategoryType] : categoryOddColor[sCategoryType];
            }
            iBeforeOffsetTop = $(this).attr('offsetTop');
            $(this).css('background-color',sBgColor);
        });
    }

    $('#selArray').change(function(){
        location.href = $(this).val();
    });

    var sSortName = CAPP_SHOP_FRONT_COMMON_UTIL.getParameterByName('sort_method');

    if (sSortName !== '') {

        if (sSortName.indexOf('#Product_ListMenu') < 0 ) {
            sSortName = sSortName + '#Product_ListMenu';
        }

        $('#selArray>option').each( function() {
            if ($(this).val().indexOf(sSortName) > 0 ) {
                $(this).attr("selected","true");
            }
        });
    }


    /**
     * 옵션아이콘 onmouseover 핸들러
     */
    $('[id^="' + sOptPreviewIconId + '"]').mouseover(function()
    {
        if (sOptionPreviewMethod.indexOf('mouseover') > -1)
            setOptLayerDisplay($(this));
    });

    /**
     * 옵션아이콘 onmouseclick 핸들러
     */
    $('[id^="' + sOptPreviewIconId + '"]').click(function()
    {
        if (sOptionPreviewMethod.indexOf('mouseclick') > -1)
            setOptLayerDisplay($(this));
    });

    /**
     * 옵션 하나만 선택가능 옵션 동작
     */
    try {
        $('[name="item_code[]"]').live('click', function () {
            if ($.data(document, 'sUseOptionOne_class') === 'T') {
                if ($('input[name="item_code[]"][option_name="' + $(this).attr('option_name') + '"]:checked').length > 1) {
                    alert(__('옵션별로 1개 씩만 선택 가능한 상품입니다.'));
                    $(this).attr('checked', false);
                }

            }
        });
    } catch (e) {}
    /**
     * 옵션 레이어 display 조절
     *
     * @param object optIcon 옵션 아이콘 JQuery 객체
     * @param string sPopupMethod 팝업 method (mouseover|mouseclick)
     */
    function setOptLayerDisplay(optIcon, sPopupMethod)
    {
        var aParam = getOptionParams(optIcon.attr('id'),sOptPreviewIconId);
        // 모든 옵션미리보기창 닫기
        $('[id^="' + sOptPreviewLayerId + '"]').each(function()
        {
            $(this).css('display','none');
        });

        // 선택된 옵션미리보기창 출력
        var sLayerId = '#' + sOptPreviewLayerId + aParam['product_no'];
        var aPos = findPos(optIcon.get(0));
        $(sLayerId).css('position','absolute');
        $(sLayerId).css('left',aPos['left']);
        $(sLayerId).css('top',(aPos['top'] + optIcon.attr('offsetHeight')) + 'px');
        $(sLayerId).css('display','');
        $(sLayerId).css('z-index','9999');
    }

    /**
     * 옵션아이콘 onmouseout 핸들러
     */
    $('[id^="' + sOptPreviewIconId + '"]').mouseout(function()
    {
        var aParam = getOptionParams($(this).attr('id'),sOptPreviewIconId);
        if (sOptionLayerCloseMethod != 'use_close_button')
            $('#' + sOptPreviewLayerId + aParam['product_no']).css('display','none');
    });

    /**
     * 옵션 레이어 onmouseover 핸들러
     */
    $('[id^="' + sOptPreviewLayerId + '"]').mouseover(function()
    {
        $(this).css('display','');
    });

    /**
     * 옵션 레이어 onmouseout 핸들러
     */
    $('[id^="' + sOptPreviewLayerId + '"]').mouseout(function()
    {
        if (sOptionLayerCloseMethod != 'use_close_button')
            $(this).css('display','none');
    });

    /**
     * 옵션 레이어 닫기버튼 클릭 핸들러
     */
    $('[id^="' + sOptPreviewCloseId + '"]').click(function()
    {
        var aParam = getOptionParams($(this).attr('id'),sOptPreviewCloseId);
        $('#' + sOptPreviewLayerId + aParam['product_no']).css('display','none');
    });

    /**
     * HTML 오브젝트의 위치값 계산
     *
     * @param object obj 위치를 알고자 하는 오브젝트
     * @return object left, top 값
     */
    function findPos(obj)
    {
        var iCurLeft = iCurTop = 0;

        if (obj.offsetParent) {
            do {
                iCurLeft += obj.offsetLeft;
                iCurTop += obj.offsetTop;
            } while (obj = obj.offsetParent);
        }

        return {
            'left' : iCurLeft,
            'top' : iCurTop
        };
    }

    /**
     * 옵션관련 ID를 파싱해서 파라메터 추출, 반환
     *
     * @param string sId ID
     * @param string sPrefix 파싱할 때 삭제할 prefix
     * @return array 상품번호+팝업method
     */
    function getOptionParams(sId, sPrefix)
    {
        var aTmp = sId.replace(sPrefix,'').split('_');
        return {
            'product_no' : aTmp[0],
            'popup_method' : aTmp[1]
        };
    }

    // 할인기간 레이어 열기
    $('.shippingFee').delegate('.deliveryBenefitDetailInfo', 'click', function() {
        var oLayerDiscountDelivery = $(this).parent();

        if (oLayerDiscountDelivery.attr('benefitinfoLoaded') === undefined) {

            var iProductNo = $(this).attr("productno");
            var sBenefitType = $(this).attr("benefit_type");

            var oHtml = $('<div>');
            oHtml.addClass('ec-base-tooltip');
            oHtml.addClass('wrap');
            $(this).parent().append(oHtml);

            $.post('/exec/front/Product/Benefitinfo', 'benefit_type='+sBenefitType+'&product_no=' + iProductNo, function(sHtml) {
                oHtml.html(sHtml);
                oLayerDiscountDelivery.attr('benefitinfoLoaded', true);
            });
        }

        oLayerDiscountDelivery.find('div.ec-base-tooltip').show();
        oLayerDiscountDelivery.find('span.arrow').show();

        return false;
    });


    // 할인기간 레이어 열기
    $('.discountPeriod > a').mouseover(function() {
        $('.layerDiscountPeriod').hide();
        $(this).parent().find('.layerDiscountPeriod').show();
    }).mouseout(function() {
        $('.layerDiscountPeriod').hide();
    });

    // 차등 배송비 사용시 ToolTip 열기
    try {
        $('.btnTooltip > a').live('click',function() {
           $('.btnTooltip > .differentialShipping').show();
        });
    } catch (e) {}
    // 차등 배송비 사용시 ToolTip 닫기
    $('.btnTooltip > .differentialShipping a').unbind().click(function() {
        $('.btnTooltip > .differentialShipping').hide();
    });


    // 배송비정보 레이어 닫기
    try {
        $('.ec-base-tooltip > .close').live("click",function() {
            $(this).parents('.ec-base-tooltip').hide();
            $(this).parents('.differentialShipping').hide();
            return false;
        });
    } catch (e) {}

    // 배송비정보 레이어 닫기
    try {
        $('.differentialShipping > .close > a').live("click",function() {
            $(this).parents('.differentialShipping').hide();
        });
    } catch (e) {}

    COLORCHIPLIST.init();
    CAPP_PRODUCT_LIST_WISHICON.init();

});

var CAPP_PRODUCT_LIST_WISHICON = {
    iDuplicateSecond : 2000, //중복 클릭 제한시간
    iClickCount : 0,
    iRecentClickProductNo : 0,
    iTimeoutId: 0, // 중복방지 대기시간 실행 시퀀스 번호
    init : function()
    {
        var iProductNo = 0;
        var iCategoryNo = 0;
        var oObj = null;
        var sLogin = '';
        var bIsIndividualSetProduct = false;
        try {
            $('.ec-product-listwishicon').live('click', function () {
                oObj = $(this);
                iProductNo = parseInt(oObj.attr('productno'));
                iCategoryNo = oObj.attr('categoryno');
                sLogin = oObj.attr('login_status');
                bIsIndividualSetProduct = oObj.attr('individual-set') === 'T';

                if (sLogin !== 'T') {
                    alert(__('로그인 후 관심상품 등록을 해주세요.'));
                    location.href = '/member/login.html?returnUrl=' + encodeURIComponent(location.href);
                    return;
                }


                if (CAPP_PRODUCT_LIST_WISHICON.iRecentClickProductNo === iProductNo) {
                    if (CAPP_PRODUCT_LIST_WISHICON.iClickCount === 1) {
                        CAPP_PRODUCT_LIST_WISHICON.iClickCount++;
                        CAPP_PRODUCT_LIST_WISHICON.initCount();
                    } else if (CAPP_PRODUCT_LIST_WISHICON.iClickCount > 1) {
                        return;
                    }
                } else {
                    CAPP_PRODUCT_LIST_WISHICON.iClickCount = 0;
                    CAPP_PRODUCT_LIST_WISHICON.iRecentClickProductNo = iProductNo;
                    if (CAPP_PRODUCT_LIST_WISHICON.iTimeoutId > 0) {
                        clearTimeout(CAPP_PRODUCT_LIST_WISHICON.iTimeoutId);
                    }
                }

                // DB 처리전 카운트를 해야 정확히 중복체크가능
                CAPP_PRODUCT_LIST_WISHICON.iClickCount++;

                if (oObj.attr('icon_status') === 'on') {
                    sCommand = 'del';
                } else {
                    sCommand = 'add';
                }

                var sUrl = '/exec/front/Product/Wishlist/';
                var sParam = 'command=' + sCommand + '&from=wish_icon';
                sParam += '&referer=' + encodeURIComponent('//' + location.hostname + location.pathname + location.search);
                sParam += '&product_no=' + iProductNo + '&cate_no=' + iCategoryNo;
                if (bIsIndividualSetProduct === true) {
                    sParam += '&set_product=T';
                }

                $.post(
                    sUrl,
                    sParam,
                    function (data) {
                        CAPP_PRODUCT_LIST_WISHICON.getResultWishIconAjax(data, oObj);
                    },
                    'json');
            });
        } catch (e) {}
    },

    /**
     * 클릭후 시간체크
     */
    initCount: function()
    {
        CAPP_PRODUCT_LIST_WISHICON.iTimeoutId = setTimeout(function() {
            CAPP_PRODUCT_LIST_WISHICON.iClickCount = 0;
        }, CAPP_PRODUCT_LIST_WISHICON.iDuplicateSecond);
    },

    getResultWishIconAjax : function(aData, oObj)
    {
        var STORAGE_KEY = 'localWishList' +  EC_SDE_SHOP_NUM;
        if (aData == null) return;
        if (aData.result == 'SUCCESS') {
            var iProductNo = $(oObj).attr('productno');

            $('.ec-product-listwishicon[productno="'+iProductNo+'"]').each(function() {
                var oStorageData = $.parseJSON(sessionStorage.getItem(STORAGE_KEY));
                if ($(this).attr('icon_status') === 'off') {
                    var src = aData.data.wish_icon.on;
                    var alt = aData.data.wish_alt.on;
                    if (oStorageData !== null && oStorageData.hasOwnProperty('on_tags') === true) {
                        src = oStorageData.on_tags.src;
                        alt = oStorageData.on_tags.alt;
                    }
                    $(this).attr('src', src);
                    $(this).attr('alt', alt);
                    $(this).attr('icon_status', 'on');
                } else {
                    var src = aData.data.wish_icon.off;
                    var alt = aData.data.wish_alt.off;
                    if (oStorageData !== null && oStorageData.hasOwnProperty('off_tags') === true) {
                        src = oStorageData.off_tags.src;
                        alt = oStorageData.off_tags.alt;
                    }
                    $(this).attr('src', src);
                    $(this).attr('alt', alt);
                    $(this).attr('icon_status', 'off');
                }
            });

            if (CAPP_ASYNC_METHODS.hasOwnProperty('WishList') === true) {
                // 관심상품 추가/삭제시 sessionStorage 추가/삭제 처리
                CAPP_ASYNC_METHODS.WishList.setSessionStorageItem(iProductNo, sCommand);
            }

            if (CAPP_ASYNC_METHODS.hasOwnProperty('Wishcount') === true) {
                CAPP_ASYNC_METHODS.Wishcount.restoreCache();
                CAPP_ASYNC_METHODS.Wishcount.execute();
            }

            if (sCommand === 'add') { // 위시리스트 등록시에만 실행
                EC_PlusAppBridge.addWishList(iProductNo);
            }

        } else if (aData.result == 'ERROR') {
            alert(__('실패하였습니다.'));
        } else if (aData.result == 'NOT_LOGIN') {
            alert(__('회원 로그인 후 이용하실 수 있습니다.'));
        } else if (aData.result == 'INVALID_REQUEST') {
            alert(__('파라미터가 잘못되었습니다.'));
        }
    }
};

//컬러칩 이미지 변경(상품리스트)
var COLORCHIPLIST = {
    init : function() {
        var sSelector = '';
        if (mobileWeb === false) {
            sSelector = 'div.color > .chips';
        } else {
            sSelector = '.xans-product-colorchip .chips';
        }
        try {
            $(sSelector).live('mouseover click touchstart', function() {
                var iColorNo = $(this).attr('color_no');
                var iDisplayGroup = $(this).attr('displayGroup');

                if (iColorNo != '') {
                    $(this).css('cursor', 'pointer');
                    COLORCHIPLIST.getImage(this, iColorNo, iDisplayGroup);
                }
            });
        } catch (e) {}
    },

    getImage : function(oObj, iColorNo, iDisplayGroup) {
        var sImageUrl = $.data($(oObj)[0], 'image');

        if (sImageUrl == undefined) {
            COLORCHIPLIST.getAjax(oObj, iColorNo, iDisplayGroup);
        } else {
            COLORCHIPLIST.setDisplayImage(oObj);
        }
    },

    getAjax : function(oObj, iColorNo, iDisplayGroup)
    {
        $.get(
            '/exec/front/Product/Colorimage',
            'iColorNo=' + iColorNo + '&iDisplayGroup=' + iDisplayGroup,
            function(sResponse) {
                if (sResponse != '') {
                    var oJson = $.parseJSON(sResponse);
                    $.data($(oObj)[0], 'image', oJson.sImageUrl);
                    $.data($(oObj)[0], 'displayGroup', oJson.iDisplayGroup);
                    $.data($(oObj)[0], 'product_no', oJson.iProductNo);
                    COLORCHIPLIST.setDisplayImage(oObj);
                }
            }
        );
    },

    setDisplayImage : function(oObj)
    {
        var iDisplayGroup = $.data($(oObj)[0], 'displayGroup');
        var iProductNo = $.data($(oObj)[0], 'product_no');
        var sImageUrl = $.data($(oObj)[0], 'image');

        var oEl = $('#eListPrdImage' + iProductNo + '_' + iDisplayGroup);
        oEl.attr('src', sImageUrl);


    }
};

// 상품 확대보기 아이콘 ID prefix
var sProductZoomIdPrefix = 'product_zoom_';

/**
 * 상품 확대보기
 *
 * @param int iProductNo 상품번호
 * @param int iCategoryNo 카테고리 번호
 * @param int iDisplayGroup display_group
 * @param string sLink 팝업창 URL
 * @param string sOption 팝업 옵션
 */
function zoom(iProductNo, iCategoryNo, iDisplayGroup, sLink, sOption)
{
    // 팝업창 링크
    var sLink = sLink ? sLink : '/product/image_zoom.html';
    sLink += '?product_no=' + iProductNo + '&cate_no=' + iCategoryNo + '&display_group=' + iDisplayGroup;
    // 팝업창 옵션
    var sOptions = sOption ? sOption : 'toolbar=no,scrollbars=no,resizable=yes,width=800,height=640,left=0,top=0';
    // 팝업창 이름
    var sWinName = 'image_zoom';

    window.open(sLink,sWinName,sOptions);
}

/**
 * 상품상세 확대보기
 *
 * @param int iProductNo 상품번호
 * @param int iCategoryNo 카테고리 번호
 * @param int iDisplayGroup display_group
 * @param string sLink 팝업창 URL
 * @param string sOption 팝업 옵션
 */
function zoom2(iProductNo, iCategoryNo, iDisplayGroup, sLink, sOption)
{
    // 팝업창 링크
    var sLink = sLink ? sLink : '/product/image_zoom2.html';
    sLink += '?product_no=' + iProductNo + '&cate_no=' + iCategoryNo + '&display_group=' + iDisplayGroup;
    // 팝업창 옵션
    var sOptions = sOption ? sOption : 'toolbar=no,scrollbars=no,resizable=yes,width=800,height=640,left=0,top=0';
    // 팝업창 이름
    var sWinName = 'image_zoom2';

    window.open(sLink,sWinName,sOptions);
}

/**
 * 상품 진열시 높이가 달라서 li가 깨지는 현상이 나타날때 이를 진열된 상품의 기준으로 높이를 다시 재설정해주는 스크립트입니다.
 * 이 스크립트는 반드시 고정폭에서 사용되어야 합니다.
 * 해당스크립트 실행문은 각각 모듈의 js에서 합니다.
 */
$.fn.productResize = function(nodeName) {
    nodeName = nodeName || 'li';

    return $(this).each(function() {
        var iTargetHeight = 0;
        var aTargetElement = new Array();
        var nodes = $(this).find(nodeName);
        var iFirstChildDepth = $(nodes[0]).parents().length; // 타겟 depth
        for (var x = 0; x < nodes.length; x++) {
            if ($(nodes[x]).parents().length == iFirstChildDepth) {
                aTargetElement.push(x);
                if (iTargetHeight < $(nodes[x]).height()) {
                    iTargetHeight = $(nodes[x]).height();
                }
            }
        }
        for (var x in aTargetElement) {
            $(nodes[aTargetElement[x]]).height(iTargetHeight);
        }
    });
};
/**
 * 상품 리스트에서 쓰이는 기능 모음 1. 옵션 미리보기 2. 장바구니 넣기 3. 이미지 줌 4. 요약정보
 */
var EC_ListAction = {
    getOptionSelect : function(iProductNo, iCategoryNo, iDisplayGroup, sBasketType)
    {
        element = document;
        $('div.xans-product-basketoption').remove();
        $.get(basket_option,{
            'product_no' : iProductNo,
            'cate_no' : iCategoryNo,
            'display_group' : iDisplayGroup,
            'basket_type' : sBasketType
        },function(sHtml)
        {
            $('body').append($(sHtml.replace(/[<]script( [^ ]+)? src=\"[^>]*>([\s\S]*?)[<]\/script>/g,"")));
        });
    },
    getOptionSelectValidate : function(sType)
    {
        var iCheckCount = 0;
        var bReturn = true;
        var bFirst = true;
        var eLists = $('.xans-product-optionlist');
        var iProductMin = parseInt($.data(document,'ProductMin_class'),10);

        // 뉴상품인 경우에만 있는 데이터
        var iProductMax = parseInt($.data(document,'ProductMax_class'),10);
        var iBuyUnit = parseInt($.data(document,'ProductBuyUnit_class'),10);

        if (isNaN(iBuyUnit) === true) iBuyUnit = 1;
        if (isNaN(iProductMax) === true) iProductMax = 0;

        if ($.data(document,'BundlePromotion') === true) {
            iBuyUnit = 1;
            iProductMin = 1;
            iProductMax = 0;
        }

        var sOptionType = $.data(document, 'sOptionType_class');
        var aOptionName = $.parseJSON($.data(document, 'aOptionName_class'));
        if (sOptionType === 'F') {
            $(aOptionName).each(function(i){
                if ($('input[option_name="'+aOptionName[i]+'"]:checked').length == 0 && $('input[option_name="'+aOptionName[i]+'"]').attr('require') === 'T') {
                    alert(__('필수옵션은 반드시 1개 이상 선택하셔야 구매 또는 장바구니 담기가 가능합니다.'));
                    eOptionName.focus();
                    bReturn = false;
                    bFirst = false;
                    return false;
                }
            });
            if (bReturn === false) {
                bFirst = false;
                return false;
            }
        }

        var aQuantity = new Array();
        for ( var x = 0; x < eLists.length; x++) {
            var eList = $(eLists[x]);
            eList.find('.' + $.data(document,'Check_class')).each(function() {
                if ($(this).attr('checked') === true) {
                    iCheckCount++;
                    eList.find('.' + $.data(document,'Quantity_class')).each(function() {
                        var iQuantity = parseInt($(this).val(), 10);
                        aQuantity.push(iQuantity);
                        if (bFirst === true) {
                            if (iQuantity < 1) {
                                alert(__('구매하실 수량을 입력해주세요'));
                                $(this).focus();
                                bReturn = false;
                                return false;
                            }

                            if (($(this).attr('stock') > 0 || $(this).attr('is_soldout') === 'T') && iQuantity > $(this).attr('stock')) {
                                alert(__('선택하신 옵션에 해당하는 상품의 재고 수량이 구매하실 수량보다 적습니다.'));
                                $(this).focus();
                                bReturn = false;
                                return false;
                            }

                            if (iQuantity % iBuyUnit !== 0) {
                                alert(sprintf(__('선택하신 상품은 %s개 단위로 구매 하실 수 있습니다.'), iBuyUnit));
                                $(this).focus();
                                bReturn = false;
                                return false;
                            }

                            if (iQuantity < iProductMin) {
                                alert(sprintf(__('최소 주문수량은 %s개 입니다.'), iProductMin));
                                $(this).focus();
                                bReturn = false;
                                return false;
                            }
                            if (iProductMax > 1 && iQuantity > iProductMax) {
                                alert(sprintf(__('최대 주문수량은 %s개 입니다.'), iProductMax));
                                $(this).focus();
                                bReturn = false;
                                return false;
                            }
                        }

                        if (bReturn === false) {
                            bFirst = false;
                        }
                    });
                    if (bReturn === false) {
                        bFirst = false;
                    }
                }
            });
        }
        if (iCheckCount < 1) {
            alert(__('구매 또는 장바구니에 담을 상품을 선택해주세요.'));
            return false;
        }
        if (bReturn === true) {
            if (typeof (EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE) === 'object') {
                var iProductNum = $.data(document,'iProductNo_class');
                if (EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig.hasOwnProperty(iProductNum) === true) {
                    if (EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig[iProductNum] !== null) {
                        if (EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.isValidQuantity(aQuantity, iProductNum) === false) {
                            return false;
                        }
                    }
                }
            }

            this.setBasketPrepare(sType);
        } else {
            return false;
        }
    },
    setBasketPrepare : function(sType)
    {
        var frm = this.getBasketForm();
        this.getHiddenElement('product_no',$.data(document,'iProductNo_class')).appendTo(frm);
        this.getHiddenElement('main_cate_no',$.data(document,'iCategoryNo_class')).appendTo(frm);
        this.getHiddenElement('display_group',$.data(document,'iDisplayGroup_class')).appendTo(frm);
        this.getHiddenElement('basket_type',$.data(document,'sBasketType_class')).appendTo(frm);
        this.getHiddenElement('product_min',$.data(document,'ProductMin_class')).appendTo(frm);
        this.getHiddenElement('delvtype',$('input[name="delvtype"]').val()).appendTo(frm);
        this.getHiddenElement('option_type','T').appendTo(frm);
        this.getHiddenElement('command','add').appendTo(frm);
        this.getHiddenElement('has_option','T').appendTo(frm);
        var eLists = $('.xans-product-optionlist');
        var bAddProduct = false;
        var sOptionParam = '';
        for ( var x = 0; x < eLists.length; x++) {
            var eList = $(eLists[x]);
            eList.find('.' + $.data(document,'Check_class') + ':checked').each(function()
            {
                var sOptionId = $(this).val();
                var iQuantity = eList.find('.' + $.data(document,'Quantity_class')).val();
                if (bAddProduct === false) {
                    var aOption = sOptionId.split('-');
                    var k = 0;
                    for ( var z = 0; z < aOption.length; z++) {
                        key = z + 1;
                        EC_ListAction.getHiddenElement('option' + key,aOption[z]).appendTo(frm);
                    }

                    eList.find('.' + $.data(document,'Quantity_class')).each(function()
                    {
                        EC_ListAction.getHiddenElement('quantity',iQuantity).appendTo(frm);
                        bAddProduct = true;
                    });
                } else {
                    var aBasketInfo = new Array();
                    aBasketInfo.push($.data(document,'iProductNo_class'));
                    aBasketInfo.push($.data(document,'iCategoryNo_class'));
                    aBasketInfo.push($.data(document,'iDisplayGroup_class'));
                    aBasketInfo.push($.data(document,'ProductMin_class'));
                    aBasketInfo.push('product_name');
                    aBasketInfo.push('product_price');
                    aBasketInfo.push('T');
                    aBasketInfo.push(iQuantity);
                    aBasketInfo.push($.data(document,'iOptionSize_class'));
                    var aOption = sOptionId.split('-');
                    var k = 0;
                    for ( var z = 0; z < aOption.length; z++) {
                        if (aOption[z] != '0') {
                            aBasketInfo.push(aOption[z]);
                        }
                    }
                    EC_ListAction.getHiddenElement('basket_info[]',aBasketInfo.join('|')).appendTo(frm);
                }

                if (iQuantity > 0) {
                    frm.append(getInputHidden('selected_item[]',iQuantity+'||'+sOptionId));
                }
            });
        }
        // 선택한상품만 주문하기
        if (sType == 1 || sType == 'naver_checkout') {
            // 이미 장바구니에 들어있는지 체크
            this.selectbuy_action($.data(document,'iProductNo_class'));
            EC_ListAction.getHiddenElement('quantity_override_flag', sIsPrdOverride).appendTo(frm);
        }

        var sAction = '/exec/front/order/basket/';
        action_basket(sType,'category',sAction,frm.serialize(),$.data(document,'sBasketType_class'));
        // 장바구니옵션창 자동으로 닫기게 처리-요거 처리 안하믄 레이어장바구니쪽에서 오류남 ECHOSTING-68196
        $('.xans-product-basketoption').remove();
    },
    getHiddenElement : function(sName, sValue)
    {
        return $('<input />').attr({
            'type' : 'hidden',
            'name' : sName,
            'value' : sValue
        });
    },
    getBasketForm : function()
    {
        return $('<form>').attr({
            'method' : 'POST',
            'name' : 'CategoryBasket'
        });
    },
    /**
     * 리스트에서 상품 비교로 값을 넘긴다.
     */
    setProductCompare : function()
    {
        if ($('.ProductCompareClass:checked').length < 1) {
            alert(__('비교할 상품을 선택해 주세요.'));
            return false;
        } else {
            var aProductNo = new Array();
            $('.ProductCompareClass:checked').each(function()
            {
                var aClass = $(this).attr('class').split(' ');

                var iSize  = aClass.length;
                for ( var x = 0; x < iSize; x++ ) {
                    var iProductNo = parseInt(aClass[x].split('_')[1],10);
                    if (aClass != '' && aClass[x].indexOf('ECPCNO_') == 1 && $.inArray(iProductNo,aProductNo) < 0) {
                        aProductNo.push(iProductNo);
                    }
                }
            });
            if (aProductNo.length > 1) {
                if (aProductNo.length > max_comp_number) {
                    alert(sprintf(__('%s개까지 비교 가능합니다.'), max_comp_number));
                } else {
                    var eForm = $('<form>').attr({
                        'method' : 'get',
                        'action' : sComparePageUrl
                    });
                    var iSize = aProductNo.length;
                    for (var x = 0; x < iSize; x++) {
                        $('<input />').attr({
                            'type' : 'hidden',
                            'name' : 'product_no[]'
                        }).val(aProductNo[x]).appendTo(eForm);
                    }
                    eForm.appendTo($('body')).submit();
                }
            } else {
                alert(__('두개 이상의 상품을 선택하세요.'));
            }
        }
    },
    /**
     * 선택한상품만 주문하기
     *
     * @param string sOptionParam 옵션 파람값
     * @param int iProductNo 상품번호
     */
    selectbuy_action :function(iProductNo)
    {
        // ECHOSTING-95935 장바구니 상품 INSERT 실패 log방지
        if (typeof iProductNo == 'undefined') return;

        var aOptionId = new Array();
        var aTargetElement = $('.' + $.data(document,'Check_class')+':checked');
        for (var x = 0; x < aTargetElement.length; x++) {
            var sOptionId = $(aTargetElement[x]).val();
            aOptionId.push("item_code[]=" + sOptionId);
        }

        var sUrl = '/exec/front/order/basket/?command=select_prdcnt&product_no=' + iProductNo + '&option_type=T&' + aOptionId.join("&");
        $.ajax({
            url : sUrl,
            dataType : 'json',
            async : false,
            success : function(data) {
                if (data.result > 0 && !confirm(sprintf(__('동일상품이 장바구니에 %s개 있습니다.'), data.result) +'\n'+ __('함께 구매하시겠습니까?'))) {
                    sIsPrdOverride = 'T';
                }
            }
        });
    }
};

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
