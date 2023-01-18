<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Dashboard\User;

use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\InviteBuddy\InvalidInvitationTokenException;
use Tuleap\InviteBuddy\InvitationByTokenRetriever;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\RetrieveUserById;

final class FirstTimerPresenterBuilder
{
    public function __construct(
        private InvitationByTokenRetriever $invitation_dao,
        private SplitTokenIdentifierTranslator $split_token_identifier,
        private RetrieveUserById $user_manager,
    ) {
    }

    public function buildPresenter(\Codendi_Request $request): ?FirstTimerPresenter
    {
        $token = $request->get('invitation-token');
        if (! \is_string($token)) {
            return null;
        }

        $token = new ConcealedString($token);
        try {
            $invitation = $this->invitation_dao->searchBySplitToken(
                $this->split_token_identifier->getSplitToken($token)
            );

            return new FirstTimerPresenter(
                $request->getCurrentUser()->getRealName(),
                \ForgeConfig::get(ConfigurationVariables::NAME),
                $this->user_manager->getUserById($invitation->from_user_id),
                new JavascriptViteAsset(
                    new IncludeViteAssets(
                        __DIR__ . '/../../../scripts/first-timer/frontend-assets',
                        '/assets/core/first-timer',
                    ),
                    'src/first-timer.ts',
                ),
            );
        } catch (InvalidIdentifierFormatException | InvalidInvitationTokenException) {
            throw new ForbiddenException(_('Your invitation link is not valid'));
        }
    }
}
