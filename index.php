<!DOCTYPE html>
<html lang="ru" dir="ltr">
<head>
  <meta charset="utf-8">
  <title></title>
</head>
<body>
  <form action="index.php" method="post">
    <input type="text" name="commentText" placeholder="Поиск по комментариям" minlength="3">
    <button type="submit" name="searchComment" >Найти</button>
  </form>
  <div class="">
    <table class="table table-bordered" border="1">
      <thead>
        <tr>
          <th>Заголовок поста</th>
          <th>Текст комментария</th>
        </tr>
      </thead>
      <tbody>
        <?php
        /*
        В данном фрагменте происходит проверка на длину запроса в 3 и более символов, после чего выполняется сам запрос,
         результаты выводятся в виде таблицы
        */
        if (isset($_POST['searchComment']))
        {
          $DBconnection=checkDBConnection();
          if (!is_null($DBconnection))
          {
            $findingComment=$_POST['commentText'];
            if (strlen($findingComment)>=3)
            {
              echo 'Результаты по запросу '.$findingComment.':<br>';
              $stml = $DBconnection->query("SELECT posts.title, comments.body FROM posts join comments where posts.id=comments.postId and comments.body like '%".$findingComment."%'");
              foreach ($stml as $row)
              {
                ?>
                <tr>
                  <?php
                  echo "<td>" . $row["title"] . "</td>";
                  echo "<td>" . $row["body"] . "</td>";
                  ?>
                </tr>
                <?php
              }

            }
          }
        }
        ?>
      </tbody>
    </table>
  </div>

</body>
</html>


<?php

$postUrl='https://jsonplaceholder.typicode.com/posts';
$commentsUrl='https://jsonplaceholder.typicode.com/comments';

$isCommentsSaved=saveJsonFile($commentsUrl,'comments.json');
$isPostsSaved=saveJsonFile($postUrl,'posts.json');

insertData('posts.json','posts');
insertData('comments.json','comments');


/*
Фукнция проверки подключения к базе данных
создается подключение к БД с указанными параметрами, которое возвращается из функции
для дальнейшего использования при создание запросов к БД
Возвращается NULL при ошибке подключения
*/
function checkDBConnection()
{
  $hostname="localhost";
  $databaseName="test_task_schema";
  $username = "root";
  $password = "1234";

  try {
    $DBconnection = new PDO("mysql:host=$hostname;dbname=$databaseName", $username, $password);
    echo "<script>console.log('Подключение к базе данных успешно')</script>";
    return $DBconnection;
  }
  catch(PDOException $e)
  {
    echo "<script>console.log('Ошибка подключения к базе данных: '. $e->getMessage())</script>";
    return NULL;
  }


}


/*
Функция вставки данных из указаного файла в таблицу

После проверки подключения к базе данных и проверки на существование таблицы json файл декодируется.
В зависимости от имени таблицы выполняется поготовленный sql запрос на заполнение таблицы с подсчётом количества записей
*/
function insertData($jsonFile,$tableName)
{

  $DBconnection=checkDBConnection();
  if (!is_null($DBconnection))
  {

    $ourData = file_get_contents($jsonFile);
    $arrayDecode = json_decode($ourData, true);

    //в данной фрагменте кода выполняется подчёт значений для определённой таблице, и, если значение нулевое, то происходит заполнение таблицы
    $rowCount;
    if ($tableName=='posts') {
      $rowCount = $DBconnection->query("select count(*) from posts")->fetchColumn();
    }
    else if ($tableName=='comments') {
      $rowCount = $DBconnection->query("select count(*) from comments")->fetchColumn();
    }

    //
    // echo $rowCount;
    if (is_null($rowCount)) {
      if ($tableName=='comments') {
        $commentsCount=0;
        $stmt = $DBconnection->prepare("INSERT INTO comments (id,postId,name,email,body) VALUES (:id,:postId,:name,:email,:body)");

        foreach($arrayDecode as $key=>$value)
        {
          $commentsCount++;
          $commentId = $key;
          $postId = $value['postId'];
          $name = $value['name'];
          $email=$value['email'];
          $body = $value['body'];

          $stmt->bindparam(":id", $commentId);
          $stmt->bindparam(":postId", $postId);
          $stmt->bindparam(":name", $name);
          $stmt->bindparam(":email", $email);
          $stmt->bindparam(":body", $body);
          $stmt->execute();
        }
        echo "<script>console.log('Загружено комментариев: $commentsCount')</script>";
      }
      if ($tableName=='posts') {
        $postsCount=0;
        $stmt = $DBconnection->prepare("INSERT INTO posts (id,userId,title,body) VALUES (:id,:userId,:title,:body)");

        foreach($arrayDecode as $key=>$value)
        {
          $postsCount++;
          $postsId = $key;
          $userId = $value['userId'];
          $title = $value['title'];
          $body = $value['body'];

          $stmt->bindparam(":id", $postsId);
          $stmt->bindparam(":userId", $userId);
          $stmt->bindparam(":title", $title);
          $stmt->bindparam(":body", $body);
          $stmt->execute();
        }

        echo "<script>console.log('Загружено постов: $postsCount')</script>";
      }
      echo "<script>console.log('Данные занесены в таблицу $tableName')</script>";
    }
    echo "<script>console.log('Таблица $tableName с данными уже существует')</script>";
  }
}


/*
Функция сохранения json файла по url ссылке
В случае, если файл не существует, файл скачивается по ссылке и сохраняется по указанному пути
Если файл существует, то он не скачивается повторно и ничего не изменяется
*/
function saveJsonFile($url,$saveFileName)
{
  if (!file_exists($saveFileName))
  {
    $json = file_get_contents($url);
    if($json)
    {
      if((file_put_contents($saveFileName, $json)))
      {
        echo "<script>console.log('JSON-файлы сохранен')</script>";
      }
      else
      {
        echo "<script>console.log('При сохранении файла произошла ошибка')</script>";
      }
    }
    else
    {
      echo "<script>console.log('Невозможно найти файл по ссылке')</script>";
    }
  }
}
?>
