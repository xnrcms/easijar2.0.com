<?php
class ControllerExtensionInterfaceTranslate extends Controller {

	//翻译-阿里云接口
	public function translate_aliyun($data)
	{

		if (!isset($data['d']) || !isset($data['q']) || !isset($data['s'])) return '';

		$apiurl 			= "https://trans.xiaohuaerai.com/trans";

	    $headers 			= [];
	    $appcode 			= "c8fab8ba97dc49b896c2daf7ca91ae3e";
	    array_push($headers, "Authorization:APPCODE " . $appcode);

	    //根据API的要求，定义相对应的Content-Type
	    array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");

	    $parame 			= [];
	    $parame['d'] 		= $data['d'];
	    $parame['q'] 		= $data['q'];
	    $parame['s'] 		= $data['s'];

		$data 				= curl_http($apiurl,$parame,'POST',$headers);
		$data 				= !empty($data) ? json_decode($data,true) : [];
		
		return (isset($data['status']) && $data['status'] === 200) ? $data['msg'] : '';
	}

	public function support_language()
	{
		$support 		= [
			'阿尔巴尼亚语'		=>'sq',
			'阿姆哈拉语'			=>'am',
			'阿拉伯语'			=>'ar',
			'亚美尼亚语'			=>'hy',
			'阿塞拜疆语'			=>'az',
			'巴斯克语'			=>'eu',
			'白俄罗斯语'			=>'be',
			'孟加拉语'			=>'bn',
			'波斯尼亚语'			=>'bs',
			'保加利亚语'			=>'bg',
			'加泰罗尼亚语'		=>'ca',
			'宿务语'				=>'ceb (ISO-639-2)',
			'中文（简体）'		=>'zh-CN (BCP-47)',
			'中文（繁体）'		=>'zh-TW (BCP-47)',
			'科西嘉语'			=>'co',
			'克罗地亚语'			=>'hr',
			'捷克语'				=>'cs',
			'丹麦语'				=>'da',
			'荷兰语'				=>'nl',
			'英语'				=>'en',
			'世界语'				=>'eo',
			'爱沙尼亚语'			=>'et',
			'芬兰语'				=>'fi',
			'法语'				=>'fr',
			'弗里斯兰语'			=>'fy',
			'加利西亚语'			=>'gl',
			'格鲁吉亚语'			=>'ka',
			'德语'				=>'de',
			'希腊语'				=>'el',
			'古吉拉特语'			=>'gu',
			'海地克里奥尔语'		=>'ht',
			'豪萨语'				=>'ha',
			'夏威夷语'			=>'haw (ISO-639-2)',
			'希伯来语'			=>'iw',
			'印地语'				=>'hi',
			'苗语'				=>'hmn (ISO-639-2)',
			'匈牙利语'			=>'hu',
			'冰岛语'				=>'is',
			'伊博语'				=>'ig',
			'印度尼西亚语'		=>'id',
			'爱尔兰语'			=>'ga',
			'意大利语'			=>'it',
			'日语'				=>'ja',
			'爪哇语'				=>'jw',
			'卡纳达语'			=>'kn',
			'哈萨克语'			=>'kk',
			'高棉语'				=>'km',
			'韩语'				=>'ko',
			'库尔德语'			=>'ku',
			'吉尔吉斯语'			=>'ky',
			'老挝语'				=>'lo',
			'拉丁语'				=>'la',
			'拉脱维亚语'			=>'lv',
			'立陶宛语'			=>'lt',
			'卢森堡语'			=>'lb',
			'马其顿语'			=>'mk',
			'马尔加什语'			=>'mg',
			'马来语'				=>'ms',
			'马拉雅拉姆语'		=>'ml',
			'马耳他语'			=>'mt',
			'毛利语'				=>'mi',
			'马拉地语'			=>'mr',
			'蒙古语'				=>'mn',
			'缅甸语'				=>'my',
			'尼泊尔语'			=>'ne',
			'挪威语'				=>'no',
			'尼杨扎语（齐切瓦语）'=>'ny',
			'普什图语'			=>'ps',
			'波斯语'				=>'fa',
			'波兰语'				=>'pl',
			'葡萄牙语(葡萄牙、巴西)'=>'pt',
			'旁遮普语'			=>'pa',
			'罗马尼亚语'			=>'ro',
			'俄语'				=>'ru',
			'萨摩亚语'			=>'sm',
			'苏格兰盖尔语'		=>'gd',
			'塞尔维亚语'			=>'sr',
			'塞索托语'			=>'st',
			'修纳语'				=>'sn',
			'信德语'				=>'sd',
			'僧伽罗语'			=>'si',
			'斯洛伐克语'			=>'sk',
			'斯洛文尼亚语'		=>'sl',
			'索马里语'			=>'so',
			'西班牙语'			=>'es',
			'巽他语'				=>'su',
			'斯瓦希里语'			=>'sw',
			'瑞典语'				=>'sv',
			'塔加路语（菲律宾语）'=>'tl',
			'塔吉克语'			=>'tg',
			'泰米尔语'			=>'ta',
			'泰卢固语'			=>'te',
			'泰语'				=>'th',
			'土耳其语'			=>'tr',
			'乌克兰语'			=>'uk',
			'乌尔都语'			=>'ur',
			'乌兹别克语'			=>'uz',
			'越南语'				=>'vi',
			'威尔士语'			=>'cy',
			'班图语'				=>'xh',
			'意第绪语'			=>'yi',
			'约鲁巴语'			=>'yo',
			'祖鲁语'				=>'zu',
		];
	}
}