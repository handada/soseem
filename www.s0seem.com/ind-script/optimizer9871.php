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

//수량 input id
var quantity_id = '#quantity:not(.ec-debug)';
var bRestockChange = false;

$(document).ready(function()
{
    // 기존 레거시 코드(혹 사용하는몰이 있을까 하여 유지)
    if ($('.ec-product-couponAjax').length > 0) {
        getPrdDetailNewAjax();
    }

    // 신규 기본디자인에 반영
    if ($('.ec-product-coupon').length > 0) {
        EC_SHOP_FRONT_PRODUCT_INFO_COUPON.getPrdDetailCouponAjax(iProductNo,iCategoryNo);
    }

    // ECHOSTING-90301 모바일 zoom.html 페이지에서 에러 - 예외처리
    try { TotalAddSale.setParam('product_no', iProductNo); } catch (e) {}

    $("select[id*='product_option_id']").each ( function () {
        $(this).val('*');

    });

    // 디자인 마이그레이션 - 이걸 여기서 해야할까..
    if ($('#NewProductQuantityDummy').length > 0 && $('#totalProducts').length > 0) {
        $('#NewProductQuantityDummy').parents('tr').remove();
    }
    // 수량 초기화
    $(quantity_id).val(EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getProductMinQuantity());
    $('input.single-quantity-input[product-no='+iProductNo+']').val(EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getProductMinQuantity());
    // 펀딩
    $('input.quantity').val(EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getProductMinQuantity());

    // 품절일 경우 수량 0 설정
    if (EC_FRONT_JS_CONFIG_SHOP.bDirectBuyOrderForm === true && EC_FRONT_JS_CONFIG_SHOP.bSoldout === true) {
        $(quantity_id).attr('value', 0);
    }

    // 판매가 초기화
    try {
        setPrice(true, false, '');
    } catch(e) {}


    // 배송타입 초기화
    if (delvtype == 'A') {
        $('#delv_type_A').attr('checked','checked');
    }

    // 배송타입 선택
    $('[id^="delv_type_"]').change(function()
    {
        delvtype = $(this).val();

        // 해외배송이면 선결제 고정
        if (delvtype == 'B') {
            $('#delivery_cost_prepaid').val('P');
            if ($('.delv_price_C').length > 0) {
                $('.delv_price_B').hide();
                $('.delv_price_C').show();
            }
            try {
                if (document.getElementById('NaverChk_Button') != null) {
                    document.getElementById('NaverChk_Button').style.display = 'none';
                }
            } catch (e) {}
        } else {
            $('.delv_price_B').show();
            $('.delv_price_C').hide();
            try {
                if (document.getElementById('NaverChk_Button') != null) {
                    document.getElementById('NaverChk_Button').style.display = '';
                }
            } catch (e) {}
        }

    });

    // 해외 배송 전용 상품은 hidden값 처리
    var oHiddenDeliveryType = $('[name="delv_type"]:hidden:not(:radio)');
    if (oHiddenDeliveryType.length > 0) {
        if ($('input:radio[id^="delv_type_"]').is(':visible') === true) {
            delvtype = $('input:radio[id^="delv_type_"]:checked').val();
        } else {
            oHiddenDeliveryType.each(function() {
                // delv_type의 input태그 존재 자체가 해외배송을 사용한다는 의미
                if ($(this).attr('product_no') == iProductNo) {
                    delvtype = 'B';
                    return false;
                }
            });
        }
    }

    if (oSingleSelection.isItemSelectionTypeS() === true) {
        // 본체 상품만
        oSingleSelection.setProductTargetKey();

        $('input.single-quantity-input, img.quantity-handle.product-no-' + iProductNo).live('click change', function(e) {
            var eSelf = $(this);
            oSingleSelection.setProductTargetKey(eSelf);
            var iBuyUnit  = EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getBuyUnitQuantity();
            var iQuantity = parseInt(oSingleSelection.getQuantityInput(eSelf).val(),10);
            if (eSelf.hasClass('up') === true) {
                iQuantity = iQuantity + iBuyUnit;
            } else if (eSelf.hasClass('down') === true) {
                iQuantity = iQuantity - iBuyUnit;
            }
            var sQuantityInputSelector = ':text,input[type=tel]';
            var sContext = 'tr[target-key='+oSingleSelection.getProductTargetKey()+']';
            if (EC_MOBILE_DEVICE === true || EC_MOBILE === true) {
                sQuantityInputSelector = '[type=number]';
                if (has_option === 'F') {
                    sContext = '';
                    sQuantityInputSelector = quantity_id+'[type=tel]';
                }
            } else {
                if (has_option === 'F') {
                    sContext = '#totalProducts tbody:not(.add_products)';
                }
            }

            $('input'+sQuantityInputSelector, sContext).not('.ec-debug').val(iQuantity).trigger('change');
            e.stopPropagation();
        });

        $('.xans-product-quantity a.eClearUp, .xans-product-quantity a.eClearDown').live('click', function () {
            $(this).find('.quantity-handle.product-no-' + iProductNo).click();
        });
    }


    try {
        var sContext = ((typeof(isOrderForm) !== 'undefined' && isOrderForm === 'T') || isNewProductSkin() === false || EC_MOBILE === true ? '' :'#totalProducts');
        if (typeof(EC_SHOP_FRONT_PRODUCT_FUNDING) === 'object' && EC_SHOP_FRONT_PRODUCT_FUNDING.isFundingProduct() === true) {
            sContext = '.xans-product-funding';
            quantity_id = '[id^="quantity_"]';
        }
        // 수량 증감 버튼(옵션 없는 상품)
        $('.QuantityUp' + ',' + '.QuantityDown' + ',' + quantity_id+':not(.ec-debug)', sContext).live({
            click: function() {
                setQuantity('click', this);
            },
            change: function() {
                setQuantity('change', this);
            }
        });
    } catch (e) {}

    // 옵션박스 수량 증감 버튼
    try {
        $('.eProductQuantityClass' + ',' + '.option_box_up' + ',' + '.option_box_down').live({
            click: function(e) {
                if ($(this).hasClass('eProductQuantityClass') === true) {
                    return;
                }

                setOptionBoxQuantity('click', this);
                e.stopPropagation();
            },
            change: function(e) {
                e.preventDefault();
                if ($(this).hasClass('single-quantity-input') === false && $(this).hasClass('eProductQuantityClass') === false) {
                    return;
                }
                setOptionBoxQuantity('change', this);
            }
        });

        $('a.eProductQuantityUpClass, a.eProductQuantityDownClass').live('click', function () {
            setOptionBoxQuantity('click', document.getElementById($(this).data('target')));
        });
    } catch (e) {}


    // 옵션박스 선택상품 삭제
    try {
        $('#totalProducts a.delete').live('click', function () {
            $(this).find('.option_box_del').click();
        });
        $('.option_box_del').live('click', function (event) {
            // onlyone 옵션 셀렉트 박스 원복
            var eSelectedItem = $('#' + $(this).attr('id').replace('_del', '_id'));
            $('option[value="' + eSelectedItem.val() + '"]').parent().removeAttr('is_selected');
            $(this).parents('tr,li').eq(0).remove();

            var sDelId = $(this).attr('id');
            if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer(true) === true) {
                parent.$('option[value="' + eSelectedItem.val() + '"]').parent().removeAttr('is_selected');
                parent.$('#' + sDelId + '').parents('tr,li').eq(0).remove();
            }
            if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isExistLayer() === true) {
                if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer() === false) {
                    $("#productOptionIframe").contents().find('option[value="' + eSelectedItem.val() + '"]').parent().removeAttr('is_selected');
                    $("#productOptionIframe").contents().find('#' + sDelId + '').parents('tr,li').eq(0).remove();
                }
            }

            if (EC_FRONT_JS_CONFIG_SHOP.bDirectBuyOrderForm === true) {
                EC_SHOP_FRONT_ORDERFORM_DIRECTBUY.proc.deleteBasketProduct({
                    'item_code': eSelectedItem.val(),
                    'opt_id': eSelectedItem.attr('data-option-id')
                });
            }
            if (TotalAddSale.needRecalculatorSalePrice() === true) {
                oProductList = TotalAddSale.getProductList();
                TotalAddSale.setSubscriptionParam();
                // 옵션삭제후 재계산
                delete oProductList[eSelectedItem.val()];

                // 선택옵션없을시 ajax호출안함
                if (jQuery.isEmptyObject(oProductList)) {
                    TotalAddSale.setParam('product', oProductList);
                    TotalAddSale.setTotalAddSalePrice(0);
                    setTotalData();
                } else if ($('input.quantity_opt').length > 0) {
                    TotalAddSale.setSoldOutFlag(false);
                    TotalAddSale.setParam('product', oProductList);
                    TotalAddSale.getCalculatorSalePrice(function () {
                        setTotalData();

                        // 적립금 / 품목금액 갱신
                        TotalAddSale.updatePrice();
                    });
                }
            } else {
                setTotalData();
            }

            try {
                if ($('#NaverChk_Button').length > 0) {
                    if ($('#NaverChk_Button').children().length < 1) {
                        return;
                    }
                    var iSoldOut = 0;
                    $('.option_box_id, .soldout_option_box_id').each(function () {
                        if (checkSoldOut($(this).val()) === true) {
                            iSoldOut++;
                        }
                    });
                    if (iSoldOut > 0) {
                        $('#NaverChk_Button').css('display', 'none');
                    } else {
                        $('#NaverChk_Button').css('display', 'block');
                    }
                }
            } catch (e) {}

            event.stopPropagation();
        });
    } catch (e) {}

    try {
        if (EC_MOBILE === true || EC_MOBILE_DEVICE === true) {
            $('.differentialShipping > a').live('click',function() {
               $('.differentialShipping > .layerShipping').show();
               return false;
            });

            $('.layerShipping .btnClose').live('click', function() {
                $(this).parent().hide();
                 return false;
             });
        }
    } catch (e) {}

    // 차등 배송비 사용시 ToolTip 열기
    try {
        $('.btnTooltip > a').live('click', function () {
            $('.btnTooltip > .differentialShipping').show();
        });
    } catch (e) {}
    // 차등 배송비 사용시 ToolTip 닫기
    $('.btnTooltip > .differentialShipping a').unbind().click(function() {
        $('.btnTooltip > .differentialShipping').hide();
    });

    // 차등 배송비 사용시 ToolTip 열기 (모바일)
    $('.differentialShipping > .btnHelp').unbind().click(function() {
       $('.differentialShipping > .layerShipping').show();
    });
    // 차등 배송비 사용시 ToolTip 닫기 (모바일)
    $('.differentialShipping > .layerShipping > a').unbind().click(function() {
        $('.differentialShipping > .layerShipping').hide();
    });

    try {
        // 추가입력옵션 글자 길이 체크
        $('.input_addoption').live('keyup', function() {
            var iLimit = $(this).attr('maxlength');
            addOptionWord($(this).attr('id'), $(this).val(), iLimit);
        });
    } catch (e) {}

    $('ul.discountMember img.ec-front-product-show-benefit-icon').click(function() {

        $('ul.discountMember li > div.discount_layer').hide();

        if ($(this).parent().parent().has('div.discount_layer').length == 0) {
            var sBenefitType = $(this).attr('benefit');
            var oObj = $(this);
            var oHtml = $('<div>');
            var iBenefitProductNo = $(this).attr('product-no');
            oHtml.addClass('ec-base-tooltip discount_layer');

            //회원등급관리의 등급할인인 경우 class추가
            if (sBenefitType == 'MG') {
                oHtml.addClass('member_rating');
            }

            $(this).parent().parent().append(oHtml);
            $.post('/exec/front/Product/Benefitinfo', 'benefit_type='+sBenefitType+'&product_no=' + iBenefitProductNo, function(sHtml) {
                oHtml.html(sHtml);
            });

        } else {
            $(this).parent().parent().find('div.discount_layer').show();
        }
        return false;
    });

    try {
        $('div.discount_layer .close').live('click', function () {
            $(this).parent().hide();
            return false;
        });
    } catch (e) {}

    $('div.shippingFee a').click(function() {
        $('ul.discountMember li > div.discount_layer').hide();
        $('ul.discountMember li > span.arrow').hide();

        if ($(this).parent().parent().has('div.ec-base-tooltip').length == 0) {
            var sBenefitType = $(this).attr('benefit');
            var oObj = $(this);
            var oHtml = $('<div>');
            oHtml.addClass('ec-base-tooltip');
            oHtml.addClass('wrap');

            //회원등급관리의 등급할인인 경우 class추가
            if (sBenefitType == 'MG') {
                oHtml.addClass('member_rating');
            }

            $(this).parent().append(oHtml);
            $.post('/exec/front/Product/Benefitinfo', 'benefit_type=' + sBenefitType + '&product_no=' + iProductNo, function(sHtml) {
                oHtml.html(sHtml);
            });
        }

        $(this).parent().parent().find('div.ec-base-tooltip').show();
        $(this).parent().parent().find('span.arrow').show();
        return false;
    });

    try {
        $('.ec-base-tooltip .close').live('click', function () {
            $(this).parent().hide();
            $(this).parent().parent().find('span.arrow').hide();
            $('.differentialShipping').hide();
            return false;
        });
    } catch (e) {}

    // 단일 상품 품절일 경우 수량 0 설정
    if (EC_FRONT_JS_CONFIG_SHOP.bSoldout === true) {
        $(quantity_id).attr('value', 0);
    }
    // 구매옵션레이어 사용가능 여부 세팅
    // Controller에서 확인하도록 바꿀까...
    EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.init();
    // sms 재입고 알림 레이어 팝업 노출여부 확인
    EC_SHOP_FRONT_PRODUCT_SMS_RESTOCK_LAYER.setCheckSmsRestockLayerPopup();

    // 바로구매 주문서 로그인페이지로 이동
    EC_SHOP_FRONT_NEW_PRODUCT_DIRECT_BUY.setAccessRestriction();
});

