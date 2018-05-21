<?php

namespace app\common\logic;

use app\common\logic\LogicBase;
use think\Commutator;

class DiamondCertificate extends LogicBase{
 
 
	public function GetData($zid,$ztype,$weight=''){
			$CacheData = $this->GetInfo($zid,$ztype);
			if(empty($CacheData)){
				switch($ztype){
					case 1:		//GIA
						$url = 'https://www.gia.edu/report-check?reportno='.$zid;
						$doc =  $this->request_by_curl($url);
						preg_match('/\<input\s*type=\"hidden\"\s*name=\"encryptedString\"\s*id=\"encryptedString\"\s*value=\"(.*)\"\/\>/i',$doc,$matchs);
						$document = $this->request_by_curl('https://www.gia.edu/otmm_wcs_int/loadXML.jsp?ReportNumber='.$matchs[1]);
						$result   = (array)(simplexml_load_string($this->autoToutf8($document))->REPORT_DTLS->REPORT_DTL);

						$data 	  = [];
						foreach($result as $k=>$v){
						  if(trim($v)<>'')$data[$k]=strval($v);
						}

						//<a href="/index/other/onlineView/reportNumber/'.$data['REPORT_NO'].'.html" class="btn-link"  target="_blank">在线预览</a>
						if(!empty($data) && $data['MESSAGE']!='Please check your entries and try again.'){
							$datas = array('0'=>array('证书编号'	=>$data['REPORT_NO'],'颁发日期'=>$data['REPORT_DT']),
										   '1'=>array('证书类型'	=>$data['REPORT_TYPE'],'证书下载'=>'<a  onclick="download_pdf_ajax('.$data['REPORT_NO'].')"   id="pdfLink" class="btn-link"  target="_blank">请点击此处下载证书</a> &nbsp; '),
										   '2'=>array('尺寸'		=>$data['WIDTH'],'重量'=>$data['WEIGHT'].'carat'),
										   '3'=>array('颜色'		=>$data['COLOR'],'净度'=>$data['CLARITY']),	
										   '4'=>array('切工'		=>$data['FINAL_CUT'],'全深比'=>$data['DEPTH_PCT'].'%'),						   
										   '5'=>array('台宽比'		=>$data['TABLE_PCT'].'%','冠角'=>$data['CRN_AG']),	
										   '6'=>array('冠高比'		=>$data['CRN_HT'],'亭角'=>$data['PAV_AG']),
										   '7'=>array('亭深比'		=>$data['PAV_DP'],'星小面比'=>$data['STR_LN']),
										   '8'=>array('下腰小面比'	=>$data['LR_HALF'],'腰棱'=>$data['GIRDLE'].' , '.$data['GIRDLE_CONDITION'].' , '.$data['GIRDLE_PCT']),
										   '9'=>array('底尖'		=>$data['CULET_SIZE'],'拋光'=>$data['POLISH']),
										   '10'=>array('对称'		=>$data['SYMMETRY'],'荧光'=>$data['FLUORESCENCE_INTENSITY']),	
										   '11'=>array('净度特征'	=>$data['KEY_TO_SYMBOLS'],'腰码'=>$data['INSCRIPTION']),										   
										   '12'=>array('备注'		=>$data['REPORT_COMMENTS']),
										 );
							$this->InsertRow(['zid'=>$zid,'ztype'=>1,'data'=>serialize($datas)]);
						}
						

						break;
					case 2:		//IGI	F5D17713	0.07	M1F58085	0.41
						$zs_url = 'http://www.igiworldwide.com/ch/searchreport_postreq.php?r='.$zid;
						$doc = $this->getContent($zs_url);
						preg_match_all('/<span.*>(.*)<\/span>/isU',$doc,$result);
						$datas	= '';
						if(!empty($result[1])){
							$datas =  array('0'=>array($result[1][0]=>$result[1][1],$result[1][4]=>$result[1][5]),
											'1'=>array($result[1][8]=>$result[1][9],$result[1][12]=>$result[1][13]),
											'2'=>array($result[1][16]=>$result[1][17],$result[1][20]=>$result[1][21]),
											'3'=>array($result[1][24]=>$result[1][25],$result[1][28]=>$result[1][29]),	
											'4'=>array($result[1][32]=>$result[1][33],$result[1][36]=>$result[1][37]),						   
											'5'=>array($result[1][40]=>$result[1][41],$result[1][44]=>$result[1][45]),	
											'6'=>array($result[1][48]=>$result[1][49],$result[1][52]=>$result[1][53]),
											'7'=>array($result[1][56]=>$result[1][57],$result[1][60]=>$result[1][61]),
											'8'=>array($result[1][64]=>$result[1][65],'LASERSCRIBE'=>'IGI '.$result[1][1]),
							);
							$this->InsertRow(['zid'=>$zid,'ztype'=>2,'data'=>serialize($datas)]);
						}
						break;
					case 3:		//HRD
						// $zs_url = 'http://my.hrdantwerp.com?id=1&no_cache=1&L=3';
						$post_string = '?id=34&no_cache=1&L=3&record_number='.$zid.'&weight='.$weight;
						$document = $this->request_by_curl('https://my.hrdantwerp.com',$post_string);
						$document = $this->autoToutf8($document);
						preg_match_all('/<span.*>(.*)<\/span>/',$document,$mark_reportData);
						preg_match_all('/<strong.*>(.*)<\/strong>/isU',$document,$title_reportData);
						preg_match_all("/<table[^>]*?>(.*?)<\/table>/s",$document,$reportDatas);
						preg_match_all('/<td[^>]*>([\s\S]*?)<\/td>/i',$reportDatas[0][0],$result[0]);
						preg_match_all('/<td[^>]*>([\s\S]*?)<\/td>/i',$reportDatas[0][1],$result[1]);
						$datas = '';
						if(!empty($result[0][1])){
								$datas =  array('0'=>array('证书编号'=>$title_reportData[1][0],'颁发日期'=>$title_reportData[1][2]),
												'1'=>array('证书类型'=>$title_reportData[1][1],'抛光'=>$result[0][1][13]),
												'2'=>array('形状'=>$result[0][1][1],'荧光'=>$result[1][1][1]),
												'3'=>array('重量'=>$result[0][1][3],'尺寸'=>$result[1][1][3]),
												'4'=>array('色级'=>$result[0][1][5],'腰线'=>$result[1][1][5]),	
												'5'=>array('净度'=>$result[0][1][7],'底尖'=>$result[1][1][7]),						   
												'6'=>array('全深比'=>$result[1][1][9],'切工比例'=>$result[0][1][11]),	
												'7'=>array('台宽比'=>$result[1][1][11],'亭深比'=>$result[1][1][15]),
												'8'=>array('冠高比'=>$result[1][1][13],'对称性'=>$result[0][1][15]),
												'9'=>array('亭部下腰面水平投影长度比'=>$result[1][1][19],'冠高比与亭深比之和'=>$result[1][1][21]),
												'10'=>array('冠部上腰面水平投影长度比'=>$result[1][1][17],'备注'=>$mark_reportData[1][4]),
								);
							$this->InsertRow(['zid'=>$zid,'ztype'=>3,'data'=>serialize($datas)]);
						}
						break;
				}
			}else{
				$datas = $CacheData;
			}
			return $datas;	//返回证书数据
	}
	
	
	public function DownloadGIA($zid){
		$url = '';
		$doc =  $this->request_by_curl('https://www.gia.edu/report-check?reportno='.$zid);
		preg_match('/\<input\s*type=\"hidden\"\s*name=\"encryptedString\"\s*id=\"encryptedString\"\s*value=\"(.*)\"\/\>/i',$doc,$matchs);		
		if(strlen($matchs[1]) == 32){
			$url = 'https://www.gia.edu/otmm_wcs_int/proxy-pdf/?ReportNumber='.$zid.'&url=https://myapps.gia.edu/RptChkClient/reportClient.do?ReportNumber='.$matchs[1];
		}
		return $url;
		/*
		$b = './zs/data.cache/'.$this->InitData['id'].'.pdf';
		if(!file_exists($b)){		 
			$this->str2file(cacheDir,'log-'.date('Ymd',time()).'.php',date('H:i:s',time())."Start the download Certificate ... Certificate Number:$reportNo\r\n",'a+');
			//获取远程文件所采用的方法   
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:220.181.112.143', 'CLIENT-IP:220.181.112.143'));
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5');			
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_REFERER, "http://www.baidu.com/");	
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$pdf_file = curl_exec($ch);
			curl_close($ch);
			$size=strlen($pdf_file);
			if($size>2000){
				$fp2=@fopen($b,'w');  
				fwrite($fp2,$pdf_file);  
				fclose($fp2);  
			}
			unset($pdf_file,$url);
		}
		*/
	}
	
	
	
	
	public function request_by_curl($remote_server, $post_string='' ,$referer=''){
			set_time_limit(0);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $remote_server);
            if($referer<>''){
                    curl_setopt($ch, CURLOPT_REFERER, $referer);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);

