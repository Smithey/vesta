<?php
// Init
error_reporting(NULL);
ob_start();
session_start();

include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");

// Check token
if ((!isset($_POST['token'])) || ($_SESSION['token'] != $_POST['token'])) {
    header('location: /login/');
    exit();
}

$domain = $_POST['domain'];
$action = $_POST['action'];

if ($_SESSION['user'] == 'admin') {
    switch ($action) {
        case 'delete': $cmd='v-delete-domain';
            break;
        case 'suspend': $cmd='v-suspend-domain';
            break;
        case 'unsuspend': $cmd='v-unsuspend-domain';
            break;
        case 'enable-letsencrypt': $cmd="v-add-letsencrypt-domain";
            break;
        case 'disable-letsencrypt': $cmd="v-delete-letsencrypt-domain";
            break;
        case 'ssl-home-html': $cmd="ssl-home-html";
            break;
        case 'ssl-home-shtml': $cmd="ssl-home-shtml";
            break;
        default: header("Location: /list/web/"); exit;
    }
} else {
    switch ($action) {
        case 'delete': $cmd='v-delete-domain';
            break;
        default: header("Location: /list/web/"); exit;
    }
}

foreach ($domain as $value) {
    $value = escapeshellarg($value);

    if ($cmd == "v-add-letsencrypt-domain") {

      exec (VESTA_CMD."v-list-web-domain ".$user." ".$value." json", $output, $return_var);
      $data = json_decode(implode('', $output), true);
      unset($output);
      $v_aliases = str_replace(',', "\n", $data[$value]['ALIAS']);

      $l_aliases = str_replace("\n", ',', $v_aliases);
      exec (VESTA_CMD."v-add-letsencrypt-domain ".$user." ".$value." '".$l_aliases."' 'no'", $output, $return_var);
      check_return_code($return_var,$output);
      unset($output);

    } elseif ($cmd == "v-delete-letsencrypt-domain") {

      exec (VESTA_CMD."v-delete-web-domain-ssl ".$user." ".$value." 'no'", $output, $return_var);
      check_return_code($return_var,$output);
      unset($output);

      exec (VESTA_CMD."v-delete-letsencrypt-domain ".$user." ".$value." 'no'", $output, $return_var);
      check_return_code($return_var,$output);
      unset($output);

    } elseif ($cmd == "ssl-home-html") {
      exec (VESTA_CMD."v-change-web-domain-sslhome ".$user." ".$value." same 'no'", $output, $return_var);
      check_return_code($return_var,$output);
      unset($output);

    } elseif ($cmd == "ssl-home-shtml") {
      exec (VESTA_CMD."v-change-web-domain-sslhome ".$user." ".$value." single 'no'", $output, $return_var);
      check_return_code($return_var,$output);
      unset($output);

    } else {
      exec (VESTA_CMD.$cmd." ".$user." ".$value." no", $output, $return_var);
    }
    $restart='yes';
}

if (isset($restart)) {
    exec (VESTA_CMD."v-restart-web", $output, $return_var);
    exec (VESTA_CMD."v-restart-proxy", $output, $return_var);
    exec (VESTA_CMD."v-restart-dns", $output, $return_var);
}

header("Location: /list/web/");
