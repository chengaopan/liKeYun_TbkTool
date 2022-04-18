<?php
header("Content-type:application/json");
error_reporting(E_ALL ^ E_DEPRECATED);
// 获得前端POST过来的参数
$zjy_long_title = trim($_POST["zjy_long_title"]);
$zjy_short_title = trim($_POST["zjy_short_title"]);
$zjy_yprice = trim($_POST["zjy_yprice"]);
$zjy_qhprice = trim($_POST["zjy_qhprice"]);
$zjy_tkl = trim($_POST["zjy_tkl"]);
$zjy_cover = trim($_POST["zjy_cover"]);
$zjy_yuming = trim($_POST["zjy_yuming"]);
$zjy_dwzapi = trim($_POST["zjy_dwzapi"]);
$zjy_template = trim($_POST["zjy_template"]);
$AppRedUrl = trim($_POST["AppRedUrl"]);


// 验证登录状态
session_start();
if(isset($_SESSION["tbktools.admin"])){

	// 当前登录的用户
	$user = $_SESSION["tbktools.admin"];

	// 过滤空值
	if (empty($zjy_long_title)) {
		$result = array(
			"result" => "101",
			"msg" => "长标题不得为空"
		);
	}else if (empty($zjy_short_title)) {
		$result = array(
			"result" => "102",
			"msg" => "短标题不得为空"
		);
	}else if (empty($zjy_yprice)) {
		$result = array(
			"result" => "103",
			"msg" => "原价不得为空"
		);
	}else if (empty($zjy_qhprice)) {
		$result = array(
			"result" => "104",
			"msg" => "券后价不得为空"
		);
	}else if (empty($zjy_tkl)) {
		$result = array(
			"result" => "105",
			"msg" => "淘口令不得为空"
		);
	}else if (empty($zjy_cover)) {
		$result = array(
			"result" => "106",
			"msg" => "主图不得为空"
		);
	}else if (empty($zjy_yuming)) {
		$result = array(
			"result" => "107",
			"msg" => "请选择落地页域名"
		);
	}else if (empty($zjy_dwzapi)) {
		$result = array(
			"result" => "108",
			"msg" => "请选择短网址API"
		);
	}else if (empty($zjy_template)) {
		$result = array(
			"result" => "109",
			"msg" => "请选择中间页模板"
		);
	}else{
		// 引入数据库配置
		include '../Db_Connect.php';

		// 连接数据库
		$conn = new mysqli($db_url, $db_user, $db_pwd, $db_name);
		mysqli_query($conn, "SET NAMES UTF-8");
		$zid = rand(100000,999999);

		if ($zjy_dwzapi == '1') {
			// 插入数据库
			$sql = "INSERT INTO tbk_zjy (zjy_id, zjy_long_title, zjy_short_title, zjy_yprice, zjy_qhprice, zjy_cover, zjy_yuming, zjy_template, zjy_dwz, zjy_tkl, user, AppRedUrl) VALUES ('$zid', '$zjy_long_title', '$zjy_short_title', '$zjy_yprice', '$zjy_qhprice', '$zjy_cover', '$zjy_yuming', '$zjy_template', '不生成短网址', '$zjy_tkl', '$user', '$AppRedUrl')";

			// 验证插入结果
			if ($conn->query($sql) === TRUE) {
			    $result = array(
					"result" => "100",
					"msg" => "创建成功"
				);
			} else {
			    $result = array(
					"result" => "110",
					"msg" => "创建失败，数据库配置发生错误，请查看Creat-Zjy-do.php是否存在服务错误，可F12打开开发者工具选择NetWork->Preview查看网络请求进行排查。"
				);
			}

			// 断开数据库连接
			$conn->close();
		}else if($zjy_dwzapi == '2'){
		    
		    // 生成短网址
            function getrandstr($length){
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
                $randStr = str_shuffle($str);
                $rands= substr($randStr,0,$length);
                return $rands;
            }
            $dwzkey = getrandstr(5);
            
            // 拼接本地短网址
		    $dwz_local = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER["REQUEST_URI"])).'s/'.$dwzkey;
		    
		    // 插入数据库
			$sql = "INSERT INTO tbk_zjy (zjy_id, zjy_long_title, zjy_short_title, zjy_yprice, zjy_qhprice, zjy_cover, zjy_yuming, zjy_template, zjy_dwz, zjy_tkl, user, AppRedUrl) VALUES ('$zid', '$zjy_long_title', '$zjy_short_title', '$zjy_yprice', '$zjy_qhprice', '$zjy_cover', '$zjy_yuming', '$zjy_template', '$dwz_local', '$zjy_tkl', '$user', '$AppRedUrl')";

			// 验证插入结果
			if ($conn->query($sql) === TRUE) {
			    $result = array(
					"result" => "100",
					"msg" => "创建成功"
				);
			} else {
			    $result = array(
					"result" => "110",
					"msg" => "创建失败，数据库配置发生错误，请查看Creat-Zjy-do.php是否存在服务错误，可F12打开开发者工具选择NetWork->Preview查看网络请求进行排查。"
				);
			}

			// 断开数据库连接
			$conn->close();
		}else{
		    
		    // 拼接长链接
    		$longUrl = $zjy_yuming.dirname(dirname($_SERVER["REQUEST_URI"]))."/"."index.php?zid=".$zid;
    
    		// 通过短网址API生成短网址
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $zjy_dwzapi.$longUrl);
    		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		$dwzStr = curl_exec($ch);
    		$arr_dwzStr = json_decode($dwzStr, true);
    		$dwz = $arr_dwzStr["dwz"];
    		curl_close($ch);
    		sleep(1); // 防止太快来不及生成，延时1秒
    		
			// 验证短网址是否生成成功
			if (strpos($dwz,'http') !== false) {
				// 插入数据库
				$sql = "INSERT INTO tbk_zjy (zjy_id, zjy_long_title, zjy_short_title, zjy_yprice, zjy_qhprice, zjy_cover, zjy_yuming, zjy_template, zjy_dwz, zjy_tkl, user, AppRedUrl) VALUES ('$zid', '$zjy_long_title', '$zjy_short_title', '$zjy_yprice', '$zjy_qhprice', '$zjy_cover', '$zjy_yuming', '$zjy_template', '$dwz', '$zjy_tkl', '$user', '$AppRedUrl')";
				
				// 验证插入结果
				if ($conn->query($sql) === TRUE) {
				    $result = array(
						"result" => "100",
						"msg" => "创建成功"
					);
				} else {
				    $result = array(
						"result" => "110",
						"msg" => "创建失败，数据库配置发生错误，请查看Creat-Zjy-do.php是否存在服务错误，可F12打开开发者工具选择NetWork->Preview查看网络请求进行排查。"
					);
				}

				// 断开数据库连接
				$conn->close();
			}else{
				$result = array(
					"result" => "112",
					"msg" => "短网址生成失败，请更换API或重试"
				);
			}
		}
	}
}else{
	$result = array(
		"result" => "111",
		"msg" => "未登录"
	);
}
// 返回JSON
echo json_encode($result,JSON_UNESCAPED_UNICODE);
?>