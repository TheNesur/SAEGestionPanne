<?php
// src/Security/MachineVoter.php
namespace App\Security;

use App\Entity\Machine;
use App\Entity\User;
use App\Utils\Enum\USER_ROLES;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MachineVoter extends Voter
{
    // these strings are just invented: you can use anything
    const VIEW = 'viewMachine';
    const EDIT = 'editMachine';
    const CREATE = 'createMachine';
    const DELETE = 'deleteMachine';
    const ACCESS = 'accessMachine';

    public function __construct(private Security $security,){}


    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::CREATE, self::DELETE, self::ACCESS])) {
            return false;
        }

        /*if (!$subject instanceof Machine && !$subject==null) {
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
        /** @var Machine $machine */
        $machine = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($machine, $user),
            self::EDIT => $this->canEdit($machine, $user),
            self::CREATE => $this->canCreate($user),
            self::DELETE => $this->canDelete($user),
            self::ACCESS => $this->canAccess($user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canEdit(Machine $machine, User $user):bool{
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if($this->security->isGranted(USER_ROLES::ADMIN->value)){
            return $user->getCabinet() === $machine->getCabinet();
        }else{
            return false;
        }
    }

    private function canAccess(User $user):bool{
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if($this->security->isGranted(USER_ROLES::USER->value)){
            return $user->getCabinet() !=null;
        }else{
            return false;
        }
    }

    private function canDelete(User $user):bool{
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        /*}else if($this->security->isGranted('ROLE_ADMIN')){
            return $user->getCabinet() === $machine->getCabinet();*/
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

    private function canView(Machine $machine, User $user): bool
    {
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if($this->security->isGranted(USER_ROLES::USER->value)){
            return $user->getCabinet() === $machine->getCabinet();
        }else{
            return false;
        }
    }
}