<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BuildingRepository")
 */
class Building
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Length(
     *     min="2",
     *     max="20",
     *     minMessage="Building name must be at least {{ limit }} characters long.",
     *     maxMessage = "Building name cannot be longer than {{ limit }} characters."
     * )
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     */
    private $location;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^[0-9]{1,3}$/",
     *     message="Starting floor must be a number."
     * )
     * @Assert\GreaterThanOrEqual(
     *     value="0",
     *     message="Starting floor must be greater than or equal to zero."
     * )
     * @Assert\Expression(
     *     "value <= this.getEndFloor()",
     *     message="Starting floor must be less than or equal to ending floor."
     * )
     */
    private $startFloor;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^[0-9]{1,3}$/",
     *     message="Ending floor must be a number."
     * )
     * @Assert\LessThanOrEqual(
     *     value="163",
     *     message="Ending floor must not exceed {{ compared_value }} floor."
     * )
     */
    private $endFloor;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="admin", referencedColumnName="id")
     */
    private $admin;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateCreated;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="buildings")
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     */
    public function setLocation($location): void
    {
        $this->location = $location;
    }

    /**
     * @return mixed
     */
    public function getStartFloor()
    {
        return $this->startFloor;
    }

    /**
     * @param mixed $startFloor
     */
    public function setStartFloor($startFloor): void
    {
        $this->startFloor = $startFloor;
    }

    /**
     * @return mixed
     */
    public function getEndFloor()
    {
        return $this->endFloor;
    }

    /**
     * @param mixed $endFloor
     */
    public function setEndFloor($endFloor): void
    {
        $this->endFloor = $endFloor;
    }

    /**
     * @return mixed
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @param mixed $dateCreated
     */
    public function setDateCreated($dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }

    /**
     * @return mixed
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * @param mixed $admin
     */
    public function setAdmin($admin): void
    {
        $this->admin = $admin;
    }

    /**
     * @return mixed
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param User $user
     */
    public function addUser(User $user): void
    {
        $this->users[] = $user;
    }

    /**
     * @param User $user
     */
    public function removeUser(User $user)
    {
        $this->users->remove($user);
    }

    /**
     * @return array
     */
    public function getFloors(): array
    {
        $floors = array();
        for ($i = $this->startFloor; $i <= $this->endFloor; $i++) {
            $floors[$i] = $i;
        }
        return $floors;
    }

    /**
     * @return int
     */
    public function getNumberOfFloors(): int
    {
        return $this->endFloor - $this->startFloor + 1;
    }

    /**
     * @return int
     */
    public function getNumberOfMembers(): int
    {
        return $this->users->count();
    }

    public function removeAdmin(): void
    {
        $this->admin = null;
    }

}
