// window.onload 이벤트추가
function addSuioLoadEvent(func) {
    var oldonload = window.onload;
    if (typeof window.onload != 'function') {
        window.onload = func;
    } else {
        window.onload = function() {
          if (oldonload) {
            oldonload();
          }
          func();
        }
    }
}

var SUIO = function(){
    // tab(old)
    $.eTab = function(ul){
        $(ul).find('a').click(function(e){
            var _li = $(this).parent('li').addClass('selected').siblings().removeClass('selected'),
                _target = $(this).attr('href'),
                _siblings = '.' + $(_target).attr('class');
            $(_target).show().siblings(_siblings).hide();
            e.preventDefault();
        });
    }
    if ( window.call_eTab ) {
        call_eTab();
    };
    // tab(new)
    var findTab = $('.mTab');
    if(findTab.length > 0){
        findTab.each(function(){
            var selected = $(this).find('> ul > li.selected > a');
            if(selected.siblings('ul').length <= 0){
                $(this).removeClass('gExtend');
            }
        });
    }

    $('body').delegate('.eTab li a', 'click', function(e){
        var _li = $(this).parent('li').addClass('selected').siblings().removeClass('selected'),
            _target = $(this).attr('href'),
            _siblings = $(_target).attr('class');
        if(_siblings){
            var _arr = _siblings.split(" "),
                _classSiblings = '.'+_arr[0];
            $(_target).show().siblings(_classSiblings).hide();
        }

        // gExtend ctrl
        var mtab = $(this).parents('.mTab:first');
        if($(this).siblings('ul').length > 0){
            if(!mtab.hasClass('gExtend')){
                mtab.addClass('gExtend');
            }
        } else {
            if($(this).parents('ul:first').siblings('a').length <= 0){
                mtab.removeClass('gExtend');
            }
        }
        e.preventDefault();
    });

	//tab gPaginate
	var findPag = $('.mTab.gPaginate');
	if(findPag.length > 0){
		findPag.each(function(i){
			var figureLen = $(this).find('ul > li').length,
			    figureDiv = gridNumRetrun($(this));

			if(figureLen > figureDiv && figureDiv > 0){
				$(this).find('.btnPrev').attr("disabled", true);
				$(this).find('.btnNext').attr("disabled", false);

				$(this).attr("data-page-no",0);
			}else{
				$(this).find('.btnPrev').attr("disabled", true);
				$(this).find('.btnNext').attr("disabled", true);
			}
		});

		$('body').delegate('.mTab.gPaginate .btnPrev', 'click', function(e){
			pageNoneBlock($(this), "prev");
			$(this).parents('.mTab').find('.btnNext').attr("disabled", false);

			e.preventDefault();
		});

		$('body').delegate('.mTab.gPaginate .btnNext', 'click', function(e){
			pageNoneBlock($(this), "next");
			$(this).parents('.mTab').find('.btnPrev').attr("disabled", false);

			e.preventDefault();
		});

		function pageNoneBlock(target, str){
			var disabledChk = target.attr("disabled");
			if(!disabledChk){
				var figureNowNum = parseInt(target.parents('.mTab').attr("data-page-no"));
				figureNowNum = str == "next" ? figureNowNum += 1 : figureNowNum -= 1;

				var figureDiv = gridNumRetrun(target.parents('.mTab')),
					figureCul = figureNowNum*figureDiv,
					figureTotal = target.parents('.mTab').find('ul > li').length;

				target.parents('.mTab').find('ul > li').css({"display":"none"});

				for(var i = figureCul; i<(figureCul+figureDiv); i++){
					if(str == "next"){
						if(i < figureTotal){
							target.parents('.mTab').find('ul > li').eq(i).css({"display":""});
						}else{
							target.attr("disabled", true);
							break;
						}
					}else{
						if(i >= 0){
							target.parents('.mTab').find('ul > li').eq(i).css({"display":""});
							if(i == 0){
								target.attr("disabled", true);
							}
						}
					}
				}
				target.parents('.mTab').attr("data-page-no",figureNowNum);
			}
		}

		function gridNumRetrun(target){
			var strChk = 'grid',
			    strClassChk = target.attr("class"),
				figureIndex = strClassChk.indexOf(strChk),
				figureGrid = -1;

			if(figureIndex >= 0){
				var arrGrid = strClassChk.split(strChk);
				figureGrid = parseInt(arrGrid[1]);
			}

			return figureGrid;
		}
	}

   // mLayer : close
    $.mLayer_close = function(target){
        var findParent = target.parents('.mLayer:first');
        var findDimmed = $('#dimmed_' + findParent.attr('id'));
        findParent.hide();

        if(findDimmed){
            if($('.dimmed').length > 1){
                $('.dimmed').removeClass('hide');
            }
            findDimmed.remove();
        }
        return false
    }
    $('body').delegate('.mLayer .footer .eClose', 'click', function(){
        $.mLayer_close($(this));
    });
    $('body').delegate('.mLayer > .eClose', 'click', function(){
        $.mLayer_close($(this));
    });

    // mLayer : typeView
    $('body').delegate('.mLayer.typeView', 'mouseleave', function(){
        $(this).hide();
    });

    // mLayer : eLayerClick
    $('body').delegate('.eLayerClick', 'click', function(e){
        var findThis = $(this),
            findTarget = $($(this).attr('href')),
            propTargetWidth = findTarget.outerWidth(),
            propTargetHeight = findTarget.outerHeight(),
            propDocWidth = $(document).width(),
            propDocHeight = $(document).height(),
            propTop = findThis.offset().top,
            propLeft = findThis.offset().left,
            figure = propLeft + propTargetWidth,
            propMarginLeft = 0;

        var propFooterHeight = $('body').find('#footer').outerHeight();
        if(propFooterHeight == null){
            propFooterHeight = 0;
        }
        propTargetHeight = propTargetHeight+propFooterHeight+20;

        if((propDocHeight-propTop) < propTargetHeight){
            if(propDocHeight < propTargetHeight) {
                propTop = -20;
            } else {
                propTop = propDocHeight - propTargetHeight - 10;
            }
        }

        if(propDocWidth <= figure){
            if(propTargetWidth > propLeft){
                propMarginLeft = '-' + ( propTargetWidth / 2 ) + 'px';
                propLeft = '50%';
            } else {
                propLeft = propLeft - propTargetWidth + 20;
            }
        }
        findTarget.css({'top':propTop+10, 'left':propLeft, 'marginLeft':propMarginLeft}).show();
        e.preventDefault();
    });

    // mLayerOver
    // case1) </body> 직전에 하나의 레이어팝업으로 존재하는 경우 (mLayerOver > mLayer)
    $('body').delegate('.eLayerOver', 'mouseenter', function(){
        eLayerOver($(this), "enter", "over");
    });
    // case1) mouseleave
    $('body').delegate('.eLayerOver', 'mouseleave', function(){
        eLayerOver($(this), "leave", "over");
    });

    // eLayerOverSide
    $('body').delegate('.eLayerOverSide', 'mouseenter', function(){
        eLayerOver($(this), "enter", "side");
    });
    $('body').delegate('.eLayerOverSide', 'mouseleave', function(){
        eLayerOver($(this), "leave", "side");
    });

    var flagLayer = true;
    function eLayerOver(findThis, str, opt){
        var findTarget = $(findThis.attr('href')),
            propClass = findThis.attr('class');

        if(str == "enter"){
            // 레이어팝업의 넓이 + offset좌표 의 값이 body태그의 width보다 클때 좌표값 왼쪽으로 이동
            var bodyWidth = $('body').width(),
                targetWidth = findTarget.outerWidth(),
                setTop = findThis.offset().top+findThis.height(),
                setLeft = findThis.offset().left;
            if(opt == "side"){
                setTop = findThis.offset().top-5;
                setLeft = findThis.offset().left+findThis.width()+3;
            }
            var posWidth = targetWidth + setLeft;
            if(bodyWidth < posWidth){
                if(opt == "side"){
                    targetWidth = posWidth - bodyWidth;
                }else{
                    targetWidth = targetWidth - findThis.width();
                }
                findTarget.css({"top":setTop,"left":setLeft, "margin-left":'-'+ targetWidth +'px'});
            } else {
                findTarget.css({"top":setTop,"left":setLeft, "margin-left":0});
            }
            if(flagLayer){
                flagLayer = false;
                findTarget.mouseenter(function() {
                    $(this).show();
                }).mouseleave(function() {
                    $(this).hide();
                });
            }
            findTarget.show();
        }else{
            findTarget.hide();
        }
    }

    /*// case2) 각 링크의 레이어팝업이 따로 존재하는 경우 (mLayerWrap > eLayerOver2, mLayer) 검토필요
    $('body').delegate('.eLayerOver2', 'mouseenter', function(){
        $('.mLayerWrap').css("position","static");
        var findTarget = $(this).siblings('.mLayer');
        findTarget.css("left",$(this).offset().left);
        findTarget.show();
    });
    // case2) mouseleave
    $('body').delegate('.mLayerWrap', 'mouseleave', function(){
        $('.mLayerWrap').css("position","");
        var findTarget = $(this).find('.mLayer');
        findTarget.hide();
    });*/

    // eModal : dimmed layer position
    function dimmedLayerPosition(target){
        if(!target.attr('fixed')){
            var findLayer = target,
                propWinWidth = $(window).width(),
                propWinHeight = $(window).height(),
                propWidth = findLayer.outerWidth(),
                propHeight = findLayer.outerHeight(),
                propWinScroll = $(window).scrollTop(),
                propTop = 0,
                propHeadHeight = 0;

            if(propWinWidth < propWidth){
                findLayer.css({'left':0, 'marginLeft':0});
            } else {
                var propLeft = propWidth/2;
                findLayer.css({'left':'50%', 'marginLeft':'-'+ propLeft +'px'});
            }

            if($('#header').length >= 1){ propHeadHeight = $('#header').height()+10; }
            if(propHeight-propWinHeight>0){
                propTop = propWinScroll + propHeadHeight;
            }else{
                propTop = ((propWinHeight+propHeadHeight)/2) - (propHeight/2) + propWinScroll;
            }
            findLayer.css({'top':propTop});

            findLayer.show();
        }
    }
    // eModal : show
    $('body').delegate('.eModal', 'click', function(e){
        var findTarget = $($(this).attr('href'));
        //call dimmed layer position function
        dimmedLayerPosition(findTarget);
        findTarget.parent().append('<div id="dimmed_'+ findTarget.attr('id') +'" class="dimmed"></div>');
        if($('.dimmed').length > 1 ){
            $('.dimmed').addClass('hide');
            var propZIndex = 110 + $('.dimmed').length;
            $(findTarget).css({'zIndex':propZIndex+5});
            $('#dimmed_'+ findTarget.attr('id')).css({ 'zIndex' : propZIndex }).removeClass('hide');
        }
        e.preventDefault();
    });

    // window resize : dimmed layer position
    $(window).resize(function(){
        if($('.dimmed').length > 0){
            $('.dimmed').each(function(){
                if($(this).css('display') == 'block'){
                    var layerId = $(this).attr('id').replace('dimmed_','');
                    dimmedLayerPosition($('#'+layerId));
                }
            });
        }
    });

    // notice
    $('.mNotice .eClose').click(function(e){
        $(this).parents('.mNotice:first').hide();
        e.preventDefault();
    });

    // toolTip
    //cTip으로 인한 .mMemo.typeOrder 자식에 eTip 있을경우 eTipScroll class 변경
    var findMemo = $('.mMemo.typeOrder');
    if(findMemo.length > 0){
        setTimeout(mMemoCheckEvent, 500);
        function mMemoCheckEvent(){
            findMemo.each(function(i){
                var findTip = $(this).find('.cTip .eTip');
                if(findTip.length > 0){
                    findTip.addClass('eTipScroll').removeClass('eTip');
                }
            });
        }

        findMemo.find('.gLeft, .gRight').scroll(function(){
            toolTipScrollCheck();
        });
    }

    //cTip으로 인한 mBoard.gScroll 자식에 eTip 있을경우 eTipScroll class 변경
    var findBoard = $('.mBoard');
    if(findBoard.length > 0){
        setTimeout(eTipCheckEvent, 500);
        function eTipCheckEvent(){
            findBoard.each(function(i){
                var strClassName = $(this).hasClass('gScroll');
                if(strClassName){
                    var findTip = $(this).find('.cTip .eTip');
                    if(findTip.length > 0){
                        findTip.addClass('eTipScroll').removeClass('eTip');
                    }
                }
            });
        }
        $('.mBoard.gScroll').scroll(function(){
            toolTipScrollCheck();
        });
    }
    // 고정
    $('body').delegate('.mTooltip .eTip', 'click', function(e){
        mTooltipMouseEvent(this, e);
    });
    // mouseover
    $('body').delegate('.mTooltip .eTipHover', 'mouseover', function(e){
        mTooltipMouseEvent(this, e);
    });

    function mTooltipMouseEvent(_this, e){
        var findSection = $(_this).parents('.section:first'),
            findTarget = $($(_this).siblings('.tooltip')),
            findTooltip = $('.tooltip'),
            findHover = $(_this).hasClass('eTipHover'),
            findShow = $(_this).parents('.mTooltip:first').hasClass('show');

        if(findShow && !findHover){
            $('.mTooltip').removeClass('show');
            findTarget.hide();
            findSection.css({'zIndex':'', 'position':''});
        }else{
            $('.mTooltip').removeClass('show');
            $(_this).parents('.mTooltip:first').addClass('show');

            $('.section').css({'zIndex':'', 'position':''});
            findSection.css({'zIndex':100, 'position':'relative'});//.siblings().css({'zIndex':'', 'position':''});

            // 툴팁의 넓이 + offset좌표 의 값이 body태그의 width보다 클때 좌표값 왼쪽으로 이동
            var bodyWidth = $('body').width(),
                targetWidth = findTarget.outerWidth(),
                offsetLeft = $(_this).offset().left,
                posWidth = targetWidth + offsetLeft;

            if(bodyWidth < posWidth){
                var propMarginLeft = (targetWidth+$(_this).width()+10);
                var propWidth = offsetLeft - targetWidth;
                if(propWidth > 0){
                    findTarget.addClass('posRight').css({'marginLeft': '-'+ targetWidth +'px' });
                }else{
                    findTarget.removeClass('posRight').css({'marginLeft': 0 });
                }
            } else {
                findTarget.removeClass('posRight').css({'marginLeft': 0 });
            }
            // 툴팁의 top 값이 window height값보다 클때 좌표값 상단으로 이동
            var findFooter = $('#footer');
            var propFooterHeight = 0;
            if(findFooter.length >= 1){
                propFooterHeight = findFooter.outerHeight();
            }
            var propwindowHeight = $(window).height()-propFooterHeight,
                targetHeight = findTarget.outerHeight(),
                propscrollTop = $(window).scrollTop(),
                offsetTop = $(_this).offset().top,
                posHeight = (offsetTop-propscrollTop)+targetHeight+$(_this).height();

            if(propwindowHeight < posHeight){
                var propMarginTop = (targetHeight+$(_this).height()+10);
                var propHeight = (offsetTop-propscrollTop) - targetHeight;
                var propHeadHeight = 0;
                if($('#header').length >= 1){
                    propHeadHeight = $('#header').height();
                }
                if(propHeight > propHeadHeight){
                    findTarget.addClass('posTop').css({'marginTop': '-'+ propMarginTop +'px' });
                }else{
                    findTarget.removeClass('posTop').css({'marginTop': 0 });
                }
            }else{
                findTarget.removeClass('posTop').css({'marginTop': 0 });
            }

            findTooltip.hide();
            findTarget.show();

            if($('#tooltipSCrollView').length > 0){
                $('#tooltipSCrollView').remove();
            }
        }
        e.preventDefault();
    }

    /**
     * .mTooltip 요소가 프레임(frame or table) 안에 들어갈 경우, 프레임 공간제약으로 인해 툴팁이 가려지게 됨.
     * body 에 새로운 툴팁 생성하고 컨텐츠를 붙임.
     *
     * - 비동기방식으로 툴팁 컨텐츠가 보충되는 형태로 변경에 의한 툴팁 열리는 타이밍 수정
     * @modify : https://jira.simplexi.com/browse/ECHOSTING-309581
     */
    $('body').delegate('.mTooltip .eTipScroll', 'click', function(e) {

        if(e) e.preventDefault();

        var findShow,
            findThis = $(this),
            parentEl = $(this).closest('.mTooltip'),
            tooltip = $(this).siblings('.tooltip').clone(),
            prevClass = parentEl.attr('class'),
            asynceContentCheck = findThis.siblings('.tooltip').find('>.content').html(),
            asyncContentTimer = null,
            asyncContentTimerCount = 0,
            asyncContentTimerLimit = 30;

        function createOutSideTooltip() {

            var findTarget,
                propTargetWidth,
                propDocWidth = $(document).width(),
                propTop = findThis.offset().top + 5,
                propLeft = findThis.offset().left,
                figure,
                propMarginLeft = '-12px',
                propMarginTop = findThis.outerHeight(),
                findFooter = $('#footer'),
                propFooterHeight = 0,
                propwindowHeight,
                targetHeight,
                propscrollTop = $(window).scrollTop(),
                posHeight,
                figureTop,
                figurelLen,
                figureChk,
                figureCul,
                propHasClass;

            if( $('#tooltipSCrollView').length ) $('#tooltipSCrollView').remove();

            $('body').append('<div id="tooltipSCrollView" class="'+ prevClass +'" virtual="true">');
            $('#tooltipSCrollView').append(tooltip);
            $('#tooltipSCrollView').find('.tooltip').css("z-index","");

            $('.mTooltip').removeClass('show');
            setTimeout(function() { parentEl.addClass('show'); },100);

            findTarget = $('#tooltipSCrollView').find('.tooltip');
            propTargetWidth = findTarget.outerWidth();
            figure = propLeft + propTargetWidth;
            targetHeight = findTarget.outerHeight();
            posHeight = ( propTop - propscrollTop ) + targetHeight + findThis.height();

            if(propDocWidth <= figure) {

                propLeft = propLeft - propTargetWidth + 20;
                findTarget.addClass('posRight');

            } else {

                findTarget.removeClass('posRight');

            }

            if(findFooter.length >= 1) {

                propFooterHeight = findFooter.outerHeight();
                propwindowHeight = $(window).height() - propFooterHeight;

            }

            if(propwindowHeight < posHeight && propTop - propscrollTop - 1 > targetHeight) {

                findTarget.addClass('posTop');
                propTop = propTop - ( targetHeight+30 );

            } else {
                findTarget.removeClass('posTop');

            }

            findTarget.css({'top':propTop, 'left':propLeft, 'marginLeft':propMarginLeft, 'marginTop':propMarginTop}).show();

            figureTop = Math.abs(propTop - $('#tooltipSCrollView').find('.tooltip').offset().top);
            if(figureTop > $('.mTooltip .icon').height()) {

                figureChk = figureTop - $('.mTooltip .icon').height();
                figureCul = propTop - figureChk;
                propHasClass = findTarget.hasClass('posTop');

                if( propHasClass ) {

                    findTarget.css({"top":(figureCul+5)});

                } else {

                    findTarget.css({"top":figureCul});

                }
            }

            //.mMemo.typeOrder 일때 툴팁 fixed
            figurelLen = $(this).parents('.mMemo.typeOrder').length;
            if(figurelLen > 0) {

                propTop = propTop - $(document).scrollTop();
                findTarget.css({"position":"fixed", "top":propTop});

            }

            $('.mTooltip .icon').each(function(idx) {

                var findScroll = $(this).hasClass('eTipScroll');
                if(!findScroll) {

                    $(this).parent().removeClass('show');
                    $(this).parent().find('.tooltip').hide();

                }

            });

        }

        $('.section').css({'zIndex':'', 'position':''});

        findShow = parentEl.hasClass('show');

        if( findShow ) {

            parentEl.removeClass('show');
            $('#tooltipSCrollView').remove();

        } else {


            if( asynceContentCheck ) {

                createOutSideTooltip();

            } else {

                asyncContentTimer = setInterval(function() {

                    if( findThis.siblings('.tooltip').find('>.content').html() != '' ) {

                        tooltip = findThis.siblings('.tooltip').clone();
                        createOutSideTooltip();
                        clearTimeout(asyncContentTimer);

                    }

                    if(asyncContentTimerCount > asyncContentTimerLimit) clearTimeout(asyncContentTimer);
                    asyncContentTimerCount++;

                }, 300);

            }

        }


    });

    if($(".btnSliding").length > 0){
        $(".btnSliding").click(function(e){
            toolTipScrollCheck();
        });
    }

    function toolTipScrollCheck(){
        var findScrollTip = $('#tooltipSCrollView');
        if(findScrollTip.length > 0){
            $('#tooltipSCrollView').remove();
            $('.section').css({'zIndex':'', 'position':''});
            $('.mTooltip').removeClass('show');
        }
    }

    $('body').delegate('.mTooltip .eClose', 'click', function(e){
        // 동적
        if($(this).parents('.mTooltip:first').attr('virtual')){
            $('#tooltipSCrollView').remove();
        } else {
            var findSection = $(this).parents('.section:first');
            var findTarget = $(this).parents('.tooltip:first');
            findTarget.hide();
            findSection.css({'zIndex':0, 'position':'static'});
        }
        $('.mTooltip').removeClass('show');
        e.preventDefault();
    });

    // mTip
    $('body').delegate('.mTip .eTip', 'click', function(e){
        var findTarget = $(this).siblings('.tip');
        var findParent = $(this).parent('.mTip');

        if(findParent.hasClass('show')){
            findTarget.hide();
            findParent.removeClass('show');

            e.preventDefault();
        } else {
            findTarget.slideDown( "fast" );
            findParent.addClass('show');

            e.preventDefault();
        }
    });
    $('body').delegate('.mTip .eClose', 'click', function(e){
        var findTarget = $(this).parents('.mTip .tip');
        var findParent = $(this).parents('.mTip');

        findTarget.hide();
        findParent.removeClass('show');

        e.preventDefault();
    });

    // mOpen
    $('body').delegate('.mOpen .eOpenClick', 'click', function(e){
        var findTarget = $($(this).attr('href'));
        findTarget.toggle();
        e.preventDefault();
    });
    $('body').delegate('.mOpen .eOpenOver', 'mouseenter', function(){
        var findTarget = $(this).siblings('.open');
        var flag = $(this).attr('find');
        findTarget.show();
        if(flag){
            $(this).parents('.'+ flag +':first').css({'zIndex':1});
        }
    });
    $('body').delegate('.mOpen', 'mouseleave', function(){
        var findClose = $(this).find('.eClose');
        if(findClose.length <= 0){
            var findTarget = $(this).find('.open');
            var flag = $(this).find('.eOpenOver').attr('find');
            findTarget.hide();
            if(flag){
                var findShow = $(this).parent().find('.mTooltip').hasClass('show');
                if(!findShow){
                    $(this).parents('.'+ flag +':first').css({'zIndex':0});
                }else{
                    $(this).parents('.'+ flag +':first').css({'zIndex':2});
                }
            }
        }
    });
    $('body').delegate('.mOpen .eClose', 'click', function(e){
        $(this).parents('.open:first').hide();
        e.preventDefault();
    });

    //vi_VN table setting span title insert
    var strLang = $('html').attr("lang");
    if(strLang == "vi"){
        var findViTarget = $('.mCtrl .gSetting .btnSetting');
        findViTarget.find('span').attr("title","Cài đặt");
    }

    // mToggle
    $('.mToggle .eToggle').click(function(e){
        var findParent = $(this).parents('.mToggle:first');
        // typeTop
        if(findParent.hasClass('typeHeader')){
            var findTarget = findParent.next('.toggleArea');
        }
        // typeBtm
        if(findParent.hasClass('typeFooter')){
            var findTarget = findParent.prev('.toggleArea');
        }

        var strOpen = "열기",
            strClose = "닫기",
            propData = $(this).attr('data-toggle');
        if(propData){
            var obj = $.parseJSON(propData);
            strOpen = obj.open;
            strClose = obj.close;
        }

        if(findTarget.css('display') == "none"){
            $(this).find('em').text(strClose);
            $(this).addClass('selected');
            findTarget.show();
        } else {
            $(this).find('em').text(strOpen);
            $(this).removeClass('selected');
            findTarget.hide();
        }
        e.preventDefault();
    });

    // mTerm typeToggle
    $('.mTerm.typeToggle .eToggle').click(function(e){
        var findThis = $(this);
        var findParent = findThis.parents('.typeToggle');
        var findTarget = findParent.find('.gToggle');
        if(findTarget.css('display') == "none"){
            findThis.addClass('selected');
            findTarget.show();
        } else {
            findThis.removeClass('selected');
            findTarget.hide();
        }
        e.preventDefault();
    });

    // mToggleBar
    $('.mToggleBar.eToggle').click(function(e){
        var findThis = $(this);
        var findTarget = findThis.next('.toggleArea');
        var findText = $(this).find('em');
        if(findTarget.css('display') == "none"){
            findThis.addClass('selected');
            findTarget.show();
            findText.text('접기');
        } else {
            findThis.removeClass('selected');
            findTarget.hide();
            findText.text('펼치기');
        }
        e.preventDefault();
    });
    $('.mToggleBar.eToggle .gLabel').click(function(e){
        e.stopPropagation();
    });

    // checkbox, radio
    var regexpSelectedClassName = /(^|\s)eSelected($|\s)/;
    function updateCheckedDesign(target) {
        var cur = target;
        for (var i = 0; i < 4; i++) {
            cur = cur.parentNode;
            if ( ! cur || ! cur.ownerDocument) {
                break;
            }
            if (cur.tagName.toUpperCase() === 'LABEL') {
                var className = cur.className;
                var hasClass = regexpSelectedClassName.test(className);
                if (target.checked && hasClass === false) {
                    cur.className = $.trim(className) + ' eSelected';
                } else if ( ! target.checked && hasClass === true) {
                    cur.className = $.trim(className.replace(regexpSelectedClassName, ' '));
                }
                break;
            }
        }
    }
    $('body')
        // checked 상태에 따라 해당 요소의 design을 자동으로 업데이트해줍니다.
        .delegate('input[type=checkbox],input[type=radio]', 'updateDesign', function() {
                var target = this;
                window.setTimeout(function(){updateCheckedDesign(target);});
         })
        // checked 상태에 따라 label에 eSelected class를 붙여줍니다.
        .delegate('input[type=checkbox],input[type=radio]', 'click', function() {
                var target = this;
                window.setTimeout(function(){updateCheckedDesign(target);});
            // radio를 클릭한 경우에 대한 처리
            if ($(this).is('input[type=radio]') === true) {
                $('input[type=radio][name="' + this.name + '"]').each(function() {
                    var target = this;
                    window.setTimeout(function(){updateCheckedDesign(target);});
                 });
             }
         });
        // 페이지 로드시 checked 상태에 따라 design 요소 업데이트
        $('input[type=checkbox],input[type=radio]').each(function() {
                updateCheckedDesign(this);
        });

    // placeholder
    $.fn.extend({
        placeholder : function() {
            if (hasPlaceholderSupport() === true) {
                return this;
            }
            return this.each(function(){
                var findThis = $(this);
                var sPlaceholder = findThis.attr('placeholder');
                if ( ! sPlaceholder) {
                    return;
                }
                findThis.wrap('<label class="ePlaceholder" />');
                var sDisplayPlaceHolder = $(this).val() ? ' style="display:none;"' : '';
                findThis.before('<span' + sDisplayPlaceHolder + '>' + sPlaceholder + '</span>');
                this.onpropertychange = function(e){
                    e = event || e;
                    if (e.propertyName == 'value') {
                        $(this).trigger('focusout');
                    }
                };
            });
        }
    });
    $(':input[placeholder]').placeholder();
    $('body').delegate('.ePlaceholder span', 'click', function(){
        $(this).hide();
    });
    $('body').delegate('.ePlaceholder :input', 'focusin', function(){
        $(this).prev('span').hide();
    });
    $('body').delegate('.ePlaceholder :input', 'focusout', function(){
        if (this.value) {
            $(this).prev('span').hide();
        } else {
            $(this).prev('span').show();
        }
    });
    function hasPlaceholderSupport() {
        if ('placeholder' in document.createElement('input')) {
            return true;
        } else {
            return false;
        }
    }

    // table : rowChk
    $('body').delegate('.eChkColor .rowChk', 'click', function(){
        var findChkTarget = $(this).parent('td').parent('tr');
        var findRowspan = parseInt(findChkTarget.children().attr('rowspan'));
        if(findRowspan > 1){
            var figureNum = $(this).parents('tbody tr').index();
            chkTrHover($(this), figureNum, findRowspan);
        }else{
             if($(this).is(':checked')){
                $(this).parents('tr:first').addClass('selected');
            } else {
                $(this).parents('tr:first').removeClass('selected');
            }
        }
    });
    //chk rowspan hover color
    function chkTrHover(findTarget, figureNum, findRowspan){
        var findTisTarget = $('.eChkColor').find('tbody tr:not(tbody table tr)');
        for(var i = figureNum; i< (figureNum + findRowspan) ; i++){
            if(findTarget.is(':checked')){
                findTisTarget.eq(i).addClass('selected');
            }else{
                findTisTarget.eq(i).removeClass('selected');
            }
        }
    }


    // table : allCheck
    $('body').delegate('.mBoard .allChk', 'click', function(){
        var findThis = $(this),
            findTable = $(this).parents('table:first'),
            findMboard = $(this).parents('.mBoard:first'),
            findColspan;

        if(findTable.hasClass('eChkBody')){
            var findRowChk = findTable.find('.rowChk').not(':disabled');
            if(findThis.is(':checked')){
                findRowChk.attr('checked', true);
            } else {
                findRowChk.attr('checked', false);
            }
        } else {
            if(findMboard.hasClass('typeHead')){
                var findNext = findMboard.next();
                var findRowChk = findNext.find('.rowChk').not(':disabled');
            } else {
                var findRowChk = findTable.find('.rowChk').not(':disabled');
            }
            if(findThis.is(':checked')){
                findRowChk.each(function(){
                    $(this).attr('checked', true);
                    if($(this).parents('table:first').hasClass('eChkColor')){
                        $(this).parents('tr:first').addClass('selected');
                    }
                });
            } else {
                findRowChk.each(function(){
                    $(this).attr('checked', false);
                    if($(this).parents('table:first').hasClass('eChkColor')){
                        $(this).parents('tr:first').removeClass('selected');
                    }
                });
            }

            //allchk colspan selected
            if(findMboard.hasClass('typeHead')){
                findColspan = findMboard.next();
            }else{
                findColspan = findTable;
            }
            var findNoRowChk = findColspan.find('tbody tr:not(tbody table tr)').each(function(i){
                if(!$(this).children().children().hasClass('rowChk')){
                    if(findThis.is(':checked')){
                        findColspan.find('tbody tr:not(tbody table tr)').eq(i).addClass('selected');
                    }else{
                        findColspan.find('tbody tr:not(tbody table tr)').eq(i).removeClass('selected');
                    }
                }
            });
        }
    });

    // Table : tr hover
    $('body').delegate('.eChkColor > tbody:not(.empty) > tr', 'mouseover', function(){
        tableTrHover($(this), "over");
    });
    $('body').delegate('.eChkColor > tbody:not(.empty) > tr', 'mouseout', function(){
        tableTrHover($(this), "out");
    });

    function tableTrHover(_this, str){
        var findTarget = _this.parents('.eChkColor');
        var figurei = 0;
        var findRowspan = 0;
        var figureindex = _this.index();
        findTarget = findTarget.find('tbody tr:not(tbody table)');

        var findNoRowspan = findTarget.each(function(i){
            var figureNum = parseInt($(this).children().attr('rowspan'));
            if(!figureNum){
                figureNum = 1;
            }
            if(figureNum >= 1){
                figurei = i;
                findRowspan = figureNum;
                if(figureindex >= figurei && figureindex < (figurei + findRowspan)){
                    for(var j = figurei; j< (figurei + findRowspan) ; j++){
                        if(str == "over"){
                            findTarget.eq(j).addClass('hover');
                        }else{
                            findTarget.eq(j).removeClass('hover');
                        }
                    }
                }else{
                    if(str == "over"){
                        findTarget.eq(figureindex).addClass('hover');
                    }else{
                        findTarget.eq(figureindex).removeClass('hover');
                    }
                }
            }
        });
    }

    // inlay
    $('body').delegate('.eInlay a', 'click', function(e){
        var flag = $(this).parents('.eInlay').hasClass('multi');
        if($(this).hasClass('active')){
            $(this).removeClass('active').parents('tr:first').next('.gInlay').removeClass('enabled');
        } else {
            if(flag){
                $(this).addClass('active').parents('tr:first').next('.gInlay').addClass('enabled');
            } else {
                $(this).addClass('active').parents('tr:first').siblings('tr:not(.gInlay)').find('a').removeClass('active');
                $(this).parents('tr:first').next('.gInlay').addClass('enabled').siblings('.gInlay').removeClass('enabled');
            }
        }
        e.preventDefault();
    });

    // (SearchSelect) Search toggle : option
    $('body').delegate('.eOptionToggle', 'click', function(e){
        var findThis = $(this),
            findParent = findThis.parents('.mSearchSelect:first'),
            findList = findParent.find('.list'),
            propFix = findThis.attr('fix'),
            strLang = $('html').attr("lang"),
            strOpen = "전체 펼치기",
            strClose = "전체 줄이기";

        if(strLang == "vi"){
            strOpen = "Mở rộng tất cả",
            strClose = "Thu nhỏ toàn bộ";
        }else if(strLang == "en"){
			strOpen = "Show all",
            strClose = "Hide";
		}

        // scroll 고정 여부
        if(propFix == undefined){
            var propScrollHeight = 'auto';
        } else {
            var propScrollHeight = propFix + 'px';
        }
        if(findThis.hasClass('selected')){
            findThis.removeClass('selected');
            findThis.find('span').text(strOpen);
            findList.removeAttr("style");
        } else {
            findThis.addClass('selected');
            findThis.find('span').text(strClose);
            findList.css({'height':propScrollHeight});
        }
        e.preventDefault();
    });

    //(optionArea) Search toggle : order
    $('body').delegate('.eOrdToogle', 'click', function(e){
        var findThis = $(this),
            findTarget = findThis.parents('.mOptionToogle:first').prev('.gDivision'),
            strText = findThis.text(),
            strLang = $('html').attr("lang");
            strOpen = "열기",
            strClose = "닫기";

        if(findTarget.css('display') == 'block'){
            findTarget.hide();
            findThis.parent('span').removeClass('selected');

            var figureOpen = strText.indexOf(strClose);
            if(figureOpen > 0){
                var strTxt = returnTxt(strText, strClose)+" "+strOpen;
                findThis.text(strTxt);
            }else{
                if(strLang == "ja"){
                    findThis.text('さらに絞り込む');
                }else if(strLang == "vi"){
                    findThis.text('Mở tìm kiếm nâng cao');
				}else if(strLang == "en"){
					findThis.text('Advanced search');
                }else{
                    findThis.text('상세검색 열기');
                }
            }
        } else {
            findTarget.show();
            findThis.parent('span').addClass('selected');

            var figureClose = strText.indexOf(strOpen);
            if(figureClose > 0){
                var strTxt = returnTxt(strText, strOpen)+" "+strClose;
                findThis.text(strTxt);
            }else{
                if(strLang == "ja"){
                    findThis.text('閉じる');
                }else if(strLang == "vi"){
                    findThis.text('Đóng tìm kiếm nâng cao');
				}else if(strLang == "en"){
					findThis.text('Close');
                }else{
                    findThis.text('상세검색 닫기');
                }
            }
        }
        e.preventDefault();
    });

    function returnTxt(strText, str){
        var arrClose = strText.split(str),
            strTxt = $.trim(arrClose[0]);

        return strTxt;
    }

    // eSelect
    $('body').delegate('.eSelect li', 'mouseenter', function(){
        $(this).addClass('selected').siblings('li').removeClass('selected');
    });
    $('body').delegate('.eSelect', 'mouseleave', function(){
        $('li', this).removeClass('selected');
    });

    // eClick
    $('body').delegate('.mSelect.eClick li', 'click', function(){
        $(this).addClass('selected').siblings('li').removeClass('selected');
    });

    // 이미지 미리보기
    $.imgPreview = function(parm){
        var speed = parm.speed;
        var wrap = parm.wrap;
        var index = parm.index;
        var detailView = parm.detailView;
        var thumbIndex = 0;
        var detail = $('.detail', wrap);
        var thumb = $('.thumbnail', wrap);
        var thumbWidth = parseInt(thumb.outerWidth());
        var thumbLiWidth = $('li:first', thumb).outerWidth();
        var length = thumb.find('li').length;
        var thumbList = $('> ul', thumb);
        var space = parseInt(thumb.find(' > ul > li:first-child').css('paddingRight'));
        var modeArea = wrap.find('.mode');
        var view = parm.view;
        var thumbViewItem = Math.floor(thumbWidth / (thumbLiWidth - space));
        var thumbLimit =  Math.ceil(length / thumbViewItem) - 1;
        // 섬네일 리스트 넓이 설정
        var thumbListWidth = parseInt(thumbLiWidth) * length;
        thumbList.css({'width': + thumbListWidth + 'px' });
        // 섬네일 선택
        $('li span ', thumb).click(function(e){
            $(this).parent().addClass('selected').siblings().removeClass('selected');
            index = $(this).parent().index() + 1;
            if(detail.hasClass('typeGrid')){
                multiView(index);
            } else {
                singleView(index);
            }
            return index;
            e.preventDefault();
        });
        // 섬네일 이전
       wrap.find('.prev').unbind('click').click(function(e){
            if(thumbIndex > 0){
                thumbIndex --;
                thumbSlide(thumbIndex, space);
            }
            e.preventDefault();
        });
        // 섬네일 다음
       wrap.find('.next').unbind('click').click(function(e){
            if(thumbIndex < thumbLimit){
                thumbIndex ++;
                thumbSlide(thumbIndex, space);
            }
            e.preventDefault();
        });
        // 섬네일 롤링
        function thumbSlide(rollingIndex, space, ani){
            var left = rollingIndex * 100;
            var space = space * rollingIndex;
            var nowPage = rollingIndex + 1;
            var nextItemIndex = ( ( nowPage * thumbViewItem ) - thumbViewItem );
            var maxPage = nowPage * thumbViewItem;
            if(ani == false){
                thumbList.css({'left' : '-' + left + '%', 'marginLeft' : '-' + space + 'px'});
            } else {
                thumbList.stop().css({'marginLeft' : '-' + space + 'px'}).animate({'left' : '-' + left + '%'}, speed, function(){
                    var nextItem = thumb.find('li:eq('+ nextItemIndex +') span');
                    nextItem.trigger('click');
                });
            }
            if(thumbIndex == 0){
                $('.prev', wrap).addClass('disabled');
            } else {
                $('.prev', wrap).removeClass('disabled');
            }
            if(thumbIndex == thumbLimit){
                $('.next', wrap).addClass('disabled');
            } else {
                $('.next', wrap).removeClass('disabled');
            }
        }
        // 상세이미지 한개 보기
        function singleView(){
            $('.single', wrap).addClass('selected').siblings().removeClass('selected');
            detail.removeClass('typeGrid');
            $('li:eq('+ ( index - 1 ) +')', detail).show().siblings().hide();
            $('li:eq('+ ( index - 1 ) +')', thumb).addClass('selected');
        }
        // 상세이미지 여러개 보기
        function multiView(){
            $('.multi', wrap).addClass('selected').siblings().removeClass('selected');
            detail.addClass('typeGrid');
            detail.find('li').hide();
            for(var i=0; i<detailView; i++){
                detail.find('li:eq('+ ( ( index - 1 ) + i )  +')').show();
            }
        }
        // 한개(single) or 여러개(multi)
        $('.mode a', wrap).click(function(e){
            var flag = $(this).attr('class');
            switch(flag){
                case "single":
                    detail.removeClass('typeGrid');
                    singleView()
                break;
                case "multi":
                    detail.addClass('typeGrid');
                    multiView();
                break;
            }
            e.preventDefault();
        });
        // 첨부이미지 개수
        $('.imgCount', wrap).text(length);
        // 기본설정
        if(!view){
            view = 'single';
        }
        switch(view){
            case "single":
                singleView();
            break;
            case "multi":
                multiView();
            break;
            default:
                detail.removeClass('typeGrid');
                singleView();
            break;
        }
        if(length > 0){
            var nowSelect = $('li:eq('+ index +') span', thumb);
            nowSelect.trigger('click');
            var nowLeft = nowSelect.position().left;
            thumbIndex = Math.floor(nowLeft / thumbWidth);
            thumbSlide(thumbIndex, space, false);
        }
    }

    //mDropDown
    $('.mDropDown .eDropDown').click(function(e){
        if(!$(this).hasClass('disabled')){
            var DropList = $(this).next('.list');
            if(DropList.css('display') == "none"){
                $(this).parent().addClass('show');
            } else {
                $(this).parent().removeClass('show');
            }
        }
    });

    //mFixNav
    $(function(){
        var findBody = $("body").find('.eFixNav');
        var findClass = findBody.hasClass('eFixNav');

        //eFixNav 없을 경우 예외처리
        if(findClass){
            var findFixNav = $('.eFixNav');
            var propFixNavHeight = $('.eFixNav').outerHeight();
            var propFixNavTop = findFixNav.position().top;
            var findFixNavLength = $('.eFixNav li').length;

            var fixIndex = 0;
            $(window).scroll(function(){
                if(findFixNavLength != 1){
                    if($(document).scrollTop() >= propFixNavTop){
                        findFixNav.addClass('fixed');
                        if($('#cloneFix').length == 0){
                            findFixNav.before('<div id="cloneFix" style="height:'+ propFixNavHeight +'px"></div>');
                        }
                    } else {
                        findFixNav.removeClass('fixed');
                        $('#cloneFix').remove();
                    }
                }
            });

            $('.eFixNav a').click(function(e){
                $(this).parent().addClass('selected').siblings().removeClass('selected');
                if($($(this).attr('href')).length == 0){
                    return false
                }
                if(findFixNav.hasClass('fixed')){
                    var propTargetTop = $($(this).attr('href')).offset().top - propFixNavHeight;
                } else {
                    var propTargetTop = $($(this).attr('href')).offset().top - propFixNavHeight * 2;
                }
                // 에니메이션 사용함
                $('html,body').stop().animate({scrollTop: propTargetTop}, 300);
                // 에니메이션 사용안함
                e.preventDefault();
            });
        }
    });

    //thumbSelectArea toggle
    $('.thumbSelectArea .eToggle').click(function(){
        var findTarget = $(this).next('.box');
        var findParent = findTarget.parent('.thumbSelectArea');
        if(findParent.hasClass('show')){
            findTarget.hide();
            findParent.removeClass('show');
        } else {
            findTarget.slideDown('fast');
            findParent.addClass('show');
        }
    });

    //mThumbSelect
    $('.mThumbSelect li').children().click(function(){
       var findSibling = $(this).parent().siblings();
       $(this).addClass('eSelected');
       findSibling.children().removeClass('eSelected');
    });

    //mAccordion typeButton toggle
    $('.mAccordion.typeButton .eToggle').click(function(){
        var findTarget = $(this).next('.box');
        var findParent = findTarget.parent('.mAccordion.typeButton');
        if(findParent.hasClass('show')){
            findTarget.hide();
            findParent.removeClass('show');
        } else {
            findTarget.slideDown('fast');
            findParent.addClass('show');
        }
    });

    //mDropSelect
    var flagDropMouse = false;
    $('.mDropSelect.selected .value .fChk').focus();
    $('body').delegate('.mDropSelect', 'mouseover', function(){
        flagDropMouse = true;
    });

    $('body').delegate('.mDropSelect', 'mouseout', function(){
        flagDropMouse = false;
    });

    $('body').delegate('.mDropSelect .btnCover.eDropSelect', 'click', function(){
        var propClass = $(this).parents('.mDropSelect').hasClass('selected');
        if(!propClass){
            $(this).parents('.mDropSelect').focus();

            var findFooter = $('#footer'),
                propFooterHeight = 0;
            if(findFooter.length >= 1){
                propFooterHeight = findFooter.outerHeight();
            }
            var propwindowHeight = $(window).height()-propFooterHeight,
                targetHeight = $(this).parents('.mDropSelect').find('.result').outerHeight(),
                propscrollTop = $(window).scrollTop(),
                offsetTop = $(this).offset().top,
                posHeight = (offsetTop-propscrollTop)+targetHeight+$(this).height();

            if(propwindowHeight < posHeight){
                var propMarginTop = (targetHeight+$(this).height()+10),
                    propHeight = (offsetTop-propscrollTop) - targetHeight,
                    propHeadHeight = 0;
                if($('#header').length >= 1){
                    propHeadHeight = $('#header').height();
                }
                if(propHeight > propHeadHeight){
                    $(this).parents('.mDropSelect').addClass('posTop');
                    $(this).parents('.mDropSelect').find('.result').css({"height":""});
                }else{
                    var figureUpHeight = offsetTop-propscrollTop,
                        figureDownHeight = $(window).height()-(figureUpHeight+$(this).height()),
                        figureFixHeight = 0;
                    if(figureUpHeight >= figureDownHeight){
                        $(this).parents('.mDropSelect').addClass('posTop');
                        figureFixHeight = figureUpHeight;
                    }else{
                        $(this).parents('.mDropSelect').removeClass('posTop');
                        figureFixHeight = figureDownHeight-propFooterHeight;
                    }
                    $(this).parents('.mDropSelect').find('.result').css({"height":figureFixHeight});
                }
            }else{
                $(this).parents('.mDropSelect').removeClass('posTop');
                $(this).parents('.mDropSelect').find('.result').css({"height":""});
            }
        }else{
            $(this).parents('.mDropSelect').find('.result').css({"height":""});
        }
        $(this).parents('.mDropSelect').toggleClass('selected').siblings().removeClass('selected');
    });

    $('body').delegate('.mDropSelect .result .all .gLabel', 'click', function(){
        var flagChk = $(this).find('input:checkbox').is(":checked");
        if(flagChk){
            $(this).parents('.list').find('.gLabel').addClass('eSelected');
            $(this).parents('.list').find('.gLabel .fChk').attr('checked', true);
        }else{
            $(this).parents('.list').find('.gLabel').removeClass('eSelected');
            $(this).parents('.list').find('.gLabel .fChk').attr('checked', false);
        }
    });

    $('body').delegate('.mDropSelect .result .gLabel', 'click', function(){
        var figureLen = $(this).parents('.list').find('li').length-1,
            figureAdd = 0,
            flagThis = $(this).parents('li').hasClass('all');

        if(!flagThis){
            $(this).parents('.list').find('li').each(function(i){
                var flag = $(this).hasClass('all');
                if(!flag){
                    var flagLabel = $(this).find('input:checkbox').is(":checked");
                    if(flagLabel){
                        figureAdd ++;
                        if(figureAdd == figureLen){
                            $(this).parents('.list').find('.all .fChk').attr('checked', true);
                        }else{
                            $(this).parents('.list').find('.all .fChk').attr('checked', false);
                        }
                    }
                }
            });
        }
    });

    $('body').delegate('.mDropSelect .result li', 'click', function(){
        $(this).find('.fChk').focus();
    });

    $('.mDropSelect').focusout(function() {
        if(!flagDropMouse){
            $('.mDropSelect').removeClass('selected');
            $(this).find('.result').css({"height":""});
        }
    });

    //inputFormArea, mInputForm
    $(function(){
        var flagSelect = true;//,
            //findTarget = $('.mInputForm.eToggle');

        //findTarget 자식 노드 fText 포커스 인일때 부모 노드 eToggle class selected 추가
        $('body').delegate('.mInputForm.eToggle .fText', 'focusin', function(e){
            $(this).parents('.eToggle').addClass('selected');
        });

        //findTarget 자식 노드 fText 포커스 아웃일때 flagSelect true 일 경우 부모 노드 eToggle class selected 삭제
        $('body').delegate('.mInputForm.eToggle .fText', 'focusout', function(e){
            if(flagSelect){
                $(this).parents('.eToggle').removeClass('selected');
            }
        });

        //findTarget 자식 노드 list 클릭시 flagSelect true 일 경우 부모 노드 eToggle class selected 삭제
        $('body').delegate('.mInputForm.eToggle .list', 'click', function(e){
            if(flagSelect){
                $(this).parents('.eToggle').removeClass('selected');
            }
        });

        //findTarget 자식 노드 label mousedown 일경우 flag false 일 경우 focus class 추가, flag true 일 경우 focus class 삭제
        $('body').delegate('.mInputForm.eToggle label', 'mousedown', function(e){
            var flag = $(this).find('.fChk').is(":checked");
            if(!flag){
                $(this).parents('li').addClass('focus');
            }else{
                $(this).parents('li').removeClass('focus');
            }
        });

        //total, result, eLayerClick 클릭할경우 fText 포커스 됨.
        $('body').delegate('.mInputForm.eToggle .total, .result, .eLayerClick', 'click', function(e){
           $(this).parents('.eToggle').find('.fText').focus();
        });

        //total, result, eLayerClick mouseover 일때 flagSelect false.
        $('body').delegate('.mInputForm.eToggle .total, .result, .eLayerClick', 'mouseover', function(e){
            flagSelect = false;
        });

        //total, result, eLayerClick mouseout 일때 flagSelect false.
        $('body').delegate('.mInputForm.eToggle .total, .result, .eLayerClick', 'mouseout', function(e){
            flagSelect = true;
        });

        //inputFormArea mousedown 일때 eToggle display none일 경우 selected 추가, display block일 경우 selected 삭제.
        $('body').delegate('.inputFormArea > a', 'mousedown', function(e){
            if($(this).next('.eToggle').css('display') == "none"){
                $(this).next('.eToggle').addClass('selected');
            } else {
                $(this).next('.eToggle').removeClass('selected');
            }
        });

        //inputFormArea mouseup 일때 eToggle display none일 경우 input blur(포커스 삭제), display block일 경우 input focus().
        $('body').delegate('.inputFormArea > a', 'mouseup', function(e){
            if($(this).next('.eToggle').css('display') == "none"){
                $(this).next('.eToggle').find('input').blur();
            } else {
                $(this).next('.eToggle').find('input').focus();
            }
        });

        //findTarget 자식 노드 select mousedown 일때 flagSelect false
        $('body').delegate('.mInputForm.eToggle select', 'mousedown', function(e){
            flagSelect = false;
        });

        //findTarget 자식 노드 select change 일때 flagSelect true 및 부모 노드 eToggle class input focus.
        $('body').delegate('.mInputForm.eToggle select', 'change', function(e){
            flagSelect = true;
            $(this).parents('.eToggle').find('input').focus();
        });

        //findTarget 자식 노드 select focusout 일때 부모 노트 eToggle class selected class 삭제 및 flagSelect true.
        $('body').delegate('.mInputForm.eToggle select', 'focusout', function(e){
            $(this).parents('.eToggle').removeClass('selected');
            flagSelect = true;
        });
    });
}

addSuioLoadEvent(SUIO);