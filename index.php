<?php

define('LIMIT_AUTH', 3);
define('DB_HOST','localhost');
define('DB_NAME','tasktest');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');


// Если нажали кнопку выхода
if(isset($_GET['logout']))
{
    // Убиваем сессию и удаляем куки
    // Перенаправляем на форму входа
}

function get_db()
{
    $dbh = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USERNAME, DB_PASSWORD);
    $dbh->exec('SET NAMES UTF8');
    return $dbh;
}

if(isset($_COOKIE['auth']))
{
    $dbh = get_db();
    $sth = $dbh->prepare("SELECT id, phone, login, instagram, email, address, CONCAT_WS(' ',lname,fname,mname) AS name, MD5(CONCAT_WS(':',MD5(`login`),`password`,'".$_SERVER["REMOTE_ADDR"]."')) AS `hash` FROM `users` HAVING `hash` = ? LIMIT 1");
    $sth->execute(array($_COOKIE['auth']));
    $user_data = $sth->fetch(PDO::FETCH_ASSOC);
    $user_data['inst_data'] = [];
    if(!empty($user_data['instagram']))
    {
        $user_data['inst_data'] = get_data_instagram($user_data['instagram']);
    }
}

// ОТправка формы
if(count($_POST)) {

    $post_data = json_decode(array_keys($_POST)[0]);

    $errors = [];

    $ip = $_SERVER["REMOTE_ADDR"];
    $dbh = get_db();
    $sth = $dbh->prepare('SELECT failedto FROM `logs` WHERE ip = ? LIMIT 1');
    $sth->execute(array($ip));
    $failedto = (int) $sth->fetch(PDO::FETCH_ASSOC)['failedto'];

    if($failedto >= LIMIT_AUTH)
    {
        $errors[] = 'Вы привысели количество попыток входа. Свяжитесь с технической поддержкой.';
    }

    // Проверяем блокировку

    if(!isset($post_data->login) || $post_data->login == "")
    {
        $errors[] = 'Забыли ввести логин';
    }

    if(!isset($post_data->password) || $post_data->password == "")
    {
        $errors[] = 'Забыли ввести пароль';
    }

    if(empty($errors))
    {
        // Авторизуем пользователя
        $sth = $dbh->prepare('SELECT id, login, instagram, phone, email, address, CONCAT_WS(" ", lname, fname, mname) AS name FROM `users` WHERE login = ? AND password = ? LIMIT 1');
        $sth->execute(array($post_data->login, md5($post_data->password)));
        $user = $sth->fetch(PDO::FETCH_ASSOC);
        if(empty($user))
        {
            $failedto++;
            $errors[] = 'Неверно ввели логин или пароль. Попытка ' . $failedto . ' из ' . LIMIT_AUTH;
            $sth = $dbh->prepare('SELECT id FROM `logs` WHERE ip = ? LIMIT 1');
            $sth->execute(array($ip));
            $log = $sth->fetch(PDO::FETCH_ASSOC);
            $ban = $failedto >= LIMIT_AUTH ? 1 : 0;
            if($log['id'])
            {
                $sth = $dbh->prepare('UPDATE logs SET failedto = ?, ban = ? WHERE ip = ?');
                $sth->execute(array($failedto,$ban,$ip));
            }
            else
            {
                $sth = $dbh->prepare('INSERT INTO logs (failedto,ip,ban) VALUES (?,?,?)');
                $sth->execute(array($failedto,$ip,$ban));
            }
        }
        else
        {
            // Устанавливаем куки
            setcookie("auth",md5(md5($post_data->login).":".md5($post_data->password).":".$_SERVER["REMOTE_ADDR"]));
        }
        // Получаем данные из Инстаграмма
    }

    if(count($errors))
    {
        $response = [
            'status' => 'fail',
            'error' => $errors
        ];
    }
    else
    {
        $user['inst_data'] = [];
        if(!empty($user['instagram']))
        {
            $user['inst_data'] = get_data_instagram($user['instagram']);
        }
        $response = [
            'user' => $user,
            'status' => 'ok'
        ];
    }

    header('Content-Type: application/json');
    exit(json_encode($response));
}

