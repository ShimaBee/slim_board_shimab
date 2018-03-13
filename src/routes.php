<?php

use Slim\Http\Request;
use Slim\Http\Response;




// ユーザ登録
$app->get('/register', function ($request, $response, $args) {
    return $this->renderer->render($response, 'register.phtml');
});


//
//$app->post('/register', function ($request, $response, $args) {
//    $data = $request->getParsedBody();
//    $userName = $data['userName'];
//    $password = $data['password'];
//    $email = $data['email'];
//    $stmt = $this->db->prepare("INSERT INTO users (userName, password,email) VALUES (:userName, :password,:email)");
//    $stmt->bindParam(':userName', $userName, PDO::PARAM_STR);
//    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
//    $password = password_hash($password, PASSWORD_DEFAULT);
//    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
//    try {
//        $stmt->execute();
//    } catch (PDOException $e) {
//        exit('登録失敗' . $e->getMessage());
//    }
//    header('Location: /login');
//    exit();
//
//});



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
//        var_dump($user);
//        die;

    } catch (PDOException $e) {
        exit('登録失敗' . $e->getMessage());
    }
    // ハッシュ化されたパスワードがマッチするかどうかを確認
    if (password_verify($password, $user['password'])) {
        $_SESSION['user']['userName'] = $user['userName'];

        header('Location: /');
        exit();
    } else {
        $errors[] = 'パスワードが違います';
        $data = ['errors' => $errors];
        return $this->renderer->render($response, 'login.phtml', $data);
    }
});

$app->get('/logout', function ($request, $response, $args) {
    session_destroy();
    unset($_SESSION['user']);
    header("Location: /login");
    exit;
});

// 一覧表示
$app->get('/', function (Request $request, Response $response) {

    checkLogin();


    $sql = 'SELECT id,userName FROM users ORDER BY id DESC ';
    $stmt = $this->db->query($sql);
    $users = [];
    while($row = $stmt->fetch()) {
        $users[] = $row;
    }
    $data = ['users' => $users];
    return $this->renderer->render($response, 'index.phtml', $data);
    return $response->write('/');
});



// 新規作成
//$app->post('/board', function (Request $request, Response $response) {
//    $userName = $request->getParsedBodyParam('userName');
//    $password = $request->getParsedBodyParam('password');
//
//    // ここに保存の処理を書く
//    $sql = 'INSERT INTO users (userName,password) values (:userName,:password)';
//    $stmt = $this->db->prepare($sql);
//    $params = array(':userName' => $userName,':password' => $password);
//    $result = $stmt->execute($params);
//    if (!$result) {
//        throw new \Exception('could not save the board');
//    }
//
//    // 保存が正常にできたら一覧ページへリダイレクトする
//    return $response->withRedirect("/board");
//});



// 編集用フォームの表示
//$app->get('/board/{id}/edit', function (Request $request, Response $response, array $args) {
//    $sql = 'SELECT id,userName,comment,date FROM board WHERE id = :id';
//    $stmt = $this->db->prepare($sql);
//    $stmt->execute(['id' => $args['id']]);
//    $board = $stmt->fetch();
//
//    if (!$board) {
//        return $response->withStatus(404)->write('not found');
//    }
//    $data = ['board' => $board];
//    return $this->renderer->render($response, 'edit.phtml', $data);
//});



//更新
//$app->put('/board/{id}', function (Request $request, Response $response, array $args) {
//    $sql = 'SELECT id FROM board WHERE id = :id';
//    $stmt = $this->db->prepare($sql);
//    $stmt->execute(['id' => $args['id']]);
//    $board = $stmt->fetch();
//
//    if (!$board) {
//        return $response->withStatus(404)->write('not found');
//    }
//    $board['comment'] = $request->getParsedBodyParam('comment');
//    $stmt = $this->db->prepare('UPDATE board SET comment = :comment WHERE id = :id');
//
//    $stmt->execute($board);
//
//    return $response->withRedirect("/board");
//});




// 削除
//$app->delete('/board/{id}', function (Request $request, Response $response, array $args) {
//
//    $sql = 'SELECT id,userName,comment,date FROM board WHERE id = :id';
//
//    $stmt = $this->db->prepare($sql);
//    $stmt->execute(['id' => $args['id']]);
//
//    $board = $stmt->fetch();
//
//    if (!$board) {
//        return $response->withStatus(404)->write('not found');
//    }
//
//
//    $stmt = $this->db->prepare("DELETE FROM board WHERE id = :id");
//
//    $stmt->execute(['id' => $board['id']]);
//    return $response->withRedirect("/board");
//});
//

//ログインcheck
function checkLogin()
{
    if (!isset($_SESSION['user'])) {
        header("Location: /login");
        exit;
    }
}


