<?php


namespace touchdownstars\communication\chat;


use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * Class Chat
 * @package touchdownstars\communication\chat
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getIdUser1()
 * @method void setIdUser1(int $idUser1)
 * @method int getIdUser2()
 * @method void setIdUser2(int $idUser2)
 */
#[Setter, Getter]
class Chat extends Helper
{
    private int $id;
    private int $idUser1;
    private int $idUser2;
}