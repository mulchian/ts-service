<?php

function check_input($data): string
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url)
{
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $url . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        echo '</noscript>';
        exit;
    }
}

function select(PDO $pdo, string $select, string $className, array $params)
{
    $selectStmt = $pdo->prepare($select);
    $selectStmt->execute($params);
    $selectStmt->setFetchMode(PDO::FETCH_CLASS, $className);
    return $selectStmt->fetch(PDO::FETCH_CLASS);
}

function getFormattedCurrency(int $currency): string
{
    return number_format($currency, 0, ',', '.') . ' €';
}

function shuffle_assoc(&$array): bool
{
    $new = $array;
    $keys = array_keys($array);

    shuffle($keys);

    foreach ($keys as $key) {
        $new[$key] = $array[$key];
    }

    $array = $new;

    return true;
}