/**
 * 모바일 상품옵션Layer 닫기
 * @param bIsOptionInit 옵션선택 레이어 닫을때 선택된 옵션을 부모창과 동기화할것인지 여부
 */
function closeBuyLayer(bIsOptionInit)
{
    if (bIsOptionInit !== false) {
        var iTotalOptCnt = $('select[id^="' + product_option_id + '"]').length;
        $('select[id^="' + product_option_id + '"]').each(function (i) {
            //독립형은 이미 선택되어있는 상태이기때문에 Pass
            if (EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionType(this) === 'F') {
                return;
            }
            var sSelectOptionId = $(this).attr('id');
            var sParentVal = EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSelectedValue(this);
            var oTarget = parent.$('#'+sSelectOptionId+'');
            parent.EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue(oTarget, sParentVal);
            if (i < iTotalOptCnt - 1) {
                parent.$('#'+sSelectOptionId+'').trigger('change');
            }
        });

        // 파일첨부 리스트 복사
        if ($('[name^="file_option"]').length > 0) {
            FileOptionManager.sync($('[name^="file_option"]').attr('id'), parent.$("ul#ul_file_option"));
        }
    }
    parent.$('html, body').css({'overflowY':'auto', height:'auto', width:'100%'});
    if (typeof(bIsOptionInit) === 'undefined') {
        parent.$('#opt_layer_window').remove();
    } else {
        parent.$('#opt_layer_window').hide();
    }
}


/**
 * 선택한 옵션 품절여부 체크
 * @param sOptionId 옵션 id
 * @returns 품절여부
 */
function checkSoldOut(sOptionId)
{
    var aStockData = $.parseJSON(option_stock_data);
    var bSoldOut = false;

    // get_stock_info
    if (aStockData[sOptionId] == undefined) {
        iStockNumber = -1;
        iOptionPrice = 0;
        bStock = false;
        sIsDisplay = 'T';
        sIsSelling = 'T';
    } else {
        iStockNumber = aStockData[sOptionId].stock_number;
        iOptionPrice = aStockData[sOptionId].option_price;
        bStock = aStockData[sOptionId].use_stock;
        sIsDisplay = aStockData[sOptionId].is_display;
        sIsSelling = aStockData[sOptionId].is_selling;
    }
    if (sIsSelling == 'F' || ((iStockNumber < buy_unit || iStockNumber <= 0) && (bStock === true || sIsDisplay == 'F'))) {
        bSoldOut = true;
    }
    return bSoldOut;
}


/**
 * 옵션없는 구매수량 체크
 * @param sEventType 이벤트 타입
 * @param oObj Object정보
 */
function setQuantity(sEventType, oObj)
{
    // 단일 상품 품절일 경우 수량 계산 하지 않음.
    if (EC_FRONT_JS_CONFIG_SHOP.bSoldout === true) {
        return;
    }

    var $oQuantityElement = $(quantity_id);
    if ($('.EC-funding-checkbox').length > 0) {
        $oQuantityElement = $(oObj).closest('.xans-product-funding').find('input.quantity');
    }
    var iQuantity = parseInt($oQuantityElement.val(),10);
    var iBuyUnit  = parseInt(buy_unit);
    var iProductMin = EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getProductMinQuantity();

    if (sEventType == 'click') {
        var iProductCustom = $('#product_custom').val();
        var sQuantityClass = '.' + oObj.className;
        if (sQuantityClass.indexOf('.QuantityUp') >= 0 || $(oObj).hasClass('QuantityUp') || $(oObj).hasClass('up')) {
            iQuantity = iQuantity + iBuyUnit;
        } else if (sQuantityClass.indexOf('.QuantityDown') >= 0 || $(oObj).hasClass('QuantityDown') || $(oObj).hasClass('down')) {
            iQuantity = iQuantity - iBuyUnit;
        }
    }

    if (iQuantity > product_max && product_max > 0) {
        alert(sprintf(__('최대 주문수량은 %s개 입니다.'), product_max));
        if (iBuyUnit == 1) {
            $oQuantityElement.val(product_max);
        } else {
            $oQuantityElement.val($oQuantityElement.val());
        }
        return;
    }

    // 최대 구매수량과 펀딩 제한 수량은 별개로 동작해야함
    if ($('.EC-funding-checkbox').length > 0) {
        var iCompositionLimit = parseInt($oQuantityElement.attr('limit-quantity'), 10);
        if (iCompositionLimit > 0 && iQuantity > iCompositionLimit) {
            alert(sprintf(__('최대 주문수량은 %s개 입니다.'), iCompositionLimit));
            $oQuantityElement.val(iCompositionLimit);
            return;
        }
    }

    if (iQuantity < iProductMin) {
        alert(sprintf(__('최소 주문수량은 %s개 입니다.'), iProductMin));
        $oQuantityElement.val(iProductMin);
        return;
    }


    $oQuantityElement.val(iQuantity);
    if (oSingleSelection.isItemSelectionTypeS() === true) {
        $('input.single-quantity-input[product-no='+iProductNo+']').val(iQuantity);
    }
    if ($('.EC-funding-checkbox').length > 0) {
        var sCompositionCode = $oQuantityElement.attr('composition-code');
        $('.selected-funding-item.option_box_price[composition-code="'+sCompositionCode+'"]').attr('quantity', iQuantity);
    }

    setPrice(false, false, '');

    // 총 주문금액/수량 처리
    setTotalData();

    // 구스킨인경우 판매금액 계산
    if (isNewProductSkin() === false) {
        setOldTotalPrice();
    }
}

/**
 * 옵션박스 구매수량 체크
 * @param sEventType 이벤트별 수량 체크
 * @param oObj Object정보
 */
