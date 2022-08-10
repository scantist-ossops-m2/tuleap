<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\DefaultBranchPush;

/**
 * A push on the default branch of a Git repository was received
 * @psalm-immutable
 */
final class DefaultBranchPushReceived
{
    public function __construct(
        private \GitRepository $repository,
        private \PFUser $pusher,
        /**
         * @var list<CommitHash>
         */
        private array $commit_hashes,
    ) {
    }

    public function getRepository(): \GitRepository
    {
        return $this->repository;
    }

    public function getPusher(): \PFUser
    {
        return $this->pusher;
    }

    /**
     * @return list<CommitHash>
     */
    public function getCommitHashes(): array
    {
        return $this->commit_hashes;
    }

    /**
     * @return list<CommitAnalysisOrder>
     */
    public function analyzeCommits(): array
    {
        $orders = [];
        foreach ($this->commit_hashes as $commit_hash) {
            $orders[] = CommitAnalysisOrder::fromComponents($commit_hash, $this->pusher, $this->repository);
        }
        return $orders;
    }
}