function get_data_instagram($nickname)
{
    $url = 'https://instagram.com/'.$nickname.'/?__a=1';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json') );
    $data = curl_exec($ch);
    curl_close($ch);
    $obj_inst = json_decode($data);
    return [
        'name' => $obj_inst->graphql->user->username,
        'pic' => $obj_inst->graphql->user->profile_pic_url,
        'pic_hd' => $obj_inst->graphql->user->profile_pic_url_hd
    ];
}

?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($user_data) ? $user_data['name'] : 'Форма авторизации' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-size: 17px;
            font-family: SF Pro Text,SF Pro Icons,Helvetica Neue,Helvetica,Arial,sans-serif;
        }
        .message {
            margin: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .message.error {
            color: #721c24;
            background-color: #f8d7da;
        }
        .message.success {
            color: #155724;
            background-color: #d4edda;
        }
        .container {
            display: flex;
            align-items: center;
            position: relative;
            flex-direction: column;
            min-width: 300px;
            margin: 0 auto;
        }
        .form {
            transition: 2s;
        }
        .form input {
            font-size: 17px;
            outline: none;
        }
        .form #login {
            border: 1px solid #494949;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 12px 20px;
        }
        .form #password {
            border-top: 0px;
            border-left: 1px solid #494949;
            border-right: 1px solid #494949;
            border-bottom: 1px solid #494949;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
            padding: 12px 20px;
        }
        .form #btn {
            background-color: #4CAF50;
            color: white;
            font-size: 17px;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            float: right;
            width: 100%;
            margin-top: 10px;
            outline: none;
        }
        .none {
            display: none
        }
        .show {
            display: block;
        }
        .photo img {
            width: 300px;
        }
        .row {
            padding: 15px 10px;
            border-bottom: 1px solid #494949;
            overflow: auto;
        }
        .col1 {
            color: #494949;
            float: left;
            width: 40%;
        }
        .logout {
            margin-top: 10px;
        }
        .logout a {
            color: red;
        }
    </style>
</head>
<body>
<div class="container">
    <div id="message" class="message"></div>
    <div class="form <?php echo isset($user_data) ? ' none' : 'show' ?>" id="form">
        <div class="field1">
            <input id="login" class="box" name="login" placeholder="Логин">
        </div>
        <div class="field2">
            <input id="password" name="password" type="password" placeholder="Пароль">
        </div>
        <div>
            <button id="btn">Войти</button>
        </div>
    </div>
    <div class="profile <?php echo isset($user_data) ? ' show' : 'none'?>" id="profile">
        <div class="photo">
            <img id="photo" src="photos/<?php echo isset($user_data) ? $user_data['id'].'.jpg' : '0.jpg' ?>" />
        </div>
        <div class="row" id="name"><?php echo isset($user_data['name']) ? $user_data['name'] : '' ?></div>
        <div class="row">
            <div class="col1">Логин:</div>
            <div class="col2" id="login_profile"><?php echo isset($user_data['login']) ? $user_data['login'] : '' ?></div>
        </div>
        <div class="row">
            <div class="col1">Телефон:</div>
            <div class="col2" id="phone"><?php echo isset($user_data['phone']) ? $user_data['phone'] : '' ?></div>
        </div>
        <div class="row">
            <div class="col1">E-Mail:</div>
            <div class="col2" id="email"><?php echo isset($user_data['address']) ? $user_data['address'] : '' ?></div>
        </div>
        <div class="row">
            <div class="col1">Адрес:</div>
            <div class="col2" id="address"><?php echo isset($user_data['email']) ? $user_data['email'] : '' ?></div>
        </div>
        <div class="row">
            <div class="col1">Инстаграм:</div>
            <div class="col2" id="instagram"><?php echo isset($user_data['instagram']) ? $user_data['instagram'] : '' ?></div>
        </div>
        <div id="data_inst" class="row <?php echo isset($user_data['inst_data']) && count($user_data['inst_data']) > 0 ? 'show' : 'none' ?>">
            Данные из инстаграм:
        </div>
        <div id="data_inst_name" class="row <?php echo isset($user_data['inst_data']) && count($user_data['inst_data']) > 0 ? 'show' : 'none' ?>">
            <div class="col1">Имя в инстаграм</div>
            <div class="col2">
                <div id="inst_name"><?php echo isset($user_data['inst_data']['name']) ? $user_data['inst_data']['name'] : '' ?></div>
            </div>
        </div>
        <div id="data_inst_photo" class="row <?php echo isset($user_data['inst_data']) && count($user_data['inst_data']) > 0 ? 'show' : 'none' ?>">
            <div class="col1">Фото в инстаграм</div>
            <div class="col2">
                <img id="inst_photo" src="<?php echo isset($user_data['inst_data']['pic']) ? $user_data['inst_data']['pic'] : '' ?>" />
            </div>
        </div>
        <div class="logout">
            <a id="logout" href="">Выйти</a>
        </div>
    </div>
