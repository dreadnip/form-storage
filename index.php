<?php

declare(strict_types=1);

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    header('HTTP/1.0 405 Not Allowed');
    die;
}

$queryString = $_SERVER['REQUEST_URI'];

if (str_starts_with($queryString, DIRECTORY_SEPARATOR)) {
    $queryString = substr($queryString, 1);
}

$formHandler = new FormHandler();

[$input, $config] = $formHandler->parse($_POST);

if (
    array_key_exists('_dry', $config) &&
    $config['_dry'] !== ''
) {
    header('HTTP/1.0 405 Not Allowed');
    die;
}

$result = $formHandler->insert($queryString, json_encode($input));

if (array_key_exists('_redirect', $config)) {
    $redirect = $config['_redirect'];
} else {
    $redirect = $_SERVER['HTTP_REFERER'];
}

header('Location: ' . $redirect);
die;

final class FormHandler
{
    public function __construct(
        private readonly PDO $pdo = new PDO('sqlite:forms.db')
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        $createTable = <<<QUERY
        CREATE TABLE IF NOT EXISTS "forms" (
            "id"	INTEGER NOT NULL,
            "key"	TEXT NOT NULL,
            "body"	TEXT,
            "createdOn"	TEXT NOT NULL,
            PRIMARY KEY("id" AUTOINCREMENT)
        );
        QUERY;

        $this->pdo->exec($createTable);
    }

    public function parse(array $post): array
    {
        $config = [];

        foreach ($post as $key => $value) {
            if (str_starts_with($key, '_')) {
                $config[$key] = $value;
                unset($post[$key]);
            }
        }

        return [$post, $config];
    }

    public function insert(string $key, string $body): string
    {
        $query = 'INSERT INTO "forms" (key, body, createdOn) VALUES (:key, :body, :date);';

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':key', $key);
        $statement->bindValue(':body', $body);
        $statement->bindValue(':date', (new DateTime())->format('d-m-Y H:i'));
        $statement->execute();

        if (!$this->pdo->lastInsertId()) {
            throw new RuntimeException('No record made');
        }

        return $this->pdo->lastInsertId();
    }
}
