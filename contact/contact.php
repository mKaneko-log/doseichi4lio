<?php
define("SESSNM", "contact_confirm");
define("SALT", "ajinomoto");
define("PHAZE", strval(microtime()));

if(isset($_POST["val"]) && !empty($_POST["val"])) {
    // データがある時だけ処理する
    session_start();
    $log_file = "/var/www/html/__product/contact/log.txt";

    $post = json_decode($_POST["val"], true);
    if(strpos($post["doing"], "submit") !== false) {
        // 送信
        // error_log("\n --- [submit] ---", 3, $log_file);
        // error_log("\n post: ". $post[SESSNM], 3, $log_file);
        // error_log("\n sess: ". $_SESSION[SESSNM], 3, $log_file);
        if(isset($_SESSION[SESSNM]) && hash_equals($_SESSION[SESSNM], $post[SESSNM])) {
            // セッションが同じ
            $result = [];
            $file_name = date("Ymd") .".csv";
            $is_exist = file_exists("./". $file_name);
            $f_pointer = fopen("./". $file_name, "a");
            try {
                // csv作成
                
                if(!$is_exist) {
                    // 新規
                    $csv_header = array("時間", "名前", "メールアドレス", "詳細", "IPアドレス");
                    fputcsv($f_pointer, $csv_header);
                }
                $csv_value = array(
                    date("H:i:s"),
                    htmlspecialchars($post["input_name"]),
                    htmlspecialchars($post["input_email"]),
                    htmlspecialchars($post["input_text"]),
                    $_SERVER["REMOTE_ADDR"]
                );
                fputcsv($f_pointer, $csv_value);
                // -->| csv作成
                $result["result"] = true;
                $result["message"] = "「". $file_name ."」に書き込みました";
            } catch (Exception $th) {
                echo($th->getMessage());
                $result["result"] = false;
                $result["message"] = $th->getMessage();
                throw $th;
            } finally {
                fclose($f_pointer);
            }
            
            echo(json_encode($result));
        } else {
            $result = [];
            $result["result"] = false;
            $result["message"] = "セッションがおかしい... ". $_SESSION[SESSNM];
            echo(json_encode($result));
        }
    } elseif(strpos($post["doing"], "confirm") !== false) {
        // 確認
        $result = [];
        $result["input_name"] = htmlspecialchars($post["input_name"]);
        $result["input_email"] = htmlspecialchars($post["input_email"]);
        $result["input_text"] = htmlspecialchars($post["input_text"]);
        if(!filter_var($post["input_email"], FILTER_VALIDATE_EMAIL)) {
            $result["invalid"] = "input_email";
        } else {
            // error_log("\n\n----- ". PHAZE, 3, $log_file);
            $phaze = crypt(PHAZE, SALT);
            // error_log("\nret: ". $phaze, 3, $log_file);
            $result[SESSNM] = $phaze;
            // error_log("\n --> ". $result[SESSNM], 3, $log_file);
            // error_log("\nsess: ". $phaze, 3, $log_file);
            $_SESSION[SESSNM] = $phaze;
            // error_log("\n --> ". $_SESSION[SESSNM], 3, $log_file);
        }
        echo json_encode($result);
    }
}