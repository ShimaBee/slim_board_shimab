<?php

use Slim\Http\Request;
use Slim\Http\Response;



//top
$app->get('/', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index.phtml');

});


// ユーザ登録画面表示
$app->get('/register', function ($request, $response, $args) {
    return $this->renderer->render($response, 'register.phtml');
});


//ユーザー登録
$app->post('/register', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $username = $data['userName'];
    $email = $data['email'];
    $password = $data['password'];
    $stmt = $this->db->prepare("INSERT INTO users (userName, email, password) VALUES (:userName, :email, :password)");
    $stmt->bindParam(':userName', $username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $password = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        exit('登録失敗' . $e->getMessage());
    }
    header('Location: /login');
    exit();
});



// ログイン画面表示
$app->get('/login', function ($request, $response, $args) {
    return $this->renderer->render($response, 'login.phtml');
});



//ログイン
$app->post('/login', function ($request, $response, $args) {

    $data = $request->getParsedBody();
    $email = $data['email'];
    $password = $data['password'];
    $stmt = $this->db->prepare('SELECT userName,email,password FROM users WHERE  email = :email');
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    try {
        $stmt->execute();
        $user = $stmt->fetch();

    } catch (PDOException $e) {
        exit('登録失敗' . $e->getMessage());
    }
    // ハッシュ化されたパスワードがマッチするかどうかを確認
    if (password_verify($password, $user['password'])) {
        $_SESSION['user']['userName'] = $user['userName'];
        header('Location: /timeLine');
        exit();
    } else {
        $errors[] = 'パスワードが違います';
        $data = ['errors' => $errors];
        return $this->renderer->render($response, 'login.phtml', $data);
    }
});



//ログアウト
$app->get('/logout', function ($request, $response) {
    session_destroy();
    unset($_SESSION['user']);
    header("Location: /");
    exit;
});


// timeLine表示
$app->get('/timeLine', function (Request $request, Response $response) {
    checkLogin();

    $sql = 'SELECT id,comments.userName,comment FROM users INNER JOIN comments ON users.userName = comments.userName';
    $stmt = $this->db->query($sql);
    $users = [];
    while($row = $stmt->fetch()) {
        $users[] = $row;
    }
    $data = ['users' => $users];
    return $this->renderer->render($response, 'timeLine.phtml', $data);
    return $response->write('/timeLine');
});



//newComment
$app->post('/newComment/{userName}', function (Request $request, Response $response, array $args) {

//    user_idにidとリンクさせた数字を入れる必要がある。
    $sql = 'SELECT user_id,comment FROM comments INNER JOIN users ON users.Name = comments.userName WHERE userName = :userName';
//    プリペアドステートメントを用意
    $stmt = $this->db->prepare($sql);
//    SQL文を実行する 　$args の中に id というキーで格納されてので、 $args['id'] で取得。
    $stmt->execute(['userName' => $args['userName']]);
//    fetch メソッドを使って、レコードを取り出す。
    $board = $stmt->fetch();
    if (!$board) {
        return $response->withStatus(404)->write('not found');
    }
    $user_id = $args['id'];
    $comment = $request->getParsedBodyParam('comment');
    $sql =  'INSERT INTO comments (user_id,comment,date) values (:user_id,:comment,now())';
    $stmt = $this->db->prepare($sql);
    $params = array(':user_id' => $user_id,':comment' => $comment);

    $result = $stmt->execute($params);
    if (!$result) {
        throw new \Exception('could not save the board');
    }



    // 保存が正常にできたら一覧ページへリダイレクトする
    return $response->withRedirect("/myPage");
});




//myPage表示
$app->get('/myPage', function (Request $request, Response $response) {

    checkLogin();

    $sql = 'SELECT id,comments.userName,comment FROM users INNER JOIN comments ON users.userName = comments.userName';
    $stmt = $this->db->query($sql);
    $users = [];
    while($row = $stmt->fetch()) {
        $users[] = $row;
    }
    $data = ['users' => $users];
    return $this->renderer->render($response, 'myPage.phtml', $data);
    return $response->write('/myPage');
});



// 編集用フォームの表示
$app->get('/board/{id}/edit', function (Request $request, Response $response, array $args) {

    checkLogin();

    $sql = 'SELECT id,userName,comment FROM users INNER JOIN comments ON users.id = comments.user_id WHERE id = :id';
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $args['id']]);
    $board = $stmt->fetch();

    if (!$board) {
        return $response->withStatus(404)->write('not found');
    }
    $data = ['board' => $board];
    return $this->renderer->render($response, 'edit.phtml', $data);
});



//更新
$app->put('/board/{userName}', function (Request $request, Response $response, array $args) {

    $sql = 'SELECT user_id FROM comments INNER JOIN users ON users.userName = comments.userName WHERE userName = :userName';
//    プリペアドステートメントを用意
    $stmt = $this->db->prepare($sql);
//    SQL文を実行する 　$args の中に id というキーで格納されてので、 $args['id'] で取得。
    $stmt->execute(['userName' => $args['userName']]);
//    fetch メソッドを使って、レコードを取り出す。
    $board = $stmt->fetch();

    if (!$board) {
        return $response->withStatus(404)->write('not found');
    }
//    $board = ["id"]=> string(2) "14"
//      $board = ['user_id'] => string(2) "14"
    $board['comment'] = $request->getParsedBodyParam('comment');
    $stmt = $this->db->prepare('UPDATE comments SET comment = :comment WHERE user_id = :user_id');
    $stmt->execute($board);

    return $response->withRedirect("/myPage");
});


//$app->put('/board/{id}', function (Request $request, Response $response, array $args) {
//    $sql = 'SELECT * FROM users WHERE id = :id';
//    $stmt = $this->db->prepare($sql);
//    $stmt->execute(['id' => $args['id']]);
//    $board = $stmt->fetch();
//
//    var_dump($board);
//    die;
//    if (!$board) {
//        return $response->withStatus(404)->write('not found');
//    }
//    $ticket['subject'] = $request->getParsedBodyParam('subject');
//    $stmt = $this->db->prepare('UPDATE tickets SET subject = :subject WHERE id = :id');
//    $stmt->execute($ticket);
//    return $response->withRedirect("/tickets");
//});



// 削除
$app->delete('/board/{userName}', function (Request $request, Response $response, array $args) {



    $sql = 'SELECT id,users.userName,comment FROM users INNER JOIN comments ON users.userName = comments.userName';
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['userName' => $args['userName']]);
    $board = $stmt->fetch();
    if (!$board) {
        return $response->withStatus(404)->write('not found');
    }
    $stmt = $this->db->prepare("DELETE FROM comments.comment WHERE userName = :userName");
    $stmt->execute(['userName' => $board['userName']]);
    return $response->withRedirect("/timeLine");
});






//ログインcheck
function checkLogin()
{
    if (!isset($_SESSION['user'])) {
        header("Location: /login");
        exit;
    }
}