function setOptionBoxQuantity(sEventType, oObj)
{
    var sOptionId = '', sOptionBoxId = '', sProductPrice = '';
    var iQuantity = 0;
    var iBuyUnit  = EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getBuyUnitQuantity();

    if (sEventType == 'click') {
        // 구매수량 화살표로 선택
        var sType = $(oObj).attr('id').indexOf('_up') > 0 ? '_up' : '_down';
        sOptionBoxId = '#' + $(oObj).attr('id').substr(0, $(oObj).attr('id').indexOf(sType));
        iQuantity = parseInt($(sOptionBoxId + '_quantity').val(), 10);
        sOptionId = $(sOptionBoxId + '_id').val();
        if (sType == '_up') {
            iQuantity = iQuantity + iBuyUnit;
        } else if (sType == '_down') {
            iQuantity = iQuantity - iBuyUnit;
        }
    } else if (sEventType == 'change') {
        // 구매수량 직접 입력
        sOptionBoxId = '#' + $(oObj).attr('id').substr(0, $(oObj).attr('id').indexOf('_quantity'));
        iQuantity = parseInt($(oObj).val(), 10);
        sOptionId = $(sOptionBoxId + '_id').val();
    }
    // 최소 구매 수량 체크
    var iProductMin = EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getProductMinQuantity();

    if (iQuantity < iProductMin) {
        alert(sprintf(__('최소 주문수량은 %s개 입니다.'), iProductMin));
        iQuantity = iProductMin;
        $(oObj).val(iQuantity).blur();
        return;
    }

    if (iQuantity > product_max && product_max > 0) {
        alert(sprintf(__('최대 주문수량은 %s개 입니다.'), product_max));
        iQuantity = product_max;
        $(oObj).val(iQuantity).blur();
        return;
    }
    var aStockData     = $.parseJSON(option_stock_data);
    var iOptionPrice   = 0;
    var iTotalQuantity = iQuantity;
    var iStockNumber   = 0;
    var bUseStock      = '';
    var bUseSoldOut    = '';
    var iAddOptionPrice = 0; // 연동형 옵션인 경우 판매가를 제외한 옵션 자체에 붙은 금액을 따로 보관하자

    if (Olnk.isLinkageType(sOptionType) === true) {
        var aOptionTmp = sOptionId.split('||');
        var aOptionIdTmp = new Array;
        var sOptionIdTemp = '';
        for ( i = 0; i < aOptionTmp.length; i++ ) {
            if (aOptionTmp[i] !== '' ) {
                aOptionIdTmp = aOptionTmp[i].split('_');
                if (/^\*+$/.test(aOptionIdTmp[0]) === false )  {
                    iOptionPrice = iOptionPrice + parseFloat(aStockData[aOptionIdTmp[0]].option_price);
                    iAddOptionPrice = parseFloat(aStockData[aOptionIdTmp[0]].option_price);
                    sOptionIdTemp = aOptionIdTmp[0];
                }

            }
        }
        if ( (Olnk.bAllSelectedOption === true ||  Olnk.getOptionPushbutton($('#option_push_button')) === true ) && sOptionIdTemp === '') {
            sOptionIdTemp = sProductCode;
        }

        iOptionPrice = parseFloat(product_price) + iOptionPrice;

        iStockNumber   = parseInt(aStockData[sOptionIdTemp].stock_number);
        bUseStock      = aStockData[sOptionIdTemp].use_stock;
        bUseSoldOut    = aStockData[sOptionIdTemp].use_soldout;


        // iTotalQuantity 연동형 옵션의 경우 현재 옵션박스에 되어 있는 모든 품목의 재고를 더해야 한다.(추가 구성상품의 경우 따로 체크함)
        var sAddOptionBoxNum = '';
        $('[name="quantity_opt[]"]').each(function() {
            sAddOptionBoxNum = $(this).attr('id').replace('quantity','');
            if ($(this).attr('id').indexOf('add_') < 0 && $(oObj).attr('id').indexOf(sAddOptionBoxNum) < 0 ) {
                iTotalQuantity += parseInt($(this).val());
            }

        });

        // 최대 재고 수량 체크
        if (bUseSoldOut === 'T' && bUseStock === true && iTotalQuantity > iStockNumber) {
            alert(sprintf(__('재고 수량이 %s개 존재합니다. 재고수량 이하로 입력해주세요.'), iStockNumber));
            $(oObj).val(iStockNumber);
            return;
        }
    } else {
        iStockNumber   = parseInt(aStockData[sOptionId].stock_number);
        iOptionPrice  = parseFloat(aStockData[sOptionId].option_price);
    }

    if (oSingleSelection.isItemSelectionTypeS() === true) {
        var iProductNum = iProductNo;
        var iOptionSequence = 1;
        if (option_type === 'F') {
            iOptionSequence = $(oObj).parents('tr.option_product').attr('target-key').split('|')[1];
        }
        $('input.single-quantity-input[product-no='+iProductNum+'][option-sequence='+iOptionSequence+']').val(iQuantity);

    }


    iProductPrice = getProductPrice(iQuantity, iOptionPrice, sOptionId, null, function(iProductPrice)
    {
        var bIsValidBundleObject = typeof(EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE) === 'object';
        var iProductNum = (has_option === 'T') ? $(sOptionBoxId + '_quantity').attr('product-no') : iProductNo;
        //1+N 상품일 경우 품목별 가격은 변경되지 않음
        var iTotalPrice = (bIsValidBundleObject === true && EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig.hasOwnProperty(iProductNum) === true) ? iOptionPrice : iOptionPrice * iQuantity;
        sProductPrice = SHOP_PRICE_FORMAT.toShopPrice(iTotalPrice);

        // ECHOSTING-58174
        if (sIsDisplayNonmemberPrice == 'T') {
            sProductPrice = sNonmemberPrice;
            iProductPrice = 0;
        }

        $(sOptionBoxId + '_quantity').val(iQuantity);
        $(sOptionBoxId + '_price').find('span').html(sProductPrice);
        $(sOptionBoxId + '_price').find('input').val(iProductPrice);

        // 적립금 계산
        if (typeof (mileage_val) != 'undefined') {

            var iStockPrice = 0;
            if (Olnk.isLinkageType(sOptionType) === true) {
                iStockPrice = iAddOptionPrice;
            } else if (typeof (aStockData[sOptionId].stock_price) != 'undefined' ) {
                iStockPrice = aStockData[sOptionId].stock_price;
            }
            var mileage_price = TotalAddSale.getMileageGenerateCalc(sOptionId, iQuantity);

            if (EC_MOBILE === true || EC_MOBILE_DEVICE === true) {
                $(sOptionBoxId + '_mileage').html(SHOP_PRICE_FORMAT.toShopMileagePrice(mileage_price));
            } else {
                if (mileage_price > 0) {
                    $(sOptionBoxId + '_mileage').html(SHOP_PRICE_FORMAT.toShopMileagePrice(mileage_price));
                }
            }
            if (sIsDisplayNonmemberPrice == 'T') {
                $(sOptionBoxId + '_mileage').html(sNonmemberPrice);
            }
        }

        // 구매레이어
        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer(true) === true) {
            parent.$(sOptionBoxId + '_quantity').val(iQuantity);
            parent.$(sOptionBoxId + '_price').find('span').html(sProductPrice);
            parent.$(sOptionBoxId + '_price').find('input').val(iProductPrice);
            if (typeof (mileage_val) != 'undefined') {
                parent.$(sOptionBoxId + '_mileage').html(SHOP_PRICE_FORMAT.toShopMileagePrice(mileage_price));
                if (sIsDisplayNonmemberPrice == 'T') {
                    parent.$(sOptionBoxId + '_mileage').html(sNonmemberPrice);
                }
            }
        }
        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isExistLayer() === true) {
            if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer() === false) {
                $("#productOptionIframe").contents().find(sOptionBoxId + '_quantity').val(iQuantity);
                $("#productOptionIframe").contents().find(sOptionBoxId + '_price').find('span').html(sProductPrice);
                $("#productOptionIframe").contents().find(sOptionBoxId + '_price').find('input').val(iProductPrice);
            }
            if (typeof (mileage_val) != 'undefined') {
                $("#productOptionIframe").contents().find(sOptionBoxId + '_mileage').html(SHOP_PRICE_FORMAT.toShopMileagePrice(mileage_price));
                if (sIsDisplayNonmemberPrice == 'T') {
                    $("#productOptionIframe").contents().find(sOptionBoxId + '_mileage').html(sNonmemberPrice);
                }
            }
        }
        // 총 주문금액/수량 처리
        setTotalData();

        // 적립금 / 품목금액 갱신 (현재 품목 제외)
        TotalAddSale.updatePrice(sOptionBoxId, sOptionId);
    });
}

// 자바스크립트 number_format jsyoon
function number_format(str)
{
    str += '';

    var objRegExp = new RegExp('(-?[0-9]+)([0-9]{3})');

    while (objRegExp.test(str)) {
        str = str.replace(objRegExp,'$1,$2');
    }

    return str;
}

/**
 * 가격계산 후 판매가에 반영
 * @param bInit 초기값여부
 * @param bOption 옵션선택여부
 * @param sOptionId 단독구성형일때는 SelectBox가 여러개이므로 선택한 OptionId 필요
 */
function setPrice(bInit, bOption, sOptionId)
{
    var sQuantityString = '(' + sprintf(__('%s개'),0) + ')';

    // 판매가 대체 문구시 가격 계산 안함
    if (product_price_content == true) {
        if (sIsDisplayNonmemberPrice == 'T') {
            $('#totalProducts .total').html('<strong><em>'+sNonmemberPrice+'</em></strong> ' + sQuantityString + '</span>');
        }
        return false;
    }

    // 옵션이 없는 경우 수량 초기화
    if (has_option == 'F' && (isNaN($(quantity_id).val()) === true || $(quantity_id).val() == '' || $(quantity_id).val().indexOf('.') > 0)) {
        $(quantity_id).val(product_min);
    }

    if (bInit === true) {
        setProductPriceText();
    }
    // 옵션이 없을 경우
    if (has_option == 'F') {
        setPriceHasOptionF();
    } else if (has_option == 'T'){
        if (typeof sOptionType != 'undefined' && Olnk.isLinkageType(sOptionType) === false) {
            setPriceHasOptionT(bOption, sOptionId);
        } else {
            if (Olnk.getOptionPushbutton($('#option_push_button')) === false) {
                iQuantity = EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getProductMinQuantity();
                if (oSingleSelection.isItemSelectionTypeS() === true) {
                    iQuantity = PRODUCTSUBMIT.getQuantity();
                    Olnk.bAllSelectedOption = true;
                }
                if ($('.EC-funding-checkbox').length > 0) {
                    var sCompositionCode = $(EC_SHOP_FRONT_NEW_OPTION_BIND.oOptionObject).attr('composition-code');
                    if (EC_SHOP_FRONT_NEW_OPTION_BIND.oOptionObject === null) {
                        sCompositionCode = EC_SHOP_FRONT_PRODUCT_FUNDING.sCurrentCompositionCode;
                    }
                    sSelector = $('#quantity_'+sCompositionCode);
                    iQuantity = PRODUCTSUBMIT.getQuantity(sSelector);
                    //Olnk.bAllSelectedOption = true;
                }
                Olnk.handleTotalPrice(option_stock_data, product_price, sIsDisplayNonmemberPrice,false, iQuantity);

                // 적립금 / 품목금액 갱신
                TotalAddSale.updatePrice();
            }
        }
    }

    // 적립금 처리
    setMileage(bInit);
}

/**
 *  모바일 할인가 계산 후 리턴
*/
function getMobileDcPrice( iPrice ){

    var iReturnMobileDcPrice = 0;
    var iTmpBasePrice = 0;
    var iPer = 0;

    // 정율 할인일 경우
    if (sc_mobile_dc_value_flag == 'P') {
        iPer = sc_mobile_dc_value * 0.01;
        iTmpBasePrice = iPrice * iPer;
        iTmpBasePrice = getMobileDcLimitPrice( iTmpBasePrice );
        iReturnMobileDcPrice = Math.ceil( iPrice - iTmpBasePrice );
    }
    // 금액 할인일 경우
    else{
        iReturnMobileDcPrice = iPrice - sc_mobile_dc_value;
    }

    return iReturnMobileDcPrice;
}

/**
 *  모바일 할인가 금액 절사 후 리턴
 *
*/
function getMobileDcLimitPrice( MobileDcPrice ){

    var iFloat = 0;
    var iOpp = 0;

    switch ( sc_mobile_dc_limit_value ) {

        // 절사 안함
        case "F" : return MobileDcPrice; break;

        // 원단위 절사
        case "O" :
            iFloat = 0.1;
            iOpp = 10;
        break;

        // 십원단위 절사
        case "T" :
            iFloat = 0.01;
            iOpp = 100;
        break;

        // 백원단위 절사
        case "M" :
            iFloat = 0.001;
            iOpp = 1000;
        break;
    }

    MobileDcPrice = MobileDcPrice * iFloat;

    // 반올림인지 내림인지
    if (sc_mobile_dc_limit_flag == 'L') { MobileDcPrice = Math.floor( MobileDcPrice ) * iOpp; }
    else if (sc_mobile_dc_limit_flag == 'U') { MobileDcPrice = Math.round(MobileDcPrice) * iOpp; }

    return MobileDcPrice;
}

/**
 * 적립금 계산 후 반영
 */
function setMileage(bInit)
{
    if (bInit === true && (EC_MOBILE === true || EC_MOBILE_DEVICE === true)) {
        if (sIsDisplayNonmemberPrice == 'T') {
            $('#span_mileage_text').html(sNonmemberPrice);
        }
    }

}

/**
 * 싸이월드 스크랩 하기
 * @param sMallId 몰아이디
 * @param iPrdNo 상품번호
 * @param iCateNo 카테번호
 * @param iSid 승인번호
 * @author 김성주 <sjkim@simplexi.com>
 */
function cyConnect(sMallId, iPrdNo, iCateNo, iSid)
{
    var strUrl = "http://api.cyworld.com/openscrap/shopping/v1/?";
    //strUrl += "xu=" + escape("http://www2.1300k.com/shop/makeGoodsXml/makeGoodsXml.php?f_goodsno="+prdNo+"&cate_no="+cate_no);
    //strUrl += "&sid=s0200002";

    strUrl += "xu=" + escape("//"+sMallId+"." + EC_ROOT_DOMAIN + "/front/php/ghost_mall/makeCyworldPrdXml.php?product_no="+iPrdNo+"&cate_no="+iCateNo+"&sid="+iSid);
    strUrl += "&sid="+iSid;

    var strOption = "width=450,height=410";

    var objWin = window.open(strUrl, 'cyopenscrap',  strOption);
    objWin.focus();
}

/**
 * 싸이월드 스크랩 설명 보여주기
 * @author 김성주 <sjkim@simplexi.com>
 */
function openNateInfo(num)
{
    if (num == "1"){
        document.getElementById('divNate').style.display="none";
    }else{
        document.getElementById('divNate').style.display="";
    }
}

/**
 * 판매가 표시설정
 */
