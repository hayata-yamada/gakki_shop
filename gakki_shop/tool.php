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
$msg = [];

// MsySQL用のDSN文字列
$dsn = 'mysql:dbname=' . $dbname . ';host=' . $host . ';charset=' . $charset;

session_start();
if(isset($_SESSION['user_id']) === false){
  header('Location: login.php');
  exit;
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

try {
  // 現在日時を取得
  $now_date = date('Y-m-d H:i:s');
  // データベースに接続
  $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sqlvalue']) === TRUE) {
      $sqlvalue = $_POST['sqlvalue'];
    }

    if ($sqlvalue === "insert") {

      if (isset($_POST['item_name']) !== TRUE || mb_strlen($_POST['item_name']) === 0) {
        $err_msg['item_name'] = '名前を入力してください';
      } else{
        $item_name = $_POST['item_name'];
      }

      if (isset($_POST['price']) !== TRUE || mb_strlen($_POST['price']) === 0) {
        $err_msg['price'] = '値段を入力してください';
      } elseif (preg_match('/^[0-9]+$/', $_POST['price']) === 0) {
        $err_msg['price'] = '値段は半角で入力してください';
      } else {
        $price = $_POST['price'];
      }

      if (isset($_POST['stock']) !== TRUE || mb_strlen($_POST['stock']) === 0) {
        $err_msg['stock'] = '個数を入力してください';
      } elseif (preg_match('/^[0-9]+$/', $_POST['stock']) === 0) {
        $err_msg['stock'] = '個数は半角で入力してください';
      } else {
        $stock = $_POST['stock'];
      }

      if (isset($_POST['status']) === TRUE) {
        if ((int) $_POST['status'] === 0 || (int) $_POST['status'] === 1) {
          $status = (int) $_POST['status'];
        } else {
          $err_msg[] = 'ステータスは公開か非公開を選択してください';
        }
      } else {
        $err_msg[] = 'ステータスを選択してください';
      }

      // HTTP POST でファイルがアップロードされたかどうかチェック
      if (is_uploaded_file($_FILES['img']['tmp_name']) === TRUE) {
        // 画像の拡張子を取得
        $extension = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
        // 指定の拡張子であるかどうかチェック
        if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'png') {
          // 保存する新しいファイル名の生成（ユニークな値を設定する）
          $img = sha1(uniqid(mt_rand(), true)) . '.' . $extension;
          // 同名ファイルが存在するかどうかチェック
          if (is_file($img_dir . $img) !== TRUE) {
            // アップロードされたファイルを指定ディレクトリに移動して保存
            if (move_uploaded_file($_FILES['img']['tmp_name'], $img_dir . $img) !== TRUE) {
              $err_msg[] = 'ファイルアップロードに失敗しました';
            }
          } else {
            $err_msg[] = 'ファイルアップロードに失敗しました。再度お試しください。';
          }
        } else {
          $err_msg[] = 'ファイル形式が異なります。画像ファイルは「JPEG」か「PNG」のみ利用可能です。';
        }
      } else {
        $err_msg[] = 'ファイルを選択してください';
      }
      
    } 

    if ($sqlvalue === "insert") {

      if (count($err_msg) === 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // SQL文を作成

        // トランザクション開始
        $dbh->beginTransaction();
        try {
          $sql = 'INSERT INTO ec_item_master(item_name, price, img, status, create_datetime, update_datetime) values(?,?,?,?,?,?)';
          // SQL文を実行する準備
          $stmt = $dbh->prepare($sql);
          // SQL文のプレースホルダに値をバインド
          $stmt->bindValue(1, $item_name, PDO::PARAM_STR);
          $stmt->bindValue(2, $price, PDO::PARAM_STR);
          $stmt->bindValue(3, $img, PDO::PARAM_STR);
          $stmt->bindValue(4, $status, PDO::PARAM_STR);
          $stmt->bindValue(5, $now_date, PDO::PARAM_STR);
          $stmt->bindValue(6, $now_date, PDO::PARAM_STR);
          // SQLを実行
          $stmt->execute();

          // SQL文を作成
          $sql = 'INSERT INTO ec_item_stock(item_id, stock, create_datetime, update_datetime) values(?,?,?,?)';
          // SQL文を実行する準備
          $stmt = $dbh->prepare($sql);
          // SQL文のプレースホルダに値をバインド
          $stmt->bindValue(1, $dbh->lastInsertId(), PDO::PARAM_STR);
          $stmt->bindValue(2, $stock, PDO::PARAM_STR);
          $stmt->bindValue(3, $now_date, PDO::PARAM_STR);
          $stmt->bindValue(4, $now_date, PDO::PARAM_STR);
          // SQLを実行
          $stmt->execute();

          $dbh->commit();
          $msg[] = '追加できました';
        } catch (PDOException $e) {
          // ロールバック処理
          $dbh->rollback();
          // 例外をスロー
          throw $e;
        }
      }
    }

    if ($sqlvalue === "update_stock") {
      if (isset($_POST['item_id']) === TRUE) {
        $stock = 0;
        if (isset($_POST['stock']) !== TRUE || mb_strlen($_POST['stock']) === 0) {
          $err_msg['stock'] = '個数を入力してください';
        } elseif (preg_match('/^[0-9]+$/', $_POST['stock']) === 0) {
          $err_msg['stock'] = '個数は半角で入力してください';
        } else {
          $stock = $_POST['stock'];
        }

        if (count($err_msg) === 0) {
          // TODO エラーメッセージがなければ更新処理
          $sql = 'UPDATE ec_item_stock SET stock = ?, update_datetime = ? WHERE item_id = ?';
          $stmt = $dbh->prepare($sql);
          $stmt->bindValue(1, $stock, PDO::PARAM_STR);
          $stmt->bindValue(2, $now_date, PDO::PARAM_STR);
          $stmt->bindValue(3, $_POST['item_id'], PDO::PARAM_STR);
          $stmt->execute();
          $msg[] = '在庫を更新しました';
        }
      }
    }

    if ($sqlvalue === "update_status") {
      $item_id = "";
      if (isset($_POST['item_id']) === TRUE) {
        $item_id = $_POST['item_id'];
      }
      $status = "";
      if (isset($_POST['status']) === TRUE) {
        $status = $_POST['status'];
      }
      if ($status !== "0" && $status !== "1"){
        $err_msg[] = 'ステータスが違います';
      }

      if (count($err_msg) === 0) {
        // TODO エラーメッセージがなければ更新処理
        $sql = 'UPDATE ec_item_master SET status = ?, update_datetime = ? WHERE item_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(1, $status, PDO::PARAM_STR);
        $stmt->bindValue(2, $now_date, PDO::PARAM_STR);
        $stmt->bindValue(3, $_POST['item_id'], PDO::PARAM_STR);
        $stmt->execute();
        $msg[] = 'ステータスを更新しました';
      }else{
        $err_msg[] = 'ステータスを更新できませんでした';
      }
    }
    
    if($sqlvalue === "delete"){
      $item_id = "";
      if (isset($_POST['item_id']) === TRUE) {
        $item_id = $_POST['item_id'];
      }
      
      $dbh -> beginTransaction();
      try{
        
        $sql = 'DELETE FROM ec_item_stock WHERE item_id = ?;';
        // SQL文を実行する準備
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(1, $item_id, PDO::PARAM_INT);
        // SQLを実行
        $stmt->execute();
        
        $sql = 'DELETE FROM ec_item_master WHERE item_id = ?;';
        // SQL文を実行する準備
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(1, $item_id, PDO::PARAM_INT);
        // SQLを実行
        $stmt->execute();
        $msg[] = '商品を削除しました';
        
        $dbh->commit();
      }catch (PDOException $e) {
          // ロールバック処理
          $dbh->rollback();
          // 例外をスロー
          throw $e;
        }
    }
  }

  // SQL文を作成
  $sql = 'SELECT * FROM ec_item_master INNER JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id';
  // SQL文を実行する準備
  $stmt = $dbh->prepare($sql);
  // SQLを実行
  $stmt->execute();
  // レコードの取得
  $data = $stmt->fetchAll();

  // コミット処理


} catch (PDOException $e) {
  $err_msg[] = 'データベース処理でエラーが発生しました。理由：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="gakki_shop.css">
  <title>商品管理ページ</title>
  <h1>商品管理ページ</h1>
  <style>
    table {
      width: 660px;
      border-collapse: collapse;
      
    }

    table,
    tr,
    th,
    td {
      border: solid 1px;
      padding: 10px;
      text-align: center;
      width: 100%;
      table-layout: fixed;
    }
  </style>
</head>

<body>

  <?php foreach ($err_msg as $value) { ?>
    <p class = "text"><?php print $value; ?></p>
  <?php } ?>
  <?php foreach ($msg as $value) { ?>
    <p class = "text"><?php print $value; ?></p>
  <?php } ?>
  <h2 class = "text">新規商品追加</h2>
    <form class = "text" method="post" enctype="multipart/form-data">
    <div><label>商品名： <input type="text" name="item_name" value=""></label></div>
    <div><label>&emsp;値段： <input type="text" name="price" value=""></label></div>
    <div><label>在庫数： <input type="text" name="stock" value=""></label></div>
    <div>商品画像<input type="file" name="img"></div>
    <select name="status">
      <option value="0">非公開</option>
      <option value="1">公開</option>
    </select>
    <div><input type="submit" value="商品追加"></div>
    <input type="hidden" name="sqlvalue" value="insert">
  </form>
  </div>
    <h2 class = "text">商品情報一覧</h2>
    <table>
      <tr>
        <th>商品画像</th>
        <th>商品名</th>
        <th>値段</th>
        <th>在庫数</th>
        <th>ステータス</th>
        <th></th>
      </tr>

      <?php foreach ($data as $value) { ?>
        <tr>
          <td><img class="img" src="<?php print $img_dir . $value['img']; ?>"></td>
          <td><?php print htmlspecialchars($value['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php print htmlspecialchars($value['price'], ENT_QUOTES, 'UTF-8'); ?>円</td><br>
          <td>
            <form method="post">
              <input type="text" name="stock" value="<?php print htmlspecialchars($value['stock'], ENT_QUOTES, 'UTF-8'); ?>">個
              <input type="hidden" name="item_id" value="<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="submit" value="変更">
              <input type="hidden" name="sqlvalue" value="update_stock">
            </form>
          </td>
          <td>
            <form method="post">
        <?php if((int)$value['status'] ===0){ ?>
              <input type="submit" value="非公開→公開">
              <input type="hidden" name="status" value="1">
        <?php }else {?>
              <input type="submit" value="公開→非公開">
              <input type="hidden" name="status" value="0">
              <?php } ?>
              <input type="hidden" name="item_id" value="<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="sqlvalue" value="update_status">
            </form>
          </td>
          <td>
            <form method = "post">
              <input type="hidden" name="item_id" value="<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'UTF-8'); ?>">
              <input type="submit" value="削除">
              <input type="hidden" name="sqlvalue" value="delete">    
            </form>
          </td>
        <tr>
        <?php } ?>
    </table>
</body>

</html>