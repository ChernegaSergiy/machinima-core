<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Avatar;

use Composer\InstalledVersions;
use DiceBear\Avatar;
use DiceBear\Style;

class InitialsAvatarProvider implements AvatarProviderInterface
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function getAvatarUrl(int $userId): string
    {
        $avatarsDir = $this->projectDir.'/public/avatars';
        $avatarPath = $avatarsDir.'/'.$userId.'.svg';
        $avatarUrl = '/avatars/'.$userId.'.svg';

        if (!is_dir($avatarsDir)) {
            mkdir($avatarsDir, 0755, true);
        }

        if (!file_exists($avatarPath)) {
            $this->generateAvatar($userId, $avatarPath);
        }

        return $avatarUrl;
    }

    private function generateAvatar(int $userId, string $path): void
    {
        $basePath = InstalledVersions::getInstallPath('dicebear/styles');
        $style = Style::fromJson(file_get_contents($basePath.'/src/initials.json'));

        $avatar = new Avatar($style, [
            'seed' => (string) $userId,
        ]);

        file_put_contents($path, (string) $avatar);
    }
}
