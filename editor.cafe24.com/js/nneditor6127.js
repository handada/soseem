if (!window.NN || !NN.version) {
	var NN = {
		runtime: 'dev',
		version: 'r3.4.0.20200729.1',
		'urlCore': null,
		'charset': 'ko',
		'runtime': 'prod',
		'Browser': {
			'MSIE': !!(window.attachEvent && !window.opera),
			'MSIEVer': null,
			'isOpera': !!window.opera,
			'isWebKit': navigator.userAgent.indexOf('AppleWebKit/index.html') > -1,
			'isGecko': navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('KHTML') == -1,
			'Firefox': (navigator.userAgent.indexOf("Firefox") != -1)
		}
	};
	if (!window.SXI) {
		var SXI = {};
	}
	if (/(MSIE|Trident\/)/.test(navigator.userAgent) && !window.opera) {
		NN.Browser.MSIEVer = (document.documentMode || (!!document.body.dataset + !!window.atob + !!document.addEventListener + !!document.querySelector + !!window.XMLHttpRequest + !!document.compatMode + 5));
	}
	NN.Config = {};
	function LoadScript(url) {
		document.write('<scr' + 'ipt type="text/javascript" src="' + url + '" charset="UTF-8"><\/scr' + 'ipt>');
	}
	(function () {
		var aScript = document.getElementsByTagName('script'), src;
		for (var i = 0; i < aScript.length; i++) {
			src = aScript[i].src;
			if (src && src.indexOf('nneditor.html') != -1) {
				/runtime=([^&]+)/.exec(src) && (NN.runtime = RegExp.$1);
				NN.urlCore = src.substring(0, src.lastIndexOf('js/index.html'));
			}
			if (NN.urlCore && src.indexOf('c=') != -1) {
				NN.charset = src.substring(src.lastIndexOf('c=') + 2, src.lastIndexOf('c=') + 4);
			}
		}
		document.write('\
		<style type="text/css">\
			@import "' + NN.urlCore + 'css/style.css?ver=' + NN.version + '";\
			@import "' + NN.urlCore + 'css/styleie8.css?ver=' + NN.version + '";\
		</style>\
		');
		if (+document.documentMode >= 11) {
			LoadScript(NN.urlCore + 'lang/' + NN.charset + '.js?version=' + NN.version);
			LoadScript(NN.urlCore + 'js/r3.3.4.20160425/base.js?version=' + NN.version);
			LoadScript(NN.urlCore + 'js/r3.3.4.20160425/nneditorUtils.dev.js?version=' + NN.version);
			LoadScript(NN.urlCore + 'js/r3.3.4.20160425/nneditorRange.dev.js?version=' + NN.version);
			LoadScript(NN.urlCore + 'js/r3.3.4.20160425/nneditorCore.dev.js?version=' + NN.version);
		} else
			if (/^(dev|3\.3\.3)$/.test(NN.runtime)) {
				LoadScript(NN.urlCore + 'lang/' + NN.charset + '.js?version=' + NN.version);
				LoadScript(NN.urlCore + 'js/nneditorUtils.dev.js?version=' + NN.version);
				LoadScript(NN.urlCore + 'js/nneditorRange.dev.js?version=' + NN.version);
				LoadScript(NN.urlCore + 'js/nneditorCore.dev.js?version=' + NN.version);
			} else {
				LoadScript(NN.urlCore + 'lang/' + NN.charset + '.js?version=' + NN.version);
				LoadScript(NN.urlCore + 'js/nneditorUtils.dev.js?version=' + NN.version);
				LoadScript(NN.urlCore + 'js/nneditorRange.dev.js?version=' + NN.version);
				LoadScript(NN.urlCore + 'js/nneditorCore.dev.js?version=' + NN.version);
			}
	})();
}
