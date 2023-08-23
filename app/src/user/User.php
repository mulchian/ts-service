<?php

namespace touchdownstars\user;

use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * class User
 * @package touchdownstars\user
 * @method int getId()
 * @method void setId(int $id)
 * @method string getUsername()
 * @method void setUsername(string $username)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method string getRealname()
 * @method void setRealname(string $realname)
 * @method string getCity()
 * @method void setCity(string $city)
 * @method string getGender()
 * @method void setGender(string $gender)
 * @method string getBirthday()
 * @method void setBirthday(string $birthday)
 * @method string getRegisterDate()
 * @method void setRegisterDate(string $registerDate)
 * @method int getLastActiveTime()
 * @method void setLastActiveTime(string $lastActiveTime)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method bool isAdmin()
 * @method void setAdmin(bool $isAdmin)
 * @method bool isDeactivated()
 * @method void setDeactivated(bool $isDeactivated)
 * @method bool isActivationSent()
 * @method void setActivationSent(bool $activationIsSent)
 * @method bool isActivated()
 * @method void setActivated(bool $isActivated)
 * @method array getProfilePicture()
 * @method void setProfilePicture(array $picture)
 */
#[Setter, Getter]
class User extends Helper implements JsonSerializable
{
    private int $id;
    private string $username;
    private string $password;
    private string $email;
    private ?string $realname;
    private ?string $city;
    private string $gender;
    private ?string $birthday;
    private ?string $registerDate;
    private int $lastActiveTime;
    private string $status;
    private bool $admin = false;
    private bool $deactivated = false;
    private bool $activationSent = false;
    private bool $activated = false;
    private array $profilePicture = array();

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'username' => $this->getUsername(),
            'email' => $this->getEmail(),
            'realname' => $this->getRealname(),
            'city' => $this->getCity(),
            'gender' => $this->getGender(),
            'birthday' => $this->getBirthday(),
            'registerDate' => $this->getRegisterDate(),
            'lastActiveTime' => $this->getLastActiveTime(),
            'status' => $this->getStatus(),
            'admin' => $this->isAdmin(),
            'deactivated' => $this->isDeactivated(),
            'activationSent' => $this->isActivationSent(),
            'activated' => $this->isActivated(),
            'profilePicture' => $this->getProfilePicture()
        ];
    }
}