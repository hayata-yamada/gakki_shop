<?php
$host     = 'localhost';
$username = 'codecamp39865';        // MySQLのユーザ名（マイページのアカウント情報を参照）
$password = 'codecamp39865';       // MySQLのパスワード（マイページのアカウント情報を参照）
$dbname   = 'codecamp39865';   // MySQLのDB名(このコースではMySQLのユーザ名と同じです）
$charset  = 'utf8';   // データベースの文字コード

$img_dir    = './gakki_img/';    // アップロードした画像ファイルの保存ディレクトリ
$data       = [];
$err_msg    = []; 
$msg = [];
$img = '';   // アップロードした新しい画像ファイル名
$total = 0;
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
    
    if($_SERVER['REQUEST_METHOD']=='POST'){

         // SQL文を作成
        $sql = 'SELECT * 
        FROM ec_item_master 
        INNER JOIN ec_cart ON ec_item_master.item_id = ec_cart.item_id
        INNER JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
        WHERE ec_cart.user_id = ?';
      
        // SQL文を実行する準備
        $stmt = $dbh->prepare($sql);
        $stmt -> bindValue(1, $user_id, PDO::PARAM_INT);
        // SQLを実行
        $stmt->execute();
        // レコードの取得
        $data = $stmt->fetchAll();
        
        //エラーチェック
        foreach($data as $item){
            //ステータスが公開のままであるか
            if($item['status'] !== 1){
              $err_msg[] = htmlspecialchars( $item['item_name'], ENT_QUOTES, 'UTF-8') .'この商品は購入できません';
            } else if($item['stock']  <= 0){
              $err_msg[] = htmlspecialchars( $item['item_name'], ENT_QUOTES, 'UTF-8') .'在庫がありません';
            } else if (($item['stock'] - $item['amount']) < 0){
              $err_msg[] = htmlspecialchars( $item['item_name'], ENT_QUOTES, 'UTF-8') .'在庫が' . $item['stock'] . '個しかありません';    
            }
        }
        
        if(count($err_msg) === 0){
            
            $dbh -> beginTransaction();
            try{
                foreach($data as $item){
                    $amount = $item['amount'];
        
                    $sql = 'UPDATE ec_item_stock SET stock = stock - ? , update_datetime = NOW() WHERE item_id = ?;';
                    // SQL文を実行する準備
                    $stmt = $dbh->prepare($sql);
                    // SQL文のプレースホルダに値をバインド
                    $stmt->bindValue(1, $amount, PDO::PARAM_INT);
                    $stmt->bindValue(2, $item['item_id'], PDO::PARAM_INT);
                    // SQLを実行
                    $stmt->execute();
                    // $total += $item['amount'] * $item['price'];
                    
                    $sql = 'INSERT INTO ec_item_history(user_id, item_id, item_name, price, amount, img, create_datetime) values(?,?,?,?,?,?,NOW())';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(2, $item['item_id'], PDO::PARAM_INT);
                    $stmt->bindValue(3, $item['item_name'], PDO::PARAM_INT);
                    $stmt->bindValue(4, $item['price'], PDO::PARAM_INT);
                    $stmt->bindValue(5, $item['amount'], PDO::PARAM_INT);
                    $stmt->bindValue(6, $item['img'], PDO::PARAM_INT);
                    // SQLを実行
                    $stmt->execute();
               }
           
                $sql = 'DELETE FROM ec_cart WHERE user_id = ?';
                $stmt = $dbh->prepare($sql);
                $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $msg[] = 'ご購入ありがとうございます';
                $dbh->commit();
                
            }catch (PDOException $e) {
                // ロールバック処理
                $dbh->rollback();
                $err_msg[] = '購入できませんでした';
                // 例外をスロー
                throw $e;
           }    
            foreach($data as $row){
                $total += $row['price'] * $row['amount'];
            }
        }  
    } else {
        $err_meg[] = '不正なアクセスです';
    }   
 }catch (PDOException $e) {
  $err_msg[] = 'データベース処理でエラーが発生しました。理由：' . $e->getMessage();
}


?>

<!DOCTYPE html>
<html lang="ja">
<head>
 <meta charset="UTF-8">
 <link rel="stylesheet" href="gakki_shop.css">
 <title>購入画面</title>
</head>

<body>
    <header>
        <h1>楽器オンラインSHOP</h1>
    </header>
    
    <div class = "text">
    ようこそ<?php print $user_name; ?>さん<br>
    <a href="index.php">買い物を続ける</a>
    <a href="history.php">購入履歴を見る</a>
    <a href="login.php">ログアウトする</a>
    </div>
    
    <?php foreach ($err_msg as $value) { ?>
    <p class = "text"><?php print $value; ?></p>
    <?php } ?>
    <?php foreach ($msg as $value) { ?>
    <p class = "text"><?php print $value; ?></p>
    <?php } ?>
    <p class = "buy">合計金額：<?php print number_format($total); ?>円</p>  
    <?php foreach ($data as $value) { ?>
    <div class="item">
        <div class='img'><img src="<?php print $img_dir . $value['img']; ?>"></div>
        <div><?php print htmlspecialchars($value['item_name'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><?php print htmlspecialchars($value['amount'], ENT_QUOTES, 'UTF-8'); ?>個</div>
    </div>
    <?php } ?>
</body>

</html>