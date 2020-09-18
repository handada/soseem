//window popup script
function winPop(url) {
    window.open(url, "popup", "width=300,height=300,left=10,top=10,resizable=no,scrollbars=no");
}
/**
 * document.location.href split
 * return array Param
 */
function getQueryString(sKey)
{
    var sQueryString = document.location.search.substring(1);
    var aParam       = {};

    if (sQueryString) {
        var aFields = sQueryString.split("&");
        var aField  = [];
        for (var i=0; i<aFields.length; i++) {
            aField = aFields[i].split('=');
            aParam[aField[0]] = aField[1];
        }
    }

    aParam.page = aParam.page ? aParam.page : 1;
    return sKey ? aParam[sKey] : aParam;
};

$(document).ready(function(){
    // tab
    $.eTab = function(ul){
        $(ul).find('a').click(function(){
            var _li = $(this).parent('li').addClass('selected').siblings().removeClass('selected'),
                _target = $(this).attr('href'),
                _siblings = '.' + $(_target).attr('class');
            $(_target).show().siblings(_siblings).hide();
            return false
        });
    }
    if ( window.call_eTab ) {
        call_eTab();
    };
});
/**
 * 팝업창에 리사이즈 관련
 */

function setResizePopup() {
    var iWrapWidth    = $('#popup').width();
    var iWrapHeight   = $('#popup').height();

    var iWindowWidth  = $(window).width();
    var iWindowHeight = $(window).height();

    window.resizeBy(iWrapWidth - iWindowWidth, iWrapHeight - iWindowHeight);
}
setResizePopup();

// 팝업 페이지 로드가 완료된 후에 리사이징 함수를 다시 호출
$( window ).load(function() {
    setResizePopup();
});
