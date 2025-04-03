<?php
// src/Security/CabinetVoter.php
namespace App\Security;

use App\Entity\Cabinet;
use App\Entity\User;
use App\Utils\Enum\USER_ROLES;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CabinetVoter extends Voter
{
    // these strings are just invented: you can use anything
    const VIEW = 'viewCabinet';
    const EDIT = 'editCabinet';
    const CREATE = 'createCabinet';
    const DELETE = 'deleteCabinet';
    const ACCESS = 'accessCabinet';

    public function __construct(private Security $security,){}


    protected function supports(string $attribute, mixed $subject): bool
    {
        
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::CREATE, self::DELETE, self::ACCESS])) {
            return false;

        }
        
        /*if (!($subject instanceof Cabinet) && (!is_null($subject))) {
            
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

        // you know $subject is a Cabinet object, thanks to `supports()`
        /** @var Cabinet $cabinet */
        $cabinet = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($cabinet, $user),
            self::EDIT => $this->canEditCreateDelete(),
            self::CREATE => $this->canEditCreateDelete(),
            self::DELETE => $this->canEditCreateDelete(),
            self::ACCESS => $this->canAccess($user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canEditCreateDelete():bool{
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else {
            return false;
        }
    }

    public function canAccess(User $user):bool{
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if($this->security->isGranted(USER_ROLES::ADMIN->value) && $user->getCabinet()!=null) {
            return true;
        }else{
            return false;
        }
    }

    private function canView(Cabinet $cabinet, User $user): bool
    {
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if($this->security->isGranted(USER_ROLES::ADMIN->value)){
            return $user->getCabinet() === $cabinet->getId();
        }else{
            return false;
        }
    }
}