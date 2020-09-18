/**
 * 사업자 인증
 *
 * @package app/Member
 * @subpackage Resource
 * @author 이장규
 * @since 2011. 10. 13.
 * @version 1.0
 *
 */
var CompanyCheck = new function()
{
    /**
     * 사업자 인증 체크 main method
     * @return bool (성공, 실패)
     */
    this.checkDupl = function()
    {
        if ( action() == false) return false;
        
        AuthSSLManager.weave({
            'auth_mode': 'encrypt'
            , 'aEleId': ['joinForm::bname', 'joinForm::bssn1', 'joinForm::bssn2']
            , 'auth_callbackName': 'CompanyCheck.process'
        });
        
    };
    
    /**
     * 인증 process
     */
    this.process = function(sOutput)
    {
        
        $.ajax({
            url: '/exec/front/Member/CheckCompany',
            cache: false,
            type: 'POST',
            dataType: 'json',
            data: '&encrypted_str='+encodeURIComponent(sOutput),
            timeout: 3000,
            success: function(response){
                alert(response['msg']);
                if (response['passed'] == true) {
                    $('#nameContents').html($('#bname').val());//법인명
                    $('#ssnContents').html($('#bssn1').val() + '-*******');//법인번호
                    $('#companyName').html($('#bname').val());
                    $('#realNameEncrypt').val(response['registNameAuth']);
                }
            }
        });
    };
    
    /**
     * validate
     * @return bool validate 결과
     */
    var action = function()
    {
        if ( $.trim($('#bname').val()).length < 1 ) {
            alert(__('법인명을 입력하세요.'));
            $('#bname').focus();
            return false;
        }
        
        if ($.trim($('#bssn1').val()).length < 1) {
            alert(__('법인번호를 입력하세요.'));
            $('#bssn1').focus();
            return false;
        }
        
        if ($.trim($('#bssn2').val()).length < 1) {
            alert(__('법인번호를 입력하세요.'));
            $('#bssn2').focus();
            return false;
        }
        
        return true;

    };
    

    
    
    
};

/**
 * 외국인 번호 체크
 *
 * @package app/Member
 * @subpackage Resource
 * @author 백충덕, 이장규
 * @since 2011. 10. 17.
 * @version 1.0
 *
 */

/**
 * 외국인 번호 체크
 */
function checkForeignerNumber()
{

    var foreignerType = $('input[name=foreigner_type]:checked').val();
    var foreignerName = $('#foreigner_name').val();
    var foreignerSsn  = $('#foreigner_ssn').val();

    if ($.trim(foreignerName).length < 1) {
        alert(__('이름을 입력해 주세요.'));
        $('#foreigner_name').focus();
        return false;
    }

    if ($.trim(foreignerSsn).length < 1) {
        var sType = '';
        if (foreignerType == 'f') sType = __('외국인 등록번호');
        else if (foreignerType == 'p') sType = __('여권번호');
        else if (foreignerType == 'd') sType = __('국제운전면허증번호');

        alert(sprintf(__('%s를 입력해 주세요.'), sType));
        $('#foreigner_ssn').focus();
        return false;
    }
    
    if ($('#f_identification_check0').length > 0) {
        if ($('#f_identification_check0')[0].checked === false) {
            alert(__('고유식별정보 처리에 동의해 주세요.'));
            $('#f_identification_check0').focus();
            return false;
        }
    }    

    
    AuthSSLManager.weave({
        'auth_mode': 'encrypt'
        , 'aEleId': ['joinForm::foreigner_name', 'joinForm::foreigner_type', 'joinForm::foreigner_ssn']
        , 'auth_callbackName': 'callbackForeignerCheck'
    });
}

/**
 * 외국인 번호 체크 callback
 * */
function callbackForeignerCheck(sOutput)
{
    $.ajax({
        url: '/exec/front/Member/CheckForeigner',
        cache: false,
        type: 'POST',
        dataType: 'json',
        data: '&encrypted_str='+encodeURIComponent(sOutput),
        timeout: 3000,
        success: function(response){
            alert(response['msg']);
            if (response['passed'] == true) {
                $('#realNameEncrypt').val(response['registNameAuth']);
                $('#nameContents').html($('#foreigner_name').val());

                var sTmpFssn = $('#foreigner_ssn').val();
                $('#ssnContents').html('***' + sTmpFssn.slice(-4));
            }
        }
    });
}

/**
 * 닉네임 중복 체크
 */
function checkNick()
{
    var sNickName = $.trim($('#nick_name').val());
    var bIsLength = checkLength(sNickName);
    
    if (bIsLength['passed'] == false) {
        $('#nickMsg').html(bIsLength['msg']);
        return false;
    }
    
    $.ajax({
        url: '/exec/front/Member/CheckNick',
        cache: false,
        type: 'POST',
        dataType: 'json',
        data: '&nickName=' + sNickName,
        timeout: 1000,   
        success: function(response){
            
            $('#nickMsg').html(response['msg']);
            
            if (response['passed'] == true) { 
                $('#nick_name_confirm').val('T');
            } else {
                $('#nick_name_confirm').val('F');
            }
            
        }
    });
}

/**
 * 닉네임 글자수 체크
 * @param sNickName 닉네임
 * @returns {Boolean}
 */
function checkLength(sNickName)
{
        
    if ($('#nick_name_flag').val() != 'T') return {'passed' : true};//닉네임 사용 안함    
    
    var iBtye = 0;
    
    for (var i = 0; i < sNickName.length; i++) {
        
        if (sNickName.charCodeAt(i) > 128) {
            iBtye += 2;
        } else {
            iBtye += 1;
        }
    }
    
    if (iBtye < 4)
        return {'passed' : false, 'msg' : __('한글 2자 이상/영문 대소문자 4자/숫자 혼용 사용 가능합니다.')};        


    if (iBtye > 20)        
        return {'passed' : false, 'msg' : __('한글 10자 이하/영문 대소문자 20자/숫자 혼용 사용 가능합니다.')};

    return {'passed' : true};
}

/**
 * 아이디 중복 체크
 */
function checkDuplId()
{
    if ($('#etc_subparam_member_id').length > 0) {
        var sMemberId = $('#etc_subparam_member_id').val();
        var aEleId = [$('#etc_subparam_member_id')];
    } else {
        var sMemberId = $.trim($('#joinForm').find('#member_id').val());
        var aEleId = [$('#joinForm #member_id')];
    }

    var bCheck = checkIdValidation(sMemberId);

    if (bCheck['passed'] == false) {
        $('#idMsg').addClass('error').html(bCheck['msg']);
        return false;
    }

    AuthSSLManager.weave({
        'auth_mode': 'encrypt',
        'aEleId': aEleId,
        'auth_callbackName': 'checkIdEncryptedResultForMobile'
    });
}

/**
 * 아이디중복체크 암호화 처리 (모바일)
 * @param output
 */
function checkIdEncryptedResultForMobile(output)
{
    var sEncrypted = encodeURIComponent(output);

    if (AuthSSLManager.isError(sEncrypted) == true) {
        return;
    }

    $.ajax({
        url: '/exec/front/Member/CheckId',
        cache: false,
        type: 'POST',
        dataType: 'json',
        data: '&encrypted_str=' + sEncrypted + '&Country=' + SHOP.getLanguage(),
        timeout: 3000,
        success: function(response){
            var msg = response['msg'];

            try {
                msg = decodeURIComponent(msg);
            } catch (err) {}

            if (response['passed'] == true) {
                $('#idMsg').removeClass('error');
                $('#idMsg').html(msg);
                $('#idDuplCheck').val('T');
            } else {
                $('#idMsg').addClass('error').html(msg);
                $('#idDuplCheck').val('F');
            }
        }
    });
}

/**
 * 글자수 체크
 * @param 회원아이디 닉네임
 * @returns {Boolean}
 */
function checkIdValidation(sMemberId)
{
    if (sMemberId.length == 0 )
        return {'passed' : false, 'msg' : __('아이디를 입력해 주세요.')};

    if (sMemberId.length < 4 || sMemberId.length > 16)
        return {'passed' : false, 'msg' : __('아이디는 영문소문자 또는 숫자 4~16자로 입력해 주세요.')};

    return {'passed' : true};
}

function validatePassword()
{
    if ($('#passwd').val() == '' || $('#user_passwd_confirm').val() == '') {
        alert(__('비밀번호 항목은 필수 입력값입니다.'));
        return false;
    }

    var sPasswdType = ($('#passwd_type').val() == '' || $('#passwd_type').length < 1 ) ? 'A' : $('#passwd_type').val();

    // 비밀번호 조합 체크
    var passwd_chk = ckPwdPattern($('#passwd').val(), sPasswdType);

    if (passwd_chk !== true) {

        // 뉴상품 구분
        if (typeof(SHOP) == 'object' && SHOP.getProductVer() > 1) {

        } else {
            // 구상품용 알럿 처리
            return oldPasswdChk('passwd', sPasswdType);
        }


        var sMsgWord = __("입력 가능 특수문자 :  ~ ` ! @ # $ % ^ ( ) _ - = { } [ ] | ; : < > , . ? /");
        var aMsgWord = sMsgWord.split(':');
        var aMsgWordSub = {};

        if (sPasswdType == 'A') {
            var sMsg = ''
                + __('비밀번호 입력 조건을 다시 한번 확인해주세요.') + "\n"
                + "\n"
                + '※ ' + __('비밀번호 입력 조건') + "\n"
                + '- ' + __('대소문자/숫자 4자~16자') + "\n"
                + '- ' + __('특수문자 및 공백 입력 불가능') + "\n";
        } else {
            if (sPasswdType == 'B') {
                aMsgWordSub = __('대소문자/숫자/특수문자 중 2가지 이상 조합, 8자~16자');
            } else if (sPasswdType == 'C') {
                aMsgWordSub = __('대소문자/숫자/특수문자 중 2가지 이상 조합, 10자~16자');
            } else if (sPasswdType == 'D') {
                aMsgWordSub = __('대소문자/숫자/특수문자 중 3가지 이상 조합, 8자~16자');
            }
            var sMsg = ''
                + __('비밀번호 입력 조건을 다시 한번 확인해주세요.') + "\n"
                + "\n"
                + '※ ' + __('비밀번호 입력 조건') + "\n"
                + '- ' + aMsgWordSub + "\n"
                + '- ' + aMsgWord[0] + "\n" + "  " + aMsgWord[1] + ":" + aMsgWord[2] + "\n"
                + '- ' + __('공백 입력 불가능') + "\n";
        }

        if (sMsg) alert(sMsg);

        $('#passwd').focus();
        return false;
    }
}

/**
 * 비밀번호 확인 체크
 */
function checkPwConfirm(sType) {

    if (sType == 'new_passwd_confirm') {
        var sPasswdInput = '#new_passwd';
        var sPasswdConfirmInput = '#new_passwd_confirm';
        var sElementIdMsg = '#new_pwConfirmMsg';
    } else if (sType == 'etc_subparam_user_passwd_confirm') {
        var sPasswdInput = '#etc_subparam_passwd';
        var sPasswdConfirmInput = '#etc_subparam_user_passwd_confirm';
        var sElementIdMsg = '#pwConfirmMsg';
    } else {
        var sPasswdInput = '#passwd';
        var sPasswdConfirmInput = '#user_passwd_confirm';
        var sElementIdMsg = '#pwConfirmMsg';
    }

    var sPasswd = $.trim($(sPasswdInput).val());
    var sPasswdConfirm = $.trim($(sPasswdConfirmInput).val());
    
    if (sPasswd != sPasswdConfirm) {
        $(sElementIdMsg).addClass('error').html(__('비밀번호가 일치하지 않습니다.'));        
    } else {
        $(sElementIdMsg).removeClass('error').html(' ');
    }
}

function oldPasswdChk(sPasswdId, sPasswdType)
{
    var oCheckErrorMessage = {
        A : __('4~16자로 입력해 주세요.'),
        B : __('영문 대소문자, 숫자, 또는 특수문자 중 2가지 이상 조합하여 8~16자로 입력해 주세요.'),
        C : __('영문 대소문자, 숫자, 또는 특수문자 중 2가지 이상 조합하여 10~16자로 입력해 주세요.'),
        D : __('비밀번호는 영문 대소문자/숫자/특수문자 중 3가지 이상 조합,8자 ~ 16자로 설정하셔야 합니다.')
    };

    var sDefaultErrorMessage = __("공백 또는 허용된 특수문자 (~ ` ! @ # $ % ^ ( ) _ - = { [ } ] ; : < > , . ? /) 외의 특수문자는 사용할 수 없습니다.");
    var sDefaultErrorMessage2 = __("공백 또는 허용 불가한 특수문자는 사용할 수 없습니다.");

    if (sPasswdType == 'A') {
        sDefaultErrorMessage = sDefaultErrorMessage2;
    }

    // 비밀번호 조합 체크
    var passwd_chk = ckPwdPattern($('#' + sPasswdId).val(), sPasswdType);
    if (passwd_chk !== true) {
        $('#' + sPasswdId).focus();

        var sMessage = passwd_chk == 'F' ? sDefaultErrorMessage : oCheckErrorMessage[sPasswdType];

        alert(sMessage);

        return false;
    }
    return true;
}

/**
 * 비밀번호 체크
 */
function ckPwdPattern(sPwd, sPwdType)
{
    if (sPwdType == 'A') {
        var pattern = /^[a-zA-Z0-9]{4,16}$/; //조합없이 4~16
        var chk = (pattern.test(sPwd)) ? true : 'F';
        // 4보다 작거나 16보다 큰경우
        if (sPwd.length < 4 || 16 < sPwd.length) {
            chk = false;
        }
        return chk;
    } else {
        var chars1 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'; //영대소문자
        var chars2 = '0123456789'; //숫자
        var chars3 = '\~\`\!\@\#\$\%\^\(\)\_\-\=\{\}\[\]\|\;\:\<\>\,\.\?\/';

        var s = containsChar(sPwd, chars1, chars2, chars3);
        var s1 = s.split("/");
        var check_length = 0;

        if (s1[0] > 0) {
            check_length = parseInt(check_length)+parseInt(s1[0]);
            s1[0] = 1;
        }
        if (s1[1] > 0) {
            check_length = parseInt(check_length)+parseInt(s1[1]);
            s1[1] = 1;
        }
        if (s1[2] > 0) {
            check_length = parseInt(check_length)+parseInt(s1[2]);
            s1[2] = 1;
        }

        //영대문자, 숫자, 특수문자 중 2가지 이상 조합이면
        if ((parseInt(s1[0]) + parseInt(s1[1]) + parseInt(s1[2])) >= 2) {
            if (sPwdType == 'B') {
                if (sPwd.length >= 8 && sPwd.length <=16) {
                    if (sPwd.length > check_length) {//허용되지 않은 문자가 포함된 경우
                        return 'F';//허용되지 않은 문자가 포함됨
                    } else {
                        return true;
                    }
                } else {
                    return false;//8자~16자가 안됨
                }
            } else if (sPwdType == 'C') {
                if (sPwd.length >= 10 && sPwd.length <=16) {
                    if (sPwd.length > check_length) {
                        return 'F';
                    } else {
                        return true;
                    }
                } else {
                    return false;//10자~16자가 안됨
                }
            } else if (sPwdType == 'D') {
                if (parseInt(s1[0]) + parseInt(s1[1]) + parseInt(s1[2]) != 3)
                    return false;

                if (sPwd.length >= 8 && sPwd.length <=16) {
                    if (sPwd.length > check_length) {
                        return 'F';
                    } else {
                        return true;
                    }
                } else {
                    return false;//8자~16자가 안됨
                }
            } else {
                return false;
            }
        } else {
            return false; //영문대소문자, 숫자, 특수문자 2가지 조합이 안됨
        }
    }
}

function containsChar(input, chars1, chars2, chars3)
{
    var cnt1 = 0;
    var cnt2 = 0;
    var cnt3 = 0;

    for (var i=0; i<input.length; i++)
    {
        //영대소문자 포함여부
        if (chars1.indexOf(input.charAt(i))!= -1) {
            cnt1++;
        }
        //숫자 포함여부
        if (chars2.indexOf(input.charAt(i))!= -1) {
            cnt2++;
        }
        //특수문자 포함여부
        if (chars3.indexOf(input.charAt(i))!= -1) {
            cnt3++;
        }
    }
    return (cnt1+"/"+cnt2+"/"+cnt3);
}
/**
 * 이메일 중복 체크
 */
function checkDuplEmail()
{
    var aEleId = [];

    if ( $('#member_id').val() != '' && $('#member_id').val() != undefined ) {
        aEleId.push('member_id');
    }

    if ($('#email2').length > 0) {
        var sEmail = $.trim($('#email1').val())+'@'+$.trim($('#email2').val());
        aEleId.push('email1', 'email2');
    } else {
        if ( $('#etc_subparam_email1').val() != undefined ) {
            var sEmail = $.trim($('#etc_subparam_email1').val());
            aEleId.push('etc_subparam_email1');
        } else {
            var sEmail = $.trim($('#email1').val());
            aEleId.push('email1');
        }
    }

    var bCheck = checkEmailValidation(sEmail);
    if (bCheck['passed'] == false) {
        $('#emailMsg').addClass('error').html(bCheck['msg']);
        return false;
    }

    AuthSSLManager.weave({
        'auth_mode': 'encrypt',
        'aEleId': aEleId,
        'auth_callbackName': 'checkEmailEncryptedResult'
    });

}

/**
 *  * 아이디중복체크 암호화 처리 (모바일)
 *   * @param output
 *    */
function checkEmailEncryptedResult(output)
{
    var sEncrypted = encodeURIComponent(output);

    if (AuthSSLManager.isError(sEncrypted) == true) {
        return;
    }

    $.ajax({
        url: '/exec/front/Member/CheckEmail',
        cache: false,
        type: 'POST',
        dataType: 'json',
        data: '&encrypted_str=' + sEncrypted,
        timeout: 3000,   
        success: function(response){
            var msg = response['msg'];
            try {
                msg = decodeURIComponent(msg);
            } catch (err) {}
            
            if (response['passed'] == true) {
                $('#emailMsg').removeClass('error').html(msg);
                $('#emailDuplCheck').val('T');

                if (SHOP.getLanguage() == 'ja_JP' && response['jp_email_check'] != '') {
                    checkSoftbankEmail(response['jp_email_check']);
                }

                // 중복 체크 성공 
                bCheckedEmailDupl = true;
            } else {
                $('#emailMsg').addClass('error').html(msg);
                $('#emailDuplCheck').val('F');
                bCheckedEmailDupl = false;
            }
            
            // 추천아이디 중복체크 완료 (회원가입, 수정페이지 둘다쓰임)
            var $oMemberId = '';
            if ( $('#etc_subparam_member_id').val() != undefined ) {
                $oMemberId = $('#etc_subparam_member_id');
            } else {
                $oMemberId = $('#joinForm').find('#member_id');
            }
            if ( response['id'] != null && $oMemberId.val() == '' && $('#login_id_type').val() == 'email') {
                $oMemberId.val(response['id']);
                $('#idDuplCheck').val('T');
                $('#idMsg').removeClass('error').html( __('추천아이디이므로 그대로 사용할 수 있으며, 수정도 가능합니다.') );
            }
        }
    });
}

/**
 * 글자수 체크
 * @param 회원아이디 닉네임
 * @returns {Boolean}
 */
function checkEmailValidation(sEmail)
{       
    if (sEmail.length == 0 )
        return {'passed' : false, 'msg' : __('이메일을 입력해 주세요.')};
    
    if (FwValidator.Verify.isEmail(sEmail) == false || sEmail.length > 255)
        return {'passed' : false, 'msg' : __('유효한 이메일을 입력해 주세요.')};

    return {'passed' : true};
}

/**
 * 소프트뱅크 메일여부 체크
 * @param sEmail 이메일주소
 */
function checkSoftbankEmail(jp_email_check)
{
    if (SHOP.getLanguage() != 'ja_JP') return;
    
    // 모바일 구디자인의 경우 emailMsg가 없어서 처리 해줌 ( memberJoin에 같은 소스가 있는데 모바일일 경우 중복 노출 되어 위치 이동 시킴 )
    if ( $("#emailMsg").length > 0) {
        
        if (jp_email_check == 'jp_email_wanning') {
            $('#emailMsg').html('ご入力のアドレスはMMSサービスとなり、大容量のデータ受信ができかねます。');
        }
    } else {
        
        var bExistEmailBtn = false;
        if ($('#check_email_button').length > 0) bExistEmailBtn = true;
        (bExistEmailBtn == true) ? $('#check_email_button').next('p').remove() : $('#email1').next('p').remove();
        
        if (jp_email_check == 'jp_email_wanning') {
            $sInfoText = '<p style="color:#747474;">ご入力のアドレスはMMSサービスとなり、大容量のデータ受信ができかねます。</p>';
            if (bExistEmailBtn) {
                $('#check_email_button').after($sInfoText);
            } else {
                $('#email1').after($sInfoText);
            }
        }
    }
}

/**
 * 개인사업자번호 중복 체크
 */
function checkCssnDupl()
{
    var sCssn = EC$('#cssn').val();

    if (sCssn == '') {
        EC$('#cssnMsg').addClass('error').html(__('올바른 사업자번호를 입력해 주세요.'));
        return false;
    }

    var aData = ['cssn'];
    var bIsLogin = document.cookie.match(/(?:^| |;)iscache=F/) ? true : false;

    // 수정 페이지에서 넘어왔다면
    if (bIsLogin) {
        aData.push('member_id');
    }

    // ssl 암호화 처리
    AuthSSLManager.weave({
        'auth_mode': 'encrypt',
        'aEleId': aData,
        'auth_callbackName': 'callbackCssnCheck'
    });
}

/**
 * 개인사업자번호 체크 callback
 */
function callbackCssnCheck(sOutput)
{
    EC$.ajax({
        url: '/exec/front/Member/CheckCssn',
        cache: false,
        type: 'POST',
        dataType: 'json',
        data: '&encrypted_str='+encodeURIComponent(sOutput),
        timeout: 3000,
        success: function(response){
            if (response['passed'] == true) {
                EC$('#cssnMsg').removeClass('error').html(response['msg']);
                EC$('#cssnDuplCheck').val('T');
            } else {
                EC$('#cssnMsg').addClass('error').html(__(response['msg']));
                EC$('#cssnDuplCheck').val('F');
            }
        }
    });
}

/**
 * 사업자 번호 valid 체크
 * @return Boolean
 */
function checkCssnValid(sCssn)
{
    // 정규식 체크
    var regexp = /^([0-9]{3})-([0-9]{2})-([0-9]{5})$/;
    var regexp2 = /^([0-9]{10})$/;
    if (regexp.test(sCssn) === false && regexp2.test(sCssn) === false) {
        EC$('#cssnMsg').addClass('error').html(__('올바른 사업자번호를 입력해 주세요.'));
        return false;
    } else {
        EC$('#cssnMsg').removeClass('error').html(__('사용 가능합니다.'));
        return true;
    }
}
/**
 * 가입 정보 확인
 */

$(document).ready(function(){
    $("#ec_shop_confirm-checkingjoininfo_action").click(function(){
        return CheckingJoinInfoOk();
    });
});

function CheckingJoinInfoLayerClose()
{
    $('#ec_shop_member_confirm-infolayer').css("display","none");    
    return false;
}

function CheckingJoinInfoOk()
{
    $("#is_use_checking_join_info").val('F');
    return memberJoinAction();
}

function CheckingJoinInfo() {
    var obj;
    var pobj=$("#ec_shop_member_confirm-infolayer");
    if (pobj.length === 0) {
        return false;
    }
    
    var bExits = true;
    // 가입 사전 체크
    try{
        if (AuthSSL.bIsSsl) {
            var aEleId = FormSSLContainer.aFormSSL['joinForm'].aEleId;        
            AuthSSLManager.weave({
                'auth_mode'        : 'encrypt',
                'aEleId'           : aEleId,
                'auth_callbackName': "CheckingJoinInfoAuthsslSuccess"
            });    
        }
    }catch(e) {    
        bExits=false;
    }
        
    return bExits;
}

function CheckingJoinInfoAuthsslSuccess(sOutput) {
    if ( AuthSSLManager.isError(sOutput) == true ) {
        alert('[Error]\n'+sOutput);
        return;
    }

    $.ajax({
        type: 'POST',
        url:  '/exec/front/Member/Join/',
        data: {"encrypted_str":sOutput,"is_checking_join_info":"T"},
        dataType: 'json',
        success : CheckingJoinInfoCallback
    });
}

