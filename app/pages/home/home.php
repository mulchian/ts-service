<?php

if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
}

$todayDate = new DateTime();
$today = $todayDate->format('l');

?>

<!-- Kalender -->
<div class="panel panel-default opacity">
    <div class="row my-1">
        <div class="col-sm">
            <table id="calendar" class="table table-dark" data-header-style="headerStyle">
                <thead>
                <tr>
                    <th data-field="monday" scope="col" data-width="15" data-width-unit="%">MONTAG</th>
                    <th data-field="tuesday" scope="col" data-width="15" data-width-unit="%">DIENSTAG</th>
                    <th data-field="wednesday" scope="col" data-width="15" data-width-unit="%">MITTWOCH</th>
                    <th data-field="thursday" scope="col" data-width="15" data-width-unit="%">DONNERSTAG</th>
                    <th data-field="friday" scope="col" data-width="15" data-width-unit="%">FREITAG</th>
                    <th data-field="saturday" scope="col" data-width="15" data-width-unit="%">SAMSTAG</th>
                    <th data-field="sunday" scope="col" data-width="15" data-width-unit="%">SONNTAG</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>

    <?php if (isset($user)): ?>
        <div class="row my-3">
            <div class="col-sm">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            Willkommen <?php echo $user->getUsername(); ?> bei Touchdown Stars!
                        </h3>
                    </div>
                    <div class="panel-body">
                        Deine E-Mail-Adresse ist: <?php echo $user->getEmail(); ?><br>
                        Du hast dich am <?php echo date('d.m.Y H:i:s', strtotime($user->getRegisterDate())); ?> registriert.
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Script for home.php -->
<script src="/app/scriptsipts/home/home.js"></script>