</div>
<script type="application/javascript">
    var submit = document.getElementById('btn');
    submit.onclick = function () {
        var data = {
            'login': document.getElementById('login').value,
            'password': document.getElementById('password').value
        };
        document.getElementById('message').innerHTML = '';
        document.getElementById('message').classList.remove('error');
        document.getElementById('message').classList.remove('success');
        fetch('index.php', {
            'method': 'POST',
            'cache': 'no-cache',
            'credentials': 'same-origin',
            'headers': {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            'body': JSON.stringify(data)
        })
            .then((response) => {
                response.json().then(function (data) {
                    if (data.status == "fail") {
                        document.getElementById('message').innerHTML = data.error.join('<br />');
                        document.getElementById('message').classList.add('error');
                    } else {
                        document.getElementById('message').innerHTML = 'Вы успешно авторизовались';
                        document.getElementById('message').classList.add('success');
                        document.getElementById('login').value = '';
                        document.getElementById('password').value = '';
                        document.getElementById('login').disabled = true;
                        document.getElementById('password').disabled = true;
                        document.getElementById('btn').disabled = true;

                        document.getElementById('photo').src = 'photos/' + data.user.id + '.jpg';
                        document.getElementById('address').innerText = data.user.address;
                        document.getElementById('login_profile').innerText = data.user.login;
                        document.getElementById('phone').innerText = data.user.phone;
                        document.getElementById('email').innerText = data.user.email;
                        document.getElementById('instagram').innerText = data.user.instagram;

                        if(data.user.inst_data != undefined )
                        {
                            document.getElementById('inst_name').innerText = data.user.inst_data.name;
                            document.getElementById('inst_photo').src = data.user.inst_data.pic;
                            document.getElementById('data_inst').classList.remove('none');
                            document.getElementById('data_inst').classList.add('show');
                            document.getElementById('data_inst_name').classList.remove('none');
                            document.getElementById('data_inst_name').classList.add('show');
                            document.getElementById('data_inst_name').classList.remove('none');
                            document.getElementById('data_inst_name').classList.add('show');
                        }

                        setTimeout(function () {
                            document.getElementById('message').innerHTML = '';
                            document.getElementById('message').classList.remove('success');
                            document.title = data.user.name;
                            document.getElementById('form').classList.remove('show');
                            document.getElementById('form').classList.add('none');
                            document.getElementById('profile').classList.remove('none');
                            document.getElementById('profile').classList.add('show');
                        }, 10000);
                    }
                })
            })
            .then((data) => {
                // console.log(data);
            });
        return false;
    };
    var logout = document.getElementById('logout');
    logout.onclick = function () {
        document.cookie = 'auth=;expires=Thu, 31 Dec 1999 00:00:00 UTC; path=/';
        document.title = 'Форма авторизации';
        document.getElementById('profile').classList.remove('show');
        document.getElementById('profile').classList.add('none');
        document.getElementById('form').classList.remove('none');
        document.getElementById('form').classList.add('show');
        document.getElementById('address').innerText = '';
        document.getElementById('login').innerText = '';
        document.getElementById('phone').innerText = '';
        document.getElementById('email').innerText = '';
        document.getElementById('instagram').innerText = '';
        document.getElementById('login').disabled = false;
        document.getElementById('password').disabled = false;
        document.getElementById('btn').disabled = false;
        return false;
    };
</script>
</body>
</html>