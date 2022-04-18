<?php
header("Content-type:application/json");

// 获得前端POST过来的参数
$active_title = trim($_POST["active_title"]);
$active_yuming = trim($_POST["active_yuming"]);
$active_dwzapi = trim($_POST["active_dwzapi"]);

// 验证登录状态
session_start();
if(isset($_SESSION["tbktools.admin"])){
    
    // 当前登录的用户
    $user = $_SESSION["tbktools.admin"];
    
	// 过滤空值
	if (empty($active_title)) {
		$result = array(
			"result" => "101",
			"msg" => "标题不得为空"
		);
	}else if (empty($active_yuming)) {
		$result = array(
			"result" => "102",
			"msg" => "未选择域名"
		);
	}else if (empty($active_dwzapi)) {
		$result = array(
			"result" => "103",
			"msg" => "未选择短网址API"
		);
	}else{
		// 引入数据库配置
		include '../Db_Connect.php';

		// 连接数据库
		$conn = new mysqli($db_url, $db_user, $db_pwd, $db_name);
		mysqli_query($conn, "SET NAMES UTF-8");
		
		// 生成中间页id
		$active_id = rand(100000,999999);

		// 暂不生成短网址
		if ($active_dwzapi == '1') {

			// 插入数据库
			$sql = "INSERT INTO tbk_active_zjy (active_id, active_title, active_dwz, active_yuming, user) VALUES ('$active_id','$active_title','不生成短网址','$active_yuming','$user')";
			
			// 验证插入结果
			if ($conn->query($sql) === TRUE) {
			    $result = array(
					"result" => "100",
					"msg" => "创建成功"
				);
			} else {
			    $result = array(
					"result" => "110",
					"msg" => "创建失败，数据库配置发生错误，请查看Creat-ActiveZjy-do.php是否存在服务错误，可F12打开开发者工具选择NetWork->Preview查看网络请求进行排查。"
				);
			}

			// 断开数据库连接
			$conn->close();
			
		}else if($active_dwzapi == '2'){
		    
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
			$sql = "INSERT INTO tbk_active_zjy (active_id, active_title, active_dwz, active_yuming, user) VALUES ('$active_id','$active_title','$dwz_local','$active_yuming','$user')";
			
			// 验证插入结果
			if ($conn->query($sql) === TRUE) {
			    $result = array(
					"result" => "100",
					"msg" => "创建成功"
				);
			} else {
			    $result = array(
					"result" => "110",
					"msg" => "创建失败，数据库配置发生错误，请查看Creat-ActiveZjy-do.php是否存在服务错误，可F12打开开发者工具选择NetWork->Preview查看网络请求进行排查。"
				);
			}
			// 断开数据库连接
			$conn->close();
			
		}else{
		    
		    // 拼接长链接
    		$longUrl = $active_yuming.dirname(dirname($_SERVER["REQUEST_URI"]))."/"."activezjy.php?activeid=".$active_id;
    
    		// 通过短网址API生成短网址
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $active_dwzapi.$longUrl);
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
				$sql = "INSERT INTO tbk_active_zjy (active_id, active_title, active_dwz, active_yuming, user) VALUES ('$active_id','$active_title','$dwz','$active_yuming','$user')";
				
				// 验证插入结果
				if ($conn->query($sql) === TRUE) {
				    $result = array(
						"result" => "100",
						"msg" => "创建成功"
					);
				} else {
				    $result = array(
						"result" => "110",
						"msg" => "创建失败，数据库配置发生错误，请查看Creat-ActiveZjy-do.php是否存在服务错误，可F12打开开发者工具选择NetWork->Preview查看网络请求进行排查。"
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