<?php
if (isset($_GET['site'])) {
    $site = $_GET['site'];
}
?>

<div class="panel panel-default opacity">
    <div class="row mt-5 justify-content-center">
        <div class="col-4">
            <div class="card alert-danger text-danger">
                <div class="card-header">
                    <h3>Seite nicht gefunden!</h3>
                </div>
                <div class="card-body">
                    <h5>Fehler:</h5>
                    <p>Die angeforderte Seite <?php echo isset($site) ? '\'' . $site . '\'' : ''; ?> wurde nicht gefunden.</p>
                </div>
            </div>
        </div>
    </div>
</div>