function CheckingJoinInfoCallback(response)
{
    try{        
        if (response.result!='1') {
            alert(__(response.msg));
            return true;
        }
    }catch(e){        
    }
    

    var obj;
    var pobj=$("#ec_shop_member_confirm-infolayer");
    pobj.css("display","block");    
    
    // 이름
    if ($("#nameContents > :input").length>0) {
        pobj.find("#ec_shop_member_confirm-name_contents").html( $("#nameContents > :input").val() );        
    } else {
        pobj.find("#ec_shop_member_confirm-name_contents").html( $("#nameContents").text() );        
    }    
    
    // 사업자        
    if ($("#companyRow").css("display")!=="none") {
        
        if ($("#companyName > :input").length>0) {
            $("#ec_shop_member_confirm-company_name").show().find("td").html( $("#companyName > :input").val() );
        } else {
            $("#ec_shop_member_confirm-company_name").show().find("td").html( $("#companyName").text() );
        }
        
        $("#ec_shop_member_confirm-company_ssn").show().find("td").html( $("#cssn").val() );
    } else {
        $("#ec_shop_member_confirm-company_name").hide();
        $("#ec_shop_member_confirm-company_ssn").hide();        
    }
    
    // 인증정보
    obj = $("#ec_shop_member_confirm-ssn_title");
    if (obj.length!==0) {
        $("#ec_shop_member_confirm-ssn_title").parent().show();        
        if ($("#ssnTitle").parent().css("display")!=="none") {
            pobj.find("#ec_shop_member_confirm-ssn_title").html( $("#ssnTitle").text() );
            pobj.find("#ec_shop_member_confirm-ssn_contents").html( $("#ssnContents").text() );
        }else{            
            $("#ec_shop_member_confirm-ssn_title").parent().hide();            
        }
    }
    
    // 국적
    obj = $("#ec_shop_member_confirm-nation");
    if (obj.length!==0) {
        $("#ec_shop_member_confirm-nation").show();        
        if ($("#nation").css("display")==="none") {
            $("#ec_shop_member_confirm-nation").hide();            
        }
    }

    // 국가
    var oCountry = $("#country");
    var sCountryCode = '';
    if (oCountry.length > 0) {
        sCountryCode = oCountry.val();
        // 국가코드가 2자리일 경우 3자리로 변환
        if (sCountryCode.length === 2) {
            sCountryCode = EC_ADDR_COUNTRY_CONVERT_MAP.aCountryDomainToCodeMap[sCountryCode];
        }
    }

    // city, state filed 노출 여부
    if (typeof common_aAddrInfo === 'object' && common_aAddrInfo['sIsRuleBasedAddrForm'] === 'T') {
        // city
        var display = ($('#city_name').length) ? $('#city_name').css("display") : '';
        if (display.indexOf('block') > -1) display = '';
        $('tr:has(td:has(#ec_shop_member_confirm_field-city_name))').css("display",display);

        // state
        if (sCountryCode === 'USA' && $('#stateListUs').length) {
            display = $('#stateListUs').css("display");
        } else if (sCountryCode === 'CAN' && $('#stateListCa').length) {
            display = $('#stateListCa').css("display");
        } else if ($('#state_name').length) {
            display = $('#state_name').css("display");
        } else {
            display = '';
        }
        if (display.indexOf('block') > -1) display = '';
        $('tr:has(td:has(#ec_shop_member_confirm_field-state_name))').css("display",display);

    } else {
        var display = "";

        // city
        display = $('tr:has(td:has(#city_name))').css("display");
        $('tr:has(td:has(#ec_shop_member_confirm_field-city_name))').css("display",display);

        // state
        display = $('tr:has(td:has(#state_name))').css("display");
        $('tr:has(td:has(#ec_shop_member_confirm_field-state_name))').css("display",display);
    }

    // field    
    pobj.find("tr[class!='displaynone'] > td").find("span[id^='ec_shop_member_confirm_field-']").each(function(){
        var name = $(this).attr("id");
        name = name.replace("ec_shop_member_confirm_field-","");

        var query = "[name='"+name+"']";

        if (name==="") {
            return;
        }
        
        // 추가정보 체크박스 처리
        if (name.match(/add\d+/)) {
            query+=",:input[name='"+name+"[]']";
        }        
        else if ( name==="phone" || name==="mobile" || name==="inter_check") {
            query+=",:input[name='"+name+"[]']";
        }


        
        obj = $("#joinForm").find(":input"+query);        
                
        if (obj.length===0) {
            return;
        }

        var type = obj.attr("type");
        var value;        
        if (name==="phone" || name==="mobile") {
            value = obj.map(function () {
                return $(this).val();
            }).get().join('-');
        } else if (name == "is_sms" || name == "is_news_mail") {
            value = __('동의안함');
            if (obj.is(":checked") === true) {
                var sTempId = obj.filter(":checked").attr("id");
                value = $("#joinForm").find("label[for='"+sTempId+"']").text();
            }

            if (mobileWeb == true && obj.val() == 'T' && type !== "checkbox") {
                value = __('동의함');
            }
        } else if (type==="text" && obj.length===1) {
            value = obj.val();

            if (name == "fssn")  value = '***' + obj.val().slice(-4);
        } else if (type==="checkbox" && obj.length>0) {            
            if (name.match(/add\d+/)) {
                value = obj.filter(":checked").map(function(){
                    return $(this).val();
                }).get().join(', ');                                
            } else if (name==="inter_check") {
                value = obj.filter(":checked").map(function(){
                    var sTempId = $(this).attr("id");
                    return $("#joinForm").find("label[for='"+sTempId+"']").text();                    
                }).get().join(', ');
            }
        } else if (type==="select-one") {        
            if (obj.find("option:selected").val()=="") {
                value="";
            } else {
                value = obj.find("option:selected").text();
            }            
        } else if (type==="radio") {            
            var sTempId = obj.filter(":checked").attr("id");
            value = $("#joinForm").find("label[for='"+sTempId+"']").text();
        } else if (name == "addr1" && type==="hidden") {
            // 주소 input 창 변경으로 추가
            value = obj.val();
        }

        if (name === "state_name" && value == '') {
            value = $("#joinForm").find(":input[name='__"+name+"']").val();
        }

        if ($('#sUseSeparationNameFlag').val() == "T") {
            var aLastNameObject = ["name", "name_en", "name_phonetic"];
            var iLastNameObjectKey = $.inArray(name, aLastNameObject);
            if (iLastNameObjectKey > -1) {
                if ($("#joinForm").find(':input[name=last_' + aLastNameObject[iLastNameObjectKey] + ']').val() != undefined) {
                    value = obj.val() + " " + $("#joinForm").find(':input[name=last_' + aLastNameObject[iLastNameObjectKey] + ']').val();
                }
            }
        }

        if (name == "email1") {
            if ($("#ec_shop_member_confirm_field-email2").length > 0) {
                var aMail = value.split("@");
                value = aMail[0];
                $("#ec_shop_member_confirm_field-email2").html(aMail[1]);
            }
        }

        if (name == "email2") {
            if (value == "") {
                return true;
            }
        }

        $(this).html(value);
    });

    // 중국, 베트남 주소 처리
    if (sCountryCode !== '') {
        viewViVnAddress(sCountryCode);
        viewZhCnAddress(sCountryCode);
    }

    
    // 미입력값 삭제    
    obj = pobj.find("#ec_shop_member_confirm_field-birth_year");
    if (obj.length!==0) {
        obj.parent().find("span").show();
        if (
                pobj.find("#ec_shop_member_confirm_field-birth_year").text()==="" &&
                pobj.find("#ec_shop_member_confirm_field-birth_month").text()==="" &&
                pobj.find("#ec_shop_member_confirm_field-birth_day").text()===""
        ) {
            obj.parent().find("span").hide();
        }        
    }
    
    obj = pobj.find("#ec_shop_member_confirm_field-marry_year");
    if (obj.length!==0) {
        obj.parent().find("span").show();
        if (
                pobj.find("#ec_shop_member_confirm_field-marry_year").text()==="" &&
                pobj.find("#ec_shop_member_confirm_field-marry_month").text()==="" &&
                pobj.find("#ec_shop_member_confirm_field-marry_day").text()===""
        ) {
            obj.parent().find("span").hide();
        }
    }
    
    obj = pobj.find("#ec_shop_member_confirm_field-partner_year");
    if (obj.length!==0) {
        obj.parent().find("span").show();
        if (
                pobj.find("#ec_shop_member_confirm_field-partner_year").text()==="" &&
                pobj.find("#ec_shop_member_confirm_field-partner_month").text()==="" &&
                pobj.find("#ec_shop_member_confirm_field-partner_day").text()===""
        ) {
            obj.parent().find("span").hide();
        }
    }
    
    return true;
}

/**
 * 베트남 주소 처리
 * @param sCountryCode 국가코드
 */
function viewViVnAddress(sCountryCode)
{
    if (typeof common_aAddrInfo === 'object' && common_aAddrInfo['sIsRuleBasedAddrForm'] === 'T') {
        if (sCountryCode !== "VNM") {
            return;
        }
    } else {
        if (SHOP.getLanguage() !== "vi_VN") {
            return;
        }
    }

    var oAddr1 = $("#addr1");
    if (oAddr1.length < 1) {
        return;
    }

    var oAddr2 = $("#addr2");
    if (oAddr2.length < 1) {
        return;
    }

    var oConfirmAddr1 = $("#ec_shop_member_confirm_field-addr1");
    var oConfirmAddr2 = $("#ec_shop_member_confirm_field-addr2");

    if (oConfirmAddr1.length < 1) {
        return;
    }

    oConfirmAddr1.html(oAddr2.val());

    if (oConfirmAddr2.length < 1) {
        return;
    }

    if ($("#ec_shop_member_confirm_field-city_name").parent().parent().css("display") != "none") {
        $("#ec_shop_member_confirm_field-city_name").parent().parent().css("display", "none");
    }

    if ($("#ec_shop_member_confirm_field-state_name").parent().parent().css("display") != "none") {
        $("#ec_shop_member_confirm_field-state_name").parent().parent().css("display", "none");
    }

    var sAddr1 = oAddr1.val();
    if ($('#city_name').length > 0) {
        if ($.trim($('#city_name').val()) != "") {
            sAddr1 += " " + $.trim($('#city_name').val());
        }
    }

    if ($('#state_name').length > 0) {
        if ($.trim($('#state_name').val()) != "") {
            sAddr1 += " " + $.trim($('#state_name').val());
        }
    }

    oConfirmAddr2.html(sAddr1);
}

/**
 * 중국 주소 처리
 * @param sCountryCode 국가코드
 */
