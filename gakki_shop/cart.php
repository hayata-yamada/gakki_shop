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
$sqlvalue = '';
$total = 0;
// MsySQL用のDSN文字列
$dsn = 'mysql:dbname=' . $dbname . ';host=' . $host . ';charset=' . $charset;

session_start();
if(isset($_SESSION['user_id']) === false){
  header('Location: login.php');
  exit;
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

try{
    
  $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);    

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sqlvalue']) === TRUE) {
      $sqlvalue = $_POST['sqlvalue'];
    }
  
    if ($sqlvalue === "update_amount") {
        if (isset($_POST['amount']) === TRUE) {
          $amount = 0;
          if (isset($_POST['amount']) !== TRUE || mb_strlen($_POST['amount']) === 0) {
            $err_msg['amount'] = '個数を入力してください';
          } elseif (preg_match('/^[1-9][0-9]*$/', $_POST['amount']) === 0) {
            $err_msg['amount'] = '個数は半角で入力してください';
          } else {
            $amount = $_POST['amount'];
          }
  
          if (count($err_msg) === 0) {
            // TODO エラーメッセージがなければ更新処理
            $sql = 'UPDATE ec_cart SET amount = ?, update_datetime = NOW() WHERE id = ?';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1, $amount, PDO::PARAM_INT);
            $stmt->bindValue(2, $_POST['cart_id'], PDO::PARAM_INT);
            $stmt->execute();
            $msg[] = '個数を更新しました';
          }
        }
      }
      
      if ($sqlvalue === "delete_item"){
        $sql = 'DELETE FROM ec_cart WHERE id = ?;';
        // SQL文を実行する準備
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(1, $_POST['cart_id'], PDO::PARAM_INT);
        // SQLを実行
        $stmt->execute();
        $msg[] = '商品を削除しました';
      }
  }
  
    // SQL文を作成
  $sql = 'SELECT * FROM ec_item_master INNER JOIN ec_cart ON ec_item_master.item_id = ec_cart.item_id WHERE user_id = ? AND amount != 0';
  // SQL文を実行する準備
  $stmt = $dbh->prepare($sql);
  $stmt -> bindValue(1, $user_id, PDO::PARAM_INT);
  // SQLを実行
  $stmt->execute();
  // レコードの取得
  $data = $stmt->fetchAll();
  
  foreach($data as $row){
  $total += $row['price'] * $row['amount'];
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
  <title>ショッピングカート</title>
</head>

<body>
  <header>
    <h1>楽器オンラインSHOPカート</h1>
    <div class = "text">
    ようこそ<?php print $user_name; ?>さん<br>
    <a href="index.php">買い物を続ける</a>
    <a href="history.php">購入履歴を見る</a>
    <a href="login.php">ログアウトする</a><br>
    <p>合計金額：<?php print number_format($total); ?>円</p>
    </div>
  </header>

  <?php foreach ($err_msg as $value) { ?>
  <p class = "text"><?php print $value; ?></p>
  <?php } ?>
  
  <?php foreach ($msg as $value) { ?>
  <p class = "text"><?php print $value; ?></p>
  <?php } ?>
  
  <?php if(count($data) === 0){ ?>
  <p class = "text">カートに商品がありません</p>
  <?php } else { ?>
  <?php foreach ($data as $value) { ?>
  <div class="item">
    <form method="post">
      <div class="img"><img src="<?php print $img_dir . $value['img']; ?>"></div>
      <div><?php print htmlspecialchars($value['item_name'], ENT_QUOTES, 'UTF-8'); ?></div>
      <div><?php print htmlspecialchars($value['price'], ENT_QUOTES, 'UTF-8'); ?>円</div>
      <input type="text" name="amount" value="<?php print htmlspecialchars($value['amount'], ENT_QUOTES, 'UTF-8'); ?>">個
      <input type="hidden" name="cart_id" value="<?php print htmlspecialchars($value['id'], ENT_QUOTES, 'UTF-8'); ?>">
      <input type="submit" value="変更">
      <input type="hidden" name="sqlvalue" value="update_amount">
    </form>
    <form  method="post">
      <input type="submit" value="削除する">
      <input type="hidden" name="sqlvalue" value="delete_item"> 
      <input type="hidden" name="cart_id" value="<?php print htmlspecialchars($value['id'], ENT_QUOTES, 'UTF-8'); ?>">
    </form>
  </div>
      <?php } ?>
  <form method="post" action="result.php">
    <div class="buy"><label><input type="submit" name="buy" value="購入する"></label></div>
  </form>
  <?php } ?>
</body>
</html>
