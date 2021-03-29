<?php
$host     = 'localhost';
$username = 'codecamp39865';        // MySQLのユーザ名（マイページのアカウント情報を参照）
$password = 'codecamp39865';       // MySQLのパスワード（マイページのアカウント情報を参照）
$dbname   = 'codecamp39865';   // MySQLのDB名(このコースではMySQLのユーザ名と同じです）
$charset  = 'utf8';   // データベースの文字コード

$img_dir    = './gakki_img/';    // アップロードした画像ファイルの保存ディレクトリ
$data       = [];
$err_msg    = [];     // エラーメッセージ
$img = '';   // アップロードした新しい画像ファイル名
$item_id = '';
// MsySQL用のDSN文字列
$dsn = 'mysql:dbname=' . $dbname . ';host=' . $host . ';charset=' . $charset;
// アップロードした新しい画像ファイル名の登録、既存の画像ファイル名の取得

session_start();
if(isset($_SESSION['user_id']) === false){
  header('Location: login.php');
  exit;
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

try {
  // データベースに接続
  $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  
         // SQL文を作成
        $sql = 'SELECT * 
        FROM ec_item_history 
        WHERE ec_item_history.user_id = ? ORDER BY create_datetime DESC';        
        $stmt = $dbh->prepare($sql);
        $stmt -> bindValue(1, $user_id, PDO::PARAM_INT);
        // SQLを実行
        $stmt->execute();
        // レコードの取得
        $data = $stmt->fetchAll();
        
}catch (PDOException $e) {
  $err_msg[] = 'データベース処理でエラーが発生しました。理由：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="gakki_shop.css">
    <title>購入履歴</title>
</head>

<body>
  <header>
    <h1>購入履歴</h1>
    <div class = "text">
    ようこそ<?php print $user_name; ?>さん<br>
    <a href="cart.php">カートに戻る</a>
    <a href="index.php">買い物を続ける</a>
    <a href="login.php">ログアウトする</a>
    </div>
  </header>
  
    <?php foreach ($err_msg as $value) { ?>
    <p class = "text"><?php print $value; ?></p>
    <?php } ?>
    
    <?php foreach ($data as $value)  { ?>
    <div class="item">
      <div class='img'><img src="<?php print $img_dir . $value['img']; ?>"></div>
      <div><?php print htmlspecialchars($value['item_name'], ENT_QUOTES, 'UTF-8'); ?></div>
      <div><?php print htmlspecialchars($value['price'], ENT_QUOTES, 'UTF-8'); ?>円</div>
      <div><?php print htmlspecialchars($value['amount'], ENT_QUOTES, 'UTF-8'); ?>個</div>
      <div><?php print htmlspecialchars($value['create_datetime'], ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <?php } ?>
   
</body>

</html>