<?php
// src/Security/BreakdownVoter.php
namespace App\Security;

use App\Entity\Breakdown;
use App\Entity\User;
use App\Utils\Enum\USER_ROLES;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BreakdownVoter extends Voter
{
    // these strings are just invented: you can use anything
    const VIEW = 'viewBreakdown';
    const EDIT = 'editBreakdown';
    const CREATE = 'createBreakdown';
    const DELETE = 'deleteBreakdown';
    const ACCESS = 'accessBreakdown';

    public function __construct(private Security $security,){}


    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::CREATE, self::DELETE, self::ACCESS])) {
            return false;
        }

        /*if (!$subject instanceof Breakdown && !$subject==null) {
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
        /** @var Breakdown $breakdown */
        $breakdown = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($breakdown, $user),
            self::EDIT => $this->canUpdate($breakdown, $user),
            self::CREATE => $this->canAccessCreate($user),
            self::DELETE => $this->canDelete(),
            self::ACCESS => $this->canAccessCreate($user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canDelete():bool{
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else {
            return false;
        }
    }


    public function canAccessCreate(User $user):bool{
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if($this->security->isGranted(USER_ROLES::USER->value) && $user->getCabinet()!=null) {
            return true;
        }else{
            return false;
        }
    }

    private function canView(Breakdown $breakdown, User $user): bool
    {
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if($this->security->isGranted(USER_ROLES::USER->value)){
            return $user->getCabinet() === $breakdown->getMachine()->getCabinet();
        }else{
            return false;
        }
    }

    private function canUpdate(Breakdown $breakdown, User $user): bool
    {
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if($this->security->isGranted(USER_ROLES::USER->value)){
            $sameCabinet= $user->getCabinet() === $breakdown->getMachine()->getCabinet();
            return $sameCabinet && $breakdown->getStatus()!="repare";
        }else{
            return false;
        }
    }
}