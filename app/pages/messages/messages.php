<?php

use touchdownstars\communication\chat\ChatController;

if (isset($pdo)) :
    $chatController = new ChatController($pdo);

    if (isset($_SESSION['user'])) :
        $user = $_SESSION['user'];

        $chatsOfUser = $chatController->getAllChats($user);

        ?>
        <div class="panel panel-default opacity">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']); ?>"
                  method="post">
                <div class="row my-3 justify-content-center">
                    <div id="chats" class="col-sm-3 col-md-3 col-lg-3 col-xl-3">
                        <div class="card bg-dark text-white">
                            <div class="card-header">Chats</div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php
                                    if (isset($chatsOfUser)) :
                                        foreach ($chatsOfUser as $chat) :
                                            ?>
                                            <li class="list-group-item bg-dark text-white" style="cursor: pointer"
                                                onclick="document.location = 'index.php?site=messages&chat=<?php echo $chat->getId(); ?>'">
                                                <div class="row">
                                                    <div class="col text-left">
                                                        <?php echo $user->getName() == $chat->getUser1() ? $chat->getUser2() : $chat->getUser1(); ?>
                                                    </div>
                                                    <?php if ($chatController->hasUnreadMessages($chat)) : ?>
                                                        <div class="col-1">
                                                            <i class="fas fa-envelope"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="col text-right"> <?php echo $chatController->lastSentMessageTime() . ' Uhr'; ?>
                                                        20:10
                                                        Uhr
                                                    </div>
                                                </div>
                                            </li>
                                        <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div id="messageBlock" class="col-sm">
                        <div class="row mb-3">
                            <div class="col">
                                <div id="messageBox" class="input-group">
                            <textarea id="textareaMessage" class="form-control bg-dark text-white"
                                      style="font-size: 12px;" rows="7"
                                      aria-label="sendMessage"></textarea>
                                    <div class="input-group-append">
                                        <button id="sendMessage" name="sendMessage" type="button" class="btn btn-dark"
                                                style="width: 100px;">Senden
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="messages">
                            <div class="row mb-3 mr-2">
                                <div class="col-9">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body text-left">
                                            <u><a href="index.php?site=profile&id=1" class="text-white">Du</a>, heute
                                                20:20 Uhr</u>
                                            <br>
                                            <br>
                                            <span>Nachrichtentext</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-3"></div>
                            </div>
                            <div class="row mb-3 mr-2">
                                <div class="col-3"></div>
                                <div class="col-9">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body text-left">
                                            Nachrichtentext
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 mr-2">
                                <div class="col-9">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body text-left">
                                            <u><a href="index.php?site=profile&id=1" class="text-white">Du</a>, heute
                                                20:20 Uhr</u>
                                            <br>
                                            <br>
                                            <span>Nachrichtentext</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-3"></div>
                            </div>
                            <div class="row mb-3 mr-2">
                                <div class="col-3"></div>
                                <div class="col-9">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body text-left">
                                            Nachrichtentext
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 mr-2">
                                <div class="col-9">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body text-left">
                                            <u><a href="index.php?site=profile&id=1" class="text-white">Du</a>, heute
                                                20:20 Uhr</u>
                                            <br>
                                            <br>
                                            <span>Nachrichtentext</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-3"></div>
                            </div>
                            <div class="row mb-3 mr-2">
                                <div class="col-3"></div>
                                <div class="col-9">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body text-left">
                                            Nachrichtentext
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 mr-2">
                                <div class="col-9">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body text-left">
                                            <u><a href="index.php?site=profile&id=1" class="text-white">Du</a>, heute
                                                20:20 Uhr</u>
                                            <br>
                                            <span>Nachrichtentext</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-3"></div>
                            </div>
                            <div class="row mb-3 mr-2">
                                <div class="col-3"></div>
                                <div class="col-9">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body text-left">
                                            Nachrichtentext
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    <?php
    endif;
endif;
?>