function setProductPriceText()
{
    var sString = SHOP_PRICE_FORMAT.toShopPrice(product_price);
    if (typeof product_price_ref != 'undefined' && product_price_ref > 0) {
        // 화폐 노출 순서 설정 ECHOSTING-56540
        if (currency_disp_type == 'P') {
            sString += ' ' + txt_product_price_ref;
        } else {
            sString = txt_product_price_ref + ' ' + sString;
        }
    }
    // ECHOSTING-58174
    if (sIsDisplayNonmemberPrice == 'T') {
        sString = sNonmemberPrice;
    }

    // ECHOSTING-67418 구상품일때도 판매가 영역이 바뀌게 처리 (초기화시 최소 구매수량 개수에 맞게 노출)
    if (isNewProductSkin() === false && sIsDisplayNonmemberPrice !== 'T') {
        iPrice = getProductPrice(product_min, product_price, null, null, function(iPrice) {
            sString = SHOP_PRICE_FORMAT.toShopPrice(iPrice);
            $('#span_product_price_text').html(sString);
        });
    } else {
        $('#span_product_price_text').html(sString);
    }
    var sMobileClass = '';
    if (EC_MOBILE === true || EC_MOBILE_DEVICE === true) {
        sMobileClass = ' class = "price"';
    }
    var sTotalPriceSelector = oSingleSelection.getTotalPriceSelector();
    var sQuantityString = '('+sprintf(__('%s개'),0)+')';
    if (oSingleSelection.isItemSelectionTypeS() === true) {
        var sStrPrice = SHOP_PRICE_FORMAT.toShopPrice(0);

        $(sTotalPriceSelector).html('<strong'+sMobileClass+'><em>'+sStrPrice+'</em></strong> '+sQuantityString+'</span>');
        setTotalPriceRef(0, sQuantityString);
    }

    // ECHOSTING-58174
    if (sIsDisplayNonmemberPrice == 'T') {
        if (sNonmemberPrice === "") {
            sNonmemberPrice = "-";
        }
        $(sTotalPriceSelector).html('<strong'+sMobileClass+'><em>'+sNonmemberPrice+'</em></strong> ' + sQuantityString + '</span>');
    }

}

/**
 * 전체 금액 리턴
 * @returns {Number}
 */
function getTotalPrice()
{
    var iTotalPrice = 0;
    $('.option_box_price').each(function() {
        iTotalPrice += parseInt($(this).val());
    });

    return iTotalPrice;
}

/**
 * 금액설정(옵션이 없는 경우)
 */
function setPriceHasOptionF()
{
    if ($('#totalProducts').length === 0) {
        return;
    }
    try {
        iQuantity = parseInt($(quantity_id).val().replace(/^[\s]+|[\s]+$/g,'').match(/[\d\-]+/),10);
    } catch(e) {}
    var iMaxCnt = 999999;
    if (iQuantity > iMaxCnt) {
        $(quantity_id).val(iMaxCnt);
        iQuantity = iMaxCnt;
    }
    // 모바일 할인가 추가.
    if (typeof ($('#span_product_price_mobile_text') ) != 'undefined' ) {
        try{
            var iPriceMobile = parseFloat(product_price_mobile,10);
        }
        catch(e){ var iPriceMobile = product_price; }
    }

    var iTotalPrice = getProductPrice(iQuantity, product_price, item_code, null, function(iTotalPrice){
        var sTotalOriginPrice = SHOP_PRICE_FORMAT.toShopPrice( iTotalPrice );
        var iTotalOriginPrice = iTotalPrice;

        var sItemCode = $('.option_box_price').attr('item_code');
        sItemCode = (typeof(sItemCode) === 'undefined') ? item_code : sItemCode;
        iVatSubTotalPrice = TotalAddSale.getVatSubTotalPrice(sItemCode);

        if (iVatSubTotalPrice != iTotalPrice && iVatSubTotalPrice != 0 && iTotalPrice != 0) {
            iTotalPrice = iVatSubTotalPrice;
        }

        var sTotalPrice = SHOP_PRICE_FORMAT.toShopPrice( iTotalPrice );
        var sTotalSalePrice = sTotalPrice;
        iTotalAddSalePrice = TotalAddSale.getTotalAddSalePrice();
        if (typeof(iTotalAddSalePrice) != 'undefined' && iTotalAddSalePrice != 0) {
            iTotalSalePrice = iTotalPrice - parseFloat(iTotalAddSalePrice, 10);
            sTotalSalePrice = SHOP_PRICE_FORMAT.toShopPrice( iTotalSalePrice );
        } else {
            iTotalSalePrice = iTotalPrice;
        }

        if (typeof(EC_SHOP_FRONT_PRODUCT_FUNDING) === 'object' && EC_SHOP_FRONT_PRODUCT_FUNDING.isFundingProduct() === true) {
            if (EC_SHOP_FRONT_NEW_OPTION_EXTRA_FUNDING.sCurrentCompositionCode === null) {
                return true;
            }
        }
        //옵션이 없는 상품이고 추가구성상품 추가시 수량처리 및 상품금액 처리
        var iAddQuantity = 0;
        if ($('.add_product_option_box_price').length > 0) {
            $('.quantity_opt').each(function() {
                iAddQuantity += parseFloat($(this).val());
            });

            sTotalSalePrice = getAddProductExistTotalSalePrice(iTotalSalePrice);
        }
        var iTotalQuantity = iQuantity + iAddQuantity;

        var sQuantityString = '('+sprintf(__('%s개'), iTotalQuantity) + ')';
        // ECHOSTING-58174
        if (sIsDisplayNonmemberPrice == 'T') {
            sTotalOriginPrice = sNonmemberPrice;
            sTotalPrice = sNonmemberPrice;
            sTotalSalePrice = sNonmemberPrice;
        }

        if (EC_MOBILE === true || EC_MOBILE_DEVICE === true || (typeof(isOrderForm) !== 'undefined' && isOrderForm === 'T')) {
            $(oSingleSelection.getTotalPriceSelector()).html('<strong class="price">'+sTotalSalePrice+' '+sQuantityString+'</strong>');
            $('#quantity').html('<input type="hidden" name="option_box_price" class="option_box_price" value="'+iTotalOriginPrice+'" item_code="'+item_code+'">');
        } else {
            $('#totalProducts .total').html('<strong><em>' + sTotalSalePrice + '</em></strong> ' + sQuantityString + '</span>');

            //품목 할인가 보여주는 설정일 경우 할인가 노출
            var sDisplayPrice = sTotalOriginPrice;
            if (TotalAddSale.getIsUseSalePrice() === true) {
                //1+N상품은 할인가 보여주지 않음
                sDisplayPrice = (TotalAddSale.getIsBundleProduct() === true) ? '' : sTotalSalePrice;
                sDisplayPrice = '<span class="ec-front-product-item-price" code="' + item_code + '" product-no="' + iProductNo + '">' + sDisplayPrice + '</span>';
            }

            $('#totalProducts').find('.quantity_price').html(sDisplayPrice + '<input type="hidden" name="option_box_price" class="option_box_price" value="'+iTotalOriginPrice+'" item_code="'+item_code+'">');
            if (typeof(mileage_val) !== 'undefined' && TotalAddSale.checkVaildMileageValue(mileage_val) === true) {
                var mileage_price = TotalAddSale.getMileageGenerateCalc(item_code, iQuantity);

                if (sIsDisplayNonmemberPrice == 'T') {
                    $('#totalProducts').find('.mileage_price').html(sNonmemberPrice);
                } else {
                    $('#totalProducts').find('.mileage_price').html(SHOP_PRICE_FORMAT.toShopMileagePrice( mileage_price ));
                }
            } else {
                $('#totalProducts').find('.mileage').hide();
            }
        }

        if (typeof(iTotalAddSalePrice) != 'undefined' && iTotalAddSalePrice != 0) {
            setTotalPriceRef(iTotalSalePrice, sQuantityString);
        } else {
            setTotalPriceRef(iTotalPrice, sQuantityString);
        }

        // 총 주문금액/수량 처리
        setTotalData();
        // 적립금 / 품목금액 갱신
        TotalAddSale.updatePrice();
    });
}

/**
 * 금액설정(옵션이 있는 경우)
 * 복합/조합 - 단독/일체 구분없이 item_code만으로 처리하도록 변경
 */
