<?php
    $lgn = '0';
    $bigCard = '';
    if (isset($_GET['lgn'])) {
        $lgn = $_GET['lgn'];
    }

    $passwordPattern = '^((?=.*[^A-Za-z0-9])(?=.*[0-9])(?=.*[A-Z])|(?=.*[a-z])(?=.*[0-9])(?=.*[A-Z])|(?=.*[A-Z])(?=.*[a-z])(?=.*[^A-Za-z0-9])|(?=.*[^A-Za-z0-9])(?=.*[0-9])(?=.*[a-z])|(?=.*[^A-Za-z0-9])(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])).{8,}$';
//    $emailPattern = "^[a-zA-Z0-9\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u00FF.!#$%&'*+=?^_`{|}~-]+@[a-zA-Z0-9\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u00FF](?:[a-zA-Z0-9-\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u00FF]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u00FF](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u00FF])?)*$";
    $emailPattern = "(?:[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\\.[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+)*|&quot;(?:[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x21\\x23-\\x5b\\x5d-\\x7f]|\\\\[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f])*&quot;)@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\\[(?:(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9]))\\.){3}(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])|[a-z0-9-]*[a-z0-9]:(?:[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x21-\\x5a\\x53-\\x7f]|\\\\[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f])+)\\])";
    ?>

<?php
    function checkPasswords(array $post): bool {
        $isError = true;
        if (isset($post['password'])) {
            $password = check_input($post['password']);
            if (preg_match('/(?=^.{8,255}$)((?=.*\d)(?=.*[A-Z])(?=.*[a-z])|(?=.*\d)(?=.*[^A-Za-z0-9])(?=.*[a-z])|(?=.*[^A-Za-z0-9])(?=.*[A-Z])(?=.*[a-z])|(?=.*\d)(?=.*[A-Z])(?=.*[^A-Za-z0-9]))^.*/', $password)) {
                $isError = false;
            } else {
                $isError = true;
            }
        }
        if (isset($post['passwordRepeat'])) {
            $passwordRepeat = check_input($post['passwordRepeat']);
            if (isset($password) && $password === $passwordRepeat) {
                $isError = false;
            } else {
                $isError = true;
            }
        }
        return $isError;
    }
?>

<div class="container h-100">
    <div class="d-flex justify-content-center h-100">
        <div class="user_card">
            <div class="d-flex justify-content-center">
                <div class="brand_logo_container">
                    <img src="../resources/logo_transparent.png" class="brand_logo" alt="Touchdown Stars">
                </div>
            </div>

            <?php
                switch ($lgn) {
                    case '0':
                        /* Login */
                        include('login.php');
                        break;
                    case '1':
                        /* Register */
                        include('registration.php');
                        break;
                    case '2':
                        /* Reset Password */
                        include('resetPassword.php');
                        break;
                    default:
                        break;
                }
            ?>

        </div>
    </div>
</div>

<script src="../scripts/util/validation.js"></script>