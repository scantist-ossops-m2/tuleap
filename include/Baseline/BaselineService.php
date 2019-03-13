<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline;

use PFUser;
use Project;

class BaselineService
{
    /** @var BaselineRepository */
    private $baseline_repository;

    /** @var CurrentUserProvider */
    private $current_user_provider;

    /** @var Clock */
    private $clock;

    public function __construct(
        BaselineRepository $baseline_repository,
        CurrentUserProvider $current_user_provider,
        Clock $clock
    ) {
        $this->baseline_repository   = $baseline_repository;
        $this->current_user_provider = $current_user_provider;
        $this->clock                 = $clock;
    }

    /**
     * @throws NotAuthorizedException
     */
    public function create(PFUser $current_user, TransientBaseline $baseline): Baseline
    {
        return $this->baseline_repository->add(
            $baseline,
            $this->current_user_provider->getUser(),
            $this->clock->now()
        );
    }

    /**
     * @throws NotAuthorizedException
     */
    public function findById(PFUser $current_user, int $id): ?Baseline
    {
        return $this->baseline_repository->findById($current_user, $id);
    }

    /**
     * Find baselines on given project, ordered by snapshot date.
     * @param int $page_size       Number of baselines to fetch
     * @param int $baseline_offset Fetch baselines from this index (start with 0), following snapshot date order.
     * @throws NotAuthorizedException
     */
    public function findByProject(
        PFUser $current_user,
        Project $project,
        int $page_size,
        int $baseline_offset
    ): BaselinesPage {
        $baselines = $this->baseline_repository->findByProject($current_user, $project, $page_size, $baseline_offset);
        $count     = $this->baseline_repository->countByProject($project);
        return new BaselinesPage($baselines, $page_size, $baseline_offset, $count);
    }
}