function setPriceHasOptionT(bOption, sOptionId)
{
    if (typeof(option_stock_data) == 'undefined') {
        return;
    }

    if (sIsDisplayNonmemberPrice === 'T') {
        return;
    }

    if (bOption !== true) {
        return;
    }

    var sSelectElementId = sOptionId;
    var temp_product_option_id = product_option_id;

    //뉴상품+구스킨 : 옵션추가버튼을 이용해 추가된 옵션 select box id 예외처리
    if (sOptionId.split('_')[0] == 'add') {
        temp_product_option_id = sOptionId.split('_')[0]+'_'+sOptionId.split('_')[1]+'_'+temp_product_option_id;
    }
    if (typeof($('#'+sSelectElementId).attr('composition-code')) !== 'undefined') {
        temp_product_option_id = temp_product_option_id+'_'+$('#'+sOptionId).attr('composition-code');
    }

    var sSoldoutDisplayText = EC_SHOP_FRONT_NEW_OPTION_EXTRA_SOLDOUT.getSoldoutDiplayText(iProductNo);
    var aStockData = $.parseJSON(option_stock_data);
    // bItemSelected : 모든 셀렉트 박스가 선택됐는지 여부
    var bItemSelected, bSoldOut = false;
    var sOptionId, sOptionText = '';
    var iPrice = 0;

    var iBuyUnit  = EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getBuyUnitQuantity('base');
    var iProductMin = EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getProductMinQuantity();

    var iQuantity = (iBuyUnit >= iProductMin ? iBuyUnit : iProductMin);
    // 조합구성 & 분리선택형
    if (option_type == 'T' && item_listing_type == 'S') {
        var aOption = new Array();
        $('select[id^="' + temp_product_option_id + '"]').each(function() {
            var cVal = EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSelectedValue(this);
            if (cVal.indexOf('|') > -1) {
                cVal = cVal.split('|')[0];
            }
            aOption.push(cVal);
        });

        // 아직 totalProduct에 Element추가가 안되서 getItemCode를 사용할 수 없다.
        sOptionId = ITEM.getOldProductItemCode('[id^="'+temp_product_option_id+'"]');
        sOptionValue = aOption.join('/');
        sOptionText = aOption.join('#$%');
        if (ITEM.isOptionSelected(aOption) === true) {
            bItemSelected = true;
        }

        if (typeof(aStockData[sOptionId]) != 'undefined' && aStockData[sOptionId].stock_price != 0) {
            if (typeof(product_option_price_display) == 'undefined' || product_option_price_display === 'T') {
                sOptionText += '(' + getOptionPrice(aStockData[sOptionId].stock_price) + ')';
            }
        }

        if (bItemSelected === true && sOptionId === false) {
            alert(sprintf(__("선택하신 '%s' 옵션은 판매하지 않은 옵션입니다.\n다른 옵션을 선택해 주세요."),sOptionValue));
            throw e;
            return false;
        }
    } else {
        var sElementId = sOptionId;
        var oSelect = $('#'+sElementId);

        if (oSelect.attr('is_selected') !== 'T') {
            sOptionText = $('#' + sOptionId + ' option:selected').text();
            sOptionId = $('#' + sOptionId + ' option:selected').val();
            bItemSelected = true;
        } else {
            if (isNewProductSkin() === true && NEWPRD_OPTION.isOptionSelectTitleOrDivider(EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSelectedValue(oSelect)) !== true) {
                alert(__('이미 선택되어 있는 옵션입니다.'));
                NEWPRD_OPTION.resetSelectElement(oSelect);
                return false;
            }
            sOptionId = '*';
        }

        // 독립선택형 옵션별로 한개씩 선택시
        if (oSingleSelection.isItemSelectionTypeM() === true && typeof(is_onlyone) === 'string' && is_onlyone === 'T' && isNewProductSkin() === true) {

            if (NEWPRD_OPTION.isOptionSelectTitleOrDivider(oSelect.val()) !== true) {
                $('#'+sElementId).attr('is_selected','T');
            }
        }

        if (ITEM.isOptionSelected(sOptionId) === false) {
            bItemSelected = false;
        }
    }


    if (checkOptionBox(sOptionId) === true) {
        alert(__('이미 선택되어 있는 옵션입니다.'));
        NEWPRD_OPTION.resetSelectElement(oSelect);
        return false;
    }

    // get_stock_info
    if (aStockData[sOptionId] == undefined) {
        iStockNumber = -1;
        iOptionPrice = 0;
        bStock = false;
        sIsDisplay = 'T';
        sIsSelling = 'T';
        sIsReserveStat = 'N';
    } else {
        iStockNumber = aStockData[sOptionId].stock_number;
        iOptionPrice = aStockData[sOptionId].option_price;
        bStock = aStockData[sOptionId].use_stock;
        sIsDisplay = aStockData[sOptionId].is_display;
        sIsSelling = aStockData[sOptionId].is_selling;
        sIsReserveStat = aStockData[sOptionId].is_reserve_stat; //이건 어디서
    }

    if (EC_SHOP_FRONT_NEW_OPTION_VALIDATION.isItemCode(sOptionId) === true && typeof(EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig[iProductNo]) === 'object') {
        iOptionPrice = aStockData[sOptionId].option_price - aStockData[sOptionId].stock_price;
    }
    if (sIsSelling == 'F' || ((iStockNumber < iBuyUnit || iStockNumber <= 0) && (bStock === true || sIsDisplay == 'F'))) {
        //뉴상품+구스디 스킨 (옵션추가 버튼나오는 디자인 - 옵션선택시 재고체크)
        if ($('#totalProducts').length <= 0) {
            var aOptionName = new Array();
            var aOptionText = new Array();

            aOptionName = option_name_mapper.split('#$%');
            aOptionText = sOptionText.split('#$%');
            for ( var i = 0; i < aOptionName.length; i++) {
                aOptionText[i] = aOptionName[i]+':'+aOptionText[i];
            }
            option_text = aOptionText.join('\n');
            alert(__('이 상품은 현재 재고가 부족하여 판매가 잠시 중단되고 있습니다.') + '\n\n' + __('제품명') + ' : ' + product_name + '\n\n' + __('재고없는 제품옵션') + ' : \n' + option_text);
            EC_SHOP_FRONT_NEW_OPTION_COMMON.setValue($('#' + sSelectElementId), '*');
        }
        bSoldOut = true;
        sOptionText = sOptionText.split('#$%').join('/').replace('['+sSoldoutDisplayText+']', '') + ' <span class="soldOut">['+sSoldoutDisplayText+']</span>';
    } else {
        sOptionText = sOptionText.split('#$%').join('/');
    }

    if (typeof($('#'+sSelectElementId).attr('composition-code')) !== 'undefined') {
        iQuantity = PRODUCTSUBMIT.getQuantity($('.xans-product-funding').find('#quantity_'+$('#'+sSelectElementId).attr('composition-code')));
    }
    //예약주문|당일발송
    if (aStockData[sOptionId] !== undefined) {
        if (aReserveStockMessage['show_stock_message'] === 'T' && sIsReserveStat !== 'N') {
            var sReserveStockMessage = '';
            bSoldOut = false; //품절 사용 안함

            sReserveStockMessage = aReserveStockMessage[sIsReserveStat];
            sReserveStockMessage = sReserveStockMessage.replace(aReserveStockMessage['stock_message_replace_name'], iStockNumber);
            sReserveStockMessage = sReserveStockMessage.replace('[:PRODUCT_STOCK:]', iStockNumber);

            sOptionText = sOptionText.replace(sReserveStockMessage, '') + ' <span class="soldOut">'+sReserveStockMessage+'</span>';
        }
    }

    if (oSingleSelection.isItemSelectionTypeS() === true) {
        iQuantity = PRODUCTSUBMIT.getQuantity();
        if (option_type === 'F') {
            var iOptionSequence = EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSortNum(oSelect);
            iQuantity = PRODUCTSUBMIT.getQuantity($('[product-no='+iProductNo+'][option-sequence='+iOptionSequence+']'));
        }
    }

    iPrice = getProductPrice(iQuantity, iOptionPrice, sOptionId, bSoldOut, function(iPrice){
        // 옵션박스 호출
        if (bItemSelected === true) {
            // 구상품스킨일때는 옵션박스 호출안함
            if (isNewProductSkin() === false) {
                if (sIsDisplayNonmemberPrice == 'T') {
                    $('#span_product_price_text').html(sNonmemberPrice);
                } else {
                    $('#span_product_price_text').html(SHOP_PRICE_FORMAT.toShopPrice(iPrice));
                }
            } else {
                setOptionBox(sOptionId, sOptionText, iPrice, bSoldOut, sSelectElementId, sIsReserveStat, iQuantity);
            }

            if (typeof (EC_SHOP_FRONT_NEW_OPTION_EXTRA_DIRECT_BASKET) !== 'undefined') {
                EC_SHOP_FRONT_NEW_OPTION_EXTRA_DIRECT_BASKET.bIsLoadedPriceAjax = true;
            }
        }
    });
}

/**
 * 옵션 사용가능 체크
 */
function checkOptionBox(sOptionId)
{
    if (oSingleSelection.isItemSelectionTypeS() === true) {
        return false;
    }
    if (typeof(EC_SHOP_FRONT_PRODUCT_FUNDING) === 'object' && EC_SHOP_FRONT_PRODUCT_FUNDING.isFundingProduct() === true) {
        return false;
    }
    var bSelected = false;

    // 이미 선택된 옵션은 아무 처리도 하지 않도록 처리한다.
    $('.option_box_id').each(function(i) {
        if ($(this).val() == sOptionId) {
            bSelected = true;
        }
    });

    $('.soldout_option_box_id').each(function(i) {
        if ($(this).val() == sOptionId) {
            bSelected = true;
        }
    });

    return bSelected;
}

/*
 * 옵션선택 박스 설정
 * @todo totalproduct id를 컨트롤러로 밀어야함
 */
