<?php
// src/Security/UserVoter.php
namespace App\Security;

use App\Entity\User;
use App\Utils\Enum\USER_ROLES;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    // these strings are just invented: you can use anything
    const VIEW = 'viewUser';
    const EDIT = 'editUser';
    const CREATE = 'createUser';
    const DELETE = 'deleteUser';
    const ACCESS = 'accessUser';

    public function __construct(private Security $security,){}


    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::CREATE, self::DELETE, self::ACCESS])) {
            return false;
        }

        /*if (!$subject instanceof User && !$subject==null) {
            return false;
        }*/

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Machine object, thanks to `supports()`
        /** @var User $userCrud */
        $userCrud = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($userCrud, $user),
            self::EDIT => $this->canEdit($userCrud, $user),
            self::CREATE => $this->canCreate($user),
            self::DELETE => $this->canDelete($user),
            self::ACCESS => $this->canAccess($user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canEdit(User $userCrud, User $user):bool{
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return !in_array(USER_ROLES::SUPER_ADMIN->value, $userCrud->getRoles());
        }else if($this->security->isGranted('ROLE_ADMIN')){
            if(in_array(USER_ROLES::ADMIN->value, $userCrud->getRoles()) || in_array(USER_ROLES::SUPER_ADMIN->value, $userCrud->getRoles())){
                return false;
            }
            if($userCrud->getCabinet()==null){
                return true;
            }else{
                return $user->getCabinet() === $userCrud->getCabinet();
            }
        }else{
            return false;
        }
    }

    private function canAccess(User $user):bool{
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if($this->security->isGranted(USER_ROLES::ADMIN->value)){
            return $user->getCabinet() !=null;
        }else{
            return false;
        }
    }

    private function canDelete(User $user):bool{
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else{
            return false;
        }
    }

    private function canCreate(User $user):bool{
        if($this->security->isGranted(USER_ROLES::ADMIN->value)){
            return true;
        }else{
            return false;
        }
    }

    private function canView(User $userCrud, User $user): bool
    {
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if($this->security->isGranted(USER_ROLES::ADMIN->value)){
            return $user->getCabinet() !=null;
        }else{
            return false;
        }
    }
}