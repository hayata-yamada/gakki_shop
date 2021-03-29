<?php
$host     = 'localhost';
$username = 'codecamp39865';        // MySQLのユーザ名（マイページのアカウント情報を参照）
$password = 'codecamp39865';       // MySQLのパスワード（マイページのアカウント情報を参照）
$dbname   = 'codecamp39865';   // MySQLのDB名(このコースではMySQLのユーザ名と同じです）
$charset  = 'utf8';   // データベースの文字コード

$img_dir    = './gakki_img/';    // アップロードした画像ファイルの保存ディレクトリ
$data       = [];
$err_msg    = [];     // エラーメッセージ
$msg        = [];
$img = '';   // アップロードした新しい画像ファイル名

// MsySQL用のDSN文字列
$dsn = 'mysql:dbname=' . $dbname . ';host=' . $host . ';charset=' . $charset;

try {
      // データベースに接続
      $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      
      if($_SERVER['REQUEST_METHOD']=='POST'){
        
        if(isset($_POST['user_name']) !== TRUE || mb_strlen($_POST['user_name']) === 0){
          $err_msg['user_name'] = 'ユーザー名を入力してください';
        } elseif(preg_match("/^[a-zA-Z0-9]{6,100}$/",$_POST['user_name']) !== 1){
          $err_msg['user_name'] = 'ユーザー名の使用可能文字は半角英数字6文字以上です';
        } else{
          $user_name = $_POST['user_name'];
        }
        
        if(isset($_POST['password']) !== TRUE || mb_strlen($_POST['password']) === 0){
          $err_msg['password']= 'パスワードを入力してください';
        } elseif(preg_match('/^[a-zA-Z0-9]{6,100}$/', $_POST['password']) !== 1){
          $err_msg['password'] = 'パスワードの使用可能文字は半角英数字6文字以上です';
        } else{
          $password = $_POST['password'];
        }
      
       if(count($err_msg) === 0){
             // SQL文を作成
            $sql = 'SELECT user_name , password FROM ec_user WHERE user_name = ?';
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1, $user_name, PDO::PARAM_STR);
            // SQLを実行
            $stmt->execute();
            // レコードの取得
            $data = $stmt->fetchAll(); 
            
            if(count($data) > 0){
              $err_msg[] = '既にこのユーザー名は使われています';
            }
       }
       
        if (count($err_msg) === 0) { 
          try{
               // SQL文を作成
              $sql = 'INSERT INTO ec_user (user_name , password , create_datetime , update_datetime) value(?,?,NOW(),NOW())';
              // SQL文を実行する準備
              $stmt = $dbh->prepare($sql);
              // SQL文のプレースホルダに値をバインド
              $stmt->bindValue(1, $user_name, PDO::PARAM_STR);
              $stmt->bindValue(2, $password, PDO::PARAM_STR);
              // SQLを実行
              $stmt->execute();
              
              $msg[] = '登録できました';
              
            } catch (PDOException $e) {
              // ロールバック処理
            
              // 例外をスロー
              throw $e;
            }
        }  
      }    
    } catch (PDOException $e) {
      $err_msg[] = 'データベース処理でエラーが発生しました。理由：' . $e->getMessage();
    }

?>



<!DOCTYPE html>
<html lang="ja">
 <head>
   <meta charset="utf-8">
   <link rel="stylesheet" href="gakki_shop.css">
   <title>新規会員登録</title>
 </head>
 
 <body>
   <header>
     <h1>新規会員登録</h1>
     <div class="link"><a href="login.php">ログインページへ</a></div>
   </header>
    <?php foreach ($err_msg as $value) { ?>
    <p class = "text"><?php print $value; ?></p>
    <?php } ?>
    <?php foreach ($msg as $value) { ?>
    <p class = "text"><?php print $value; ?></p>
    <?php } ?>
    <div class="form">
     <form method="post">
       <input type="text" name="user_name" placeholder="ユーザー名">
       <input type="password" name="password" placeholder="パスワード"><br>
       <button type="submit">新規会員登録する</button>
     </form>
    </div>
 </body>
</html>