function setOptionBox(sItemCode, sOptionText, iPrice, bSoldOut, sSelectElementId, sIsReserveStat, iManualQuantity)
{
    var sReadonly = '';
    var oSelect = $("#"+sSelectElementId);

    // 필수 추가옵션 작성여부 검증
    if (checkAddOption() !== true) {
        delete oProductList[sItemCode];
        NEWPRD_ADD_OPTION.resetSelectElement(oSelect);

        // 독립선택형 옵션별로 한개씩 선택시
        if (typeof(is_onlyone) === 'string' && is_onlyone === 'T' && isNewProductSkin() === true) {
            oSelect.removeAttr('is_selected');
        }

        return false;
    }

    if (checkOptionBox(sItemCode) === true) {
        alert(__('이미 선택되어 있는 옵션입니다.'));
        NEWPRD_OPTION.resetSelectElement(oSelect);
        return false;
    }

    var iBuyUnit  = EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getBuyUnitQuantity('base');
    var iProductMin = EC_FRONT_NEW_PRODUCT_QUANTITY_VALID.getProductMinQuantity();

    if (parseInt(buy_unit,10) > 1) {
        sReadonly = 'readonly';
    }

    var sStrPrice = SHOP_PRICE_FORMAT.toShopPrice(iPrice);

    var iQuantity = (iBuyUnit >= iProductMin ? iBuyUnit : iProductMin);
    if (typeof(iManualQuantity) !== 'undefined') {
        iQuantity = iManualQuantity;
    }


    // 적립금 추가 필요
    var iMileageVal = 0;
    var sMileageIcon = (typeof(mileage_icon) != 'undefined') ? mileage_icon : '//img.echosting.cafe24.com/design/common/icon_sett04.gif';
    var sMileageAlt  = (typeof(mileage_icon_alt) != 'undefined') ? mileage_icon_alt : '';

    if (typeof(option_stock_data) !== 'undefined') {
        var aStockData = $.parseJSON(option_stock_data);
    }

    if (typeof (mileage_val) != 'undefined') {
        var iStockPrice = 0;
        if (Olnk.isLinkageType(option_type) === true) {
            var aOptionTmp = sItemCode.split('||');
            var aOptionIdTmp = new Array;
            var sItemCodeTemp = '';
            for ( i = 0; i < aOptionTmp.length; i++ ) {
                if (aOptionTmp[i] !== '' ) {
                    aOptionIdTmp = aOptionTmp[i].split('_');
                    if (/^\*+$/.test(aOptionIdTmp[0]) === false )  {
                        iStockPrice = parseFloat(aStockData[aOptionIdTmp[0]].option_price);
                    }
                }
            }
        } else if (typeof (aStockData[sItemCode].stock_price) != 'undefined' ) {
            iStockPrice = aStockData[sItemCode].stock_price;
        }
        iMileageVal = TotalAddSale.getMileageGenerateCalc(sItemCode, iQuantity);
    }
    var sMileageVal = SHOP_PRICE_FORMAT.toShopMileagePrice(iMileageVal);
    // ECHOSTING-58174
    if (sIsDisplayNonmemberPrice == 'T') {
        sStrPrice = sNonmemberPrice;
        sMileageVal = sNonmemberPrice;
    }


    var sProductName = product_name;
    if (sProductName != null) {
        sProductName = product_name.replace(/\\"/g, '"');
    }

    var aAddOption = NEWPRD_ADD_OPTION.getCurrentAddOption();

    var sAddOptionTitle = NEWPRD_ADD_OPTION.getCurrentAddOptionTitle(aAddOption);

    var iIndex = 1;
    if (parseInt($('#totalProducts > table > tbody').find('tr.option_product').length) > 0) {
        // max
        iIndex = parseInt($('#totalProducts > table > tbody').find('tr.option_product').last().data('option-index')) + 1;
    }
    var iTargetKey = iProductNo;
    if (option_type === 'F') {
        iTargetKey = iProductNo+'|'+ EC_SHOP_FRONT_NEW_OPTION_COMMON.getOptionSortNum(oSelect);
    }

    var sDisplayOption = '';
    /**
     * 옵션선택시 바로 장바구니 담기 상태라면 hide처리
     * @see EC_SHOP_FRONT_NEW_OPTION_EXTRA_DIRECT_BASKET.setUseDirectBasket()
     */
    if (typeof(EC_SHOP_FRONT_NEW_OPTION_EXTRA_DIRECT_BASKET) !== 'undefined' && EC_SHOP_FRONT_NEW_OPTION_EXTRA_DIRECT_BASKET.isAvailableDirectBasket(oSelect) === true) {
        sDisplayOption = 'displaynone';
    }

    var sOptionBoxId = 'option_box' + iIndex;
    var sOptionId = (typeof(aStockData[sItemCode]) != 'undefined' && typeof(aStockData[sItemCode].option_id) != 'undefined') ? aStockData[sItemCode].option_id : '';
    var sTableRow = '<tr class="option_product ' + sDisplayOption + '" data-option-index="'+iIndex+'" target-key="'+iTargetKey+'">';

    if (EC_MOBILE === true || EC_MOBILE_DEVICE === true) {
        sTableRow += '<td>';
        sOptionText = '<p class="product"><strong>' + sProductName + '</strong><br /> - <span>' + sAddOptionTitle + sOptionText + '</span></p>';

        if (bSoldOut === true) {
            try {
                if ($('#NaverChk_Button').length > 0 && $('#NaverChk_Button').children().length > 0) {
                    $('#NaverChk_Button').css('display', 'none');
                }
            } catch(e) {}

            sTableRow += '<input type="hidden" class="soldout_option_box_id" id="'+sOptionBoxId+'_id" value="'+sItemCode+'">'+sOptionText;
            sTableRow += '<p><input type="number" readonly value="0"/> ';
            sTableRow += '<a href="#none" class="up"><img width="30" height="27" src="//img.echosting.cafe24.com/mobileWeb/common/btn_quantity_up.png"/></a> &nbsp;';
            sTableRow += '<a href="#none" class="down"><img width="30" height="27" src="//img.echosting.cafe24.com/mobileWeb/common/btn_quantity_down.png"/></a></span></p></td>';
            sTableRow += '<td class="right"><strong class="price">'+sStrPrice+'</strong></td>';
            sTableRow += '<td class="center"><a href="#none"><img src="//img.echosting.cafe24.com/design/skin/default/product/btn_price_delete.gif" alt="삭제" id="'+sOptionBoxId+'_del" class="option_box_del" /></a></td>';
        } else {

            //ECHOSTING 162635 예약주문 속성추가
            var sInputHiddenReserved = 'data-item-reserved="' + sIsReserveStat + '" ';

            sTableRow += '<input type="hidden" class="option_box_id" id="'+sOptionBoxId+'_id" value="'+sItemCode+'" name="item_code[]" data-item-add-option="'+escape(aAddOption.join(NEWPRD_OPTION.DELIMITER_SEMICOLON))+'"' + sInputHiddenReserved + ' data-option-id="'+sOptionId+'">'+sOptionText;
            sTableRow += '<p><input type="number" id="'+sOptionBoxId+'_quantity" name="quantity_opt[]" autocomplete="off" class="quantity_opt eProductQuantityClass" '+sReadonly+' value="'+iQuantity+'" product-no="'+iProductNo+'"/> ';
            sTableRow += '<a href="#none" class="up eProductQuantityUpClass" data-target="'+sOptionBoxId+'_up"><img width="30" height="27" src="//img.echosting.cafe24.com/mobileWeb/common/btn_quantity_up.png" id="'+sOptionBoxId+'_up" class="option_box_up" alt="up" /></a> &nbsp;';
            sTableRow += '<a href="#none" class="down eProductQuantityDownClass" data-target="'+sOptionBoxId+'_down"><img width="30" height="27" src="//img.echosting.cafe24.com/mobileWeb/common/btn_quantity_down.png" id="'+sOptionBoxId+'_down" class="option_box_down" alt="down" /></a></p></td>';
            sTableRow += '<td class="right"><strong id="'+sOptionBoxId+'_price" class="price"><input type="hidden" class="option_box_price" value="'+iPrice+'" product-no="'+iProductNo+'" item_code="'+sItemCode+'"><span class="ec-front-product-item-price" code="' + sItemCode + '" product-no="'+iProductNo+'">'+sStrPrice+'</span></strong>';
            if (TotalAddSale.checkVaildMileageValue(iMileageVal) === true && sIsMileageDisplay === 'T') {
                sTableRow += '<span class="mileage">(<img src="'+sMileageIcon+'" alt="'+sMileageAlt+'" /> <span id="'+sOptionBoxId+'_mileage" class="mileage_price" code="' + sItemCode + '">'+sMileageVal+'</span>)</span>';
            }
            sTableRow += '</td>';
            sTableRow += '<td class="center"><a href="#none" class="delete"><img src="//img.echosting.cafe24.com/design/skin/default/product/btn_price_delete.gif" alt="삭제" id="'+sOptionBoxId+'_del" class="option_box_del" /></a></td>';
        }
        sTableRow += '</tr>';

        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer(true) === true) {
            parent.$('#totalProducts > table > tbody').last().append(sTableRow);
        }
        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isExistLayer() === true) {
            if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer() === false) {
                $("#productOptionIframe").contents().find('#totalProducts > table > tbody').last().append(sTableRow);
            }
        }
    } else {
        sOptionText = '<p class="product">' + sProductName + '<br /> - <span>' + sAddOptionTitle + sOptionText + '</span></p>';

        if (bSoldOut === true) {
            try {
                if ($('#NaverChk_Button').length > 0 && $('#NaverChk_Button').children().length > 0) {
                    $('#NaverChk_Button').css('display', 'none');
                }
            } catch(e) {}
            sTableRow += '<td><input type="hidden" class="soldout_option_box_id" id="'+sOptionBoxId+'_id" value="'+sItemCode+'">'+sOptionText+'</td>';
            sTableRow += '<td><span class="quantity" style="width:65px;"><input type="text" '+sReadonly+' value="0"/><a href="#none" class="up"><img src="//img.echosting.cafe24.com/design/skin/default/product/btn_count_up.gif" alt="수량증가" /></a><a href="#none" class="down"><img src="//img.echosting.cafe24.com/design/skin/default/product/btn_count_down.gif" alt="수량감소" /></a></span>';
            sTableRow += '<a href="#none" class="delete"><img src="//img.echosting.cafe24.com/design/skin/default/product/btn_price_delete.gif" alt="삭제" id="'+sOptionBoxId+'_del" class="option_box_del" /></a></td>';
            sTableRow += '<td class="right"><span id="'+sOptionBoxId+'_price"><span>'+sStrPrice+'</span></span>';
        } else {

            //ECHOSTING 162635 예약주문 속성추가
            var sInputHiddenReserved = 'data-item-reserved="' + sIsReserveStat + '" ';
            sTableRow += '<td><input type="hidden" class="option_box_id" id="'+sOptionBoxId+'_id" value="'+sItemCode+'" name="item_code[]" data-item-add-option="'+escape(aAddOption.join(NEWPRD_OPTION.DELIMITER_SEMICOLON))+'"' + sInputHiddenReserved + ' data-option-id="\'+sOptionId+\'">'+sOptionText+'</td>';
            sTableRow += '<td><span class="quantity" style="width:65px;">';
            sTableRow += '<input type="text" id="'+sOptionBoxId+'_quantity" name="quantity_opt[]" class="quantity_opt eProductQuantityClass" '+sReadonly+' value="'+iQuantity+'" product-no="'+iProductNo+'"/>';
            sTableRow += '<a href="#none" class="up eProductQuantityUpClass"" data-target="'+sOptionBoxId+'_up" ><img src="//img.echosting.cafe24.com/design/skin/default/product/btn_count_up.gif" id="'+sOptionBoxId+'_up" class="option_box_up" alt="수량증가" /></a>';
            sTableRow += '<a href="#none" class="down eProductQuantityDownClass" data-target="'+sOptionBoxId+'_down"><img src="//img.echosting.cafe24.com/design/skin/default/product/btn_count_down.gif" id="'+sOptionBoxId+'_down" class="option_box_down" alt="수량감소" /></a>';
            sTableRow += '</span>';
            sTableRow += '<a href="#none" class="delete"><img src="//img.echosting.cafe24.com/design/skin/default/product/btn_price_delete.gif" alt="삭제" id="'+sOptionBoxId+'_del" class="option_box_del" /></a></td>';
            sTableRow += '<td class="right"><span id="'+sOptionBoxId+'_price">';
            sTableRow += '<input type="hidden" class="option_box_price" value="'+iPrice+'" product-no="'+iProductNo+'" item_code="'+sItemCode+'">';
            sTableRow += '<span class="ec-front-product-item-price" code="' + sItemCode + '" product-no="'+iProductNo+'">'+sStrPrice+'</span></span>';
        }

        if (TotalAddSale.checkVaildMileageValue(iMileageVal) === true && sIsMileageDisplay === 'T') {
            sTableRow += '<span class="mileage">(<img src="'+sMileageIcon+'" alt="'+sMileageAlt+'" /> <span id="'+sOptionBoxId+'_mileage" class="mileage_price" code="' + sItemCode + '">'+sMileageVal+'</span>)</span>';
        }

        sTableRow += '</td></tr>';
    }


    if (0 == $('#totalProducts > table > tbody.option_products').length) {
        $('#totalProducts > table > tbody').last().addClass("option_products").after($('<tbody class="add_products"/>'));
    }

    if ($('.EC-funding-checkbox').length === 0) {
        $('#totalProducts > table > tbody.option_products').append(sTableRow);
    } else {
        if (has_option === 'T') {
            var sCompositionCode = EC_SHOP_FRONT_NEW_OPTION_EXTRA_FUNDING.sCurrentCompositionCode;
            EC_SHOP_FRONT_PRODUCT_FUNDING.appendSelectedItem(sItemCode, sCompositionCode);
        }
    }
    // 총 주문금액/수량 처리
    setTotalData();

    //적립금 / 품목금액 업데이트
    TotalAddSale.updatePrice(sOptionBoxId, sItemCode);
}

/**
 * 총 상품금액/수량 적용
 */
function setTotalData()
{
    // 실제 계산
    var iTotalCount = 0;
    var iTotalPrice = 0;
    var iVatSubTotalPrice = 0;
    var aEventQuantity = new Array();
    var aEventQuantityCheck = {};
    //add_product_option_box_price추가구성상품
    var bIsValidBundleObject = typeof(EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE) === 'object';
    var fEventProductPrice = 0;

    $('.option_box_price, .option_add_box_price, .add_product_option_box_price').each(function(i) {
        var iProductNum = (has_option === 'T') ? $(this).attr('product-no') : iProductNo;
        var sItemCode = $(this).attr('item_code');
        if (parseInt(iProductNum) === parseInt(iProductNo) && bIsValidBundleObject === true && EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.oBundleConfig.hasOwnProperty(iProductNum) === true) {
            if (has_option === 'T') {
                var iSingleQuantity = parseInt($('.quantity_opt[product-no="'+iProductNum+'"]').eq(i).val(),10);
            } else {
                var iSingleQuantity = parseInt($('input[name="quantity_opt[]"]').eq(i).val(),10);
            }

            if (typeof(aEventQuantityCheck[iProductNum]) === 'undefined') {
                aEventQuantityCheck[iProductNum] = 0;
                aEventQuantity.push({'product_no' : iProductNum});
            }

            aEventQuantityCheck[iProductNum] += iSingleQuantity;
        } else if (typeof($(this).attr('composition-code')) !== 'undefined') {
            var sCompositionCode = $(this).attr('composition-code');
            var iQuantity = $('#quantity_'+sCompositionCode).val();
            iTotalPrice = iTotalPrice + ($(this).val() * iQuantity);
        } else {
            if (typeof EC_FRONT_JS_CONFIG_SHOP.bSoldout === 'undefined') {
                iTotalPrice += parseFloat($(this).val());
                iVatSubTotalPrice += TotalAddSale.getVatSubTotalPrice(sItemCode);
            }
        }
    });
    $(aEventQuantity).each(function() {
        fEventProductPrice = fEventProductPrice + (product_price * EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.getQuantity(aEventQuantityCheck[this.product_no], this.product_no));
    });
    iTotalPrice = iTotalPrice + fEventProductPrice;

    if (iVatSubTotalPrice != iTotalPrice && iVatSubTotalPrice != 0 && iTotalPrice != 0) {
        iTotalPrice = iVatSubTotalPrice;
    }
    iTotalAddSalePrice = TotalAddSale.getTotalAddSalePrice();
    if (typeof(iTotalAddSalePrice) != 'undefined' && iTotalAddSalePrice != 0) {
        iTotalPrice -= parseFloat(iTotalAddSalePrice, 10);
    }

    iTotalPrice = (iTotalPrice <= 0) ? 0 : iTotalPrice;
    var sStrPrice = SHOP_PRICE_FORMAT.toShopPrice(iTotalPrice);

    iTotalCount = EC_SHOP_FRONT_PRODUCT_INFO.getTotalQuantity();
    var sQuantityString = '('+sprintf(__('%s개'),iTotalCount)+')';

    // ECHOSTING-58174
    if (sIsDisplayNonmemberPrice == 'T') {
        sStrPrice = sNonmemberPrice;
    }
    var sTotalPriceSelector = oSingleSelection.getTotalPriceSelector();
    // 실제 노출
    if (EC_MOBILE === true || EC_MOBILE_DEVICE === true) {
        $(sTotalPriceSelector).html('<strong class="price">'+sStrPrice+'</strong> '+sQuantityString);

        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer(true) === true) {
            parent.$(sTotalPriceSelector).html('<strong class="price">'+sStrPrice+'</strong> '+sQuantityString);
        }
        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isExistLayer() === true) {
            if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer() === false) {
                $("#productOptionIframe").contents().find(sTotalPriceSelector).html('<strong class="price">'+sStrPrice+'</strong> '+sQuantityString);
            }
        }
    } else {
        $(sTotalPriceSelector).html('<strong><em>'+sStrPrice+'</em></strong> '+sQuantityString+'</span>');
    }

    setTotalPriceRef(iTotalPrice, sQuantityString);
    setProductPriceTaxTypeText(iTotalPrice);
    setActionButtonVisible();
}




