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

session_start();
if(isset($_SESSION['user_id']) === false){
  header('Location: login.php');
  exit;
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
// MsySQL用のDSN文字列
$dsn = 'mysql:dbname=' . $dbname . ';host=' . $host . ';charset=' . $charset;

try {
  // 現在日時を取得
  $now_date = date('Y-m-d H:i:s');
  // データベースに接続
  $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  
  if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $item_id="";
    if(isset($_POST['item_id']) === true){
      $item_id = $_POST['item_id'];
    } 
    $sql = 'SELECT * FROM ec_cart WHERE user_id = ? AND item_id = ?';
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $item_id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll();
    
    if(count($data) === 0){
      $sql = 'INSERT INTO ec_cart (user_id, item_id, amount, create_datetime) values (?,?,1,NOW())';
    }else{
      $sql = 'UPDATE ec_cart SET amount = amount+1, update_datetime = NOW() WHERE user_id = ? AND item_id = ?';
    }
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $item_id, PDO::PARAM_INT);
    $stmt->execute();
    $msg[] = 'カートに追加しました';
  }
  
  
   // SQL文を作成
  $sql = 'SELECT * FROM ec_item_master INNER JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id WHERE status = 1';
  // SQL文を実行する準備
  $stmt = $dbh->prepare($sql);
  // SQLを実行
  $stmt->execute();
  // レコードの取得
  $data = $stmt->fetchAll();
} catch (PDOException $e) {
  $err_msg[] = 'データベース処理でエラーが発生しました。理由：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="gakki_shop.css">
  <title>商品一覧ページ</title>
</head>

<body>
  <header>
    <h1>楽器オンラインSHOP</h1>
    <div class = "text">
    ようこそ<?php print $user_name; ?>さん<br>
    <a href="history.php">購入履歴を見る</a>
    <a href="login.php">ログアウトする</a>
    </div>
    <form class="form" method="post" action="cart.php">
    <input type="submit" value="カートの中を見る">
    </form>
  </header>
  <article>
      <?php foreach ($err_msg as $value) { ?>
      <p class = "text"><?php print $value; ?></p>
      <?php } ?>
      
      <?php foreach ($msg as $value) { ?>
      <p class = "text"><?php print $value; ?></p>
      <?php } ?>
      
      <?php foreach ($data as $value) { ?>
      <div class="block">
        <div class="item">
          <div class='img'><img src="<?php print $img_dir . $value['img']; ?>"></div>
          <div><?php print htmlspecialchars($value['item_name'], ENT_QUOTES, 'UTF-8'); ?></div>
          <div><?php print htmlspecialchars($value['price'], ENT_QUOTES, 'UTF-8'); ?>円</div>
          <?php if ((int) $value['stock'] === 0) { ?>
            <div class="sold_out"><?php print '売り切れ'; ?></div>
          <?php } else { ?>
          <form method="post">
          <input type="submit" value="カートに入れる"/>
          <input type="hidden" name="item_id" value="<?php print $value['item_id']; ?>"/>
          </form>
          <?php } ?>
        </div>
      </div>
      <?php } ?>
  </article>
</body>
</html>
