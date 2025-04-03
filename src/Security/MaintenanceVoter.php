<?php
// src/Security/MaintenanceVoter.php
namespace App\Security;

use App\Entity\Maintenance;
use App\Entity\User;
use App\Utils\Enum\USER_ROLES;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MaintenanceVoter extends Voter
{
    // these strings are just invented: you can use anything
    const VIEW = 'viewMaintenance';
    const EDIT = 'editMaintenance';
    const CREATE = 'createMaintenance';
    const DELETE = 'deleteMaintenance';
    const ACCESS = 'accessMaintenance';

    public function __construct(private Security $security,){}


    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::CREATE, self::DELETE, self::ACCESS])) {
            return false;
        }

        /*if (!$subject instanceof Maintenance && !$subject==null) {
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
        /** @var Maintenance $aintenance */
        $maintenance = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($maintenance, $user),
            self::EDIT => $this->canUpdate($maintenance, $user),
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

    private function canView(Maintenance $maintenance, User $user): bool
    {
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if(in_array(USER_ROLES::TECH->value, $user->getRoles())&& !$this->security->isGranted(USER_ROLES::ADMIN->value)){
            $tabBreakdowns=$maintenance->getBreakdowns();
            $sameCabinet=false;
            foreach($tabBreakdowns as $breakdown){
                if($breakdown->getMachine()->getCabinet()==$user->getCabinet()){
                    $sameCabinet=true;
                }
            }
            $libre = $maintenance->getTechnician()==null ||  $maintenance->getTechnician()==$user;
            return $sameCabinet && $libre;
        }else if($this->security->isGranted(USER_ROLES::USER->value)){
            $tabBreakdowns=$maintenance->getBreakdowns();
            $sameCabinet=false;
            foreach($tabBreakdowns as $breakdown){
                if($breakdown->getMachine()->getCabinet()==$user->getCabinet()){
                    $sameCabinet=true;
                }
            }
            return $sameCabinet;
        }else{
            return false;
        }
    }

    private function canUpdate(Maintenance $maintenance, User $user): bool
    {
        $repare=$maintenance->getStatus()=="repare";
        if($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)){
            return true;
        }else if(in_array(USER_ROLES::TECH->value, $user->getRoles())&& !$this->security->isGranted(USER_ROLES::ADMIN->value)){
            $tabBreakdowns=$maintenance->getBreakdowns();
            $sameCabinet=false;
            foreach($tabBreakdowns as $breakdown){
                if($breakdown->getMachine()->getCabinet()==$user->getCabinet()){
                    $sameCabinet=true;
                }
            }
            $libre = $maintenance->getTechnician()==null ||  $maintenance->getTechnician()==$user;
            return $sameCabinet && $libre && !$repare;
        }else if($this->security->isGranted(USER_ROLES::USER->value)){
            $tabBreakdowns=$maintenance->getBreakdowns();
            $sameCabinet=false;
            foreach($tabBreakdowns as $breakdown){
                if($breakdown->getMachine()->getCabinet()==$user->getCabinet()){
                    $sameCabinet=true;
                }
            }
            return $sameCabinet && !$repare;
        }else{
            return false;
        }
    }
}