/**
 * 예약주문, 바로구매 버튼 , 정기 배송 버튼
 */
var setActionButtonVisible = function ()
{
    var sActionButtonSelector = '#btnBuy, #actionBuy, #actionBuyClone, #actionBuyCloneFixed';
    var sReserveSelector = '#btnReserve, #actionReserve, #actionReserveClone, #actionReserveCloneFixed';
    var sActionButtonRegular = '#btnRegularDeliveryCloneFixed, #btnRegularDelivery';

    var oOptionBox = $('.option_box_id');
    var oSoldoutOptionBox = $('.soldout_option_box_id');
    var bIsReserveStatus = oOptionBox.length === oOptionBox.filter('[data-item-reserved="R"]').length;

    if (oOptionBox.length > 0) {
        $(sActionButtonSelector).show();
        $(sReserveSelector).hide();
    }

    if (oSoldoutOptionBox.length > 0 || oOptionBox.length < 1) {
        $(sActionButtonSelector).show();
        $(sReserveSelector).hide();
        setActionButtonRegular(sActionButtonSelector, sReserveSelector, sActionButtonRegular);
        return;
    }

    if (bIsReserveStatus) {
        $(sActionButtonSelector).hide();
        $(sReserveSelector).removeClass("displaynone").show();
        $(sActionButtonRegular).hide();
        return;
    }

    setActionButtonRegular(sActionButtonSelector, sReserveSelector, sActionButtonRegular);
};

/**
 * 정기 배송 버튼
 */
var setActionButtonRegular = function (sActionButtonSelector , sReserveSelector, sActionButtonRegular)
{
    if (EC_FRONT_JS_CONFIG_SHOP.bRegularConfig === true && ($('#is_subscriptionT').is(":checked") === true || EC_FRONT_JS_CONFIG_SHOP.bIsUseRegularDelivery === 'T')) {
        $(sActionButtonSelector).hide();
        $(sReserveSelector).hide();
        $(sActionButtonRegular).removeClass("displaynone").show();
    } else {
        $(sActionButtonRegular).hide();
    }
};

/**
 * 총 상품금액에 참조화폐 추가
 * @param iTotalPrice
 * @param sQuantityString
 */
function setTotalPriceRef(iTotalPrice, sQuantityString)
{
    var sPrePrice = '';
    var sPostPrice = '';
    var sTotalPrice = SHOP_PRICE_FORMAT.toShopPrice( iTotalPrice );
    var sTotalPriceRef = SHOP_PRICE_FORMAT.shopPriceToSubPrice(iTotalPrice);

    if (sTotalPriceRef == '') {
        return;
    }

    // ECHOSTING-58174
    if (sIsDisplayNonmemberPrice == 'T') {
        sTotalPrice = sNonmemberPrice;
        sTotalPriceRef = sNonmemberPrice;
    }

    var sTotalPriceSelector = oSingleSelection.getTotalPriceSelector();
    if (EC_MOBILE === true || EC_MOBILE_DEVICE === true) {
        if (currency_disp_type == 'P') {
            $(sTotalPriceSelector).find('strong').append(' / ' + sTotalPriceRef);
        } else {
            $(sTotalPriceSelector).html('<strong class="price">'+ sTotalPriceRef +' '+sQuantityString + '</strong> / ' + sTotalPrice);
        }

        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer(true) === true) {
            parent.$(sTotalPriceSelector).find('strong').append(' / ' + sTotalPriceRef);
        }
        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isExistLayer() === true) {
            if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer() === false) {
                $("#productOptionIframe").contents().find(sTotalPriceSelector).find('strong').append(' / ' + sTotalPriceRef);
            }
        }
    } else {
        if (currency_disp_type == 'P') {
            $(sTotalPriceSelector).append(' / ' + sTotalPriceRef );
        } else {
            $(sTotalPriceSelector).html('<strong><em>' + sTotalPriceRef + '</em></strong> ' + sQuantityString + '</span> / ' + sTotalPrice);
        }
    }
}

/**
 * 부가세 표시 문구 설정 반영
 * @param int iTotalPrice 총 상품 금액
 */
function setProductPriceTaxTypeText(iTotalPrice)
{
    var oProductTaxTypeText = TotalAddSale.getProductTaxTypeText();
    if (typeof(oProductTaxTypeText) === 'undefined') {
        return;
    }

    var iTotalOrderPrice = TotalAddSale.getTotalOrderPrice();
    iTotalPrice = SHOP_PRICE.toShopPrice(iTotalPrice);
    var iTaxPrice = (oProductTaxTypeText.product_tax_type_per > 0) ? SHOP_PRICE.toShopPrice(iTotalOrderPrice - iTotalPrice) : 0;
    if (iTotalPrice == 0) {
        return;
    }

    var iProductPrice = (oProductTaxTypeText.display_prd_vat_separately === 'T') ? iTotalOrderPrice : iTotalPrice;
    var iProductVatPrice = iTotalPrice;
    // 부가세율 공식
    if (oProductTaxTypeText.display_prd_vat_separately === 'F' || oProductTaxTypeText.product_tax_type !== 'A') {
        iTaxPrice = (iProductPrice * oProductTaxTypeText.product_tax_type_per) / (100 + oProductTaxTypeText.product_tax_type_per);
        var iShopDecimal = (oProductTaxTypeText.shop_decimal_place > 0) ? oProductTaxTypeText.shop_decimal_place : 1;
        iTaxPrice = Math.round(iTaxPrice * iShopDecimal) / iShopDecimal;
        iProductVatPrice = iProductVatPrice - iTaxPrice;
    }

    // 부가세가 0원 미만 및 판매가가 0원 이하 이면 부가세 발생 불가
    if (iTaxPrice < 0 || iProductPrice <= 0 || iProductVatPrice <= 0) {
        return;
    }

    var sTaxPrice = SHOP_PRICE_FORMAT.toShopPrice(iTaxPrice);
    var sProductPrice = SHOP_PRICE_FORMAT.toShopPrice(iProductPrice);
    var sProductVatPrice = SHOP_PRICE_FORMAT.toShopPrice(iProductVatPrice);

    var sProductTypeText = oProductTaxTypeText.product_tax_type_text.replace(/\[:제외금액:\]|\[:VAT_EXCLUDED_PRICE:\]/g, sProductVatPrice);
    sProductTypeText = sProductTypeText.replace(/\[:포함금액:\]|\[:VAT_INCLUDED_PRICE:\]/g, sProductPrice);
    sProductTypeText = sProductTypeText.replace(/\[:부가세:\]|\[:VAT:\]/g, sTaxPrice);

    //Tags
    var sTags = 'font-size:' + parseInt(oProductTaxTypeText.product_tax_type_text_font_size, 10) + 'px;';
    sTags += 'color:' + oProductTaxTypeText.product_tax_type_text_color + ';';
    sTags += oProductTaxTypeText.product_tax_type_text_font_type;

    sProductTypeText = ' <span style="' + sTags + '">' + sProductTypeText + '</span>';

    if (EC_MOBILE === true || EC_MOBILE_DEVICE === true) {
        $('#totalProducts .total').find('strong').append(sProductTypeText);

        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer(true) === true) {
            parent.$('#totalProducts .total').find('strong').append(sProductTypeText);
        }
        if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isExistLayer() === true) {
            if (EC_SHOP_FRONT_PRODUCT_OPTIONLAYER.isDisplayLayer() === false) {
                $("#productOptionIframe").contents().find('#totalProducts .total').find('strong').append(sProductTypeText);
            }
        }
    } else {
        $('#totalProducts .total').append(sProductTypeText);
    }
}


/**
 * 상품금액 계산 (모바일 및 할인판매가 체크)
 * @param iQuantity 수량
 * @param iQuantity 가격
 * @param sItemCode 옵션코드
 * @param bSoldout 품절여부
 * @param fCallback 콜백함수
 */
function getProductPrice(iQuantity, iOptionPrice, sItemCode, bSoldOut, fCallback)
{
    var fProductPrice = SHOP_PRICE.toShopPrice(product_price);
    if (typeof(iQuantity) == 'undefined' || iQuantity == 0) {
        iQuantity = 1;
    }
    // 1+N이벤트의 경우
    iEventQuantity = EC_SHOP_FRONT_PRODUCT_DEATAIL_BUNDLE.getQuantity(iQuantity, iProductNo);
    fProductPrice = iOptionPrice * parseInt(iEventQuantity, 10);
    oProductList = TotalAddSale.getProductList();
    TotalAddSale.setSubscriptionParam();
    if (EC_SHOP_FRONT_NEW_OPTION_EXTRA_FUNDING.sCurrentCompositionCode !== null) {
        TotalAddSale.setParam('composition_code', EC_SHOP_FRONT_NEW_OPTION_EXTRA_FUNDING.sCurrentCompositionCode);
        var iFundingNum = $('.EC-funding-checkbox[value="'+ EC_SHOP_FRONT_NEW_OPTION_EXTRA_FUNDING.sCurrentCompositionCode+'"]').data('funding-no');
        TotalAddSale.setParam('funding_no', iFundingNum);
    }

    // 할인판매가
    if (sItemCode != 'undefined' && sItemCode != '' && sItemCode != '*' && sItemCode != '**' && sItemCode !== null) {
        // 옵션이 있는 경우에는 iOptionPrice가 판매가로 들어가 있어서
        // 할인된 금액이 처리되지 않지만 옵션이 없는 경우 이쪽으로 판매가가 할인 판매가로 설정되어버림
        // 상품 상세페이지내에서는 할인 판매가로 컨트롤 없음
        //fProductPrice = SHOP_PRICE.toShopPrice(product_sale_price);
        // 품절시 ajax호출안함
        TotalAddSale.setProductOptionType(sItemCode, sOptionType);
        TotalAddSale.setSoldOutFlag(bSoldOut);
        TotalAddSale.setQuantity(sItemCode, iQuantity);
        TotalAddSale.setParam('product', oProductList);
        if (has_option === 'F') {
            iQuantity = iEventQuantity;
        }
        TotalAddSale.getCalculatorSalePrice(fCallback, iOptionPrice * parseInt(iQuantity, 10));
    } else {
        if (bSoldOut) {
            TotalAddSale.setQuantity(sItemCode, 0);
            TotalAddSale.setParam('product', oProductList);
        }
        fCallback(fProductPrice);
    }

    return fProductPrice;
}

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
    if (ch == null || ch.length == 0 ) return 0;
    var charCode = ch.charCodeAt(0);
    if (escape(charCode).length > 4 ) {
        return 2;
    } else {
        return 1;
    }
}

/**
 * 기존의 SHOP_PRICE_FORMAT.toShopPrice() 의 래핑 함수
 * @param fPrice 옵션 추가 금액
 * @returns String 옵션 추가 금액(금액이 0보다 클경우 '+' 태그 추가)
 */
function getOptionPrice(fPrice)
{
        var sPricePlusTag = '';

        if (fPrice > 0) {
            sPricePlusTag = '+';
        } else {
            sPricePlusTag = '-';
            fPrice = Math.abs(fPrice);
        }

        var aFormat = SHOP_CURRENCY_FORMAT.getShopCurrencyFormat();
        var sPrice = SHOP_PRICE.toShopPrice(fPrice, true);
        return sPricePlusTag + aFormat.head + sPrice + aFormat.tail;
}

/**
 * 추가구성상품 여부 판단후 최종금액 산출
 * @param string sTotalSalePrice 총 상품 금액
 * @param int iTotalSalePrice 판매가
 * @returns string 추가구성 총 상품금액
 */
