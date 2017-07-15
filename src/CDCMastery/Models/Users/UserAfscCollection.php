<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/9/2017
 * Time: 9:01 PM
 */

namespace CDCMastery\Models\Users;


class UserAfscCollection
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var string[]
     */
    private $associations;

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param string $association
     */
    public function addAssociation(string $association)
    {
        $this->associations[] = $association;
    }

    /**
     * @return \string[]
     */
    public function getAssociations(): array
    {
        return $this->associations;
    }

    /**
     * @param \string[] $associations
     */
    public function setAssociations(array $associations)
    {
        $this->associations = $associations;
    }
}