            $data = curl_exec($ch);
            curl_close($ch);

            return $data;
    }
	
    public function autoToutf8($data,$to='utf-8'){ //字符串转换编码
		if(is_array($data)) {
			foreach($data as $key => $val) {
				$data[$key] = phpcharset($val, $to);
			}
		} else {
			$encode_array = array('ASCII', 'UTF-8', 'GBK', 'GB2312', 'BIG5');
			$encoded = mb_detect_encoding($data, $encode_array);
			$to = strtoupper($to);    //大写
			if($encoded != $to) {
				$data = mb_convert_encoding($data, $to, $encoded);
			}
		}
		return $data;
	}	
	
	
	public function getContent($url,$httpheader=0){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		if($httpheader==0){
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:220.181.112.143', 'CLIENT-IP:220.181.112.143'));
			curl_setopt($curl, CURLOPT_REFERER, "http://www.baidu.com/");
		}
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5');
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 0);
		curl_setopt($curl, 156, 99999); // problem solved
		curl_setopt($curl, CURLOPT_REFERER, $url);
		$content = curl_exec($curl);
		curl_close($curl);
		return $content;
	}

	
	
	/*文件路径，文件名称，内容，写入方式(默认为追加写入)*/
	function str2file($file_path,$file_name,$str,$mod='a+'){
		if(!file_exists($file_path)){if(!mkdir($file_path,0777,true)){return false;}}
		$file = fopen($file_path.$file_name,$mod);	/*/读写方式向文件追加内容，没有则创建/*/
		fwrite($file,$str);	/*/序列化数组，然后写入文件/*/
		fclose($file);	/*/保存关闭文件/*/
		return true;
	}	
	
	
	public function GetInfo($zid,$ztype){
		$Data = model("DiamondCertificate")->GetInfo($zid,$ztype);					
		if($Data) $Data = unserialize($Data['data']);
		return $Data;
	}	
	
	public function InsertRow($data){
		return model("DiamondCertificate")->InsertRow($data);
	}		
	
	

}