function viewZhCnAddress(sCountryCode)
{
    if (typeof common_aAddrInfo === 'object' && common_aAddrInfo['sIsRuleBasedAddrForm'] === 'T') {
        if (sCountryCode !== "CNN") {
            return;
        }
    } else {
        if (SHOP.getLanguage() !== "zh_CN") {
            return;
        }
    }

    var oConfirmAddr1 = $("#ec_shop_member_confirm_field-addr1");

    if (oConfirmAddr1.length < 1) {
        return;
    }

    try {
        var oAddr1Title = oConfirmAddr1.parent().parent().find("th");
        oAddr1Title.html($('#sAddr1Title').text());
    } catch(e) {}
}
var memberCommon = (function() {

    var oAgreeCheckbox = [
        {"obj": $('input:checkbox[name="agree_service_check[]"]')},//이용약관 동의
        {"obj": $('input:checkbox[name="agree_privacy_check[]"]')}, // 개인정보 수집 및 이용 동의
        {"obj": $('input:checkbox[name="agree_privacy_optional_check[]"]'), 'sIsDisplayFlagId':"display_agree_privacy_optional_check_flag"}, // 개인정보 수집 및 이용 동의 (선택)
        {"obj": $('input:checkbox[name="agree_information_check[]"]'), "sIsDisplayFlagId":"display_agree_information_check_flag"}, // 개인정보 제3자 제공 동의(선택)
        {"obj": $('input:checkbox[name="agree_consignment_check[]"]'), "sIsDisplayFlagId":"display_agree_consignment_check_flag"}, // 개인정보 처리 위탁 동의
        {"obj": $('input:checkbox[name="is_sms"]'), "sIsDisplayFlagId":"required_is_sms_flag"}, // sms 수신 동의
        {"obj": $('input:checkbox[name="is_news_mail"]'), "sIsDisplayFlagId":"required_is_news_mail_flag"}, // 이메일 수신 동의
        {"obj": $('#sMarketingAgreeAllChecked')} // mobile 이메일 수신, sms 수신 동의 전체 체크
    ];

    var oMarketingAgreeCheckbox = [
        {"obj": $('input:checkbox[name="is_sms"]'), "sIsDisplayFlagId":"required_is_sms_flag"}, // sms 수신 동의
        {"obj": $('input:checkbox[name="is_news_mail"]'), "sIsDisplayFlagId":"required_is_news_mail_flag"}, // 이메일 수신 동의
    ];

    var oMarketingAgreeAllChecked = $('input:checkbox[id="sMarketingAgreeAllChecked"]');

    /**
     * 약관 일괄 동의 체크
     */
    function agreeAllChecked()
    {
        var bAgreeAllChecked = $('input:checkbox[id="sAgreeAllChecked"]').is(":checked");

        if (bAgreeAllChecked.length < 1) {
            return;
        }

        $.each(oAgreeCheckbox, function(i, oVal) {
            if (oVal.obj.length < 1) {
                // continue
                return true;
            }

            if (bAgreeAllChecked === true) {
                if ($('#'+oVal.sIsDisplayFlagId).length > 0) {
                    if ($('#'+oVal.sIsDisplayFlagId).val() != "T") {
                        return true;
                    }
                }
                oVal.obj.attr("checked", true);
            } else {
                oVal.obj.attr("checked", false);
            }
        });
    }

    /**
     * 약관 일괄 동의 체크 or 해제 처리
     */
    function agreeAllUnChecked(obj)
    {
        if (obj.length < 1) {
            return;
        }
        var sIsUnchecked = "F";
        if (obj.is(":checked") === false) {
            if ($('input:checkbox[id="sAgreeAllChecked"]').length > 0) {
                $('input:checkbox[id="sAgreeAllChecked"]').attr("checked", false);
            }
            sIsUnchecked = "T";

            // 모바일 쇼핑정보 수신 동의 선택 박스 언체크
            if (obj.attr("name") == "is_sms" || obj.attr("name") == "is_news_mail") {
                if (memberCommon.oMarketingAgreeAllChecked.length > 0) {
                    memberCommon.oMarketingAgreeAllChecked.attr("checked", false);
                }
            }
        }
        return sIsUnchecked;
    }

    /**
     * 모바일 마케팅 약관 일괄 동의 체크
     */
    function marketingAgreeAllCheckboxIsChecked()
    {
        var sIsAllChecked = "T";

        $.each(memberCommon.oMarketingAgreeCheckbox, function(i, oVal) {
            if (oVal.length < 1) {
                // continue
                return true;
            }

            if (oVal.obj.is(":checked") === false) {
                sIsAllChecked = "F";
                return false;
            }
        });

        if (sIsAllChecked == "T") {
            if (memberCommon.oMarketingAgreeAllChecked.length > 0) {
                memberCommon.oMarketingAgreeAllChecked.attr("checked", true);
            }
        }
    }

    /**
     * 모바일 sms, email 수신 동의 전체 선택
     */
    function marketingAllChecked()
    {
        if (memberCommon.oMarketingAgreeAllChecked.length < 1) {
            return;
        }
        var bAgreeAllChecked = memberCommon.oMarketingAgreeAllChecked.is(":checked");

        $.each(memberCommon.oMarketingAgreeCheckbox, function(i, oVal) {
            if (oVal.obj.length < 1) {
                // continue
                return true;
            }

            if (bAgreeAllChecked === true) {
                if ($('#'+oVal.sIsDisplayFlagId).length > 0) {
                    if ($('#'+oVal.sIsDisplayFlagId).val() != "T") {
                        return true;
                    }
                }
                oVal.obj.attr("checked", true);
            } else {
                oVal.obj.attr("checked", false);
            }
        });
    }

    /**
     * 모바일 sms, email 수신 동의 필수 입력 제거
     */
    function marketingRemoveFilter()
    {
        // sms 수신 동의
        if ($('input:checkbox[name="is_sms"]').length > 0) {
            if ($('input:checkbox[name="is_sms"]').attr("fw-filter").indexOf("isFill") > -1) {
                $('input:checkbox[name="is_sms"]').removeAttr("fw-filter");
            }
        }

        // 이메일 수신 동의
        if ($('input:checkbox[name="is_news_mail"]').length > 0) {
            if ($('input:checkbox[name="is_news_mail"]').attr("fw-filter").indexOf("isFill") > -1) {
                $('input:checkbox[name="is_news_mail"]').removeAttr("fw-filter");
            }
        }
    }

    /**
     * 전체 동의 외 체크박스 모두 체크시 전체 동의 체크
     */
    function eachCheckboxAgreeAllChecked()
    {
        var sIsAllChecked = "T";

        $.each($('.agreeArea'), function(i, oVal) {
            if (($(oVal).hasClass('displaynone')) === true) {
                return true;
            }

            $.each($(oVal).find("input:checkbox"), function(j, oVal2) {
                // 심플디자인 전체 동의 버튼 제외 처리
                if ($(oVal2).attr('id') == "sAgreeAllChecked") {
                    return true;
                }
                
                if ($(oVal2).is(":checked") === false) {
                    sIsAllChecked = "F";
                    return false;
                }
            });
        });

        if (sIsAllChecked == "T") {
            $('input:checkbox[id="sAgreeAllChecked"]').attr("checked", true);
        }
    }

    /**
     * 모바일 유효성 패턴 체크
     */
    function isValidMobile()
    {
        // 모바일 등록 여부
        if ($('#mobile2').length < 1 && $('#mobile3').length < 1) {
            return true;
        }

        // 모바일 등록 여부
        if ( SHOP.getLanguage() == 'ko_KR' ) {
            if ($('#mobile1').length < 1 && $('#mobile2').length < 1 && $('#mobile3').length < 1) {
                return true;
            }
        } else {
            if ($('#mobile1').length < 1 && $('#mobile2').length < 1) {
                return true;
            }
        }

        // 휴대폰 패턴체크
        var aMobile = {};

        if ($('#mobile1').length > 0) {
            aMobile.mobile1 = $('#mobile1').val();
        }

        if ($('#mobile2').length > 0) {
            aMobile.mobile2 = $('#mobile2').val();
        }

        if ($('#mobile3').length > 0) {
            aMobile.mobile3 = $('#mobile3').val();
        }

        if (utilValidatorController.checkMobile(aMobile) === true) {
            return true;
        }

        alert(__('올바른 휴대전화번호를 입력 하세요.'));

        var iElementNumber = utilValidatorController.getElementNumber();

        // focus 처리
        if (iElementNumber == 1) {
            $('#mobile1').focus();
        } else if (iElementNumber == 2) {
            $('#mobile2').focus();
        } else if (iElementNumber == 3) {
            $('#mobile3').focus();
        }
        return false;
    }

    /**
     * 모바일번호 회원가입 유효성 체크
     * @return boolean
     */
    function checkJoinMobile()
    {
        // 회원 가입 휴대전화 필수입력 체크를 기존에 추가로 해 주고 있는 부분 추가
        if ($('#is_display_register_mobile').val() == 'T') {
            if ( $.trim($('#mobile1').val()) == '' || $.trim($('#mobile2').val()) == '' || ($('#mobile3').length > 0 && $.trim($('#mobile3').val()) == '')) {
                alert(__('휴대전화를  입력해주세요.'));
                $('#mobile1').focus();
                return false;
            }
        }

        if (memberCommon.isJoinMobileValidPassConditionCheck() === true) {
            return true;
        }

        if (memberCommon.isValidMobile() === true) {
            return true;
        }
        return false;
    }

    /**
     * 모바일번호 유효성 체크
     * @return boolean
     */
    function checkEditMobile()
    {
        // 회원 정보 수정 휴대전화 필수입력 체크를 기존에 추가로 해 주고 있는 부분 추가
        if ($('#is_display_register_mobile').val() == 'T') {
            if ($.trim($('#mobile1').val()) == '' || $.trim($('#mobile2').val()) == '') {
                alert(__('올바른 휴대전화번호를 입력하세요.'));
                $('#mobile2').focus();
                return false;
            }
        }

        if (memberCommon.isEditMobileValidPassConditionCheck() === true) {
            return true;
        }

        if (memberCommon.isValidMobile() === true) {
            return true;
        }
        return false;
    }

    /**
     * 회원가입 유효성 체크 통과 케이스
     * @returns {boolean}
     */
    function isJoinMobileValidPassConditionCheck()
    {
        // 회원 가입 항목 상세 설정 && 일반전화 항목 등록 설정 후 다시 기본 항목 설정으로 변경시  일반전화 항목 미입력으로 설정으로 복구 되지 않는다.
        // 기존 설정 유지되는 부분이 있어 예외처리
        if ($("#useSimpleSignin").length > 0) {
            // 기본 회원가입항목
            if ($("#useSimpleSignin").val() == 'T') {
                // 휴대전화 항목 등록 항목 노출 && 휴대전화 필수입력
                if ($('#display_register_mobile').val() != "T" || $('#display_required_cell').val() != "T") {
                    return true;
                }
            }
        }

        if (SHOP.getLanguage() == 'ko_KR') {
            // 상세항목 회원가입 모바일 필수입력만 체크
            if ($('#display_required_cell').val() != "T") {
                return true;
            }
        } else {
            // 해외몰 모바일사용여부 && 필수입력 체크
            if ($('#is_display_register_mobile').val() != "T" || $('#display_required_cell').val() != "T") {
                return true;
            }
        }
        return false;
    }

    /**
     * 회원정보 수정 유효성 체크 통과 케이스
     * 회원가입과 동일하게 유지
     * @returns {boolean}
     */
    function isEditMobileValidPassConditionCheck()
    {
        if (memberCommon.isJoinMobileValidPassConditionCheck() === true) {
            return true;
        }
        return false;
    }

    /**
     * 일반전화 유효성 체크
     * @return boolean
     */
    function isValidPhone()
    {
        // 일반전화 등록 여부
        if ( SHOP.getLanguage() == 'ko_KR' ) {
            if ($('#phone1').length < 1 && $('#phone2').length < 1 && $('#phone3').length < 1) {
                return true;
            }
        } else {
            if ($('#phone1').length < 1 && $('#phone2').length < 1) {
                return true;
            }
        }

        // 일반전화 패턴체크
        var aPhone = {};

        if ($('#phone1').length > 0) {
            aPhone.phone1 = $('#phone1').val();
        }

        if ($('#phone2').length > 0) {
            aPhone.phone2 = $('#phone2').val();
        }

        if ($('#phone3').length > 0) {
            aPhone.phone3 = $('#phone3').val();
        }

        if (utilValidatorController.checkPhone(aPhone) === true) {
            return true;
        }

        alert(__('올바른 전화번호를 입력하세요.'));

        var iElementNumber = utilValidatorController.getElementNumber();

        // focus 처리
        if (iElementNumber == 1) {
            $('#phone1').focus();
        } else if (iElementNumber == 2) {
            $('#phone2').focus();
        } else if (iElementNumber == 3) {
            $('#phone3').focus();
        }
        return false;
    }

    /**
     * 일반전화 회원가입 유효성 체크 통과 케이스
     */
    function isJoinPhoneValidPassConditionCheck()
    {
        // 회원 가입 항목 상세 설정 && 일반전화 항목 등록 설정 후 다시 기본 항목 설정으로 변경시  일반전화 항목 미입력으로 설정으로 복구 되지 않는다.
        // 기존 설정 유지되는 부분이 있어 예외처리
        if ($("#useSimpleSignin").length > 0) {
            if ($("#useSimpleSignin").val() == 'T') {
                return true;
            }
        }

        if (SHOP.getLanguage() == 'ko_KR') {
            // 상세항목 회원가입 일반전화 필수입력만 체크
            if ($('#display_required_phone').val() != "T") {
                return true;
            }
        } else {
            // 해외몰 일반전화 사용여부 && 필수입력 체크
            if ($('#is_display_register_phone').val() != "T" || $('#display_required_phone').val() != "T") {
                return true;
            }
        }
    }

    /**
     * 일반전화 회원정보 수정 유효성 체크 통과 케이스
     */
    function isEditPhoneValidPassConditionCheck()
    {
        if (SHOP.getLanguage() == 'ko_KR') {
            // 상세항목 회원가입 일반전화 필수입력만 체크
            if ($('#display_required_phone').val() != "T") {
                return true;
            }
        } else {
            // 해외몰 일반전화 사용여부 && 필수입력 체크
            if ($('#is_display_register_phone').val() != "T" || $('#display_required_phone').val() != "T") {
                return true;
            }
        }
    }

    /**
     * 일반전화 회원가입 유효성 체크
     * @return boolean
     */
    function checkJoinPhone()
    {
        if (memberCommon.isJoinPhoneValidPassConditionCheck() === true) {
            return true;
        }

        if (memberCommon.isValidPhone() === true) {
            return true;
        }
        return false;
    }

    /**
     * 일반전화 회원정보 수정 유효성 체크
     * @return boolean
     */
    function checkEditPhone()
    {
        if (memberCommon.isEditPhoneValidPassConditionCheck() === true) {
            return true;
        }

        if (memberCommon.isValidPhone() === true) {
            return true;
        }
        return false;
    }

    /**
     * 우편번호 유효성 체크
     */
    function checkZipcode(bCheckKrZipcode)
    {
        var sZipcodeSelector = '#postcode1';
        var sNoZipSelector = '#nozip';

        // 우편번호 필수입력인 경우
        if ($('#is_display_register_addr').val() === 'T'
            && ($(sNoZipSelector).is(':checked') === false && $.trim($(sZipcodeSelector).val()) === '')) {
            alert(__('우편번호를 입력해주세요.'));
            $(sZipcodeSelector).focus();
            return false;
        }

        // 우편번호 포맷 체크
        if (($(sZipcodeSelector).length > 0 && $(sZipcodeSelector).val() !== '') && $(sNoZipSelector).is(':checked') === false) {
            if ($(sZipcodeSelector).val().length < 2 || $(sZipcodeSelector).val().length > 14) {
                alert(__("우편번호는 2자 ~ 14자까지 입력가능합니다."));
                $(sZipcodeSelector).focus();
                return false;
            }

            if ($(sZipcodeSelector).val().match(/^[a-zA-Z0-9- ]{2,14}$/g) == null) {
                alert(__("우편번호는 영문, 숫자, 대시(-)만 입력가능합니다.\n입력내용을 확인해주세요."));
                $(sZipcodeSelector).focus();
                return false;
            }
        }

        // 한국 우편번호 자리수 체크
        var sCountryCode = $('#country').val();
        if ((typeof common_aAddrInfo === 'object' && common_aAddrInfo['sIsRuleBasedAddrForm'] === 'T' && sCountryCode === 'KR')
            || (typeof common_aAddrInfo === 'object' && common_aAddrInfo['sIsRuleBasedAddrForm'] === 'F' && SHOP.getLanguage() == 'ko_KR')
        ) {
            if ($(sZipcodeSelector).val() != '' && $(sZipcodeSelector).val() != undefined && bCheckKrZipcode == true) {

                var zipcode = $(sZipcodeSelector).val();
                zipcode = zipcode.replace('-', '');

                // 숫자가 아니거나 5자리 미만이면 체크
                if (FwValidator.Verify.isNumber(zipcode) == false || zipcode.length < 5 || zipcode.length > 6) {
                    alert('우편번호를 확인해주세요');
                    $('#postcode2').val('');
                    $(sZipcodeSelector).focus();
                    return false;
                }
            }
        }
    }

    /**
     * 영문몰 국가 미국, 캐나다 선택 시 주/도 select box 설정
     */
    function setUsStateNameVisible() {
        if ( SHOP.getLanguage() !== 'en_US' ) {
            return;
        }

        try {
            var sCountry = $('#country').val();
            // 국가코드가 2자리일 경우 3자리로 변환
            if (sCountry.length === 2) {
                sCountry = EC_ADDR_COUNTRY_CONVERT_MAP.aCountryDomainToCodeMap[sCountry];
            }

            var sStateName = $('#__state_name').val();
            var sStateNameElement = $('#state_name');
            var sStateListCaElement = $('#stateListCa');
            var sStateListUsElement = $('#stateListUs');

            if (sCountry === 'USA') {
                sStateNameElement.attr('disabled', true);
                sStateNameElement.hide();
                sStateListCaElement.attr('disabled', true);
                sStateListCaElement.hide();
                sStateListUsElement.attr('disabled', false);
                sStateListUsElement.show();
                sStateListUsElement.val(sStateName).attr('selected', 'selected');
            } else if (sCountry === 'CAN') {
                sStateNameElement.attr('disabled', true);
                sStateNameElement.hide();
                sStateListUsElement.attr('disabled', true);
                sStateListUsElement.hide();
                sStateListCaElement.attr('disabled', false);
                sStateListCaElement.show();
                sStateListCaElement.val(sStateName).attr('selected', 'selected');
            } else {
                sStateListUsElement.attr('disabled', true);
                sStateListUsElement.hide();
                sStateListCaElement.attr('disabled', true);
                sStateListCaElement.hide();
                sStateNameElement.attr('disabled', false);
                sStateNameElement.show();
            }
        } catch(e) {}
    }

    /**
     * 미국 주/도 선택 값 설정
     */
    function setCountryUsStateNameValue() {
        var sCountryCode = $('#country').val();
        // 국가코드가 2자리일 경우 3자리로 변환
        if (sCountryCode.length === 2) {
            sCountryCode = EC_ADDR_COUNTRY_CONVERT_MAP.aCountryDomainToCodeMap[sCountryCode];
        }

        if (sCountryCode !== 'USA') {
            return;
        }

        try {
            var sStateName = $('#stateListUs').val();
            $('#__state_name').val(sStateName);
        } catch(e) {}
    }

    /**
     * 캐나다 주/도 선택 값 설정
     */
    function setCountryCaStateNameValue() {
        var sCountryCode = $('#country').val();
        // 국가코드가 2자리일 경우 3자리로 변환
        if (sCountryCode.length === 2) {
            sCountryCode = EC_ADDR_COUNTRY_CONVERT_MAP.aCountryDomainToCodeMap[sCountryCode];
        }

        if (sCountryCode !== 'CAN') {
            return;
        }

        try {
            var sStateName = $('#stateListCa').val();
            $('#__state_name').val(sStateName);
        } catch(e) {}
    }

    /**
     * 영문몰 state_name 유효성 체크
     * @returns {boolean}
     */
    function checkUsStatename()
    {
        if ((typeof common_aAddrInfo === 'object' && common_aAddrInfo['sIsRuleBasedAddrForm'] === 'T') || SHOP.getLanguage() != 'en_US') {
            return true;
        }

        if ($('#display_required_address').val() != 'T') {
            return true;
        }

        try {
            var sCountry = $('#country').val();
            var bIsEmptyStatenameValue = true;
            var sStateNameId = 'state_name';

            if (sCountry == 'USA') {
                sStateNameId = 'stateListUs';
            } else if (sCountry == 'CAN') {
                sStateNameId = 'stateListCa';
            }

            if ($('#' + sStateNameId).val() == '') {
                $('#' + sStateNameId).focus();
                bIsEmptyStatenameValue = false;

                if (sStateNameId == "state_name") {
                    alert(sprintf(__('IS.REQUIRED.FIELD', 'MEMBER.RESOURCE.JS.COMMON'), $('#' + sStateNameId).attr('fw-label')));
                } else {
                    alert(__('SELECT.STATE.PROVINCE', 'MEMBER.RESOURCE.JS.COMMON'));
                }
            }

            return bIsEmptyStatenameValue;
        } catch(e) {}

        return true;
    }

    /**
     * 국가 변경시 휴대전화, 일반전화 국가 코드 변경
     */
    function setSelectedPhoneCountryCode()
    {
        if (typeof(oCountryVars) != "object") {
            return;
        }

        if ($('#country').length < 1) {
            return;
        }

        var sCode = $('#country').val();
        // 국가코드가 2자리일 경우 3자리로 변환
        if (sCode.length === 2) {
            sCode = EC_ADDR_COUNTRY_CONVERT_MAP.aCountryDomainToCodeMap[sCode];
        }
        var sDialingCode = parseInt(oCountryVars[sCode].d_code, 10);
        var sCountryName = oCountryVars[sCode].country_name_en;
        var aMultiplCode = [1, 7, 262];
        var oFilter = eval("/" + sCountryName + "/ig");

        // 나라별 국번이 동일하면
        if ($.inArray(sDialingCode, aMultiplCode) >= 0) {
            if ($("#mobile1").length > 0) {
                $("#mobile1>option").each(function() {
                    if (oFilter.test($(this).text()) == true) {
                        $(this).attr("selected", true);
                    }
                });
            }
            if ($("#phone1").length > 0) {
                $("#phone1>option").each(function() {
                    if (oFilter.test($(this).text()) == true) {
                        $(this).attr("selected", true);
                    }
                });
            }
        } else {
            if ($("#mobile1").length > 0) { $("#mobile1").val(sDialingCode); }
            if ($("#phone1").length > 0) { $("#phone1").val(sDialingCode); }
        }

    }

    /**
     * 국가 변경시 실행 필요한 설정
     */
    function setChangeCountry()
    {
        setFindZipcode();

        try {
            // 일문 주소 readonly 설정
            zipcodeCommonController.setJapanCountryAddr1($(this).val(), $('#addr1'), $('#postcode1'));
        } catch (e) {
        }

        try {
            if (isCountryOfLanguage == 'T') {
                setAddressOfLanguage.changeCountry();
            }
        } catch (e) {}
        this.setUsStateNameVisible();
        this.setSelectedPhoneCountryCode();
    }

    /**
     * 메일 입력 폼 기존 하드코딩 되어 있을 경우 동작
     */
    function bindEmail()
    {
        if ($('#email3').length < 1) {
            return;
        }

        if ($('#email2').length < 1) {
            return;
        }

        $('#email3').bind('change', function() {

            var host = this.value;

            if (host != 'etc' && host != '') {
                $('#email2').attr('readonly', true);
                $('#email2').val(host).change();
            } else if (host == 'etc') {
                $('#email2').attr('readonly', false);
                $('#email2').val('').change();
                $('#email2').focus();
            } else {
                $('#email2').attr('readonly', true);
                $('#email2').val('').change();
            }

        });
    }

    /**
     * <a href="url" oncolick="memberCommon.agreementPopup(this)"/>
     * url 정보를 읽어 팝업을 띄운다
     */
    function agreementPopup(oALinkObject)
    {
        var sPopupUrl = oALinkObject.href;
        if (EC_MOBILE_DEVICE == true) {
            window.open(sPopupUrl);
        } else {
            window.open(sPopupUrl, '', 'width=450,height=350');
        }
    }

    /**
     * 룰셋 기반 UI에서 주소 데이터 셋팅 (수정 페이지)
     */
    function setAddrDataOfRuleBase()
    {
        if (typeof common_aAddrInfo === 'object' && common_aAddrInfo['sIsRuleBasedAddrForm'] !== 'T') {
            return;
        }

        var sPageType = 'fmodify';
        var sCountryCode = $('#country').val();
        if (common_aAddrInfo.aAllCountryFormat[sCountryCode] === undefined) {
            sCountryCode = 'DEFAULT';
        }

        setAreaAddr(sPageType, sCountryCode);
        setZipcodeConfig(sPageType, sCountryCode);
    }

    /**
     * Select 항목에 대해서 저장된 값을 selected 합니다.
     * 1) State 리스트의 값 설정 (미국, 캐나다)
     * 2) Selectbox로 주소 검색하는 국가(중국, 대만, 베트남, 필리핀)에 대해서 리스트의 값 설정
     * @param sPageType
     * @param sCountryCode
     */
    function setAreaAddr(sPageType, sCountryCode)
    {
        var aAreaHiddenData = [];
        aAreaHiddenData['sStateName'] = $("#__state_name").val();
        aAreaHiddenData['sCityName'] = $("#__city_name").val();
        aAreaHiddenData['sStreetName'] = $("#__addr1").val();

        // Area가 아니면서 state를 Selectbox로 제공하는 경우 (ex : 미국, 캐나다)
        var aIsAreaAddr = EC_ADDR_COMMONFORMAT.getConfigIsAreaAddr(sPageType);
        if (aIsAreaAddr.sIsAreaAddr === 'F'
            && (typeof common_aAddrInfo.aAllCountryFormat[sCountryCode].select !== 'undefined'
            && common_aAddrInfo.aAllCountryFormat[sCountryCode].select.indexOf('state') > 0)) {
            EC_ADDR_COMMONFORMAT.setStateSelected(sCountryCode, sPageType, aAreaHiddenData['sStateName']);
        } else { // Area인 경우 (ex : 중국, 대만, 베트남 ... )
            EC_ADDR_COMMONFORMAT.setAreaAddrSelected(sCountryCode, sPageType, aAreaHiddenData);
        }
    }

    /**
     * 해당 국가 포맷에 disabled, checked가 정의되어 있고 우편번호가 저장되어 있다면,
     * 우편번호 inputbox의 disabled와 checkbox의 checked를 해제
     * @param sPageType
     * @param sCountryCode
     */
    function setZipcodeConfig(sPageType, sCountryCode)
    {
        var bIsExistZipcodeVal = !!$('#postcode1').val();

        // 포맷에 disabled, checked 존재여부 확인
        var isHasDisabled = common_aAddrInfo.aAllCountryFormat[sCountryCode].hasOwnProperty("disabled");
        var isHasChecked = common_aAddrInfo.aAllCountryFormat[sCountryCode].hasOwnProperty("checked");
        if (isHasDisabled === false || isHasChecked === false) {
            return false;
        }

        // zipcode inputbox와 checkbox 존재여부 확인
        if (common_aAddrInfo.aAllCountryFormat[sCountryCode].checked.indexOf('zipcodeCheck') < 0
            || common_aAddrInfo.aAllCountryFormat[sCountryCode].disabled.indexOf('zipcode') < 0
            || bIsExistZipcodeVal === false) {
            return false;
        }

        EC_ADDR_COMMONFORMAT.unblockedZipcodeField(sPageType);
    }

    /*
     * 선택항목 약관 체크
     */
    function optionalCheck()
    {
        // 개인정보 수집 및 이용 동의(선택)
        if ($('#display_agree_privacy_optional_check_flag').val() != "T") {
            return true;
        }


        if ($('input[name="agree_privacy_optional_check[]"]').is(":checkbox") === true) {
            if ($("input[name='agree_privacy_optional_check[]']").is(":checked") === true) {
                return true;
            }
        } else if ($('input[name="agree_privacy_optional_check[]"]').length > 0) {
            if ($("input[name='agree_privacy_optional_check[]']").val() == "T") {
                return true;
            }
        }

        var isConfirm = true;
        $.each(registerOptionalList, function(sKey1, sValue1) {
            // 존재하는지 확인
            if ($("#"+sValue1.sDomId).length < 1) {
                return true;
            }

            // 회원 정보 입력 항목 필수 상태 값으로 처리
            if (sValue1.hasOwnProperty('is_required') === true) {
                // 필수 항목 제외
                if (sValue1.is_required == "T") {
                    return true;
                }
            }

            // 필수처리 dom 존재 확인
            if ($("#"+sValue1.is_required_dom).length > 0) {
                // 필수 항목 제외
                if ($("#"+sValue1.is_required_dom).val() == "T") {
                    return true;
                }
            }

            // data 등록 했는지 확인
            if (sValue1.sDomId.isArray === true) {
                $.each(sValue1.sDomId, function (sKey2, sValue2) {
                    if (memberCommon.issetOptionalElementValue(sValue2, sValue1.default_value) === false) {
                        isConfirm = false;
                        return false;
                    }
                });
            } else {
                if (memberCommon.issetOptionalElementValue(sValue1.sDomId, sValue1.default_value) === false) {
                    isConfirm = false;
                    return false;
                }
            }
        });

        if (isConfirm === false) {
            if (confirm(__('DO.NOT.AGREE.TERMS', 'MEMBER.RESOURCE.JS.COMMON')) === true) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * 객체 type 확인 후 값 확인
     * @param sSelector dom
     * @param sDefaultValue 기본 값
     * @returns {boolean} 결과
     */
    function issetOptionalElementValue(sSelector, sDefaultValue)
    {
        if (sSelector.length < 1) {
            return true;
        }

        if ($("#"+sSelector).is(":radio") === true || $("#"+sSelector).is(":checkbox") === true) {
            if ($("#"+sSelector).is(":checked") === true) {
                if ($("#"+sSelector).val() == sDefaultValue) {
                    return true;
                }
                return false;
            }
        }

        if ($("#"+sSelector).val() != "") {
            if ($("#"+sSelector).val() == sDefaultValue) {
                return true;
            }
            return false;
        }

    }

    return {
        oAgreeCheckbox: oAgreeCheckbox,
        oMarketingAgreeCheckbox: oMarketingAgreeCheckbox,
        oMarketingAgreeAllChecked: oMarketingAgreeAllChecked,
        agreeAllChecked: agreeAllChecked,
        marketingAgreeAllCheckboxIsChecked: marketingAgreeAllCheckboxIsChecked,
        marketingAllChecked: marketingAllChecked,
        agreeAllUnChecked: agreeAllUnChecked,
        marketingRemoveFilter: marketingRemoveFilter,
        eachCheckboxAgreeAllChecked: eachCheckboxAgreeAllChecked,
        checkJoinMobile: checkJoinMobile,
        checkEditMobile: checkEditMobile,
        isJoinMobileValidPassConditionCheck: isJoinMobileValidPassConditionCheck,
        isEditMobileValidPassConditionCheck: isEditMobileValidPassConditionCheck,
        isJoinPhoneValidPassConditionCheck: isJoinPhoneValidPassConditionCheck,
        isEditPhoneValidPassConditionCheck: isEditPhoneValidPassConditionCheck,
        checkJoinPhone: checkJoinPhone,
        checkEditPhone: checkEditPhone,
        isValidPhone: isValidPhone,
        isValidMobile: isValidMobile,
        setUsStateNameVisible: setUsStateNameVisible,
        setCountryUsStateNameValue: setCountryUsStateNameValue,
        setCountryCaStateNameValue: setCountryCaStateNameValue,
        checkUsStatename: checkUsStatename,
        setChangeCountry: setChangeCountry,
        setSelectedPhoneCountryCode: setSelectedPhoneCountryCode,
        bindEmail: bindEmail,
        agreementPopup: agreementPopup,
        optionalCheck: optionalCheck,
        issetOptionalElementValue: issetOptionalElementValue,
        setAddrDataOfRuleBase: setAddrDataOfRuleBase,
        checkZipcode: checkZipcode
    };
})();


// 이메일 중복 체크 여부
var bCheckedEmailDupl = false;
// 아이디 중복체크 공통 url
var sIdDuplicateCheckUrl = '';

$(document).ready(function(){
    // Moment 스크립트 초기화
    EC_GLOBAL_DATETIME.init(function () {});

    $('[onclick^="findAddress"]').attr('onclick','').unbind('click');
    $('[onclick^="findAddress"]').bind('click', {
            'zipId1' : 'postcode1',
            'zipId2' : 'postcode2',
            'addrId' : 'addr1',
            'cityId' : '',
            'stateId' : '',
            'type' : 'mobile',
            'sLanguage' : SHOP.getLanguage(),
            'addrId2' : ''
        }, ZipcodeFinder.Opener.Event.onClickBtnPopup);
    
    // 회원가입 설정 항목 필수 아이콘 숨김 처리 - ECHOSTING-115627
    $(':hidden[name^="display_required_"]').each(function (i) {
        bDisplayFlag = ($(this).val() == 'T') ? true : false;
        sExtractId = $(this).attr('id').substr(17);

        if (sExtractId == 'bank_account_no') { // 환불계좌 쪽은 id값이 매칭이 되지 않아 예외 처리
            sDisplayTargetId = 'icon_is_display_bank';
        } else if (sExtractId == 'name_phonetic') { // 이름 발음 쪽은 id값이 매칭이 되지 않아 예외 처리
            sDisplayTargetId = 'icon_phonetic';
        } else {
            sDisplayTargetId = 'icon_' + sExtractId;
        }

        // 한국어 몰은 이름 항목은 무조건 '필수' 
        if (SHOP.getLanguage() == 'ko_KR' && sDisplayTargetId == 'icon_name') {
            bDisplayFlag = true;
        }

        if (bDisplayFlag == false) {
            $('#' + sDisplayTargetId).hide();
        } else {
            $('#' + sDisplayTargetId).show();
        }
    });

    if (typeof common_aAddrInfo === 'object' && common_aAddrInfo['sIsRuleBasedAddrForm'] !== 'T') {
        $('#nozip').bind('change', function () {
            if ($(this).is(':checked') == true) {

                $('#postcode1').attr("disabled", true);
                //주소정보 초기화
                $('#postcode1').val("");
                $('#addr1').focus();
                if (SHOP.getLanguage() == 'en_US') {
                    return;
                }

                //우편번호 백업
                $('#postcode1').attr('backup_postcode', $('#postcode1').val());

                //주소정보 초기화
                $('#postcode2, #addr1, #city_name, #state_name, #__addr1, #__city_name, #__state_name').val("");
                if (SHOP.getLanguage() != 'vi_VN') {
                    $('#addr2').val("");
                }

                //우편번호 버튼 비활성
                $('#postcode1, #addr1').removeAttr("readonly").val('');

                $('#postBtn').attr('onclick', '').unbind('click').css('cursor', 'unset');
                $('#SearchAddress').attr('src', $('#SearchAddress').attr('off'));
            } else {
                $('#postcode1').removeAttr("disabled");
                //주소정보 초기화
                $('#postcode1').val("");
                if (SHOP.getLanguage() == 'en_US') {
                    return;
                }

                //우편번호 버튼 활성화
                $('#postcode2, #addr1').val('');

                $('#postBtn').bind('click', {
                    'zipId1' : 'postcode1',
                    'zipId2' : 'postcode2',
                    'addrId' : 'addr1',
                    'cityId' : 'city_name',
                    'stateId' : 'state_name',
                    'type' : 'layer',
                    'sLanguage' : SHOP.getLanguage(),
                    'addrId2' : 'addr2'
                }, ZipcodeFinder.Opener.Event.onClickBtnPopup);
                $('#postBtn').css('cursor','pointer');
                $('#SearchAddress').attr('src', $('#SearchAddress').attr('on'));
                setFindZipcode();
            }
        });
    }

    $('#direct_input_postcode1_addr0').bind('change', function(){
        var oPostBtn = $("#postBtn");
        var oPostcode1 = $("#postcode1");
        var oAddr1 = $("#addr1");
        oPostcode1.val('');
        oAddr1.val('');
        if ($(this).is(':checked') == true) {
            oPostBtn.hide();
            oPostcode1.attr('readonly', false);
            oAddr1.attr('readonly', false);
        } else {
            oPostBtn.show();
            oPostcode1.attr('readonly', true);
            oAddr1.attr('readonly', true);
        }
    });
    try {
        if (mobileWeb == true && $('#mobilemailduplecheckbutton').length > 0) {
            if ($("#useCheckEmailDuplication").val() == "T") {
                $('#mobilemailduplecheckbutton').css('display', '');
            }
            else {
                $('#mobilemailduplecheckbutton').css('display', 'none');
            }
        }
    } catch (e) {}


    // 닉네임 체크
    $('#nick_name').bind('blur', function(){
        checkNick();
    });

    // 이메일 중복 체크
    $('#email1').bind('change', function() {

        // 국내몰일 경우 이메일 중복 체크 기능을 사용하는 경우에만 호출.
        if (SHOP.getLanguage() == 'ko_KR') {
            if ($("#useCheckEmailDuplication").val() == "T") {
                setDuplEmail();
            }
        }
        // 해외 몰일경우 그냥 호출.
        else {
            setDuplEmail();
        }

    });

    // 이메일 중복 체크
    $('#etc_subparam_email1').bind('change', function() {

        // 국내몰일 경우 이메일 중복 체크 기능을 사용하는 경우에만 호출.
        if ( SHOP.getLanguage() == 'ko_KR' ) {
            if ( $("#useCheckEmailDuplication").val() == "T" ) { setDuplEmail(); }
        }
        // 해외 몰일경우 그냥 호출.
        else {
            setDuplEmail();
        }

    });
    
    if (SHOP.getLanguage() == 'ko_KR') {
        $('#email2').bind('change', function() {
            if ($("#useCheckEmailDuplication").val() == "T") {
                setDuplEmail();
            }
        });
    }

    function setDuplEmail() {
        if ($('#email2').length > 0) {
            var sEmail = $('#email1').val() + '@' + $('#email2').val();
        } else {
            var sEmail = $('#email1').val();
        }

        if ($('#email1').val() != undefined) {

            if ($('#email1').val().length == 0) {
                $('#emailMsg').addClass('error').html(__('이메일을 입력해 주세요.'));
                return false;
            } else {
                if (FwValidator.Verify.isEmail(sEmail) == false || sEmail.length > 255) {
                    $('#emailMsg').addClass('error').html(__('유효한 이메일을 입력해 주세요.'));
                    return false;
                }
            }
        }

        if ( $('#etc_subparam_email1').val() != undefined && SHOP.getLanguage() != 'ko_KR') {

            var sEmail = $('#etc_subparam_email1').val();

            if ($('#etc_subparam_email1').val().length == 0 ) {
                $('#emailMsg').addClass('error').html(__('이메일을 입력해 주세요.'));
                return false;
            } else {
                if (FwValidator.Verify.isEmail(sEmail) == false || sEmail.length > 255) {
                    $('#emailMsg').addClass('error').html(__('유효한 이메일을 입력해 주세요.'));
                    return false;
                }
            }
        }
        checkDuplEmail();
    }

    if (SHOP.getLanguage() != 'ko_KR' && $('#idMsg').length > 0) {
        $('#idMsg').html(__('아이디는 영문소문자 또는 숫자 4~16자로 입력해 주세요.'));
    }

    if (SHOP.getLanguage() != 'ko_KR' && $('#emailMsg').length > 0) {
        if ($('#login_id_type').val() == 'email') {
            $('#emailMsg').html(__('로그인 아이디로 사용할 이메일을 입력해 주세요.'));
        }
    }

    // 아이디 중복 체크
    $('#joinForm').find('#member_id').bind('blur', function(){
        //if ( SHOP.getLanguage() == 'ko_KR' ) return;
        if (mobileWeb) return;
        checkDuplId();
    });

    // 아이디 중복 체크
    $('#etc_subparam_member_id').bind('blur', function(){
        //if ( SHOP.getLanguage() == 'ko_KR' ) return;
        if ( mobileWeb ) return;
        checkDuplId();
    });

    // 비밀번호 확인 체크
    $('#user_passwd_confirm').bind('blur', function() {
        if ($('#pwConfirmMsg').length < 1) return;
        if ($('#user_passwd_confirm').val() == '' && $('#passwd').val() == '') return;
        checkPwConfirm('user_passwd_confirm');
    });

    // 비밀번호 확인 체크
    $('#etc_subparam_user_passwd_confirm').bind('blur', function(){
        if ( $('#pwConfirmMsg').length < 1 ) return;
        if ( $('#etc_subparam_user_passwd_confirm').val() == '' && $('#etc_subparam_passwd').val() == '') return;
        checkPwConfirm('etc_subparam_user_passwd_confirm');
    });

    EC$('#cssn').on('blur', function(){
        if (EC$('#cssn').val() == '') return;

        if (EC$('#use_checking_cssn_duplication').val() == 'F') {
            checkCssnValid(EC$('#cssn').val());
        }
    });

    EC$('#cssn').on('change', function() {
        if (EC$('#use_checking_cssn_duplication').val() == 'T') {
            EC$('#cssnDuplCheck').val('F');
        }
    });

    // 국가선택시
    $('#country').bind('change', function(){
        try {
            memberCommon.setChangeCountry();
        } catch(e) {}
    });

    //주소입력시 입력값 동기화
    $('#addr1, #city_name, #state_name').bind('change', function() {
        $('#__'+$(this).attr('id')).val($(this).val());
    });

    $('#stateListUs').bind('change', function() {
        memberCommon.setCountryUsStateNameValue();
    });

    $('#stateListCa').bind('change', function() {
        memberCommon.setCountryCaStateNameValue();
    });

    $('#bank_account_no').keyup(function(){
        filterBankAccountNo($(this));
    });

    $('#bank_account_no').blur(function(){
        filterBankAccountNo($(this));
    });

    try {
        memberCommon.bindEmail();
    } catch(e) {}

    function filterBankAccountNo(oObj)
    {
        var iLimit = 50;
        var value = oObj.val();
        if (/^[\-0-9]+$/.test(value) == false) {

            value = value.replace(/[^0-9\-]/g, '');
            value = value.substr(0, 1) + value.substr(1).replace(/[^\-0-9]/g, '');

            if (value.length > iLimit) {
                value = value.substr(0, iLimit);
            }

            oObj.val(value);
        } else {
            if (value.length > iLimit) {
                value = value.substr(0, iLimit);
                oObj.val(value);
            }
        }
    }

    //ECHOSTING-16798 새로 추가된 모바일 인증 HTML 없을경우 기존 회원인증 로직 숨김 처리
    if (mobileWeb) {
        if ($('#member_name_cert_flag').val() == 'T'
            && $('#is_mobile_auth_use').val() == 'T'
            && $('#realNameEncrypt').val() == '') {
            if (!$("#authMember").get(0)) {
                if ($("#is_ipin_auth_use").val() == "F") {
                    $("#auth_tr").empty();
                    $("#ipin_tr").css('display', 'none');
                    $("#name_tr").css('display', 'table-row');
                    $("#name_tr").find("td").empty().append('<input id="name" name="name" fw-filter="isFill&amp;isMax[20]" fw-label="이름" fw-msg="" class="inputTypeText" maxlength="20" value="" type="text" autocomplete="off">');
                } else if ($("#is_ipin_auth_use").val() == "T") {
                    //아이핀 인증 사용중이면서 디자인가이드가 추가 안되었을 때 휴대폰 인증 삭제 처리
                    $("#auth_tr").find("input[value='m']").next().remove().end().remove();
                }
                
            }
            
        }
        
    }
    
    //  회원가입 페이지 내디폴트 인증수단
    if ($("#default_auth_reg_page_flag").get(0)) {

        // 아이핀, 휴대폰 인증 둘다 존재할때
        if ($("#ipinWrap").get(0) && $("#mobileWrap").get(0)) {

            var sDefaultAuth = $("#default_auth_reg_page_flag").val();
            $("input[name='personal_type']").attr("checked", "");

            if (sDefaultAuth == "I") {
                $("input[name='personal_type'][value='i']").attr("checked", "checked");
            }

            if (sDefaultAuth == "H") {
                $("input[name='personal_type'][value='m']").attr("checked", "checked");

                $('#ipinWrap').hide();
                $('#mobileWrap').show();
                $('#emailWrap').hide();
            }

            // 둘다 없을때는 디폴트
            if ($("input[name='personal_type']:checked").length <= 0) {
                $("input[name='personal_type'][value='i']").attr("checked", "checked");
            }

            // 기본설정이 아이핀이고, 아이핀설정을 사용하지않을경우 모바일 셋팅으로
            if ($("#is_ipin_auth_use").val() == "F" && sDefaultAuth == "I") {
                $("input[name='personal_type'][value='m']").attr("checked", "checked");

                $('#ipinWrap').hide();
                $('#mobileWrap').show();
                $('#emailWrap').hide();
            }
            // ECHOSTING-89438 이메일 인증 디폴트 처리
            if (sDefaultAuth == "E") {
                $("input[name='personal_type'][value='e']").attr("checked", "checked");

                $('#ipinWrap').hide();
                $('#mobileWrap').hide();
                $('#emailWrap').show();
            }
        }
    }

    if (SHOP.getLanguage() != 'ko_KR') {
        try {
            setAddressOfLanguage.joinInit();
        } catch (e) {}

        try {
            memberCommon.setChangeCountry();
        } catch (e) {}
    }

    // ECHOSTING-89438 외국인 이름 설정
    $('#foreigner_name').bind('blur', function(){
        if ($('input[name=foreigner_type]:checked').val() == 'e') {
            $('#nameContents').html($('#foreigner_name').val());
        }
    });

    /**
     * ECHOSTING-349292 대응
     * 중/대/일/베트남 우편 번호 검색 폼 대응 이벤트 바인딩
     * 주문서 페이지에서 memberJoin.js / addr.js 의 바인딩이 2중으로 존재하여 이벤트가 2회 발생하므로,
     * 해당 두 파일에서는 주문서 페이지 일경우 바인딩 하지 않도록 예외처리하고, 주문서 페이지 용으로 이벤트 바인딩 별도 추가
     */
    if (typeof common_aAddrInfo === 'object' && common_aAddrInfo['sIsRuleBasedAddrForm'] !== 'T') {
        $('#si_name_addr').bind('change', function () {
            setAddressOfLanguage.setZipcode(this);
            setAddressOfLanguage.setLastZipcode();
        });
        $('#ci_name_addr').bind('change', function () {
            setAddressOfLanguage.setZipcode(this);
            setAddressOfLanguage.setLastZipcode();
        });
        $('#gu_name_addr').bind('change', function () {
            setAddressOfLanguage.setZipcode('last');
            setAddressOfLanguage.setLastZipcode();
        });
    }
    try {
        setAddressCommon.setUseCountryNumberModifyUi($('#phone1'), $('#mobile1'));
    } catch(e) {}

    // 약관 동의 관련 함수들
    try {
        // sms, email 수신동의 필수 입력 제거
        memberCommon.marketingRemoveFilter();

        // 약관 전체 동의 체크
        $('input:checkbox[id="sAgreeAllChecked"]').bind('change', function () {
            memberCommon.agreeAllChecked();
        });

        // 모바일 마케팅 영역 약관 전체 체크
        $('input:checkbox[id="sMarketingAgreeAllChecked"]').bind('change', function () {
            memberCommon.marketingAllChecked();
        });

        // 모바일 마케팅 영역 each 체크
        $.each(memberCommon.oMarketingAgreeCheckbox, function (i, oVal) {
            if (oVal.length < 1) {
                // continue
                return true;
            }
            oVal.obj.bind('change', function () {
                memberCommon.marketingAgreeAllCheckboxIsChecked();
            });
        });

        // 전체 약관 each 체크
        $.each($('.agreeArea'), function (i, oVal) {
            if (($(oVal).hasClass('displaynone')) === true) {
                return true;
            }

            $.each($(oVal).find("input:checkbox"), function (j, oVal2) {
                $(oVal2).bind('change', function () {
                    memberCommon.eachCheckboxAgreeAllChecked();
                });
            });
        });

        // each 전체 동의 체크 언체크
        $.each(memberCommon.oAgreeCheckbox, function (i, oVal) {
            if (oVal.obj.length < 1) {
                // continue
                return true;
            }

            oVal.obj.bind('change', function () {
                sIsUnchecked = memberCommon.agreeAllUnChecked(oVal.obj);
                if (sIsUnchecked == "T") {
                    return false;
                }
            });
        });
    } catch(e) {}
});


var globalJoinData = [];
var essn_array = null;
var check_nick_name_essn = false;
var iRerun = 0;

// 해당국가 외에는 직접 우편번호를 넣는다.
function setFindZipcode()
{
    if (typeof common_aAddrInfo === 'object' && common_aAddrInfo['sIsRuleBasedAddrForm'] === 'T') {
        return;
    }

    var sCountry = $('#country').val();
    var sLanguage = SHOP.getLanguage();

    //주소정보 초기화
    $('#postcode1, #postcode2, #addr1, #city_name, #state_name, #__addr1, #__city_name, #__state_name').val("");
    
    if (SHOP.getLanguage() != 'vi_VN') {
        $('#addr2').val("");
    }

    //우편번호 복원
    $('#postcode1').val($('#postcode1').attr('backup_postcode'));

    //멀티샵언어와 국가정보가 일치하는지 체크
    if ( ( sLanguage == 'zh_CN' && ( sCountry != 'CHN' && sCountry != 'TWN') ) ||
        ( sLanguage == 'ja_JP' && sCountry != 'JPN') ||
        ( sLanguage == 'zh_TW' && sCountry != 'TWN') ) {

        $('#SearchAddress').hide();
        if (mobileWeb == true) {
            $('#postBtn').hide();
        }

    } else {
        if ( sLanguage != 'en_US' && sLanguage != 'es_ES' && sLanguage != 'pt_PT') {
            if ($('#nozip').attr('checked') == true) {
                $('#nozip').attr('checked', false).change();
                $('#nozip').attr('checked', false);
            }

            $('#SearchAddress').show();
            if (mobileWeb == true) {
                $('#postBtn').show();
            }
            $('tr:has(td:has(#city_name)), tr:has(td:has(#state_name))').hide();
        }
    }
}

/**
 * 회원가입하기 개인정보 이용약관 체크박스 확인 후 회원가입페이지로 이동
 * @returns void
 */
function checkAgreement( sUrl )
{
    var checkAgree = [];
    $("input[type='checkbox']").each(function(){
        var attrName = $(this).attr('name');
        var bAgree = /agree_service_check/ig.test( attrName );
        var bPerson = /agree_privacy_check/ig.test( attrName );
        var bPerson = /agree_privacy_check/ig.test( attrName );
        if ( bAgree ) {
            if ( $(this).attr("checked")  ) {
                checkAgree[0] = "";
            } else {
                checkAgree[0] = $(this).attr("fw-msg");
            }
        }
        if ( bPerson )  {
            if ( $(this).attr("checked")  ) {
                checkAgree[1] = "";
            } else {
                checkAgree[1] = $(this).attr("fw-msg");
            }
        }
    });
    if ( checkAgree[0] != "" ) {
        alert( checkAgree[0] );
        return false;
    }
    if ( checkAgree[1] != "" ) {
        alert( checkAgree[1] );
        return false;
    }

    /**
     * 모바일 회원가입일때 3자 정보제공동의 값을 회원가입폼으로 전달하기 위해 처리 by sskim02
     * @returns void
     */
    var isSubmit = "F";
    var sHidden = "";
    var $agree_information = $("input:checkbox[name='agree_information_check[]']");
    var $agree_consignment = $("input:checkbox[name='agree_consignment_check[]']");
    if (($agree_information.length > 0 && $agree_information[0].checked) || ($agree_consignment.length > 0 && $agree_consignment[0].checked)) {
        sHidden = '<input type="hidden" name="agree_information" value="'+($agree_information[0].checked ? '1':'') +'"/><input type="hidden" name="agree_consignment" value="'+($agree_consignment[0].checked ? '1' : '')+'"/>';
        isSubmit = "T";
    }

    var $agree_privacy_optional = $("input:checkbox[name='agree_privacy_optional_check[]']");
    if ($agree_privacy_optional.length > 0 && $agree_privacy_optional[0].checked) {
        sHidden += '<input type="hidden" name="agree_privacy_optional_check" value="'+($agree_privacy_optional[0].checked ? 'T':'') +'"/>';
        isSubmit = "T";
    }

    var oMarketingCheckbox = [
        {obj: $('input:checkbox[name="is_sms"]'), hiddenName: "is_sms_check"}, // sms 수신 동의
        {obj: $('input:checkbox[name="is_news_mail"]'), hiddenName: "is_news_mail_check"} // 이메일 수신 동의
    ];

    $.each(oMarketingCheckbox, function(i, oVal) {
        if (oVal.obj.length < 1) {
            // continue
            return true;
        }

        isSubmit = "T";
        if (oVal.obj.is(":checked") === true) {
            sHidden += '<input type="hidden" name="'+oVal.hiddenName+'" value="T" />';
        } else {
            sHidden += '<input type="hidden" name="'+oVal.hiddenName+'" value="F" />';
        }
    });


    if (isSubmit == "T") {
        $(document.body).append('<form id="formAgreement" method="post" action="' + sUrl + '">'+sHidden+'</form>');
        $('#formAgreement').trigger('submit');
        return false;
    }
    location.href = sUrl;
}


/**
 * submit 할 때 display none 되어 있는 부분 전부 지워버리고 submit
 * post value name 이 겹치지 않기 위해 삭제
 */
function memberJoinAction()
{
    // 백업 내용있을경우 원복을 한다
    for (var key in globalJoinData) {
        if (typeof globalJoinData[key] == 'object') {
            $('#'+key).attr("fw-filter", globalJoinData[key]['fw-filter']);
        }
    }

    // 감춤 영역의 fw-filter 설정을 백업 한다
    $('#joinForm [fw-filter*="is"]:not(:visible)').each(function(){
        globalJoinData[$(this).attr('id')] = {"fw-filter" : $(this).attr('fw-filter')};
        $(this).removeAttr("fw-filter");
    });

    //아이핀 인증 체크
    if (SHOP.getLanguage() === 'ko_KR' && $('#member_name_cert_flag').val() == 'T' && $('#is_ipin_auth_use').val() == 'T' && $('#realNameEncrypt').val() == '') {
        alert(__('회원 인증을 해주세요.'));
        return false;
    }

    // 휴대폰 인증 체크
    if (SHOP.getLanguage() == 'ko_KR' && $('#member_name_cert_flag').val() == 'T' && $('#is_mobile_auth_use').val() == 'T' && $('#realNameEncrypt').val() == '') {
        // 모바일일때 회원 모바일 인증 HTML 삽입되어 있는지 확인 후 모바일 인증체크, 기존 모바일인증 사용자 회원가입 정상 동작 때문
        if ( mobileWeb ) {
            if ( $("#authMember").get(0) ) { 
                alert(__('회원 인증을 해주세요.'));
                return false;
            }
        } else {
            alert(__('회원 인증을 해주세요.'));
            return false;
        }
    }

    //주민번호 검사
    //실명인증 안할때만 검사
    if ($('#is_display_register_ssn').val() == 'T' && $('input[name=member_type]:checked').val() == 'p' && $('#member_name_cert_flag').val() != 'T') {
        if ($('#ssn1').val() == '' || $('#ssn2').val() == ''){
            alert(__('주민등록번호를 입력 해주세요.'));
            $('#ssn1').focus();
            return false;
        }

        if (isSsn($('#ssn1').val(), $('#ssn2').val()) == false) {
            alert(__('올바른 주민등록번호를 입력해 주세요.'));
            $('#ssn1').focus();
            return false;
        }

    }

    // EC-14044
    if ($('input[id^="identification_check"]:visible').length > 0) {
        if ($('input[id^="identification_check"]:visible')[0].checked !== true) {
            $('input[id^="identification_check"]:visible')[0].focus();
            alert(__('고유식별정보 처리에 동의해 주세요.'));
            return false;
        }
    }
    // EC-14044
    if ($('input[id^="f_identification_check"]:visible').length > 0) {
        if ($('input[id^="f_identification_check"]:visible')[0].checked !== true) {
            $('input[id^="f_identification_check"]:visible')[0].focus();
            alert(__('고유식별정보 처리에 동의해 주세요.'));
            return false;
        }
    }

    //id 중복 체크
    if ($('#joinForm #member_id').val() != '' && $('#idDuplCheck').val() != 'T') {
        // ECHOSTING-198247 id 잘못되어진 패턴인경우에 대한 alert 문구 보완 
        var sMsg = '';
        // id 관련 에러 메시지가 있는경우만 띄워준다
        if ($("#idMsg").attr('id') =='idMsg' && $("#idMsg.error").attr('id')) {
            sMsg = $("#idMsg").text().split('.').join(".\n");
        }
        sMsg = (sMsg) ? sMsg : __('CHECK.FOR.DUPLICATE.IDS.001');
        alert(sMsg);
        $('#member_id').focus();
        return false;
    }
    
    if ($('#email1').val() == '' || $('#email2').val() == '') {
        alert(__('이메일을 입력하세요.'));

        if ($('#email1').val() == '')            $('#email1').focus();
        else if ($('#email2').val() == '')       $('#email2').focus();

        return false;
     }

    // // 이메일 input 정보가 존재할경우
    if ($('#email1').length > 0 && $('#email2').length > 0) {
        var sEmail = $('#email1').val()+'@'+$('#email2').val();
    } else {
        var sEmail = $('#email1').val();
    }

    if ($('#email1').val() != undefined) {
        if ((FwValidator.Verify.isEmail(sEmail) == false && sEmail != null) || sEmail.length > 255) {
            alert(__('입력하신 이메일을 사용할 수 없습니다.'));
            $('#email1').focus();
            return false;
        }
    }

    // 이메일 중복 체크 기능 사용하는경우 이메일 중복 확인이 되지 않으면 alert 메시지 띄워주고 폼전송 못하게 막는다.,
    if ($("#useCheckEmailDuplication").val() == "T" && bCheckedEmailDupl == false ) {
        alert( __("이미 가입된 이메일 주소입니다.\n쇼핑몰 가입여부를 다시 확인하여 주시거나 관리자에게 문의하여 주세요.") );
        return false;
    }

    /**
     * Email 중복체크 => checkDuplEmail()의 결과값 emailDuplCheck.val()
     *   국내몰일 경우는 상점관리 설정을 따라가고 (상점관리 > 운영방식설정 > 회원관련설정 > 이메일 중복 체크)
     *   해외몰일 경우는 회원가입항목 설정 > 가입 기준을 따라간다
     */
    if ($('#emailDuplCheck').val() != 'T') {

        if ( (SHOP.getLanguage() == 'ko_KR' && $('#use_email_confirm').val() == 'T') ||
             (SHOP.getLanguage() != 'ko_KR' && $('#login_id_type').val() == 'email')) {
            // 이메일 중복 확인 전 실행 방지 처리
            if ($('#emailDuplCheck').val() == '' && iRerun < 10) {
                iRerun++;
                setTimeout(function(){ memberJoinAction(); }, 500);
                return false;
            }
            alert(__('DUPLICATE.EMAIL.CHECK', 'MEMBER.FRONT.VALIDATION'));
            $('#email1').focus();
            return false;
        }
    }

    //별명체크 / 별명이 필수 일때만 체크함.
    //need to include memberJoinCheckNick.js
    if ($('#nick_name_flag').val() == 'T' && check_nick_name_essn== true ) {
        var aCheckNick = checkLength($('#nick_name').val());

        if ($('#nick_name_confirm').val() == 'F') {
            alert(__('별명이 잘못 되었습니다.'));
            $('#nick_name').focus();
            return false;
        }

        if (aCheckNick['passed'] == false) {
            alert(aCheckNick['msg']);
            $('#nick_name').focus();
            return false;
        }
    }

    // ECHOSTING-136604 직접 우편번호 입력시에는 입력내용에 대해 체크를 한다
    var bCheckKrZipcode = true;
    if ($('#direct_input_postcode1_addr0')) {
        if ($('#direct_input_postcode1_addr0').attr('checked')){ 
            if ($("#postcode1").val().match(/^[a-zA-Z0-9- ]{2,14}$/g) == null) {
                alert(__("우편번호는 영문, 숫자, 대시(-)만 입력가능합니다.\n입력내용을 확인해주세요."));
                $("#postcode1").focus();
                return false;
            }
            bCheckKrZipcode = false;
        }
    }

    // 주소 필수시 체크 ( 심플 가입이 아닐때만 ) 
    if ( $('#is_display_register_addr').val() == 'T'  && $("#useSimpleSignin").val() !='T' ) {
        
        if ( SHOP.getLanguage() == 'ko_KR') {
            if ( $('#postcode1').val() == '') {
                alert(__('주소를 입력해주세요'));
                $('#postcode1').focus();
                return false;
            }
        }

        if ( $('#display_required_address').val() == 'T' && $('#addr1').val() == '' ) {
            alert(__('주소를 입력해주세요'));
            var sisDesignPosibbleFlag = "F";
            if (SHOP.getLanguage() == 'zh_CN' || SHOP.getLanguage() == 'zh_TW') {
                sisDesignPosibbleFlag = setAddressOfLanguage.isDesignPosibbleController();
            }
            if (sisDesignPosibbleFlag == "F") {
                $('#addr1').focus();
            }
            return false;
        }
        
        if ( $('#display_required_address2').val() == 'T' && $('#addr2').val() == '' ) {
            alert(__('주소를 입력해주세요'));
            $('#addr2').focus();
            return false;
        }
    }

    // 우편번호 체크
    if (memberCommon.checkZipcode(bCheckKrZipcode) === false) {
        return false;
    }

    if ($('#is_display_register_name').val() == 'T' && $("#useSimpleSignin").val() !='T') {
        if (SHOP.getLanguage() != 'ko_KR') {
            if ($('#sUseSeparationNameFlag').val() == 'T' && $('#last_name').length < 1) {
                alert(sprintf(__('%s 항목은 필수 입력값입니다.'), __('이름')));
                return false;
            } else if ($('#sUseSeparationNameFlag').val() == 'T' && $('#last_name').length > 0) {
                if ($.trim($('#last_name').val()) == '') {
                    alert(sprintf(__('%s 항목은 필수 입력값입니다.'), __('이름')));
                    $('#last_name').focus();
                    return false;
                }
            }
        }
    }

    // 영문이름 체크
    if ( $('#is_display_register_eng_name').val() == 'T'  && $("#useSimpleSignin").val() !='T' ) {
        if ( $('#name_en').val() == '' && $('#name_en').length > 0) {
            alert(sprintf(__('%s를 입력해 주세요.'), __('이름(영문)')));
            $('#name_en').focus();
            return false;
        }

        if (SHOP.getLanguage() != 'ko_KR') {
            if ($('#sUseSeparationNameFlag').val() == 'T' && $('#last_name_en').length < 1) {
                alert(sprintf(__('%s를 입력해 주세요.'), __('이름(영문)')));
                return false;
            } else if ($('#sUseSeparationNameFlag').val() == 'T' && $('#last_name_en').length > 0) {
                if ($.trim($('#last_name_en').val()) == '') {
                    alert(sprintf(__('%s를 입력해 주세요.'), __('이름(영문)')));
                    $('#last_name_en').focus();
                    return false;
                }
            }
        }
    }

    // 이름(발음) 체크
    if ( $('#is_display_register_name_phonetic').val() == 'T'  && $("#useSimpleSignin").val() !='T' ) {
        if ( $('#name_phonetic').val() == '' && $('#name_phonetic').length > 0) {
            alert(sprintf(__('%s를 입력해 주세요.'), __('이름발음')));
            $('#name_phonetic').focus();
            return false;
        }

        if (SHOP.getLanguage() != 'ko_KR') {
            if ($('#sUseSeparationNameFlag').val() == 'T' && $('#last_name_phonetic').length < 1) {
                alert(sprintf(__('%s를 입력해 주세요.'), __('이름발음')));
                return false;
            } else if ($('#sUseSeparationNameFlag').val() == 'T' && $('#last_name_phonetic').length > 0) {
                if ($.trim($('#last_name_phonetic').val()) == '') {
                    alert(sprintf(__('%s를 입력해 주세요.'), __('이름발음')));
                    $('#last_name_phonetic').focus();
                    return false;
                }
            }
        }
    }

    if (memberCommon.checkUsStatename() === false) {
        return false;
    }

    // 일반전화 체크
    if (memberCommon.checkJoinPhone() === false) {
        return false;
    }

    // 휴대전화 체크
    if (memberCommon.checkJoinMobile() === false) {
        return false;
    }

    // 회원구분 타입에 따른 '이름(법인명)' 체크
    var sName = '';
    var sId   = '';
    if ($('#member_type0').attr('checked')) {
        // 개인회원
        
        if ($("input[name='personal_type']:checked").val() == 'e') sId = 'name';
        else if ($('#personal_type0').val() == 'i' || $('#personal_type0').val() == 'm') sId = ''; // 실명 인증으로 아이핀만 사용할 경우 예외 처리
        else if ( $('#personal_type0').val() == 'i' && $('#personal_type1').val() == 'm' ) sId = '';
        else if ($('#name').length) sId = 'name';
        else if ($('#personal_type0').attr('checked')) sId = 'real_name';

        if (sId != '' && ($('#is_display_register_name').val() == 'T' || $('#is_email_auth_use').val() == 'T') ) {
            sName = $.trim($('#'+sId).val());
            if (sName.length == 0) {
                alert(sprintf(__('%s 항목은 필수 입력값입니다.'), __('이름')));
                $('#'+sId).focus();
                return false;
            }
        }
        // 개인회원일때 국제 체크제거
        if ( $("#citizenship").get(0) ) {
            globalJoinData['citizenship'] = {"fw-filter" : $("#citizenship").attr('fw-filter')};
            $("#citizenship").removeAttr("fw-filter");
        }

    }
    else if ($('#member_type1').attr('checked')) {

        // 사업자회원
        if ($('#company_type0').attr('checked')) {

            // 개인사업자
            if ($('#personal_type0').val() == 'i' || $('#personal_type0').val() == 'm') sId = ''; // 실명 인증으로 아이핀만 사용할 경우 예외 처리
            else if ( $('#personal_type0').val() == 'i' && $('#personal_type1').val() == 'm' ) sId = 'name';
            else if (!$('#personal_type0').attr('name')) sId = 'name';
            else if ($('#personal_type0').attr('checked')) sId = 'real_name';

            if (sId != '' && $('#is_display_register_name').val() == 'T' ) {
                sName = $.trim($('#'+sId).val());
                if (sName.length == 0) {
                    alert(sprintf(__('%s 항목은 필수 입력값입니다.'), __('이름')));
                    $('#'+sId).focus();
                    return false;
                }

            }
            sCname = $.trim($('#cname').val());
            if (sCname.length == 0) {
                alert(__('상호명을 입력해 주세요.'));
                $('#cname').focus();
                return false;
            }
        } else if ($('#company_type1').attr('checked')) {
            // 법인사업자
            sName = $.trim($('#bname').val());
            if (sName.length == 0) {
                alert(__('법인명을 입력해 주세요.'));
                $('#bname').focus();
                return false;
            }
            
            var bssn1 = $('#bssn1').val();
            var bssn2 = $('#bssn2').val();
            var realNameEncrypt = $('#realNameEncrypt').val();
            
            if ($.trim(bssn1).length < 1 || $.trim(bssn2).length < 1 ) {
                alert( __('법인 번호를 입력하여 주세요.') );
                $('#bssn1').focus();
                return false;
            }
            if ($.trim(realNameEncrypt).length < 1) {                        
                alert( __('법인번호 중복체크를 해주세요.') );
                $('#bssn1').focus();
                return false;
            }            
        }

        sCssn = $.trim($('#cssn').val());
        if (sCssn.length == 0) {
            alert(__('사업자번호를 입력해 주세요.'));
            $('#cssn').focus();
            return false;
        }

        // 사업자번호 관련 에러 메시지가 있는 경우
        if (EC$("#cssnMsg").attr('id') =='cssnMsg' && EC$("#cssnMsg").hasClass('error')) {
            alert(EC$("#cssnMsg").text());
            EC$('#cssn').focus();
            return false;
        }

        // 중복 제한 체크 설정 했는데 체크 버튼을 클릭 안한 경우
        if (EC$('#use_checking_cssn_duplication').val() == 'T' && EC$('#cssnDuplCheck').val() == 'F') {
            alert(__('사업자번호 중복 체크를 해주세요'));
            EC$('#cssn').focus();
            return false;
        }

        // 개인회원일때 국제 체크제거
        if ( $("#citizenship").get(0) ) {
            globalJoinData['citizenship'] = {"fw-filter" : $("#citizenship").attr('fw-filter')};
            $("#citizenship").removeAttr("fw-filter");
        }
    } else if ($('#member_type2').attr('checked') && ($('#is_display_register_name').val() == 'T' || $('#is_email_auth_use').val() == 'T')) {
        //개인회원과 외국인회원 반복했을때 attr 지워진거 복구
        if ( globalJoinData['citizenship'] && globalJoinData['citizenship']['fw-filter'] ) {
            $("#citizenship").attr('fw-filter',globalJoinData['citizenship']['fw-filter'] || '');
        }

        // 외국인회원
        if ($("input[name='foreigner_type']:checked").val() == 'e') {
            sName = $.trim($('#foreigner_name').val());
            if (sName.length == 0) {
                alert(sprintf(__('%s 항목은 필수 입력값입니다.'), __('이름')));
                $('#foreigner_name').focus();
                return false;
            }
        }
        // ECHOSTING-89438 이메일 인증시 외국인 번호 체크 제외
        if ($('#is_display_register_name').val() == 'T' && $("input[name='foreigner_type']:checked").val() != 'e') {
            var foreignerType = $('input[name=foreigner_type]:checked').val();
            var foreignerSsn  = $('#foreigner_ssn').val();        
            var realNameEncrypt = $('#realNameEncrypt').val();
            var sType = '';

            if (foreignerType == 'f') sType = __('외국인 등록번호');
            else if (foreignerType == 'p') sType = __('여권번호');
            else if (foreignerType == 'd') sType = __('국제운전면허증번호');
            
            if ($.trim(foreignerSsn).length < 1) {
                alert(sprintf(__('%s를 입력해 주세요.'), sType));
                $('#foreigner_ssn').focus();
                return false;
            }
            
            if ($.trim(realNameEncrypt).length < 1) {                        
                alert(sprintf(__('%s 중복체크를 해주세요.'), sType));
                $('#foreigner_ssn').focus();
                return false;            
            }
        }
        
    } else {
        // 기본은 가입요청시 감춤영역의 fw-filter 값들은 백업한다
        // 감춤 영역의 fw-filter 설정을 백업 한다
        $('#joinForm .displaynone [fw-filter*="is"]').each(function(){
            globalJoinData[$(this).attr('id')] = {"fw-filter" : $(this).attr('fw-filter')};
            $(this).removeAttr("fw-filter");
        });
    }

    if (memberVerifyMobile.isMobileVerify() === false) {
        alert(__('VERIFY.YOUR.MOBILE.NUMBER', 'MEMBER.UTIL.VERIFY'));
        return false;
    }

    //날짜 체크
    var aCheckDateMap = [{'idPrefix' : 'birth', 'idName' : __('생년월일')}, {'idPrefix' : 'marry', 'idName' : __('결혼기념일')}, {'idPrefix' : 'partner', 'idName' : __('배우자 생일')}];

    for (var i = 0; i < aCheckDateMap.length; i++) {
        var bDateResult = checkDate(aCheckDateMap[i]['idPrefix'], aCheckDateMap[i]['idName']);
        if (bDateResult == false) return false;
    }

    // 환불계좌 정보 체크
    if ( $('#is_display_bank').val() == 'T'  && $("#useSimpleSignin").val() !='T' ) {
        if ($('#bank_account_owner').val() == '') {
            alert('예금주를 입력해주세요');
            $('#bank_account_owner').focus();

            return false;
        } else if ($('#refund_bank_code').val() == '') {
            alert('은행명을 선택해주세요');
               $('#refund_bank_code').focus();

               return false;
        } else if ($('#bank_account_no').val() == '') {
            alert('환불 계좌번호를 입력해주세요');
            $('#bank_account_no').focus();
            
            return false;
        }
    }
    
    // 추천인 ID 체크
    var sRecoId = $('#joinForm #reco_id').val();
    if ($.trim(sRecoId) != '') {
        if (sRecoId == $.trim($('#joinForm').find('#member_id').val())) {
            alert(__('자기자신을 추천인으로 등록할 수 없습니다.'));
            $('#joinForm #reco_id').focus();
            return false;
        }
    }

    if (validatePassword() === false) {
        return false;
    }

    var result = FwValidator.inspection('joinForm');

    if (result.passed == true) {
        if ($("#is_use_checking_join_info").val()==="T") {
            if (CheckingJoinInfo()===true) return false;
        }

        try {
            if (memberCommon.optionalCheck() === false) {
                return false;
            }
        } catch (e) {}

        // sns 가입창일경우 joinForm 진행하지 않는다
        if ($('#mCafe24SnsAgree').css('display') == 'block') {
            // sns 가입진행
            // snsJoin();
            memberSns.joinProc();
            return false;
        }
        $('#joinForm').submit();
    }
}

/**
 * 주민번호 검사
 * @param ssn1 주민번호 앞자리
 * @param ssn2 주민번호 뒷자리
 * @returns {Boolean}
 */
function isSsn( ssn1, ssn2 )
{
    check_arr = new Array( 2, 3, 4, 5, 6, 7, 8, 9, 2, 3, 4, 5 );
    buff = new Array();

    ssn_len = 13;
    ssn = ssn1 + ssn2;

    for ( i = 0; i < ssn_len; i++ ) {
        buff[i] = ssn.substr( i, 1 );
    }

    for ( i = sum = 0; i < 12; i++ ) {
        sum += ( buff[i] *= check_arr[i] );
    }

    if ( ( ( 11 - ( sum % 11 ) ) % 10 ) != buff[12] )
        return false;

    return true;
}



/**
 * 유선전화
 * @param sElementName 체크 할 엘리먼트 id
 */
function checkPhone(sElementName)
{
    var sFirstNumber = $('#' + sElementName + '2').val();//국번
    var sLastNumber = $('#' + sElementName + '3').val();//뒷번호

    var regexp = /^\d{3,4}$/;
    var bResultFirst = regexp.test(sFirstNumber);

    regexp = /^\d{4}$/;
    var bResultLast = regexp.test(sLastNumber);

    return ((bResultFirst && bResultLast));
}

/**
 * 휴대전화 체크
 * @param sElementName 체크 할 엘리먼트 id
 */
function checkMobile(sElementName)
{

    var sTelComp = $('#' + sElementName + '1').val();//통신사
    var sFirstNumber = $('#' + sElementName + '2').val();//국번
    var sLastNumber = $('#' + sElementName + '3').val();//뒷번호

    var regexp = /^\d{3}$/;
    var bResultTelComp = regexp.test(sTelComp);

    var regexp = /^\d{3,4}$/;
    var bResultFirst = regexp.test(sFirstNumber);

    regexp = /^\d{4}$/;
    var bResultLast = regexp.test(sLastNumber);

    return ((bResultTelComp && bResultFirst && bResultLast));
}


/**
 * 생일, 결혼기념일, 배우자 생일 체크
 * @param string sIdPrefix 검사항목의 id prefix
 * @param string sIdName alert 에 띄울 항목명
 * @returns {Boolean}
 */
function checkDate(sIdPrefix, sIdName)
{
    if ($('#' + sIdPrefix + '_year').length == 0 || $('#' + sIdPrefix + '_month').length == 0 || $('#' + sIdPrefix + '_day').length == 0) {
        return true;
    }

    if ($('#' + sIdPrefix + '_year').val() != '' || $('#' + sIdPrefix + '_month').val() != '' || $('#' + sIdPrefix + '_day').val() != '') {
        var oToday = EC_GLOBAL_DATETIME.parse('', 'shop');
        var iTodayYear = oToday.format(EC_GLOBAL_DATETIME.const.YEAR_ONLY);
        var iTodayMonth = oToday.format(EC_GLOBAL_DATETIME.const.MONTH_ONLY);
        var iTodayDate = oToday.format(EC_GLOBAL_DATETIME.const.DAY_ONLY);
        var FIX_NOW_DATE = parseInt('' + iTodayYear + iTodayMonth + iTodayDate);
        var FIX_MIN_DATE = 19000101;

        year = $.trim($('#' + sIdPrefix + '_year').val());
        month = $.trim($('#' + sIdPrefix + '_month').val());
        month = month.length == 1 ? '0' + month : month;
        day = $.trim($('#' + sIdPrefix + '_day').val());
        day = day.length == 1 ? '0' + day : day;
        userDate = parseInt(year + month + day);
        lastday = EC_GLOBAL_DATETIME.parse('', 'shop')
            .set('year', year)
            .set('month', month)
            .set('date', 0)
            .date();
        
        if (userDate.toString().length < 8 || userDate.toString().length > 8) {
            alert(__('존재하지 않는 날짜 입니다.'));
            $("input[name^='"+sIdPrefix+"']").val('').first().focus();
            return false;
        } else if (month < 1 || month > 12) {
            alert(__('존재하지 않는 날짜 입니다.'));
            $('#' + sIdPrefix + '_month').val('').focus();
            return false;
        } else if (day < 1 || day > lastday) {
            alert(__('존재하지 않는 날짜 입니다.'));
            $('#' + sIdPrefix + '_day').val('').focus();
            return false;
        } else if (userDate < FIX_MIN_DATE) {        
            alert(__('1900년 이후부터 입력 가능 합니다.'));
            $("input[name^='"+sIdPrefix+"']").val('');
            $("input[name^='"+sIdPrefix+"_year']").focus();
            return false;
        } else if (userDate > FIX_NOW_DATE) {        
            alert(__('오늘날짜 까지 입력 할 수 있습니다.'));
            $("input[name^='"+sIdPrefix+"']").val('').first().focus();
            return false;
        }
    }
    return true;
}

/**
 * 아이디 중복 체크
 */
function checkId(url)
{
    if (url) {
        sIdDuplicateCheckUrl = url;
    }

    if (mobileWeb == true && $('#idMsg').length > 0) {
        checkDuplId();
    } else {
        AuthSSLManager.weave({
            'auth_mode': 'encrypt',
            'aEleId': [$("#joinForm #member_id")],
            'auth_callbackName': 'checkIdEncryptedResult'
        });
    }
}

/**
 * 이메일 중복 체크
 */
function checkEmail(url)
{
    if (mobileWeb == true && $('#emailMsg').length > 0) {
        checkDuplEmail();
    } else {
        var oEmail = $('#joinForm input[name=email1]');
        var agent = navigator.userAgent.toLowerCase();
        var bodyHeight = $('body').height();

        oEmail.val(sEmail = $.trim(oEmail.val()));

        // 모바일웹일 경우 레이어창으로 오픈
        if (agent.indexOf('iphone') != -1 || agent.indexOf('android') != -1) {
            $('body').append('<div id="emailLayer" style="position:absolute; top:0; left:0; width:100%; height:'+bodyHeight+'px; background:#fff; z-index:999;"><iframe src="'+url+'?email='+sEmail+'" style="width:100%; height:'+bodyHeight+'px; border:0;"></iframe></div>');
            //$('input, a, select, button, textarea, .trigger').hide();//ECHOSTING-42532
            $(window).scrollTop(0);
        } else {
            //상단 또는 좌우측에 에 로그인 form 이 있을 수 있기 때문에 id가 아닌 form으로 접근 함
            window.open( url + '?email=' + sEmail , 'echost_email_check', 'width=400, height=400');
        }
    }
}

/**
 * 아이디중복체크 암호화 처리 (일반)
 * @param output
 */
function checkIdEncryptedResult(output)
{
    var sEncrypted = encodeURIComponent(output);

    if (AuthSSLManager.isError(sEncrypted) == true) {
        return;
    }

    var oMemberId = $('#joinForm input[name=member_id]');
    var agent = navigator.userAgent.toLowerCase();
    var bodyHeight = $('body').height();

    oMemberId.val($.trim(oMemberId.val()));

    // 모바일웹일 경우 레이어창으로 오픈
    if (agent.indexOf('iphone') != -1 || agent.indexOf('android') != -1) {
        $('body').append('<div id="idLayer" style="position:absolute; top:0; left:0; width:100%; height:'+bodyHeight+'px; background:#fff; z-index:999;"><iframe src=' + sIdDuplicateCheckUrl + '?encrypted_str=' + sEncrypted + '" style="width:100%; height:'+bodyHeight+'px; border:0;"></iframe></div>');
        //$('input, a, select, button, textarea, .trigger').hide();//ECHOSTING-42532
        $(window).scrollTop(0);
    } else {
        //상단 또는 좌우측에 에 로그인 form 이 있을 수 있기 때문에 id가 아닌 form으로 접근 함
        window.open(sIdDuplicateCheckUrl + '?encrypted_str=' + sEncrypted , 'echost_id_check', 'width=400, height=400');
    }
}

/**
 * 아이디중복체크 암호화 처리 (레이어)
 * @param output
 */
function checkIdEncryptedResultForLayer(output)
{
    var sEncrypted = encodeURIComponent(output);

    if (AuthSSLManager.isError(sEncrypted) == true) {
        return;
    }

    var oMemberId = $('#joinForm input[name=member_id]');
    var sFormMemberId = $.trim(oMemberId.val());

    if ($('#idLayer').length < 1) {
        oMemberId.val(sFormMemberId);
        var iWidth = 440;
        var iHeight = 270;
        var sHtml = '<div id="idLayer" style="overflow:hidden; position:absolute; top:50%; left:50%; z-index:999; width:' + iWidth + 'px; margin:-120px 0 0 -220px; border:1px solid #7f8186; color:#747474; background:#fff; display:none">' + '<iframe id="checkIdLayerFrame" src=' + sIdDuplicateCheckUrl + '?encrypted_str=' + sEncrypted + '" style="width:' + iWidth + 'px; height:' + iHeight + 'px; border:0;" frameborder="0"></iframe>' + '</div>';
        $('body').append(sHtml);
    } else {
        var oFrame = $('#checkIdLayerFrame').contents();
        oFrame.find('#popup').hide();
        oFrame.find('#member_id').val(sFormMemberId);
        oFrame.find('#checkIdForm').submit();
    }

    $('#idLayer').show();
}

/**
 * 아이디 중복 체크 레이어
 */
function checkIdLayer(url)
{
    sIdDuplicateCheckUrl = url;

    AuthSSLManager.weave({
        'auth_mode': 'encrypt',
        'aEleId': [$("#joinForm #member_id")],
        'auth_callbackName': 'checkIdEncryptedResultForLayer'
    });
}

/**
 * 휴대폰, 아이폰 인증 후 이름, 휴대폰 번호등 Decrypt
 */
function callEncryptFunction() {
    AuthSSLManager.weave({
        'auth_mode' : 'decryptClient', //mode
        'auth_string' : document.getElementById('realNameEncrypt').value, //auth_string
        'auth_callbackName'  : 'setDisplayMember'      //callback function
    });
}


/**
 * 휴대폰, 아이폰 인증 후 이름, 휴대폰 번호등 display
 */
function setDisplayMember(sEncodeMember)
{
    var output = decodeURIComponent(sEncodeMember);

    if ( AuthSSLManager.isError(output) == true ) {
        alert(output);
        return;
    }

    var aMember = AuthSSLManager.unserialize(output);
    
    if ($('#nameContents') != undefined) {
        $('#nameContents').html(aMember.name);
    }
    
    try{
        $('#birth_year').val(aMember.birth_year);
        $('#birth_month').val(aMember.birth_month);
        $('#birth_day').val(aMember.birth_day);

        // 회원가입 페이지, 모바일에서 필요한 구문
        if (mobileWeb === true && $('#joinForm') != null) {

            if ($('#is_sms').val() != '' && $('#is_sms').val() != undefined && aMember.is_sms != '') {
                $('#is_sms').val(aMember.is_sms);
            } else {
                $('#joinForm').append('<input type="hidden" id="is_sms" name="is_sms" value="' + aMember.is_sms + '"/>');
            }

            if ($('#is_news_mail').val() != '' && $('#is_news_mail').val() != undefined && aMember.is_news_mail != '') {
                $('#is_news_mail').val(aMember.is_news_mail);
            } else {
                $('#joinForm').append('<input type="hidden" id="is_news_mail" name="is_news_mail" value="' + aMember.is_news_mail + '"/>');
            }
        }

        if ($('#editForm') != null) {
            $('#mobile1').val(aMember.mobile1);
            $('#mobile2').val(aMember.mobile2);
            $('#mobile3').val(aMember.mobile3);
        }
    }catch(e){}
    
    if (aMember.sIsUnder14Joinable == 'F' || aMember.sIsUnder14Joinable == 'M') {
        checkIsUnder14({ birth : aMember.birth });
    }
}

/**
 * Date 관련 util
 *
 * @package resource
 * @subpackage util
 * @author 이장규
 * @since 2011. 10. 14.
 * @version 1.0
 *
 */

var utilDate = new function() {
    
    /**
     * valid 한 날짜 체크
     * @param string sYear 년도
     * @param string sMonth 월
     * @param string sDay 일
     * @return bool
     */
    this.checkDate = function(sYear, sMonth, sDay) {

        if (sMonth.substr(0, 1) == '0') sMonth = sMonth.substr(1, 1);
        if (sDay.substr(0, 1) == '0') sDay = sDay.substr(1, 1);

        sMonth -= 1;
        var sNewDate = new Date(sYear, sMonth, sDay);
        
        return (sNewDate.getFullYear() == sYear && (sNewDate.getMonth()) == sMonth && sNewDate.getDate() == sDay)
    }


}

/**
 * dateUtil 날짜 간격 계산 스크립트
 *
 * 시작일, 종료일, 기준일 (standardDate) 을 기점으로 시작일과, 종료일을 출력합니다.
 *
 * @example
 *
 * var opts = {
 *     'startDate' : '#pr_start_date',
 *     'endDate' : '#pr_end_date'
 *  };
 *
 * standardDate = pr_start_date :: 선택적 .. 시적일, 종료일의 id 명
 * var sdate = dateUtil.init(options);
 *
 * @since 2011-03-11
 * @author jsyang < jsyang@simplexi.com >
 *
 */
var dateUtil = (function(){

    var $sDate, $eDate, opts = {
        'format'    : 'yyyy-mm-dd',
        'startDate' : false,
        'endDate'   : false,
        'year'      : null,
        'month'     : null,
        'day'       : null,
        'standardDate' : false
    };

    var formatLen = function(str){
        return str = (""+str).length<2 ? "0"+str : str;
    };

    var initDate = function(){
        opts.year  = null;
        opts.month = null;
        opts.day   = null;
    };

    var getLastDay = function(year, month){
        var dates = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        if ((year % 4) == 0) dates[1] = 29;
        return dates[month];
    };

    var targetMonth = function(std, add) {
        std = Number(std);
        var mod = add % 12;
        var sum = std + mod;

        if (sum < 0) {
            return 12 + sum;
        } else if (sum < 12) {
            return sum;
        }

        return sum - 12;
    };

    var calDate  = function(){

        var retDate  = new Date(), $standardDate = $("#" + opts.standardDate);

        opts.year  = (opts.year  == null) ? 0 : Number(opts.year);
        opts.month = (opts.month == null) ? 0 : Number(opts.month);
        opts.day   = (opts.day   == null) ? 0 : Number(opts.day);

        if ( opts.standardDate && $("#" + opts.standardDate ).get(0) && $("#" + opts.standardDate ).val() != "" ) {

            var dt = $("#" + opts.standardDate ).val(),
                yy = Number(dt.substring( opts.format.indexOf('yyyy') , opts.format.indexOf('yyyy') + 4)),
                mm = Number(dt.substring( opts.format.indexOf('mm') , opts.format.indexOf('mm')+ 2)),
                dd = Number(dt.substring( opts.format.indexOf('dd') , opts.format.indexOf('dd')+ 2));

            retDate.setYear(yy);
            retDate.setMonth(mm -1);
            retDate.setDate(dd);
        }

        var  yy = Number(retDate.getFullYear()) + opts.year,
             mm = Number(retDate.getMonth()) + opts.month,
             dd = Number(retDate.getDate()) + opts.day;

        if (getLastDay(yy, targetMonth(retDate.getMonth(), opts.month)) < dd) {
            retDate.setYear(yy);
            retDate.setDate(getLastDay(yy, targetMonth(retDate.getMonth(), opts.month)));
            retDate.setMonth(mm);
        } else {
            retDate.setYear(yy);
            retDate.setMonth(mm);
            retDate.setDate(dd);
        }

        return dateUtil.formatDate(retDate);

    };

    return {
        init : function(o){
            opts = $.extend({}, opts, o);
            this.setInputDate(opts.startDate,opts.endDate);

            function dateDiff(){
                var sdate = opts.startDate;
                var edate = opts.endDate;

                function settings(date, num){
                    dateUtil.setInputDate(sdate,edate);
                    dateUtil.setDate(date, num);
                };

                function clear(){
                    dateUtil.setInputDate(sdate,edate);
                    dateUtil.clearDate();
                };

                return {
                    'setDate' : settings,
                    'clearDate' : clear
                };
            }

            return new dateDiff;
        },

        setDate : function(date, num){
            initDate();

            if ( ( date == 'year' || date == 'month' || date == "" || date == 'day' ) ) {
                opts[ date ]  = num;
            } else if ( date == 'betweenMonth' ) {
                this.betweenMonth(num);
                return;
            }

            if ( opts.standardDate && $("#" + opts.standardDate ).get(0) && $("#" + opts.standardDate ).val() != "" ) {
                if(  $sDate.val() == "" && $eDate.val() == ""  ) {
                    this.setDefault();
                } else {
                    if ( opts.standardDate  == $sDate.attr("id") ) {
                        $eDate.val(calDate());
                    } else if ( opts.standardDate  == $eDate.attr("id") ) {
                        $sDate.val(calDate());
                    } else {
                        this.setDefault();
                    }
                }
            } else {
                this.setDefault();
            }
        },

        getLastDay : function(year, month){
            return getLastDay(year, month);
        },

        betweenMonth : function(month, year){
            var retDate  = new Date();
            retDate.setDate(1);

            if ( month && month > 0 ) {
                retDate.setMonth(month - 1);
            }

            if ( year && year > 0 ) {
                retDate.setYear(year);
            }

            var sdate = dateUtil.formatDate(retDate);

            retDate.setDate(this.getLastDay(retDate.getFullYear(), retDate.getMonth()));
            var edate = dateUtil.formatDate(retDate);

            $sDate.val(sdate);
            $eDate.val(edate);
        },

        setDefault: function(){
            $sDate.val(calDate());
            $eDate.val(this.toDay());
        },

        setInputDate : function(ss,ee){
            $sDate = $(ss);
            $eDate = $(ee);
        },

        formatDate : function(date){
            return opts.format.replace('yyyy' , date.getFullYear()).replace('mm', formatLen(date.getMonth() + 1)).replace('dd', formatLen(date.getDate()));
        },

        toDay : function(){
            return this.formatDate(new Date());
        },

        clearDate : function(){
            $sDate.val("");
            $eDate.val("");
        }
    };

})();

var agent = navigator.userAgent.toLowerCase();
var bMobileWeb = false;

$(document).ready(function(){

     // 모바일웹인지 확인
    if (window.location.hostname.substr(0, 2) == 'm.' ||
       window.location.hostname.substr(0, 12) == 'mobile--shop' ||
       window.location.hostname.substr(0, 11) == 'skin-mobile' ) {
       bMobileWeb = true;
    }

    // 모바일웹이 아닐경우만 포커스
    if (bMobileWeb !== true) {
        $('#zipcode_keyword').focus();
    }
});

var ZipcodeFinder = {};


/**
 * 부모창 객체
 */
ZipcodeFinder.Opener = {
    oLanguage: {
        apply: '',
        close: ''
    },

    /**
     * 초기화 - 이벤트 바인딩
     */
    bind : function(btnId, zipId1, zipId2, addrId, type, cityId , stateId, sLanguage, addrId2, form, sFixCountry) {
        var elmBtn = $('#' + btnId);
        if (elmBtn.data("btnEvent") != true) {
            var ci_name_item = "";
            // 기본 바인딩
            elmBtn.bind('click', {
                'zipId1' : zipId1,
                'zipId2' : zipId2,
                'addrId' : addrId,
                'cityId' : cityId,
                'stateId' : stateId,
                'type' : type,
                'sLanguage' : sLanguage,
                'addrId2' : addrId2,
                'form' : form,
                'sFixCountry' : sFixCountry,
                oLanguage: this.oLanguage
            }, this.Event.onClickBtnPopup)
            .data("btnEvent", true);
            // 우편번호 처리
            $('#postcode1').attr('fw-filter', 'isLengthRange[1][14]');
            $('#postcode2').attr('disabled', 'disabled');
        }
    },

    /**
     * 버튼 언어셋 바인딩
     * @param oLanguage
     */
    setLanguage: function(oLanguage) {
        if (!oLanguage) {
            oLanguage = {};
        }

        for (var sKey in oLanguage) {
            if (oLanguage.hasOwnProperty(sKey) && oLanguage[sKey]) {
                this.oLanguage[sKey] = oLanguage[sKey];
            }
        }
    }
};

/**
 * 부모창 객체 - 이벤트 핸들러
 */
ZipcodeFinder.Opener.Event = {

    /**
     * 클릭 - 우편번호 팝업 오픈
     */
    onClickBtnPopup : function(evt) {

        var zipId1 = evt.data.zipId1;
        var zipId2 = evt.data.zipId2;
        var addrId = evt.data.addrId;
        var stateId = evt.data.stateId;
        var cityId = evt.data.cityId;
        var type = evt.data.type;
        var sLanguage = evt.data.sLanguage;
        var addrId2 = evt.data.addrId2;
        var form = evt.data.form;
        var sFixCountry = evt.data.sFixCountry;

        var iWidth = 308;
        var iHeigth = 340;
        var posY = "60%";
        var posX = "35%";


        if (bMobileWeb === true || type == 'mobile' || (typeof EC_MOBILE_USE !== 'undefined' && EC_MOBILE_USE == false && EC_MOBILE_DEVICE === true)) {
            var body_height = document.documentElement.clientHeight;

            var sTpl = "";
            switch (sLanguage) {
                case "ja_JP" :
                    sTpl = "zipcode_mobile_jp";
                    tmp$ = $;
                    break;
                case "zh_CN" :
                    sTpl = "zipcode_mobile_cn";
                    tmp$ = $;
                    break;
                case "zh_TW" :
                    sTpl = "zipcode_mobile_tw";
                    tmp$ = $;
                    break;
                case "vi_VN" :
                    sTpl = "zipcode_mobile_vn";
                    tmp$ = $;
                    break;
                default :
                    sTpl = "zipcode_mobile";
                    break;
            }

            var source = '<div id="zipcodeLayer" ></div>';

            $.get('/protected/'+sTpl+'.html?form='+form+'&zip1='+zipId1+'&zip2='+zipId2+'&addr='+addrId+'&cityId='+cityId+'&stateId='+stateId+'&type=mobile&sLanguage='+sLanguage+'&addr2='+addrId2 + '&sFixCountry='+ sFixCountry, function(data){
                $('body').append(source);
                $("#zipcodeLayer").html(data);
                if (sTpl == 'zipcode_mobile') {
                    $('body').addClass('eMobilePopup');
                } else {
                    $('body').attr('id', 'popup');
                }
            });

        } else if ( type == 'layer' || type == undefined ) {
            if ($('#zipcodeLayer').length > 0) return false;

            var sTpl = "";
            switch (sLanguage) {
                case "ja_JP" :
                    sTpl = "zipcode_layer_jp";
                    iWidth = 617;
                    iHeigth = 620;
                    var frameborder = 'frameborder="0"';
                    break;
                case "zh_CN" :
                    sTpl = "zipcode_layer_zh";
                    iWidth = 502;
                    iHeigth = 236;
                    var frameborder = 'frameborder="0"';
                    break;
                case "zh_TW" :
                    sTpl = "zipcode_layer_tw";
                    iWidth = 502;
                    iHeigth = 217;
                    var frameborder = 'frameborder="0"';
                    break;
                case "vi_VN" :
                    sTpl = "zipcode_layer_vn";
                    iWidth = 502;
                    iHeigth = 236;
                    var frameborder = 'frameborder="0"';
                    break;
                default :
                    sTpl = "zipcode_layer_kr";
                    iHeigth = 420;
                    var frameborder = 'frameborder="0"';
                    break;
            }

            posY = $('#'+zipId1).offset().top-100 + 'px';
            posX = $('#'+zipId1).offset().left-100+'px';

            var sApplyMessage = typeof evt.data.oLanguage === 'object' ? evt.data.oLanguage.apply : '';
            var sCloseMessage = typeof evt.data.oLanguage === 'object' ? evt.data.oLanguage.close : '';
            $('body').append('<div id="zipcodeLayer" class="zipcodeLayer" style="position:absolute; top:'+posY+'; left:'+posX+'; width:'+iWidth+'px; height:'+iHeigth+'px; background:#fff; z-index:999;">' +
                '<iframe src="/protected/'+sTpl+'.html?form='+form+'&zip1='+zipId1+'&zip2='+zipId2+'&addr='+addrId+'&cityId='+cityId+'&stateId='+stateId+'&type=layer&sLanguage='+sLanguage+'&addr2=' + addrId2 + '&sFixCountry='+ sFixCountry + '&sApplyMessage='+ sApplyMessage + '&sCloseMessage='+ sCloseMessage +'" id="iframeZipcode" ' + frameborder + ' style="width:100%; height:100%; border:0;"></iframe>' +
                '</div>');

        } else {

            switch (sLanguage) {
                case "ja_JP" :
                    sTpl = "zipcode_jp";
                    break;
                case "zh_CN" :
                    sTpl = "zipcode_zh";
                    break;
                default : sTpl = "zipcode";
            }

            var url = '/protected/'+sTpl+'.html?zip1=' + zipId1 + '&zip2=' + zipId2 + '&addr=' + addrId;
            window.open(url, 'Zipcode', 'width=462, height=435, toolbar=0, menubar=0, scrollbars=0');

        }
    }

};


/**
 * 팝업 객체
 */
ZipcodeFinder.Popup = {

    /**
     * 초기화 - 이벤트 바인딩
     */
    bind : function(zipId1, zipId2, addrId, type,  cityId , stateId, sLanguage) {

        var elmKeyword = $('#zipcode_keyword');
        var elmBtnSearch = $('#zipcode_btn_search');
        var elmResult = $('#zipcode_result');
        var elmApply = $('#zipcode_apply');

        // 모바일웹일 경우 타켓 변경
        if ( (bMobileWeb === true || type == 'layer'  || (typeof EC_MOBILE_USE !== 'undefined' && EC_MOBILE_USE == false)) && parent.$('#zipcodeLayer').length > 0 ) {

            var elmZip1 = parent.$('#' + zipId1);
            var elmAddr = parent.$('#' + addrId);

            if ( zipId2 != '') {
                var elmZip2 = parent.$('#' + zipId2);
            } else {
                var elmZip2 = parent.$('#ice0917');
            }
            if ( cityId != '') {
                var elmCity = parent.$('#' + cityId);
            } else {
                var elmCity = parent.$('#ice0918');
            }
            if ( stateId != '') {
                var elmState = parent.$('#' + stateId);
            } else {
                var elmState = parent.$('#ice0919');
            }

        } else {
            var elmZip1 = opener.$('#' + zipId1);
            if ( zipId2 != '') { var elmZip2 = opener.$('#' + zipId2); }
            var elmAddr = opener.$('#' + addrId);
            var elmCity = top.$('#ice0918');
            var elmState = top.$('#ice0919');
        }

        elmBtnSearch.bind('click', {
            'parent' : this,
            'elements' : {
                'keyword' : elmKeyword,
                'result' : elmResult,
                'zip1' : elmZip1,
                'zip2' : elmZip2,
                'addr' : elmAddr,
                'cityId' : elmCity,
                'stateId' : elmState,
                'type' : type,
                'sLanguage' : sLanguage
            }
        }, this.Event.onClickBtnSearch);

        if ($('div#wrap').outerHeight() !== null) {
            window.resizeTo('500', $('div#wrap').outerHeight() + 85);
        }
        elmKeyword.bind('keyup', {
            'parent' : this,
            'elements' : {
                'keyword' : elmKeyword,
                'result' : elmResult,
                'zip1' : elmZip1,
                'zip2' : elmZip2,
                'addr' : elmAddr,
                'cityId' : elmCity,
                'stateId' : elmState,
                'type' : type,
                'sLanguage' : sLanguage
            }
        }, this.Event.onClickBtnSearch);

        // 레이어 적용 버튼
        elmApply.bind('click', {
            'parent' : this,
            'elements' : {
                'keyword' : elmKeyword,
                'result' : elmResult,
                'zip1' : elmZip1,
                'zip2' : elmZip2,
                'addr' : elmAddr,
                'cityId' : elmCity,
                'stateId' : elmState,
                'type' : type,
                'sLanguage' : sLanguage
            }
        }, this.Event.onClickLayerResult);

    },

    /**
     * 성공시 출력 데이터 완성
     */
    makeSearchSuccess : function(elements, data) {

        if (elements.type == 'layer') {
            if ( elements.sLanguage == 'ja_JP') { // 일본 우편번호
                this.makeResultLayer_jp( elements, data );
            } else { // 국내 우편번호
                this.makeResultLayer(elements, data);
            }
        } else {
            this.makeResult(elements, data);
        }

    },

    /**
     * 성공시 출력 데이터 완성(Popup) - KR
     */
    makeResult : function(elements, data) {
        var count = data.length;
        var elmItem = '';

        elements.result.html('');

        for (var i=0; i < count; ++i) {

          //<tr><td>156-012</td><td>서울 동작구 신대방2동</td></tr>
            var address = '<td>' + data[i].zipcode + '</td><td>'
                        + data[i].addr + ' '
                        + data[i].bunji + '</td> ';

            var sAddr = (data[i].bunji.indexOf("∼") > -1) ? '' : ' '+data[i].bunji;

            elmItem = $('<tr addr="' + data[i].addr + sAddr + '">' + address + '</tr>').bind('click', {'elements' : elements}, this.Event.onClickResult);

            elements.result.append(elmItem);
        }
    },

    /**
     * 성공시 출력 데이터 완성(Layer) - JP
     */
    makeResultLayer_jp : function(elements, data) {

        var count = data.length;
        var elmItem = '';

        elements.result.html('');

        for (var i=0; i < count; ++i) {
            var _zipcode = data[i].zipcode;
            var _addr = data[i].sido_name + ' ' + data[i].gugun_name + ' ' + data[i].dong_name;

            var address = '<td class="left">' + _addr + '</td>'
                        + '<td>' + _zipcode + '</td>'
                        + '<td><a href="#none" class="btnNormal"><span>Select</span></a></td>';

            elmItem = $('<tr addr="' + data[i].sido_name + '|' + data[i].gugun_name + '|' + data[i].dong_name + '">' + address + '</tr>').bind('click', {'elements' : elements, 'zipcode' : _zipcode}, this.Event.onClickLayerResultJP);

            elements.result.append(elmItem);
        }
    },

    /**
     * 성공시 출력 데이터 완성(Layer) - KR
     */
    makeResultLayer : function(elements, data) {

        var count = data.length;
        var elmItem = '';

        elements.result.html('');

        for (var i=0; i < count; ++i) {
            //<tr><td>156-012</td><td>서울 동작구 신대방2동</td></tr>
            var address = '<td>' + data[i].zipcode + '</td><td>'
                        + data[i].addr + ' '
                        + data[i].bunji + '</td> ';

            var sAddr = (data[i].bunji.indexOf("∼") > -1) ? '' : ' '+data[i].bunji;

            elmItem = $('<tr addr="' + data[i].addr + sAddr + '">' + address + '</tr>').bind('click', {'elements' : elements}, this.Event.onClickLayerResult);

            elements.result.append(elmItem);
        }
    },

    /**
     * 실패시 출력 데이터 완성
     */
    makeSearchFail : function(elements) {

        if ( elements.sLanguage != 'ko_KR') { // 일본 우편번호
            var elm = $('<tr><td colspan="3">No Result</td></tr>');
        } else {
            var elm = $('<tr><td colspan="2">우편번호 검색 내역이 없습니다.</td></tr>');
        }

        elements.result.html('');
        elements.result.append(elm);
    }

};

/**
 * 팝업 객체 - 이벤트 핸들러
 */
ZipcodeFinder.Popup.Event = {

    /**
     * 레이어 선택
     */
    onClickLayer : function() {
        $(this).parents().find('.selected').removeClass('selected');
        $(this).addClass("selected");
    },

    /**
     * 클릭 - 검색버튼
     */
    onClickBtnSearch : function(evt) {
        if ( (evt.type == 'keyup' && evt.which != 13 )) return false;//enter 로 검색

        var parent = evt.data.parent;
        var elements = evt.data.elements;

        var keyword = elements.keyword.val();
        if (keyword == '') return false;

        var url = '/exec/front/zipcode/find/';
        var params = {
            'keyword' : keyword,
            'sLanguage' : elements.sLanguage
        };

        $.ajax({
            type : 'post',
            url : url,
            data : params,
            success : function(response){
                if (response.result === true) {
                    parent.makeSearchSuccess(elements, response.data);
                } else {
                    parent.makeSearchFail(elements);
                }

            }
        });
    },

    /**
     * 부모창에 주소,우편번호 입력 - JP
     */
    onClickLayerResultJP : function(evt) {

        var elements = evt.data.elements;

        var zip1 = evt.data.zipcode.substr(0, 3);
        var zip2 = evt.data.zipcode.substr(4, 4);
        var aAddr = $(this).attr('addr').split("|",3);

        if (elements.cityId.length > 0 && elements.stateId.length > 0 ) {
            elements.cityId.val( aAddr[0] );
            elements.stateId.val( aAddr[1] );
            elements.addr.val( aAddr[2] );
        } else {
            elements.addr.val( $(this).attr('addr') );
        }

        if ( elements.zip2.length > 0 ) {
            elements.zip1.val(zip1);
            elements.zip2.val(zip2);
        } else {
            elements.zip1.val( zip1+'-'+zip2 );
        }

        // 해외몰 지역별배송비 부과를 위해 event발생
        try {
            if (elements.zip1.attr('id') == 'fzipcode') {
                parent.$('#' + elements.zip1.attr('id') + ', #' + elements.addr.attr('id')).blur();
            }
        } catch (e) {}

        top.$('#zipcodeLayer').remove();
    },

    /**
     * 부모창에 주소,우편번호 입력 - KR
     */
    onClickLayerResult : function(evt) {

        var elements = evt.data.elements;

        var zip1 = $(this).text().substr(0, 3);
        var zip2 = $(this).text().substr(4, 3);
        var addr = $(this).attr('addr');

        addr = $.trim(addr);
        elements.addr.val(addr);

        elements.zip1.val(zip1);
        elements.zip2.val(zip2);

        if (parent.$('.tSubmit2').offset() != undefined) parent.$('html, body').animate({scrollTop: parent.$('.tSubmit2').offset().top}, 0);

        // 국내몰 지역별 배송비 부과를 위해 event 발생
        try{ 
            opener.EC_SHOP_FRONT_ORDERFORM_SHIPPING.exec();

        } catch (e){}

        // 해외몰 지역별배송비 부과를 위해 event발생
        try {
            if (elements.zip1.attr('id') == 'fzipcode') {
                parent.$('#' + elements.zip1.attr('id') + ', #' + elements.addr.attr('id')).blur();
                parent.EC_SHOP_FRONT_ORDERFORM_APP_DELIVERY.exec.doAddressChange();
            }
        } catch (e) {}

        parent.$('#zipcodeLayer').remove();
    },

    /**
     * 클릭 - 검색 결과 항목
     */
    onClickResult : function(evt) {

        var elements = evt.data.elements;

        var zip1 = $(this).text().substr(0, 3);
        var zip2 = $(this).text().substr(4, 3);
        var addr = $(this).attr('addr');

        addr = $.trim(addr);

        if ( elements.zip2 != undefined ) {
            elements.zip1.val(zip1);
            elements.zip2.val(zip2);
        } else {
            elements.zip1.val( $(this).text() );
        }

        elements.addr.val(addr);

        // 모바일웹일 경우 레이어창 닫기
        if (agent.indexOf('iphone') != -1 || agent.indexOf('android') != -1) {
            if (window.top.document.getElementById('frm_order_act')) {//ECHOSTING-42532
                //frm_order_act는 주문서작성페이지에 있는 폼객체의 id값
                //order.html같은 페이지주소를 이용하지 않는 이유는
                //스디의 특성상 페이지주소는 사용자에 의해 변동될수있기때문에 페이지주소보다는 사용자가 파일명을 수정한다고해도
                //주문서작성페이지라면 꼭 존재하는 객체를 기준으로 잡았음
               top.$('input, a, select, button, textarea, .trigger').show();
            }
            if (top.$('.tSubmit2').offset() != undefined) top.$('html, body').animate({scrollTop: top.$('.tSubmit2').offset().top}, 0);

            // 해외몰 지역별배송비 부과를 위해 event발생
            try {
                if (elements.zip1.attr('id') == 'fzipcode') {
                    top.$('#' + elements.zip1.attr('id') + ', #' + elements.addr.attr('id')).blur();
                }
            } catch (e) {}

            top.$('#zipcodeLayer').remove();
        } else {
            window.self.close();
        }
    }
};

/**
 * FwValidator
 *
 * @package     jquery
 * @subpackage  validator
 */

var FwValidator = {

    /**
     * 디버그 모드
     */
    DEBUG_MODE : false,

    /**
     * 결과 코드
     */
    CODE_SUCCESS    : true,
    CODE_FAIL       : false,

    /**
     * 어트리뷰트 명
     */
    ATTR_FILTER     : 'fw-filter',
    ATTR_MSG        : 'fw-msg',
    ATTR_LABEL      : 'fw-label',
    ATTR_FIREON     : 'fw-fireon',
    ATTR_ALONE      : 'fw-alone',

    /**
     * 응답객체들
     */
    responses       : {},

    /**
     * 엘리먼트별 필수 입력 에러 메세지
     */
    requireMsgs     : {},

    /**
     * 엘리먼트의 특정 필터별 에러 메세지
     */
    elmFilterMsgs   : {},

    /**
     * Validator 기본 이벤트 등록
     */
    bind : function(formId, expand) {

        var self = this;
        var formInfo = this.Helper.getFormInfo(formId);

        if (formInfo === false) {
            alert('The form does not exist - bind');
            return false;
        }

        var elmForm = formInfo.instance;

        var Response = this._response(formId);

        this._fireon(formId, elmForm, Response);
        this._submit(formId, elmForm, expand);

        return true;

    },

    /**
     * Validator 검사 진행
     *
     * @param string formId
     * @return object | false
     */
    inspection : function(formId, expand) {

        expand = (expand === true) ? true : false;

        var self = this;
        var Response = this._response(formId);

        if (Response === false) {
            alert('The form does not exist - inspection');
            return false;
        }

        if (Response.elmsTarget.length == 0) {
            return this.Helper.getResult(Response, this.CODE_SUCCESS);
        }

        Response.elmsTarget.each(function(){
            self._execute(Response, this);
        });

        if (Response.elmsCurrErrorField.length > 0) {

            if (expand !== true) {
                this.Handler.errorHandler(Response.elmsCurrErrorField[0]);
            } else {
                this.Handler.errorHandlerByExapnd(Response);
            }

            return Response.elmsCurrErrorField[0];

        }

        return this.Helper.getResult(Response, this.CODE_SUCCESS);

    },

    /**
     * submit 이벤트 등록
     *
     * @param string    formId
     * @param object    elmForm
     */
    _submit : function(formId, elmForm, expand) {
        var self = this;

        elmForm.unbind('submit');
        elmForm.bind('submit', function(){
            var result = false;

            try{
                result = self.inspection(formId, expand);
            }catch(e){
                alert(e);
                return false;
            }

            if(!result || result.passed === self.CODE_FAIL){
                return false;
            };

            var callback = self._beforeSubmit(elmForm);

            return callback !== false ? true : false;
        });
    },

    /**
     * fireon 이벤트 등록
     *
     * @param string                formId
     * @param object                elmForm
     * @param FwValidator.Response  Response
     */
    _fireon : function(formId, elmForm, Response) {
        var self = this;
        var formInfo = this.Helper.getFormInfo(formId);

        $(formInfo.selector).find('*['+this.ATTR_FILTER+']['+this.ATTR_FIREON+']').each(function(){
            var elm = $(this);
            var evtName = $.trim(elm.attr(self.ATTR_FIREON));
            var elmMsg = '';

            elm.unbind(evtName);
            elm.bind(evtName, function(){
                var result = self._execute(Response, this);
                var targetField = Response.elmCurrField;

                //에러 메세지가 출력되 있다면 일단 지우고 체킹을 시작한다.
                if(typeof elmMsg == 'object'){
                    elmMsg.remove();
                }

                if(result > -1){
                    elmMsg = self.Handler.errorHandlerByFireon(Response.elmsCurrErrorField[result]);
                }else{
                    self.Handler.successHandlerByFireon(self.Helper.getResult(Response, self.CODE_FAIL));
                }
            });
        });
    },

    /**
     * Response 객체 생성
     *
     * @param string formId
     * @return FwValidator.Response | false
     */
    _response : function(formId) {

        var formInfo = this.Helper.getFormInfo(formId);

        if (formInfo === false) {
            alert('The form does not exist - find');
            return false;
        }

        var elmForm = formInfo.instance;
        var elmsTarget = $(formInfo.selector).find('*[' + this.ATTR_FILTER + ']');

        this.responses[formId] = new FwValidator.Response();

        this.responses[formId].formId = formId;
        this.responses[formId].elmForm = elmForm;
        this.responses[formId].elmsTarget = elmsTarget;

        return this.responses[formId];

    },

    /**
     * BeforeExecute 콜백함수 실행
     *
     * @param FwValidator.Response Response
     */
    _beforeExecute : function(Response) {

        var count = this.Handler.beforeExecute.length;

        if (count == 0) return;

        for (var i in this.Handler.beforeExecute) {
            this.Handler.beforeExecute[i].call(this, Response);
        }

    },

    /**
     * BeforeSubmit 콜백함수 실행
     *
     * @param object elmForm (jquery 셀렉터 문법으로 찾아낸 폼 객체)
     */
    _beforeSubmit : function(elmForm) {

        if(typeof this.Handler.beforeSubmit != 'function') return true;

        return this.Handler.beforeSubmit.call(this, elmForm);

    },

    /**
     * 엘리먼트별 유효성 검사 실행
     *
     * @param FwValidator.Response  Response
     * @param htmlElement           elmTarget
     * @return int(에러가 발생한 elmCurrField 의 인덱스값) | -1(성공)
     */
    _execute : function(Response, elmTarget) {

        var RESULT_SUCCESS = -1;

        Response.elmCurrField = $(elmTarget);
        Response.elmCurrLabel = Response.elmCurrField.attr(this.ATTR_LABEL);
        Response.elmCurrFieldType = this.Helper.getElmType(Response.elmCurrField);
        Response.elmCurrFieldDisabled = elmTarget.disabled;
        Response.elmCurrValue = this.Helper.getValue(Response.formId, Response.elmCurrField);
        Response.elmCurrErrorMsg = Response.elmCurrField.attr(this.ATTR_MSG);

        //_beforeExecute 콜백함수 실행
        this._beforeExecute(Response);

        //필드가 disabled 일 경우는 체크하지 않음.
        if (Response.elmCurrFieldDisabled === true) {
            return RESULT_SUCCESS;
        }

        var filter = $.trim( Response.elmCurrField.attr(this.ATTR_FILTER) );

        if (filter == '') {
            return RESULT_SUCCESS;
        }

        //is로 시작하지 않는것들은 정규표현식으로 간주
        if (/^is/i.test(filter)) {
            var filters = filter.split('&');
            var count = filters.length;

            //필수항목이 아닌경우 빈값이 들어왔을경우는 유효성 체크를 통과시킴

            if ((/isFill/i.test(filter) === false) && !Response.elmCurrValue) {
                return RESULT_SUCCESS;
            }

            for (var i=0; i < count; ++i) {
                var filter = filters[i];
                var param = '';
                var filtersInfo = this.Helper.getFilterInfo(filter);

                filter = Response.elmCurrFilter = filtersInfo.id;
                param = filtersInfo.param;

                //필수 입력 필터의 경우 항목관리에서 사용자가 메세지를 직접 지정하는 부분이 있어 이렇게 처리
                if (filter == 'isFill') {
                    Response.elmCurrValue = $.trim(Response.elmCurrValue);
                    Response.elmCurrErrorMsg = this.requireMsgs[elmTarget.id] ? this.requireMsgs[elmTarget.id] : this.msgs['isFill'];
                } else {
                    var msg = Response.elmCurrField.attr(this.ATTR_MSG);

                    if (msg) {
                        Response.elmCurrErrorMsg = msg;
                    } else if (this.Helper.getElmFilterMsg(elmTarget.id, filter)) {
                        Response.elmCurrErrorMsg = this.Helper.getElmFilterMsg(elmTarget.id, filter);
                    } else {
                        Response.elmCurrErrorMsg = this.msgs[filter];
                    }

                }

                //존재하지 않는 필터인 경우 에러코드 반환
                if(this.Filter[filter] === undefined){
                    Response.elmCurrErrorMsg = this.msgs['notMethod'];
                    var result = this.Helper.getResult(Response, this.CODE_FAIL);

                    Response.elmsCurrErrorField.push(result);
                    return Response.elmsCurrErrorField.length - 1;
                }

                //필터 실행
                var result = this.Filter[filter](Response, param);

                if (result == undefined || result.passed === this.CODE_FAIL) {
                    Response.elmsCurrErrorField.push(result);

                    //Debug를 위해 넣어둔 코드(확장형 필터를 잘못 등록해서 return값이 없는 경우를 체크하기 위함)
                    if (result == undefined) {
                        alert('Extension Filter Return error - ' + filter);
                    }

                    return Response.elmsCurrErrorField.length - 1;
                }
            }
        } else {
            var msg = Response.elmCurrErrorMsg;
            Response.elmCurrErrorMsg = msg ? msg : this.msgs['isRegex'];
            var result = this.Filter.isRegex(Response, filter);

            if(result.passed === this.CODE_FAIL){
                Response.elmsCurrErrorField.push(result);

                return Response.elmsCurrErrorField.length - 1;
            }
        }

        return RESULT_SUCCESS;
    }
};

/**
 * FwValidator.Response
 *
 * @package     jquery
 * @subpackage  validator
 */

FwValidator.Response = function() {

    this.formId = null;
    this.elmForm = null;
    this.elmsTarget = null;
    this.elmsCurrErrorField = [];

    this.elmCurrField = null;
    this.elmCurrFieldType = null;
    this.elmCurrFieldDisabled = null;
    this.elmCurrLabel = null;
    this.elmCurrValue = null;
    this.elmCurrFilter = null;
    this.elmCurrErrorMsg = null;

    this.requireMsgs = {};

};

/**
 * FwValidator.Helper
 *
 * @package     jquery
 * @subpackage  validator
 */

FwValidator.Helper = {

    parent : FwValidator,

    /**
     * 메세지 엘리먼트의 아이디 prefix
     */
    msgIdPrefix : 'msg_',

    /**
     * 메세지 엘리먼트의 클래스 명 prefix
     */
    msgClassNamePrefix : 'msg_error_mark_',

    /**
     * 결과 반환
     */
    getResult : function(Response, code, param) {

        //특수 파라미터 정보(특정 필터에서만 사용함)
        param = param != undefined ? param : {};

        var msg = '';

        if (code === this.parent.CODE_FAIL) {

            try {
                msg = Response.elmCurrErrorMsg.replace(/\{label\}/i, Response.elmCurrLabel);
            } catch(e) {
                msg = 'No Message';
            }

        } else {

            msg = 'success';

        }

        var result = {};
        result.passed = code;
        result.formid = Response.formId;
        result.msg = msg;
        result.param = param;

        try {
        result.element = Response.elmCurrField;
        result.elmid = Response.elmCurrField.attr('id');
        result.filter = Response.elmCurrFilter;
        } catch(e) {}

        return result;

    },

    /**
     * 필터 정보 반환(필터이름, 파라미터)
     */
    getFilterInfo : function(filter) {
        var matches = filter.match(/(is[a-z]*)((?:\[.*?\])*)/i);

        return {
            id : matches[1],
            param : this.getFilterParams(matches[2])
        };
    },

    /**
     * 필터의 파라미터 스트링 파싱
     * isFill[a=1][b=1][c=1] 이런식의 멀티 파라미터가 지정되어 있는 경우는 배열로 반환함
     * isFill[a=1] 단일 파라미터는 파라미터로 지정된 스트링값만 반환함
     */
    getFilterParams : function(paramStr) {
        if (paramStr == undefined || paramStr == null || paramStr == '') {
            return '';
        }

        var matches = paramStr.match(/\[.*?\]/ig);

        if (matches == null) {
            return '';
        }

        var count = matches.length;
        var result = [];

        for (var i=0; i < count; i++) {
            var p = matches[i].match(/\[(.*?)\]/);
            result.push(p[1]);
        }

        if (result.length == 1) {
            return result[0];
        }

        return result;
    },

    /**
     * 필드 타입 반환(select, checkbox, radio, textbox)
     */
    getElmType : function(elmField) {
        elmField = $(elmField);

        var elTag = elmField[0].tagName;
        var result = null;

        switch (elTag) {
            case 'SELECT' :
                result = 'select';
                break;

            case 'INPUT' :
                var _type = elmField.attr('type').toLowerCase();
                if(_type == 'checkbox') result = 'checkbox';
                else if(_type =='radio') result = 'radio';
                else result = 'textbox';

                break;

            case 'TEXTAREA' :
                result = 'textbox';
                break;

            default :
                result = 'textbox';
                break;
        }

        return result;
    },

    /**
     * 필드 값 반환
     */
    getValue : function(formId, elmField) {
        var result = '';
        var elmName = elmField.attr('name');
        var fieldType = this.getElmType(elmField);

        //checkbox 나 radio 박스는 value값을 반환하지 않음
        if (fieldType == 'checkbox' || fieldType == 'radio') {
            if(elmField.get(0).checked === true){
                result = elmField.val();
            }
            return result;
        }

        //alonefilter 속성이 Y 로 되어 있다면 해당 엘리먼트의 값만 반환함
        var aloneFilter = elmField.attr(this.parent.ATTR_ALONE);
        if(aloneFilter == 'Y' || aloneFilter == 'y'){
            return elmField.val();
        }

        //name이 배열형태로 되어 있다면 값을 모두 합쳐서 반환
        if( /\[.*?\]/.test(elmName) ){
            var formInfo = this.getFormInfo(formId);

            var groupElms = $(formInfo.selector +' [name="'+elmName+'"]');
            groupElms.each(function(i){
                var elm = $(this);
                result += elm.val();
            });
        }else{
            result = elmField.val();
        }

        return result;
    },

    /**
     * 에러메세지 엘리먼트 생성
     */
    createMsg : function(elm, msg, formId) {
        var elmMsg = document.createElement('span');

        elmMsg.id = this.msgIdPrefix + elm.attr('id');
        elmMsg.className = this.msgClassNamePrefix + formId;
        elmMsg.innerHTML = msg;

        return $(elmMsg);
    },

    /**
     * 에러메세지 엘리먼트 제거
     */
    removeMsg : function(elm) {
        var id = this.msgIdPrefix + elm.attr('id');
        var elmErr = $('#'+id);

        if (elmErr) elmErr.remove();
    },

    /**
     * 에러메세지 엘리먼트 모두 제거
     */
    removeAllMsg : function(formId) {
        var className = this.msgClassNamePrefix + formId;

        $('.' + className).remove();
    },

    /**
     * 문자열의 Byte 수 반환
     */
    getByte : function(str) {
        var encode = encodeURIComponent(str);
        var totalBytes = 0;
        var chr;
        var bytes;
        var code;

        for(var i = 0; i < encode.length; i++)
        {
            chr = encode.charAt(i);
            if(chr != "%") totalBytes++;
            else
            {
                code = parseInt(encode.substr(i+1,2),16);
                if(!(code & 0x80)) totalBytes++;
                else
                {
                    if((code & 0xE0) == 0xC0) bytes = 2;
                    else if((code & 0xF0) == 0xE0) bytes = 3;
                    else if((code & 0xF8) == 0xF0) bytes = 4;
                    else return -1;

                    i += 3 * (bytes - 1);

                    totalBytes += 2;
                }
                i += 2;
            }
        }

        return totalBytes;
    },

    /**
     * 지정한 엘리먼트의 필터 메세지가 존재하는가
     *
     * @param elmId (엘리먼트 아이디)
     * @param filter (필터명)
     * @return string | false
     */
    getElmFilterMsg : function(elmId, filter) {
        if (this.parent.elmFilterMsgs[elmId] == undefined) return false;
        if (this.parent.elmFilterMsgs[elmId][filter] == undefined) return false;

        return this.parent.elmFilterMsgs[elmId][filter];
    },

    /**
     * 폼 정보 반환
     *
     * @param formId (폼 아이디 혹은 네임)
     * @return array(
     *   'selector' => 셀렉터 문자,
     *   'instance' => 셀렉터 문법으로 검색해낸 폼 객체
     * ) | false
     */
    getFormInfo : function(formId) {
        var result = {};
        var selector = '#' + formId;
        var instance = $(selector);

        if (instance.length > 0) {
            result.selector = selector;
            result.instance = instance;

            return result;
        }

        selector = 'form[name="' + formId + '"]';
        instance = $(selector);

        if (instance.length > 0) {
            result.selector = selector;
            result.instance = instance;

            return result;
        }

        return false;
    },

    /**
     * 숫자형태의 문자열로 바꿔줌
     * 123,123,123
     * 123123,123
     * 123%
     * 123  %
     * 123.4
     * -123
     * ,123
     *
     * @param value
     * @return float
     */
    getNumberConv : function(value) {
        if (!value || value == undefined || value == null) return '';

        value = value + "";

        value = value.replace(/,/g, '');
        value = value.replace(/%/g, '');
        value = value.replace(/[\s]/g, '');

        if (this.parent.Verify.isFloat(value) === false) return '';

        return parseFloat(value);
    }
};

/**
 * FwValidator.Handler
 *
 * @package     jquery
 * @subpackage  validator
 */

FwValidator.Handler = {

    parent : FwValidator,

    /**
     * 사용자 정의형 에러핸들러(엘리먼트 아이디별로 저장됨)
     */
    customErrorHandler : {},

    /**
     * 사용자 정의형 에러핸들러(필터별로 저장됨)
     */
    customErrorHandlerByFilter : {},

    /**
     * 사용자 정의형 성공핸들러(엘리먼트 아이디별로 저장됨)
     */
    customSuccessHandler : {},

    /**
     * 사용자 정의형 성공핸들러(필터별로 저장됨)
     */
    customSuccessHandlerByFilter : {},

    /**
     * FwValidator._execute에 의해 검사되기 전 실행되는 콜백함수
     */
    beforeExecute : [],

    /**
     * FwValidator._submit에서 바인딩한 onsubmit 이벤트 발생후 실행되는 콜백함수
     * {폼아이디 : 콜백함수, ...}
     */
    beforeSubmit : {},

    /**
     * 기본 메세지 전체를 오버라이딩
     */
    overrideMsgs : function(msgs) {
        if (typeof msgs != 'object') return;

        this.parent.msgs = msgs;
    },

    /**
     * 필드에 따른 필수 입력 에러메세지 설정
     */
    setRequireErrorMsg : function(field, msg) {
        this.parent.requireMsgs[field] = msg;
    },

    /**
     * 필터 타입에 따른 에러메세지 설정
     */
    setFilterErrorMsg : function(filter, msg) {
        this.parent.msgs[filter] = msg;
    },

    /**
     * 엘리먼트의 특정 필터에만 에러메세지를 설정
     */
    setFilterErrorMsgByElement : function(elmId, filter, msg) {
        if (this.parent.elmFilterMsgs[elmId] == undefined) {
            this.parent.elmFilterMsgs[elmId] = {};
        }

        this.parent.elmFilterMsgs[elmId][filter] = msg;
    },

    /**
     * 엘리먼트 아이디별 사용자정의형 에러핸들러 등록
     */
    setCustomErrorHandler : function(elmId, func) {
        if (typeof func != 'function') return;

        this.customErrorHandler[elmId] = func;
    },

    /**
     * 필터 타입별 사용자정의형 에러핸들러 등록
     */
    setCustomErrorHandlerByFilter : function(filter, func) {
        if (typeof func != 'function') return;

        this.customErrorHandlerByFilter[filter] = func;
    },

    /**
     * 엘리먼트 아이디별 사용자정의형 성공핸들러 등록
     */
    setCustomSuccessHandler : function(elmId, func) {
        if (typeof func != 'function') return;

        this.customSuccessHandler[elmId] = func;
    },

    /**
     * 필터 타입별 사용자정의형 성공핸들러 등록
     */
    setCustomSuccessHandlerByFilter : function(filter, func) {
        if (typeof func != 'function') return;

        this.customSuccessHandlerByFilter[filter] = func;
    },

    /**
     * 확장형 필터 등록
     */
    setExtensionFilter : function(filter, func) {
        if (typeof func != 'function') return;

        if (this.parent.Filter[filter] == undefined) {
            this.parent.Filter[filter] = func;
        }
    },

    /**
     * 각 엘리먼트가 FwValidator._execute에 의해 검사되기 전 실행되는 콜백함수 등록
     */
    setBeforeExecute : function(func) {
        if (typeof func != 'function') return;

        this.beforeExecute.push(func);
    },

    /**
     * FwValidator._submit 에서 바인딩된 onsubmit 이벤트의 콜백함수 등록(유효성 검사가 성공하면 호출됨)
     */
    setBeforeSubmit : function(func) {
        if (typeof func != 'function') return;

        this.beforeSubmit = func;
    },

    /**
     * 에러핸들러 - 기본
     */
    errorHandler : function(resultData) {
        if (this._callCustomErrorHandler(resultData) === true) return;

        alert(resultData.msg);
        resultData.element.focus();
    },

    /**
     * 에러핸들러 - 전체 펼침 모드
     */
    errorHandlerByExapnd : function(Response) {
        var count = Response.elmsCurrErrorField.length;

        //해당 폼에 출력된 에러메세지를 일단 모두 지운다.
        this.parent.Helper.removeAllMsg(Response.formId);

        for (var i=0; i < count; ++i) {
            var resultData = Response.elmsCurrErrorField[i];

            if (this._callCustomErrorHandler(resultData) === true) continue;

            var elmMsg = this.parent.Helper.createMsg(resultData.element, resultData.msg, resultData.formid).css({'color':'#FF3300'});
            elmMsg.appendTo(resultData.element.parent());
        }
    },

    /**
     * 에러핸들러 - fireon
     */
    errorHandlerByFireon : function(resultData) {
        if (this._callCustomErrorHandler(resultData) === true) return;

        //해당 항목의 에러메세지 엘리먼트가 있다면 먼저 삭제한다.
        this.parent.Helper.removeMsg(resultData.element);

        var elmMsg = this.parent.Helper.createMsg(resultData.element, resultData.msg, resultData.formid).css({'color':'#FF3300'});
        elmMsg.appendTo(resultData.element.parent());

        return elmMsg;
    },

    /**
     * 성공핸들러 - fireon
     */
    successHandlerByFireon : function(resultData) {

        this._callCustomSuccessHandler(resultData);

    },

    /**
     * 정의형 에러 핸들러 호출
     *
     * @return boolean (정의형 에러핸들러를 호출했을 경우 true 반환)
     */
    _callCustomErrorHandler : function(resultData) {
        //resultData 가 정의되어 있지 않은 경우
        if (resultData == undefined) {
            alert('errorHandler - resultData is not found');
            return true;
        }

        //해당 엘리먼트에 대한 Custom에러핸들러가 등록되어 있다면 탈출
        if (this.customErrorHandler[resultData.elmid] != undefined) {
            this.customErrorHandler[resultData.elmid].call(this.parent, resultData);
            return true;
        }

        //해당 필터에 대한 Custom에러핸들러가 등록되어 있다면 탈출
        if (this.customErrorHandlerByFilter[resultData.filter] != undefined) {
            this.customErrorHandlerByFilter[resultData.filter].call(this.parent, resultData);
            return true;
        }

        return false;
    },

    /**
     * 정의형 성공 핸들러 호출 - 기본적으로 fireon 속성이 적용된 엘리먼트에만 적용됨.
     */
    _callCustomSuccessHandler : function(resultData) {

        if (this.customSuccessHandler[resultData.elmid] != undefined) {
            this.customSuccessHandler[resultData.elmid].call(this.parent, resultData);
            return;
        }

        if (this.customSuccessHandlerByFilter[resultData.filter] != undefined) {
            this.customSuccessHandlerByFilter[resultData.filter].call(this.parent, resultData);
            return;
        }

    }
};

/**
 * FwValidator.Verify
 *
 * @package     jquery
 * @subpackage  validator
 */

FwValidator.Verify = {

    parent : FwValidator,

    isNumber : function(value, cond) {
        if (value == '') return true;

        if (!cond) {
            cond = 1;
        }

        cond = parseInt(cond);

        pos = 1;
        nga = 2;
        minpos = 4;
        minnga = 8;

        result = 0;

        if ((/^[0-9]+$/).test(value) === true) {
            result = pos;
        } else if ((/^[-][0-9]+$/).test(value) === true) {
            result = nga;
        } else if ((/^[0-9]+[.][0-9]+$/).test(value) === true) {
            result = minpos;
        } else if ((/^[-][0-9]+[.][0-9]+$/).test(value) === true) {
            result = minnga;
        }

        if (result & cond) {
            return true;
        }

        return false;
    },

    isFloat : function(value) {
        if (value == '') return true;

        return (/^[\-0-9]([0-9]+[\.]?)*$/).test(value);
    },

    isIdentity : function(value) {
        if (value == '') return true;

        return (/^[a-z]+[a-z0-9_]+$/i).test(value);
    },

    isKorean : function(value) {
        if (value == '') return true;

        var count = value.length;

        for(var i=0; i < count; ++i){
            var cCode = value.charCodeAt(i);

            //공백은 무시
            if(cCode == 0x20) continue;

            if(cCode < 0x80){
                return false;
            }
        }

        return true;
    },

    isAlpha : function(value) {
        if (value == '') return true;

        return (/^[a-z]+$/i).test(value);
    },

    isAlphaUpper : function(value) {
        if (value == '') return true;

        return (/^[A-Z]+$/).test(value);
    },

    isAlphaLower : function(value) {
        if (value == '') return true;

        return (/^[a-z]+$/).test(value);
    },

    isAlphaNum : function(value) {
        if (value == '') return true;

        return (/^[a-z0-9]+$/i).test(value);
    },

    isAlphaNumUpper : function(value) {
        if (value == '') return true;

        return (/^[A-Z0-9]+$/).test(value);
    },

    isAlphaNumLower : function(value) {
        if (value == '') return true;

        return (/^[a-z0-9]+$/).test(value);
    },

    isAlphaDash : function(value) {
        if (value == '') return true;

        return (/^[a-z0-9_-]+$/i).test(value);
    },

    isAlphaDashUpper : function(value) {
        if (value == '') return true;

        return (/^[A-Z0-9_-]+$/).test(value);
    },

    isAlphaDashLower : function(value) {
        if (value == '') return true;

        return (/^[a-z0-9_-]+$/).test(value);
    },

    isSsn : function(value) {
        value = value.replace(/-/g, '');
        if (value == '') return true;

        if ( (/[0-9]{2}[01]{1}[0-9]{1}[0123]{1}[0-9]{1}[1234]{1}[0-9]{6}$/).test(value) === false ) {
            return false;
        }

        var sum = 0;
        var last = value.charCodeAt(12) - 0x30;
        var bases = "234567892345";
        for (var i=0; i<12; i++) {
            sum += (value.charCodeAt(i) - 0x30) * (bases.charCodeAt(i) - 0x30);
        };
        var mod = sum % 11;

        if ( (11 - mod) % 10 != last ) {
            return false;
        }

        return true;
    },

    isForeignerNo : function(value) {
        value = value.replace(/-/g, '');
        if (value == '') return true;

        if ( (/[0-9]{2}[01]{1}[0-9]{1}[0123]{1}[0-9]{1}[5678]{1}[0-9]{1}[02468]{1}[0-9]{2}[6789]{1}[0-9]{1}$/).test(value) === false ) {
            return false;
        }

        var sum = 0;
        var last = value.charCodeAt(12) - 0x30;
        var bases = "234567892345";
        for (var i=0; i<12; i++) {
            sum += (value.charCodeAt(i) - 0x30) * (bases.charCodeAt(i) - 0x30);
        };
        var mod = sum % 11;
        if ( (11 - mod + 2) % 10 != last ) {
            return false;
        }

        return true;
    },

    isBizNo : function(value) {
        value = value.replace(/-/g, '');
        if (value == '') return true;

        if ( (/[0-9]{3}[0-9]{2}[0-9]{5}$/).test(value) === false ) {
            return false;
        }

        var sum = parseInt(value.charAt(0));
        var chkno = [0, 3, 7, 1, 3, 7, 1, 3];
        for (var i = 1; i < 8; i++) {
            sum += (parseInt(value.charAt(i)) * chkno[i]) % 10;
        }
        sum += Math.floor(parseInt(parseInt(value.charAt(8))) * 5 / 10);
        sum += (parseInt(value.charAt(8)) * 5) % 10 + parseInt(value.charAt(9));

        if (sum % 10 != 0) {
            return false;
        }

        return true;
    },

    isJuriNo : function(value) {
        value = value.replace(/-/g, '');
        if (value == '') return true;

        if ( (/^([0-9]{6})-?([0-9]{7})$/).test(value) === false ) {
            return false;
        }

        var sum = 0;
        var last = parseInt(value.charAt(12), 10);
        for (var i=0; i<12; i++) {
            if (i % 2 == 0) {  // * 1
                sum += parseInt(value.charAt(i), 10);
            } else {    // * 2
                sum += parseInt(value.charAt(i), 10) * 2;
            };
        };

        var mod = sum % 10;
        if( (10 - mod) % 10 != last ){
            return false;
        }

        return true;
    },

    isPhone : function(value) {
        value = value.replace(/-/g, '');
        if (value == '') return true;

        return (/^(02|0[0-9]{2,3})[1-9]{1}[0-9]{2,3}[0-9]{4}$/).test(value);
    },

    isMobile : function(value) {
        value = value.replace(/-/g, '');
        if (value == '') return true;

        return (/^01[016789][1-9]{1}[0-9]{2,3}[0-9]{4}$/).test(value);
    },

    isZipcode : function(value) {
        value = value.replace(/-/g, '');
        if (value == '') return true;

        return (/^[0-9]{3}[0-9]{3}$/).test(value);
    },

    isIp : function(value) {
        if (value == '') return true;

        return (/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){2,}$/).test(value);
    },

    isEmail : function(value) {
        value = $.trim(value);
        if (value == '') return true;

        return (/^([a-z0-9\_\-\.]+)@([a-z0-9\_\-]+\.)+[a-z]{2,63}$/i).test(value);
    },

    isUrl : function(value) {
        if (value == '') return true;

        return (/http[s]?:\/\/[a-z0-9_\-]+(\.[a-z0-9_\-]+)+/i).test(value);
    },

    isDate : function(value) {
        value = value.replace(/-/g, '');
        if (value == '') return true;

        return (/^[12][0-9]{3}(([0]?[1-9])|([1][012]))[0-3]?[0-9]$/).test(value);
    },

    isPassport : function(value) {
        if (value == '') return true;

        //일반 여권
        if ( (/^[A-Z]{2}[0-9]{7}$/).test(value) === true ) {
            return true;
        }

        //전자 여권
        if ( (/^[A-Z]{1}[0-9]{8}$/).test(value) === true ) {
            return true;
        }

        return false;
    },

    isNumberMin : function(value, limit) {
        value = this.parent.Helper.getNumberConv(value);
        limit = this.parent.Helper.getNumberConv(limit);

        if (value < limit) {
            return false;
        }

        return true;
    },

    isNumberMax : function(value, limit) {
        value = this.parent.Helper.getNumberConv(value);
        limit = this.parent.Helper.getNumberConv(limit);

        if (value > limit) {
            return false;
        }

        return true;
    },

    isNumberRange : function(value, min, max) {
        value = this.parent.Helper.getNumberConv(value);

        min = this.parent.Helper.getNumberConv(min);
        max = this.parent.Helper.getNumberConv(max);

        if (value < min || value > max) {
            return false;
        }

        return true;
    }
};

/**
 * FwValidator.Filter
 *
 * @package     jquery
 * @subpackage  validator
 */

FwValidator.Filter = {

    parent : FwValidator,

    isFill : function(Response, cond) {
        if (typeof cond != 'string') {
            var count = cond.length;
            var result = this.parent.Helper.getResult(Response, parent.CODE_SUCCESS);

            for (var i = 0; i < count; ++i) {
                result = this._fillConditionCheck(Response, cond[i]);

                if (result.passed === true) {
                    return result;
                }
            }

            return result;
        }

        return this._fillConditionCheck(Response, cond);
    },

    isMatch : function(Response, sField) {
        if(Response.elmCurrValue == ''){
            return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
        }

        //Radio 나 Checkbox의 경우 무시
        if(Response.elmCurrFieldType == 'radio' || Response.elmCurrFieldType == 'checkbox'){
            return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
        }

        var elmTarget = $('#'+sField);
        var elmTargetValue = elmTarget.val();

        if (Response.elmCurrValue != elmTargetValue) {
            var label = elmTarget.attr(this.parent.ATTR_LABEL);
            var match = label ? label : sField;

            Response.elmCurrErrorMsg = Response.elmCurrErrorMsg.replace(/\{match\}/i, match);

            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isMax : function(Response, iLen) {
        var result = this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);

        if (Response.elmCurrFieldType == 'radio' || Response.elmCurrFieldType == 'checkbox') {
            var chkCount = 0;
            var sName = Response.elmCurrField.attr('name');

            $('input[name="'+sName+'"]').each(function(i){
                if ($(this).get(0).checked === true) {
                    ++chkCount;
                }
            });

            if (chkCount > iLen) {
                result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            }

        } else {
            var len = Response.elmCurrValue.length;

            if (len > iLen) {
                result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            }
        }

        if (result.passed === this.parent.CODE_FAIL) {
            result.msg = result.msg.replace(/\{max\}/i, iLen);
        }

        return result;
    },

    isMin : function(Response, iLen) {
        var result = this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);

        if(Response.elmCurrFieldType == 'radio' || Response.elmCurrFieldType == 'checkbox'){
            var chkCount = 0;
            var sName = Response.elmCurrField.attr('name');

            $('input[name="'+sName+'"]').each(function(i){
                if($(this).get(0).checked === true){
                    ++chkCount;
                }
            });

            if (chkCount < iLen) {
                result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            }

        }else{
            var len = Response.elmCurrValue.length;

            if(len < iLen){
                result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            }
        }

        if(result.passed === this.parent.CODE_FAIL){
            result.msg = result.msg.replace(/\{min\}/i, iLen);
        }

        return result;
    },

    isNumber : function(Response, iCond) {
        var result = this.parent.Verify.isNumber(Response.elmCurrValue, iCond);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isIdentity : function(Response){
        var result = this.parent.Verify.isIdentity(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isKorean : function(Response){
        var result = this.parent.Verify.isKorean(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isAlpha : function(Response){
        var result = this.parent.Verify.isAlpha(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isAlphaLower : function(Response){
        var result = this.parent.Verify.isAlphaLower(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isAlphaUpper : function(Response){
        var result = this.parent.Verify.isAlphaUpper(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isAlphaNum : function(Response){
        var result = this.parent.Verify.isAlphaNum(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isAlphaNumLower : function(Response){
        var result = this.parent.Verify.isAlphaNumLower(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isAlphaNumUpper : function(Response){
        var result = this.parent.Verify.isAlphaNumUpper(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isAlphaDash : function(Response){
        var result = this.parent.Verify.isAlphaDash(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isAlphaDashLower : function(Response){
        var result = this.parent.Verify.isAlphaDashLower(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isAlphaDashUpper : function(Response){
        var result = this.parent.Verify.isAlphaDashUpper(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isSsn : function(Response){
        var result = this.parent.Verify.isSsn(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isForeignerNo : function(Response){
        var result = this.parent.Verify.isForeignerNo(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isBizNo : function(Response){
        var result = this.parent.Verify.isBizNo(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isJuriNo : function(Response){
        var result = this.parent.Verify.isJuriNo(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isPhone : function(Response){
        var result = this.parent.Verify.isPhone(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isMobile : function(Response){
        var result = this.parent.Verify.isMobile(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isZipcode : function(Response){
        var result = this.parent.Verify.isZipcode(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isIp : function(Response){
        var result = this.parent.Verify.isIp(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isEmail : function(Response){
        var result = this.parent.Verify.isEmail(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isUrl : function(Response){
        var result = this.parent.Verify.isUrl(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isDate : function(Response){
        var result = this.parent.Verify.isDate(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isRegex : function(Response, regex){
        regex = eval(regex);

        if( regex.test(Response.elmCurrValue) === false ){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isPassport : function(Response){
        var result = this.parent.Verify.isPassport(Response.elmCurrValue);

        if(result === false){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);
    },

    isSimplexEditorFill : function(Response){

        var result = eval(Response.elmCurrValue + ".isEmptyContent();");

        if(result === true){
            return this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
        }

        return this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);

    },

    isMaxByte : function(Response, iLen) {
        var result = this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);

        var len = this.parent.Helper.getByte(Response.elmCurrValue);

        if (len > iLen) {
            result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            result.msg = result.msg.replace(/\{max\}/i, iLen);
        }

        return result;
    },

    isMinByte : function(Response, iLen) {
        var result = this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);

        var len = this.parent.Helper.getByte(Response.elmCurrValue);

        if (len < iLen) {
            result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            result.msg = result.msg.replace(/\{min\}/i, iLen);
        }

        return result;
    },

    isByteRange : function(Response, range) {
        var result = this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);

        var rangeInfo = this._getRangeNum(range);
        var iMin = rangeInfo.min;
        var iMax = rangeInfo.max;

        var len = this.parent.Helper.getByte(Response.elmCurrValue);

        if (len < iMin || len > iMax) {
            result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            result.msg = result.msg.replace(/\{min\}/i, iMin);
            result.msg = result.msg.replace(/\{max\}/i, iMax);
        }

        return result;
    },

    isLengthRange : function(Response, range) {
        var result = this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);

        var rangeInfo = this._getRangeNum(range);
        var iMin = rangeInfo.min;
        var iMax = rangeInfo.max;

        var resultMin = this.isMin(Response, iMin);
        var resultMax = this.isMax(Response, iMax);

        if (resultMin.passed === this.parent.CODE_FAIL || resultMax.passed === this.parent.CODE_FAIL) {
            result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            result.msg = result.msg.replace(/\{min\}/i, iMin);
            result.msg = result.msg.replace(/\{max\}/i, iMax);
        }

        return result;
    },

    isNumberMin : function(Response, iLimit) {
        var check = this.parent.Verify.isNumberMin(Response.elmCurrValue, iLimit);
        var result = this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);

        if(check === false){
            result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            result.msg = result.msg.replace(/\{min\}/i, iLimit);
        }

        return result;
    },

    isNumberMax : function(Response, iLimit) {
        var check = this.parent.Verify.isNumberMax(Response.elmCurrValue, iLimit);
        var result = this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);

        if(check === false){
            result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            result.msg = result.msg.replace(/\{max\}/i, iLimit);
        }

        return result;
    },

    isNumberRange : function(Response, range) {
        var iMin = range[0];
        var iMax = range[1];

        var check = this.parent.Verify.isNumberRange(Response.elmCurrValue, iMin, iMax);
        var result = this.parent.Helper.getResult(Response, this.parent.CODE_SUCCESS);

        if(check === false){
            result = this.parent.Helper.getResult(Response, this.parent.CODE_FAIL);
            result.msg = result.msg.replace(/\{min\}/i, iMin);
            result.msg = result.msg.replace(/\{max\}/i, iMax);
        }

        return result;
    },

    _getRangeNum : function(range) {
        var result = {};

        result.min = range[0] <= 0 ? 0 : parseInt(range[0]);
        result.max = range[1] <= 0 ? 0 : parseInt(range[1]);

        return result;
    },

    _fillConditionCheck : function(Response, cond) {
        cond = $.trim(cond);

        var parent = this.parent;

        //조건식이 들어오면 조건식에 맞을 경우만 필수값을 체크함
        if (cond) {
            var conditions = cond.split('=');
            var fieldId = $.trim(conditions[0]);
            var fieldVal = $.trim(conditions[1]);

            try {
                var val = parent.Helper.getValue(Response.formId, $('#'+fieldId));
                val = $.trim(val);

                if(fieldVal != val) {
                    return parent.Helper.getResult(Response, parent.CODE_SUCCESS);
                }
            } catch(e) {
                if (parent.DEBUG_MODE == true) {
                    Response.elmCurrErrorMsg = parent.msgs['isFillError'];
                    Response.elmCurrErrorMsg = Response.elmCurrErrorMsg.replace(/\{condition\}/i, cond);
                    return parent.Helper.getResult(Response, parent.CODE_FAIL);
                }

                return parent.Helper.getResult(Response, parent.CODE_SUCCESS);
            }
        }

        //Radio 나 Checkbox의 경우 선택한값이 있는지 여부를 체크함
        if (Response.elmCurrFieldType == 'radio' || Response.elmCurrFieldType == 'checkbox') {

            var sName = Response.elmCurrField.attr('name');
            var result = parent.Helper.getResult(Response, parent.CODE_FAIL);

            $('input[name="'+sName+'"]').each(function(i){
                if ($(this).get(0).checked === true) {
                    result = parent.Helper.getResult(Response, parent.CODE_SUCCESS);
                }
            });

            return result;

        }

        //일반 텍스트 박스
        if (Response.elmCurrValue != '') {
            return parent.Helper.getResult(Response, parent.CODE_SUCCESS);
        }

        return parent.Helper.getResult(Response, parent.CODE_FAIL);
    }
};

FwValidator.msgs = {

    //기본
    'isFill' : '{label} 항목은 필수 입력값입니다.',

    'isNumber' : '{label} 항목이 숫자 형식이 아닙니다.',

    'isEmail' : '{label} 항목이 이메일 형식이 아닙니다.',

    'isIdentity' : '{label} 항목이 아이디 형식이 아닙니다.',

    'isMax' : '{label} 을(를) {max}자 이하로 입력해주세요.',

    'isMin' : '{label} 항목이 {min}자(개) 이상으로 해주십시오 .',

    'isRegex' : '{label} 항목이 올바른 입력값이 아닙니다.',

    'isAlpha' : '{label} 항목이 영문이 아닙니다',

    'isAlphaLower' : '{label} 항목이 영문 소문자 형식이 아닙니다',

    'isAlphaUpper' : '{label} 항목이 영문 대문자 형식이 아닙니다',

    'isAlphaNum' : '{label} 항목이 영문이나 숫자 형식이 아닙니다.',

    'isAlphaNumLower' : '{label} 항목이 영문 소문자 혹은 숫자 형식이 아닙니다.',

    'isAlphaNumUpper' : '{label} 항목이 영문 대문자 혹은 숫자 형식이 아닙니다.',

    'isAlphaDash' : '{label} 항목이 [영문,숫자,_,-] 형식이 아닙니다.',

    'isAlphaDashLower' : '{label} 항목이 [영문 소문자,숫자,_,-] 형식이 아닙니다.',

    'isAlphaDashUpper' : '{label} 항목이 [영문 대문자,숫자,_,-] 형식이 아닙니다.',

    'isKorean' : '{label} 항목이 한국어 형식이 아닙니다.',

    'isUrl' : '{label} 항목이 URL 형식이 아닙니다.',

    'isSsn' : '{label} 항목이 주민등록번호 형식이 아닙니다.',

    'isForeignerNo' : '{label} 항목이 외국인등록번호 형식이 아닙니다.',

    'isBizNo' : '{label} 항목이 사업자번호 형식이 아닙니다.',

    'isPhone' : '{label} 항목이 전화번호 형식이 아닙니다.',

    'isMobile' : '{label} 항목이 핸드폰 형식이 아닙니다.',

    'isZipcode' : '{label} 항목이 우편번호 형식이 아닙니다.',

    'isJuriNo' : '{label} 항목이 법인번호 형식이 아닙니다.',

    'isIp' : '{label} 항목이 아이피 형식이 아닙니다.',

    'isDate' : '{label} 항목이 날짜 형식이 아닙니다.',

    'isMatch' : '{label} 항목과 {match} 항목이 같지 않습니다.',

    'isSuccess' : '{label} 항목의 데이터는 전송할 수 없습니다.',

    'isSimplexEditorFill' : '{label}(을/를) 입력하세요',

    'isPassport' : '{label} 항목이 여권번호 형식이 아닙니다.',

    'isMaxByte' : '{label} 항목은 {max}bytes 이하로 해주십시오.',

    'isMinByte' : '{label} 항목은 {min}bytes 이상으로 해주십시오.',

    'isByteRange' : '{label} 항목은 {min} ~ {max}bytes 범위로 해주십시오.',

    'isLengthRange' : '{label} 항목은 {min} ~ {max}자(개) 범위로 해주십시오.',

    'isNumberMin' : '{label} 항목은 {min} 이상으로 해주십시오.',

    'isNumberMax' : '{label} 항목은 {max} 이하로 해주십시오.',

    'isNumberRange' : '{label} 항목은 {min} ~ {max} 범위로 해주세요.',


    //디버깅
    'notMethod' : '{label} 항목에 존재하지 않는 필터를 사용했습니다.',

    'isFillError' : "[{label}] 필드의 isFill {condition} 문장이 잘못되었습니다.\r\n해당 필드의 아이디를 확인하세요."

};

FwValidator.Handler.overrideMsgs({

    //기본
    'isFill' : sprintf(__('IS.REQUIRED.FIELD', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isNumber' : sprintf(__('MAY.ONLY.CONTAIN.NUMBERS', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isEmail' : sprintf(__('VALID.EMAIL.ADDRESS', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isIdentity' : sprintf(__('FIELD.CORRECT.ID.FORMAT', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isMax' : sprintf(__('EXCEED.CHARACTERS.LENGTH', 'RESOUCE.JS.VALIDATOR'), '{label}', '{max}'),

    'isMin' : sprintf(__('MUST.AT.LEAST.CHARACTERS', 'RESOUCE.JS.VALIDATOR'), '{label}', '{min}'),

    'isRegex' : sprintf(__('FIELD.IN.CORRECT.FORMAT', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isAlpha' : sprintf(__('ALPHABETICAL.CHARACTERS', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isAlphaLower' : sprintf(__('CONTAIN.LOWERCASE.LETTERS', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isAlphaUpper' : sprintf(__('CONTAIN.UPPERCASE.LETTERS', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isAlphaNum' : sprintf(__('ALPHANUMERIC.CHARACTERS', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isAlphaNumLower' : sprintf(__('CONTAIN.LOWERCASE.LETTERS.001', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isAlphaNumUpper' : sprintf(__('CONTAIN.UPPERCASE.LETTERS.001', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isAlphaDash' : sprintf(__('UNDERSCORES.DASHES', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isAlphaDashLower' : sprintf(__('UNDERSCORES.DASHES.001', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isAlphaDashUpper' : sprintf(__('UNDERSCORES.DASHES.002', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isKorean' : sprintf(__('CONTAIN.KOREAN.CHARACTERS', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isUrl' : sprintf(__('MUST.CONTAIN.VALID.URL', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isSsn' : sprintf(__('MUST.CONTAIN.VALID.SSN', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isForeignerNo' : sprintf(__('ALIEN.REGISTRATION.NUMBER', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isBizNo' : sprintf(__('REGISTRATION.NUMBER', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isPhone' : sprintf(__('VALID.PHONE.NUMBER', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isMobile' : sprintf(__('VALID.MOBILE.PHONE.NUMBER', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isZipcode' : sprintf(__('CONTAIN.VALID.ZIP.CODE', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isJuriNo' : sprintf(__('CORPORATE.IDENTITY.NUMBER', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isIp' : sprintf(__('MUST.CONTAIN.VALID.IP', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isDate' : sprintf(__('MUST.CONTAIN.VALID.DATE', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isMatch' : sprintf(__('THE.FIELD.DOES.NOT.MATCH', 'RESOUCE.JS.VALIDATOR'), '{label}', '{match}'),

    'isSuccess' : sprintf(__('THE.DATA.BE.TRANSFERRED', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isSimplexEditorFill' : sprintf(__('THE.FIELD.MUST.HAVE.VALUE', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isPassport' : sprintf(__('VALID.PASSPORT.NUMBER', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isMaxByte' : sprintf(__('VALUE.CAN.NOT.EXCEED', 'RESOUCE.JS.VALIDATOR'), '{label}', '{max}'),

    'isMinByte' : sprintf(__('THE.FIELD.VALUE.MUST.BE', 'RESOUCE.JS.VALIDATOR'), '{label}', '{min}'),

    'isByteRange' : sprintf(__('THE.FIELD.VALUE.MUST.BE.001', 'RESOUCE.JS.VALIDATOR'), '{label}', '{min}', '{max}'),

    'isLengthRange' : sprintf(__('MUST.CHARACTERS.LENGTH', 'RESOUCE.JS.VALIDATOR'), '{label}', '{min}', '{max}'),

    'isNumberMin' : sprintf(__('THE.FIELD.VALUE.MUST.BE.002', 'RESOUCE.JS.VALIDATOR'), '{label}', '{min}'),

    'isNumberMax' : sprintf(__('VALUE.CAN.NOT.EXCEED.001', 'RESOUCE.JS.VALIDATOR'), '{label}', '{max}'),

    'isNumberRange' : sprintf(__('THE.FIELD.VALUE.MUST.BE.003', 'RESOUCE.JS.VALIDATOR'), '{label}', '{min}', '{max}'),


    //디버깅
    'notMethod' : sprintf(__('FILTER.WAS.USED.FIELD', 'RESOUCE.JS.VALIDATOR'), '{label}'),

    'isFillError' : sprintf(__('SENTENCE.INCORRECT.PLEASE', 'RESOUCE.JS.VALIDATOR'), '{label}', '{condition}')

});
/**
 * 엘리먼트 종류별 값 가져오기 form 에 의한 동일한 name 값 구별
 *
 * - 오브젝트를 받아서 사용할 수 있게함.
 *
 * @param String id
 * @return
 * @author 박난하 <nhpark@simplexi.com>, 백충덕 <cdbaek@simplexi.com>, 이재욱 <jwlee03@simplexi.com>
 */
AuthSSLManager.getValue = function(id) {
    //id 가 string인 경우
    if (typeof id == 'string') {
        var divide, o, type;

        divide = id.split('::');
        if (divide.length == 1) {
            o = document.getElementsByName(id);
        } else {
            var frm = divide[0], id = divide[1];

            // radio, checkbox
            if ($('#'+ $.escapeSelector(id)).length==0) {
                val = this.checkbox({'name': id, 'mode': 'val'});
                return val;
            }
            o = document.forms[frm][id];
        }

        if ( o == null || o == undefined || o.value == null || o.value == undefined ) {
            o = document.getElementsByName(id);
            // 전체 html 에선 id 값이 있지만 form 밖에 있을수 있으므로 조건추가 (ECHOSTING-265537)
            val = (o[0] == undefined) ? '' : o[0].value;
        } else {
            val = o.value;
        }

        return val;

    } else if (typeof id == 'object') {
        //id가 object인 경우

        //오직 하나의 오브젝트에 대해서만 처리
        if ($(id).length == 1) {
            return $(id).val();
        } else {
            return '';
        }

    } else {
        // id가 string 또는 object가 아닐 경우 빈 값 리턴
        return '';
    }
};

/**
 * 엘리먼트 종류별 값 가져오기 form 에 의한 동일한 name 값 구별
 * @param String id
 * @return
 * @author 박난하 <nhpark@simplexi.com>, 백충덕 <cdbaek@simplexi.com>
 */
AuthSSLManager.getValuePay = function(id) {
    var divide, o, type;

    // id가 string이 아닐 경우 빈 값 리턴
    if (typeof id != 'string') return '';

    divide = id.split('::');
    var frm = divide[0], id = divide[1];

    // radio, checkbox
    if ($('#'+id).length==0) {
        val = this.checkbox({'name': id, 'mode': 'val'});
        return val;
    }

    o = document.forms[frm][id];

    if ( o == null || o == undefined || o.value == null || o.value == undefined ) {
        o = document.getElementsByName(id);
        val = o[0].value;
    } else {
        val = o.value;
    }

    return val;
};

/**
 * 암호화 param 데이터 세팅
 * @param array param 암호화 관련
 * @return string p 암호화 param
 * @author 박난하 <nhpark@simplexi.com>
 * */
AuthSSLManager.setParam = function(param) {
    var p = [];
        if (param['auth_mode'] == 'encrypt1.9') {
            p.push('auth_mode=encrypt');
        } else {
            p.push('auth_mode=' + param['auth_mode']);
        }
        p.push('auth_callbackName=' + param['auth_callbackName']);
    switch(param['auth_mode']) {
        case 'encrypt1.9':
            var aEle = param['aEleId'], o, p2 = {}, v;
            var divide = '';
            var id = '';
            for ( var i in aEle ) {
                if (aEle.hasOwnProperty(i) == false) continue;
                v = this.getValuePay(aEle[i]);

                if ( v == -1 ) continue;

                divide = aEle[i].split('::');
                id = divide[1];

                p2[id] = this.getValuePay(aEle[i]);
            }
            p.push('auth_string=' + encodeURIComponent(__JSON.stringify(p2)));
            break;
        case 'encrypt':
            var aEle = param['aEleId'], o, p2 = {}, v;
            for ( var i in aEle ) {
                if (aEle.hasOwnProperty(i) == false) continue;
                v = this.getValue(aEle[i]);

                if ( v == -1 ) continue;

                //암호화 대상이 오브젝트인경우 id값이 key가 된다.
                if (typeof aEle[i] == 'object') {
                    p2[$(aEle[i]).attr('id')] = this.getValue(aEle[i]);
                } else {
                    p2[aEle[i]] = this.getValue(aEle[i]);
                }
            }
            p.push('auth_string=' + encodeURIComponent(__JSON.stringify(p2)));
            break;
        case 'decrypt':
        case 'decryptClient':
            p.push('auth_string=' + encodeURIComponent(param['auth_string']));
            break;
    }

    return p;
};


/**
 * radio, checkbox 값 가져오기
 * @param object options 옵션
 * @return string radio 또는 checkbox value 반환
 * @author 박난하 <nhpark@simplexi.com>, 백충덕 <cdbaek@simplexi.com>
 * */
AuthSSLManager.checkbox = function(options)
{
    var o = document.getElementsByName(options.name);
    if ( o == null ) return;

    // element 없음
    if (o.length<1) {
        var chk = false;
        var o = document.getElementById(options.name);
        if ( o == null ) return '';
        if ( o.checked == true ) var chk = true;
        return chk == true ? o.value : '';
    }

    var bChecked = false;
    var aChk = new Array();
    for ( var i = 0; i < o.length; i++ ) {
        var el = $('#'+o[i].id);

        if ( el.attr('checked') == true ) {
            // RADIO
            if (el.attr('type') == 'radio') return el.val();
            // CHECKBOX
            else if (el.attr('type') == 'checkbox') {
                aChk.push(el.val());
                bChecked = true;
            }
        }
    }

    if ( options.mode == 'val' ) {
        if (bChecked == false) return '';

        if (aChk.length>0) return aChk.join('|');
    }
};






/**
 * AuthSSL을 통해 submit을 할 폼 클래스
 * @author 백충덕 <cdbaek@simplexi.com>
 * @since 2011. 6. 16
 * */
var FormSSL = function()
{
    /**
     * 폼 아이디
     * @var string
     * */
    this.sFormId = null;
    /**
     * 암호화 시킬 엘리먼트 id 배열
     * @var array
     * */
    this.aEleId  = null;

    /**
     * onsubmit bind
     * @param string sFormId bind 할 폼 아이디
     * @param array  aEleId  암호화할 엘리먼트 id 배열
     * */
    this.bind = function(sFormId, aEleId)
    {
        var self = this;

        this.sFormId = sFormId;
        this.aEleId  = aEleId;

        var oForm = $('#'+sFormId);
        oForm.unbind('submit');
        oForm.bind('submit', function(){
            AuthSSL.Submit(self.sFormId, self.aEleId);

            return false;
        });
    };

    /**
     * AuthSSL submit 실행
     * */
    this.submit = function()
    {
        AuthSSL.Submit(this.sFormId, this.aEleId);
        return false;
    };
};


/**
 * AuthSSL 폼 객체 리스트 관리
 * @author 백충덕 <cdbaek@simplexi.com>
 * @since 2011. 6. 16
 * */
var FormSSLContainer = {
    /**
     * 폼 객체 리스트
     * @var object
     * */
    aFormSSL: {},

    /**
     * 폼 객체 생성 및 리스트에 추가
     * @param string sFormId 객체로 생성할 폼 아이디
     * @param array  aEleId  암호화 할 엘리먼트 아이디
     * */
    create: function (sFormId, aEleId)
    {
        if (this.formExists(sFormId)==false) {
            var oFormSSL = new FormSSL();
            oFormSSL.bind(sFormId, aEleId);
            this.aFormSSL[sFormId] = oFormSSL;
        }
    },

    /**
     * 폼 아이디로 AuthSSL submit 실행
     * @param string sFormId 폼 아이디
     * */
    submit: function (sFormId)
    {
        if (this.formExists(sFormId)==false) return;

        this.aFormSSL[sFormId].submit();
    },

    /**
     * 폼 아이디로 FormSSLContainer에 해당 폼이 있는지 체크
     * @param string sFormId 체크할 폼 아이디
     * @return bool 폼이 있으면 true, 없으면 false
     * */
    formExists: function (sFormId)
    {
        if (!this.aFormSSL[sFormId]) return false;
        else return true;
    }
};



/**
 * AuthSSL 클래스
 * @author 백충덕 <cdbaek@simplexi.com>
 * @since 2011. 6. 16
 * */
var AuthSSL = {
    /**
     * SSL on/off
     * @var bool
     * */
    bIsSsl : true,
    /**
     * 폼 아이디
     * @var string
     * */
    sFormId : null,
    /**
     * 엘리먼트 아이디
     * @var array
     * */
    aEleId : null,
    /**
     * 폼 객체 (jQuery)
     * @var object
     * */
    oFormSubmit: null,
    /**
     * 암호화 된 문자열이 저장될 input hidden id
     * @var string
     * */
    sEncryptId : 'encrypted_str',
    /**
     * callback 함수 이름
     * @var string
     * */
    sCallbackName : 'AuthSSL.encryptSubmit_Complete',

    /**
     * 멤버변수 세팅
     * @param string sFormId 폼 아이디
     * @param array  aEleId  엘리먼트 아이디
     * */
    init: function(sFormId, aEleId)
    {
        this.sFormId = sFormId;
        this.aEleId  = aEleId;
        this.oFormSubmit = $('#' + sFormId);
    },

    /**
     * AuthSSLManager 존재여부 체크
     * @return bool 존재하면 true, 아니면 false 반환
     * */
    checkAvailable: function()
    {
        // AuthSSLManager가 없음
        if (typeof AuthSSLManager!='object') {
            alert('[Error]\nneed SSL Manager');
            return false;
        }

        return true;
    },

    /**
     * onsubmit bind
     * @param string sFormId 폼 아이디
     * @param array  aEleId  암호화하고자 하는 필드의 id
     * */
    Bind: function(sFormId, aEleId)
    {
        FormSSLContainer.create(sFormId, aEleId);
    },

    /**
     * 암호화 요청 함수 - submit
     * @param string sFormId 폼 아이디
     * @param array  aEleId  엘리먼트 아이디
     * */
    Submit: function(sFormId, aEleId) {
        // AuthSSLManager 존재여부 체크
        if (this.checkAvailable()==false) return false;

        // 폼 아이디, 엘리먼트 아이디 세팅
        this.init(sFormId, aEleId);

        // 암호화 요청이 아닐 경우 그냥 submit
        if (this.bIsSsl == false) {
            this.disabledSslSubmit();
            return false;
        }

        // 암호화 된 값을 받을 input_hidden 생성
        var oInput = document.createElement('input');
        oInput.type = 'hidden';
        oInput.name = oInput.id = this.sEncryptId;
        this.oFormSubmit.append(oInput);

        // 암호화 요청
        this.encrypt(this.aEleId, this.sCallbackName);
    },

    /**
     * 암호화 요청
     * @param array aEleId 암호화할 엘리먼트 id
     * @param string sCallbackName 콜백함수 이름
     * */
    encrypt: function(aEleId, sCallbackName) {
        AuthSSLManager.weave({
            'auth_mode'        : 'encrypt',
            'aEleId'           : aEleId,
            'auth_callbackName': sCallbackName
        });
    },

    /**
     * 암호화 처리결과 callback 함수
     * @param string sOutput 암호화 된 처리결과
     * */
    encryptSubmit_Complete: function(sOutput) {
        // Error
        if ( AuthSSLManager.isError(sOutput) == true ) {
            alert('[Error]\n'+sOutput);
            return;
        }

        // 암호화 처리된 엘리먼트의 value 제거
        this.delInputValue();

        // input_hidden에 암호화 된 결과값 대입
        this.oFormSubmit.find('[id="'+this.sEncryptId+'"]').val(sOutput);

        // Form Submit
        this.oFormSubmit.unbind('submit');

        this.delInputValue();

        this.oFormSubmit.submit();
    },

    /**
     * INPUT.RADIO, INPUT.CHECKBOX의 value 지움
     * @param string sName 값을 지우고자 하는 element의 name
     * */
    delRadioValue: function(sName) {
        var oEle = document.getElementsByName(sName);
        if (oEle.length>0) {

            for (var i = 0; i < oEle.length; i++) {

                oEle[i].value = '';

                if (oEle[i].defaultValue) {

                    oEle[i].defaultValue = '';
                }
            }
        }
    },

    /**
     * 암호화 될 폼 요소들의 값을 지움
     */
    delInputValue : function() {
        for (var i=0; i<this.aEleId.length; i++) {

            // 값을 지울 element의 id 가져오기
            var sID = AuthSSL.getEleId(this.aEleId[i]);
            var oEle = this.oFormSubmit.find('[id="' + sID + '"]');

            // id를 표기하지 않고 name만 표기한 radio, checkbox
            if (oEle.length == 0) {

                this.delRadioValue(sID);
                continue;
            }

            // SELECT
            if (oEle.is('SELECT')) {

                var oSelect = oEle.children('option:selected');
                oSelect.val('');
                oSelect.attr('value', '');
                oSelect.attr('defaultValue', '');
            }
            // INPUT.TEXT, INPUT.PASSWORD, TEXTAREA
            else {

                oEle.val('');
                oEle.attr('value', '');
                oEle.attr('defaultValue', '');
            }
        } // for
    },

    /**
     * 넘겨받은 id에서 폼 아이디와 구분자를 제거하여 가져오기
     * @param string sOrgID 원본 폼 아이디
     * @return string 폼 아이디와 구분자가 제거된 아이디 반환
     * */
    getEleId: function(sOrgID)
    {
        var sID = sOrgID;
        if (/::/.test(sID)==true) {
            var aTmp = sID.split('::');
            sID = aTmp[1];
        }

        return sID;
    },

    /**
     * bIsSsl이 false 일때 실행
     */
    disabledSslSubmit : function() {
        this.oFormSubmit.unbind('submit');
        this.oFormSubmit.submit();
    }
};


// validator submit hook
$(document).ready(function(){
    if (typeof FwValidator == 'undefined') return;

    FwValidator.Handler.setBeforeSubmit(function(elmForm){
        // AuthSSL 사용폼
        if (FormSSLContainer.formExists(elmForm.attr('id'))==true) {
            if (!FormSSLContainer) return true;

            FormSSLContainer.submit(elmForm.attr('id'));
            return false;
        }

        // AuthSSL 사용폼이 아닐 경우 그냥 submit
        return true;
    });
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