function getAddProductExistTotalSalePrice(iTotalSalePrice)
{
     $('.add_product_option_box_price').each(function(){
         iTotalSalePrice += parseFloat($(this).val());
     });

     return SHOP_PRICE_FORMAT.toShopPrice( iTotalSalePrice );
}

/**
 * 상품상세페이지 기존 모듈 제거하고 신규 모듈로 (ajax)
 * coupon_productdetail_new.html 에 쿠폰다운로드 신규모둘을 추가하여 ajax처리
 */
function getPrdDetailNewAjax()
{
    var sPath = document.location.pathname;

    if (jQuery.trim(parent.$('.ec-product-couponAjax').html()) != "") {
        return;
    }

    $.get('/product/coupon_productdetail_new.html',{'product_no' : iProductNo,'cate_no' : iCategoryNo, 'sPath' : sPath} ,function(sHtml){
        parent.$('.ec-product-couponAjax').html(sHtml);
        parent.$('.ec-product-couponAjax').show();

        $('div.eToggle .title').click(function(){
            var toggle = $(this).parent('.eToggle');
            if (toggle.hasClass('disable') == false){
                $(this).parent('.eToggle').toggleClass('selected');
            }
        });
    });
}

var SELECTEDITEM = {
    iSequence : 0,
    sElementIdPrefix : 'option_box',
    getElementId : function()
    {
        return this.sElementIdPrefix+this.getSequence();
    },
    getSequence : function()
    {
        return this.iSequence++;
    }
};

var EC_SHOP_FRONT_PRODUCT_INFO = {
    getTotalQuantity : function()
    {
        var sQuantitySelector = 'input[name="quantity_opt[]"]';
        var sQuantityContext = (has_option === 'F' ? '' : '#totalProducts');
        if ($('.EC-funding-checkbox').length > 0) {
            sQuantitySelector = 'input.quantity';
            sQuantityContext = '.xans-product-funding';
        }
        var iTotalCount = 0;
        $(sQuantitySelector, sQuantityContext).each(function() {
            var iQuantity = $(this).val();
            if (typeof($(this).attr('composition-code')) !== 'undefined') {
                iQuantity = 0;
                var sCompositionCode = $(this).attr('composition-code');
                if ($('.selected-funding-item.option_box_price[composition-code="'+sCompositionCode+'"]').length > 0) {
                    iQuantity = $('.selected-funding-item.option_box_price[composition-code="'+sCompositionCode+'"]').attr('quantity');
                }
            }

            iTotalCount = iTotalCount + parseInt(iQuantity, 10);
        });
        return iTotalCount;
    }
};
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

var EC_SHOP_FRONT_NEW_LIKE_BROWSER_CACHE = {
    /**
     * 로컬 스토리지 지원 여부
     * @return bool 지원하면 true, 지원하지 않으면 false
     */
    isSupport: function() {
        if (window.localStorage) {
            return true;
        } else {
            return false;
        }
    },

    /**
     * 로컬 스토리지에 데이터 셋팅
     * @param string sKey 키
     * @param mixed mData 저장할 데이터
     * @param int iLifeTime 살아있는 시간(초) (기본 1일)
     * @return bool 정상 저장 여부
     */
    setItem: function(sKey, mData, iLifeTime) {
        if (this.isSupport() === false) {
            return false;
        }

        iLifeTime = iLifeTime || 86400;

        try {
            window.localStorage.setItem(sKey, JSON.stringify({
                iExpireTime: Math.floor(new Date().getTime() / 1000) + iLifeTime,
                mContent: mData
            }));
        } catch (e) {
            return false;
        }

        return true;
    },

    /**
     * 로컬 스토리지에서 데이터 리턴
     * @param string sKey 키
     * @return mixed 데이터
     */
    getItem: function(sKey) {
        if (this.isSupport() === false) {
            return null;
        }

        var sData = window.localStorage.getItem(sKey);
        try {
            if (sData) {
                var aData = JSON.parse(sData);
                if (aData.iExpireTime > Math.floor(new Date().getTime() / 1000)) {
                    return aData.mContent;
                } else {
                    window.localStorage.removeItem(sKey);
                }
            }
        } catch (e) { }

        return null;
    },

    /**
     * 로컬 스토리지에서 데이터 삭제
     * @param string sKey 키
     */
    removeItem: function(sKey) {
        if (this.isSupport() === false) {
            return;
        }

        window.localStorage.removeItem(sKey);
    }
};

/**
 * 좋아요 관련 공통
 */
var EC_SHOP_FRONT_NEW_LIKE_COMMON = {
    CACHE_LIFE_TIME: 3600,
    CACHE_KEY_MY_LIKE_CATEGORY: 'localMyLikeCategoryNoList',
    CACHE_KEY_MY_LIKE_PRODUCT: 'localMyLikeProductNoList',

    aConfig: {
        bIsUseLikeProduct: false,
        bIsUseLikeCategory: false
    },

    init: function(aConfig)
    {
        this.aConfig = aConfig;
    },

    /**
     * 내 분류 좋아요 번호 리스트를 가져와서 successCallbackFn 콜백 함수를 실행합니다.
     * @param function successCallbackFn 성공시 실행할 콜백 함수
     * @param function completeCallbackFn ajax 호출 완료 후 실행할 콜백 함수
     */
    getMyLikeCategoryNoInList: function(successCallbackFn, completeCallbackFn)
    {
        var self = this;

        var aData = EC_SHOP_FRONT_NEW_LIKE_BROWSER_CACHE.getItem(self.CACHE_KEY_MY_LIKE_CATEGORY);
        if (aData !== null) {
            successCallbackFn(aData);
            if (typeof completeCallbackFn === 'function') {
                completeCallbackFn();
            }
        } else {
            $.ajax({
                url: '/exec/front/shop/LikeCommon',
                type: 'get',
                data: {
                    'mode'   : 'getMyLikeCategoryNoInList'
                },
                dataType: 'json',
                success: function(oReturn) {
                    if (oReturn.bResult === true) {
                        aData = oReturn.aData;
                        EC_SHOP_FRONT_NEW_LIKE_BROWSER_CACHE.setItem(self.CACHE_KEY_MY_LIKE_CATEGORY, aData, self.CACHE_LIFE_TIME);
                        successCallbackFn(aData);
                    }
                },
                complete: function() {
                    completeCallbackFn();
                }
            });
        }
    },

    /**
     * 내 분류 좋아요 번호 리스트 캐시를 퍼지합니다.
     */
    purgeMyLikeCategoryNoInList: function()
    {
        EC_SHOP_FRONT_NEW_LIKE_BROWSER_CACHE.removeItem(this.CACHE_KEY_MY_LIKE_CATEGORY);
    },

    /**
     * 내 상품 좋아요 번호 리스트를 가져와서 successCallbackFn 콜백 함수를 실행합니다.
     * @param function successCallbackFn 성공시 실행할 콜백 함수
     * @param function completeCallbackFn ajax 호출 완료 후 실행할 콜백 함수
     */
    getMyLikeProductNoInList: function(successCallbackFn, completeCallbackFn)
    {
        var self = this;

        var aData = EC_SHOP_FRONT_NEW_LIKE_BROWSER_CACHE.getItem(self.CACHE_KEY_MY_LIKE_PRODUCT);
        if (aData !== null) {
            successCallbackFn(aData);
            if (typeof completeCallbackFn === 'function') {
                completeCallbackFn();
            }
        } else {
            $.ajax({
                url: '/exec/front/shop/LikeCommon',
                type: 'get',
                data: {
                    'mode'   : 'getMyLikeProductNoInList'
                },
                dataType: 'json',
                success: function(oReturn) {
                    if (oReturn.bResult === true) {
                        aData = oReturn.aData;
                        EC_SHOP_FRONT_NEW_LIKE_BROWSER_CACHE.setItem(self.CACHE_KEY_MY_LIKE_PRODUCT, aData, self.CACHE_LIFE_TIME);
                        successCallbackFn(aData);
                    }
                },
                complete: function() {
                    completeCallbackFn();
                }
            });
        }
    },

    /**
     * 내 상품 좋아요 번호 리스트 캐시를 퍼지합니다.
     */
    purgeMyLikeProductNoInList: function()
    {
        EC_SHOP_FRONT_NEW_LIKE_BROWSER_CACHE.removeItem(this.CACHE_KEY_MY_LIKE_PRODUCT);
    },
    // 숫자 관련 콤마 제거 처리(ECHOSTING-260504)
    getNumericRemoveCommas : function(mText) {
        var sSearchCommas = ',';
        var sReplaceEmpty = '';

        if ($.inArray(typeof(mText), ['number', 'undefined']) > -1) {
            return mText;
        }

        while (mText.indexOf(sSearchCommas) > -1) {
            mText = mText.replace(sSearchCommas, sReplaceEmpty);
        }

        return mText;
    },
    // 숫자 관련 콤마 처리 (ECHOSTING-260504)
    getNumberFormat : function(iNumber)
    {
        iNumber += '';

        var objRegExp = new RegExp('(-?[0-9]+)([0-9]{3})');
        while (objRegExp.test(iNumber)) {
            iNumber = iNumber.replace(objRegExp, '$1,$2');
        }

        return iNumber;
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

var EC_SHOP_FRONT_REVIEW_TALK_REVIEW_COUNT = {
    aProductNo: [], bIsReviewTalk: 'F', setReviewTalkCnt: function () {
        var bIsUse = this.checkUseReviewTalk();

        if (bIsUse === true) {
            this.setDataProductNo();
            this.setResponseCountData();
        }
    },

    checkUseReviewTalk: function () {
        return (this.bIsReviewTalk === 'T' && $('.reviewtalk_review_count').length > 0) ? true : false;
    },

    setDataProductNo: function () {
        var aAllProductNo = [];
        $('.reviewtalk_review_count').each(function () {
            aAllProductNo.push($(this).attr('data-product-no'));
        });

        EC_SHOP_FRONT_REVIEW_TALK_REVIEW_COUNT.aProductNo = $.unique(aAllProductNo);
    },

    setResponseCountData: function () {
        if (this.aProductNo.length < 1) {
            return;
        }

        $.ajax({
            url: '/exec/front/shop/ApiReviewtalkReviewcnt', type: 'get', data: {
                'product_no': this.aProductNo.join('_')
            }, dataType: 'json', success: function (oResponse) {
                if (oResponse.result === true) {
                    EC_SHOP_FRONT_REVIEW_TALK_REVIEW_COUNT.setResponseData(oResponse.data);
                }
            }
        });
    },

    //천단위 콤마 표시
    number_format: function(str)
    {
        // 3자리씩 ,로 끊어서 리턴
        str = String(parseInt(str));
        var regexp = /^(-?[0-9]+)([0-9]{3})($|\.|,)/;
        while (regexp.test(str)) {
            str = str.replace(regexp, "$1,$2$3");
        }
        return str;
    },

    setResponseData: function (oResponseData) {
        var oProductReviewCnt = oResponseData;

        if (this.checkUseReviewTalk() === true) {
            $('.reviewtalk_review_count').each(function () {
                var iProductNo = $(this).attr('data-product-no');
                var sFormat = $(this).attr('data-format');
                var iReviewCount = 0;

                if (oProductReviewCnt.hasOwnProperty(iProductNo) === true && oProductReviewCnt[iProductNo].hasOwnProperty('review_count') === true) {
                    iReviewCount = oProductReviewCnt[iProductNo].review_count;
                }

                $(this).text(sFormat.replace('REVIEWTALKCOUNT', EC_SHOP_FRONT_REVIEW_TALK_REVIEW_COUNT.number_format(iReviewCount)));

                var sAddClass = 'reviewtalk_count_display_' + iReviewCount;
                $(this).parent().addClass(sAddClass);
                $(this).parent().siblings('.title').addClass(sAddClass);
            });
        }
    }
};

$(document).ready(function () {
    EC_SHOP_FRONT_REVIEW_TALK_REVIEW_COUNT.setReviewTalkCnt();
});


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
