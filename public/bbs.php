<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

if (isset($_POST['body'])) {
  // POSTで送られてくるフォームパラメータ body がある場合

  $image_filename = null;
  if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
    // アップロードされた画像がある場合
    if (preg_match('/^image\//', mime_content_type($_FILES['image']['tmp_name'])) !== 1) {
      // アップロードされたものが画像ではなかった場合処理を強制的に終了
      header("HTTP/1.1 302 Found");
      header("Location: ./bbsimagetest.php");
      return;
    }

    // 元のファイル名から拡張子を取得
    $pathinfo = pathinfo($_FILES['image']['name']);
    $extension = $pathinfo['extension'];
    // 新しいファイル名を決める。他の投稿の画像ファイルと重複しないように時間+乱数で決める。
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $extension;
    $filepath = '/var/www/upload/image/' . $image_filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $filepath);
  }

  // insertする
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  // 処理が終わったらリダイレクトする
  // リダイレクトしないと，リロード時にまた同じ内容でPOSTすることになる
  header("HTTP/1.1 302 Found");
  header("Location: ./bbs.php");
  return;
}

// ページ数をURLクエリパラメータから取得
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// 1ページあたりの行数を決める
$count_per_page = 10;

// テーブルの行数をSELECT COUNT で取得
$count_sth = $dbh->prepare('SELECT COUNT(*) FROM bbs_entries;');
$count_sth->execute();
$count_all = $count_sth->fetchColumn();

if ($count_all === 0) {
  // 0件ならフォームを表示したいので、1ページ目として扱う
  $page = 1;
  $skip_count = 0;
} else {
  // 最大ページを計算して範囲外ページを調整（or リダイレクトでも可）
  $max_page = (int)ceil($count_all / $count_per_page);
  if ($page > $max_page) {
    // メッセージを出さずに最後のページに合わせる
    $page = $max_page;
  }
  $skip_count = $count_per_page * ($page - 1);
}

// ページ数に応じてスキップする行数を計算
$skip_count = $count_per_page * ($page - 1);

// テーブルからデータを取得
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC LIMIT :count_per_page OFFSET :skip_count');
// 文字列ではなく数値をプレースホルダにバインドする場合は bindParam() を使い，第三引数にINTであることを伝えるための定数を渡す
$select_sth->bindParam(':count_per_page', $count_per_page, PDO::PARAM_INT);
$select_sth->bindParam(':skip_count', $skip_count, PDO::PARAM_INT);
$select_sth->execute();

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function render_with_anchors(string $raw): string {
  $t = h($raw);
  $t = nl2br($t);
  $t = preg_replace('/(&gt;){2}(\d+)/', '<a class="anchor" href="#p$2">&gt;&gt;$2</a>', $t);
  return $t;
}
?>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>画像投稿できる掲示板</title>
  <link rel="stylesheet" href="/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="/js/script.js" defer></script>
</head>

<!-- フォームのPOST先はこのファイル自身にする -->
<form id="postForm" method="POST" action="./bbs.php" enctype="multipart/form-data" class="my-form">
 <div class="compose">
		<textarea name="body" placeholder="ここに入力してください"></textarea>

    <!-- ボタン縦並び -->
   	<div class="button-col">
     	<label class="cbtn file-btn" aria-label="ファイルを選択">
       	<input type="file" id="file-input" name="image" hidden accept="image/*">
       	<span class="text">ファイルを選択</span>
				<i class="fas fa-paperclip" aria-hidden="true"></i>
      </label>

      <button type="submit" class="cbtn send-btn" aria-label="送信">
       	<span class="text">送信</span>
       	<i class="fas fa-paper-plane" aria-hidden="true"></i>
      </button>
    </div>
  </div>

	<!-- プレビュー表示 -->
	<div id="preview"></div>
</form>

<hr>

<?php foreach ($select_sth as $entry): ?>
  <dl id="p<?= (int)$entry['id'] ?>" style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
    <dt>ID</dt>
    <dd>
      <?= (int)$entry['id'] ?>
    </dd>
    <dt>日時</dt>
    <dd><?= h($entry['created_at']) ?></dd>
    <dt>内容</dt>
    <dd>
      <?= render_with_anchors($entry['body']) ?>
      <?php if (!empty($entry['image_filename'])): ?>
        <div><img src="/image/<?= h($entry['image_filename']) ?>" style="max-height: 10em;"></div>
      <?php endif; ?>
    </dd>
  </dl>
<?php endforeach ?>


<div style="width: 100%; text-align: center; padding-bottom: 1em; border-bottom: 1px solid #ccc, margin-bottom: 0.5em">
	<?= $page ?>ページ目
  (全 <?= floor($count_all / $count_per_page) + 1 ?>ページ中)

	<div style="display: flex; justify-content: space-between; magin-bottom: 2em;">
		<div>
			<?php if($page > 1): // 前のページがあれば表示 ?>
				<button><a href="?page=<?= $page - 1 ?>">前のページ</a></button>
			<?php endif; ?>
		</div>
		<div>
      <?php if($count_all > $page * $count_per_page): // 次のページがあれば表示 ?>
        <button><a href="?page=<?= $page + 1 ?>">次のページ</a></button>
      <?php endif; ?>
    </div>
  </div>
</div>
